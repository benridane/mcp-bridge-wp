<?php
/**
 * WordPress MCP Core Class
 *
 * @package McpBridge\Core
 */

namespace McpBridge\Core;

/**
 * WpMcp Class - Main MCP handler
 */
class WpMcp
{
    /**
     * Instance
     */
    private static ?self $instance = null;

    /**
     * Authenticated user
     */
    private ?\WP_User $authenticatedUser = null;

    /**
     * Get instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct()
    {
        // Constructor intentionally empty
    }

    /**
     * Handle MCP RPC requests
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleRpcRequest(\WP_REST_Request $request): \WP_REST_Response
    {
        Logger::info('Received MCP RPC request');

        try {
            $json = json_decode($request->get_body(), true);
            
            if (!$json || !isset($json['method'])) {
                throw new \Exception('Invalid JSON-RPC request format');
            }

            $method = $json['method'];
            $params = $json['params'] ?? [];
            $id = $json['id'] ?? null;

            Logger::debug('Processing RPC method', [
                'method' => $method, 
                'params' => $params,
                'json_body' => $request->get_body()
            ]);

            // Set the authenticated user context
            if ($this->authenticatedUser) {
                wp_set_current_user($this->authenticatedUser->ID);
                Logger::debug('Set current user context', [
                    'user_id' => $this->authenticatedUser->ID,
                    'user_login' => $this->authenticatedUser->user_login
                ]);
            } else {
                Logger::warning('No authenticated user found in handleRpcRequest');
            }

            $result = $this->processRpcMethod($method, $params);

            Logger::debug('RPC method executed successfully', ['method' => $method, 'result_type' => gettype($result)]);

            return new \WP_REST_Response([
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => $id
            ], 200);

        } catch (\Exception $e) {
            Logger::error('RPC request failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_body' => $request->get_body()
            ]);

            return new \WP_REST_Response([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => $e->getCode() ?: -1,
                    'message' => $e->getMessage()
                ],
                'id' => $json['id'] ?? null
            ], 400);
        }
    }

    /**
     * Process RPC method
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    private function processRpcMethod(string $method, array $params)
    {
        // Handle MCP protocol methods
        switch ($method) {
            case 'tools/list':
                return $this->getToolsList();
            
            case 'tools/call':
                if (!isset($params['name'])) {
                    throw new \Exception('Tool name is required');
                }
                return $this->callTool($params['name'], $params['arguments'] ?? []);
            
            // Legacy methods for backward compatibility
            case 'getPosts':
                return RegisterMcpTool::executeTool('wp_get_posts', $params);
            
            case 'createPost':
                return RegisterMcpTool::executeTool('wp_create_post', $params);
            
            default:
                // Try to execute as a registered tool
                if (RegisterMcpTool::toolExists($method)) {
                    return RegisterMcpTool::executeTool($method, $params);
                }
                
                throw new \Exception("Unknown method: {$method}");
        }
    }

    /**
     * Get list of available tools
     *
     * @return array
     */
    private function getToolsList(): array
    {
        $tools = RegisterMcpTool::getTools();
        $result = [];

        foreach ($tools as $name => $tool) {
            if (!$tool['enabled']) {
                continue;
            }

            $result[] = [
                'name' => $name,
                'description' => $tool['description'],
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $tool['parameters'],
                ]
            ];
        }

        return ['tools' => $result];
    }

    /**
     * Call a specific tool
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    private function callTool(string $name, array $arguments): mixed
    {
        return RegisterMcpTool::executeTool($name, $arguments);
    }

    /**
     * Check permissions for MCP requests
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function checkPermissions(\WP_REST_Request $request): bool
    {
        Logger::debug('=== MCP Permission Check ===');
        
        // Check custom X-API-Key header first
        $apiKey = $request->get_header('X-API-Key');
        if ($apiKey) {
            Logger::debug('Found X-API-Key header');
            
            $user = $this->authenticateWithApiKey($apiKey);
            if ($user && user_can($user, MCP_BRIDGE_CAPABILITY_REQUIRED)) {
                Logger::info('Authentication successful via X-API-Key', ['user' => $user->user_login]);
                $this->authenticatedUser = $user;
                return true;
            }
        }

        // Check Authorization header (Bearer token)
        $bearerToken = $this->getBearerToken($request);
        if ($bearerToken) {
            Logger::debug('Found Bearer token');
            
            $user = $this->authenticateWithToken($bearerToken);
            if ($user && user_can($user, MCP_BRIDGE_CAPABILITY_REQUIRED)) {
                Logger::info('Authentication successful via Bearer token', ['user' => $user->user_login]);
                $this->authenticatedUser = $user;
                return true;
            }
        }

        // Check HTTP Basic Authentication
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            Logger::debug('Found HTTP Basic Auth credentials');
            
            $user = $this->authenticateWithPassword($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            if ($user && user_can($user, MCP_BRIDGE_CAPABILITY_REQUIRED)) {
                Logger::info('Authentication successful via Basic Auth', ['user' => $user->user_login]);
                $this->authenticatedUser = $user;
                return true;
            }
        }

        Logger::warning('All authentication methods failed');
        return false;
    }

    /**
     * Authenticate with API key
     *
     * @param string $apiKey
     * @return \WP_User|false
     */
    private function authenticateWithApiKey(string $apiKey)
    {
        // Try to decode base64 encoded username:password
        $decoded = base64_decode($apiKey, true);
        if ($decoded && strpos($decoded, ':') !== false) {
            [$username, $password] = explode(':', $decoded, 2);
            return $this->authenticateWithPassword($username, $password);
        }

        // Try as plain application password token
        return $this->validateApplicationPasswordFromDb($apiKey);
    }

    /**
     * Authenticate with bearer token
     *
     * @param string $token
     * @return \WP_User|false
     */
    private function authenticateWithToken(string $token)
    {
        // Try to decode base64 encoded username:password
        $decoded = base64_decode($token, true);
        if ($decoded && strpos($decoded, ':') !== false) {
            [$username, $password] = explode(':', $decoded, 2);
            return $this->authenticateWithPassword($username, $password);
        }

        // Try as plain application password token
        return $this->validateApplicationPasswordFromDb($token);
    }

    /**
     * Authenticate with username and password
     *
     * @param string $username
     * @param string $password
     * @return \WP_User|false
     */
    private function authenticateWithPassword(string $username, string $password)
    {
        $user = get_user_by('login', $username);
        if (!$user) {
            Logger::debug('User not found', ['username' => $username]);
            return false;
        }

        // Check application passwords
        $appPasswords = get_user_meta($user->ID, '_application_passwords', true);
        if (!empty($appPasswords) && is_array($appPasswords)) {
            foreach ($appPasswords as $appPassword) {
                if (isset($appPassword['password']) && wp_check_password($password, $appPassword['password'])) {
                    Logger::debug('Application password match found', ['user' => $username]);
                    return $user;
                }
            }
        }

        // Try WordPress built-in application password validation
        if (function_exists('wp_authenticate_application_password')) {
            $authenticated = wp_authenticate_application_password(null, $username, $password);
            if ($authenticated instanceof \WP_User) {
                Logger::debug('WordPress built-in validation successful', ['user' => $username]);
                return $authenticated;
            }
        }

        Logger::debug('Password authentication failed', ['user' => $username]);
        return false;
    }

    /**
     * Validate application password from database
     *
     * @param string $token
     * @return \WP_User|false
     */
    private function validateApplicationPasswordFromDb(string $token)
    {
        global $wpdb;

        Logger::debug('Validating token from database');

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT user_id, meta_value 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = '_application_passwords'
        "));

        foreach ($results as $result) {
            $appPasswords = maybe_unserialize($result->meta_value);
            
            if (empty($appPasswords) || !is_array($appPasswords)) {
                continue;
            }

            foreach ($appPasswords as $appPassword) {
                if (isset($appPassword['password']) && wp_check_password($token, $appPassword['password'])) {
                    $user = get_user_by('ID', $result->user_id);
                    Logger::debug('Found matching application password', ['user' => $user->user_login]);
                    return $user;
                }
            }
        }

        Logger::debug('No matching application password found in database');
        return false;
    }

    /**
     * Get Bearer token from request
     *
     * @param \WP_REST_Request $request
     * @return string|null
     */
    private function getBearerToken(\WP_REST_Request $request): ?string
    {
        $authHeader = $request->get_header('Authorization');
        
        if (!$authHeader) {
            // Try alternative sources
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }
        }

        if (!$authHeader) {
            return null;
        }

        if (strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }

        return null;
    }
}