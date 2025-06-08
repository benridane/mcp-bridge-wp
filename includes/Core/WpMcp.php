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
     * Current session ID for Streamable HTTP
     */
    private ?string $sessionId = null;

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
        // Generate session ID for this instance
        $this->sessionId = $this->generateSessionId();
    }

    /**
     * Handle MCP RPC requests
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleRpcRequest(\WP_REST_Request $request): \WP_REST_Response
    {
        // Ensure we always have a session ID from the start
        if (!$this->sessionId) {
            $this->sessionId = $this->generateSessionId();
        }

        // Check for existing session ID in headers (for MCP Inspector compatibility)
        $clientSessionId = $request->get_header('X-MCP-Session-ID');
        if ($clientSessionId && !empty($clientSessionId)) {
            Logger::info('Using client-provided session ID', [
                'client_session_id' => $clientSessionId,
                'server_session_id' => $this->sessionId
            ]);
            $this->sessionId = $clientSessionId;
        }

        Logger::info('Received MCP RPC request', [
            'method_type' => $request->get_method(),
            'content_type' => $request->get_header('Content-Type'),
            'user_agent' => $request->get_header('User-Agent'),
            'body_length' => strlen($request->get_body()),
            'session_id' => $this->sessionId,
            'client_session_id' => $clientSessionId
        ]);

        try {
            $body = $request->get_body();
            Logger::debug('Request body received', ['body' => $body]);
            
            $json = json_decode($body, true);
            
            if (!$json) {
                $jsonError = json_last_error_msg();
                Logger::error('JSON decode failed', ['error' => $jsonError, 'body' => $body]);
                throw new \Exception("Invalid JSON: {$jsonError}");
            }
            
            if (!isset($json['method'])) {
                Logger::error('Missing method in JSON-RPC request', ['json' => $json]);
                throw new \Exception('Invalid JSON-RPC request format: missing method');
            }

            $method = $json['method'];
            $params = $json['params'] ?? [];
            $id = $json['id'] ?? null;

            Logger::info('Processing RPC method', [
                'method' => $method,
                'params_keys' => array_keys($params),
                'session_id' => $this->sessionId,
                'request_id' => $id
            ]);

            // Set the authenticated user context if available
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

            Logger::info('RPC method executed successfully', [
                'method' => $method, 
                'result_type' => gettype($result),
                'result_keys' => is_array($result) ? array_keys($result) : 'N/A',
                'session_id' => $this->sessionId
            ]);

            $response = new \WP_REST_Response([
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => $id
            ], 200);

            // Add CORS headers
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key, X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version');
            $response->header('Access-Control-Expose-Headers', 'X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version');
            
            // Add Streamable HTTP specific headers with guaranteed session ID
            $response->header('X-MCP-Session-ID', $this->sessionId);
            $response->header('X-MCP-Transport', 'streamable-http');
            $response->header('X-MCP-Protocol-Version', '2024-11-05');
            
            // Add session tracking for MCP Inspector compatibility
            $response->header('X-Session-Status', 'active');
            $response->header('X-Server-Name', 'WordPress MCP Bridge v' . MCP_BRIDGE_VERSION);
            
            Logger::info('Response prepared with enhanced session tracking', [
                'session_id' => $this->sessionId,
                'method' => $method,
                'response_size' => strlen(json_encode($result)),
                'headers_added' => ['X-MCP-Session-ID', 'X-MCP-Transport', 'X-MCP-Protocol-Version', 'X-Session-Status']
            ]);

            return $response;

        } catch (\Exception $e) {
            Logger::error('RPC request failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_body' => $request->get_body(),
                'session_id' => $this->sessionId
            ]);

            $response = new \WP_REST_Response([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => $e->getCode() ?: -1,
                    'message' => $e->getMessage()
                ],
                'id' => isset($json) && isset($json['id']) ? $json['id'] : null
            ], 400);

            // Add CORS headers even for errors
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key, X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version');
            $response->header('Access-Control-Expose-Headers', 'X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version');
            
            // Add Streamable HTTP specific headers even for errors
            $response->header('X-MCP-Session-ID', $this->sessionId);
            $response->header('X-MCP-Transport', 'streamable-http');
            $response->header('X-MCP-Protocol-Version', '2024-11-05');
            $response->header('X-Session-Status', 'error');

            return $response;
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
        Logger::info('Processing RPC method', [
            'method' => $method,
            'params_keys' => array_keys($params),
            'session_id' => $this->sessionId
        ]);

        // Handle MCP protocol methods
        switch ($method) {
            // Core MCP protocol methods
            case 'initialize':
                return $this->initialize($params);
            
            case 'ping':
                return $this->ping();
            
            case 'notifications/initialized':
                return $this->notificationsInitialized();
                
            // Alternative notification format that some clients use
            case 'initialized':
                return $this->notificationsInitialized();
            
            // Tool-related methods
            case 'tools/list':
                return $this->getToolsList();
            
            case 'tools/call':
                if (!isset($params['name'])) {
                    throw new \Exception('Tool name is required');
                }
                return $this->callTool($params['name'], $params['arguments'] ?? []);
            
            // Resource-related methods
            case 'resources/list':
                return $this->getResourcesList();
            
            case 'resources/read':
                if (!isset($params['uri'])) {
                    throw new \Exception('Resource URI is required');
                }
                return $this->readResource($params['uri']);
            
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
                
                Logger::warning('Unknown method called', [
                    'method' => $method,
                    'available_methods' => [
                        'initialize', 'ping', 'notifications/initialized', 'initialized',
                        'tools/list', 'tools/call', 'resources/list', 'resources/read',
                        'getPosts', 'createPost'
                    ]
                ]);
                
                throw new \Exception("Unknown method: {$method}");
        }
    }

    /**
     * Handle MCP initialize method
     *
     * @param array $params
     * @return array
     */
    private function initialize(array $params): array
    {
        Logger::info('MCP initialize called', [
            'params' => $params,
            'session_id' => $this->sessionId,
            'client_info' => $params['clientInfo'] ?? 'unknown'
        ]);
        
        // Validate protocol version
        $clientProtocolVersion = $params['protocolVersion'] ?? null;
        if ($clientProtocolVersion && $clientProtocolVersion !== '2024-11-05') {
            Logger::warning('Protocol version mismatch', [
                'client_version' => $clientProtocolVersion,
                'server_version' => '2024-11-05'
            ]);
        }
        
        // Log client capabilities for debugging
        if (isset($params['capabilities'])) {
            Logger::info('Client capabilities received', [
                'capabilities' => $params['capabilities']
            ]);
        }
        
        // Enhanced server capabilities for MCP Inspector compatibility
        $response = [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => [
                    'listChanged' => false
                ],
                'resources' => [
                    'subscribe' => false,
                    'listChanged' => false
                ],
                'prompts' => [
                    'listChanged' => false
                ],
                'logging' => (object)[]  // オブジェクトとして返す（MCP Inspector v0.14.0 Zod validation対応）
            ],
            'serverInfo' => [
                'name' => 'WordPress MCP Bridge',
                'version' => MCP_BRIDGE_VERSION
            ]
        ];
        
        Logger::info('MCP initialize response prepared', [
            'response' => $response,
            'session_id' => $this->sessionId,
            'capabilities_count' => count($response['capabilities'])
        ]);
        
        return $response;
    }

    /**
     * Handle MCP ping method
     *
     * @return array
     */
    private function ping(): array
    {
        Logger::debug('MCP ping called');
        return [];
    }

    /**
     * Handle notifications/initialized method
     *
     * @return array
     */
    private function notificationsInitialized(): array
    {
        Logger::info('MCP notifications/initialized called - Handshake completing', [
            'session_id' => $this->sessionId,
            'authenticated_user' => $this->authenticatedUser ? $this->authenticatedUser->user_login : 'none',
            'timestamp' => current_time('mysql')
        ]);
        
        // Mark the MCP connection as fully initialized
        // This is the final step in the MCP handshake process
        Logger::info('MCP handshake completed successfully', [
            'session_id' => $this->sessionId,
            'connection_status' => 'fully_initialized'
        ]);
        
        // Return empty object as per MCP specification for notifications
        return [];
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
     * Get list of available resources
     *
     * @return array
     */
    private function getResourcesList(): array
    {
        Logger::debug('Getting resources list');
        
        $resources = [];
        
        // Add WordPress post types as resources
        $post_types = get_post_types(['public' => true], 'objects');
        foreach ($post_types as $post_type) {
            $resources[] = [
                'uri' => "wordpress://posts/{$post_type->name}",
                'name' => $post_type->labels->name,
                'description' => $post_type->description ?: "WordPress {$post_type->labels->name}",
                'mimeType' => 'application/json'
            ];
        }
        
        return ['resources' => $resources];
    }

    /**
     * Read a specific resource
     *
     * @param string $uri
     * @return array
     */
    private function readResource(string $uri): array
    {
        Logger::debug('Reading resource', ['uri' => $uri]);
        
        if (!preg_match('/^wordpress:\/\/posts\/(.+)$/', $uri, $matches)) {
            throw new \Exception("Unsupported resource URI: {$uri}");
        }
        
        $post_type = $matches[1];
        
        if (!post_type_exists($post_type)) {
            throw new \Exception("Post type '{$post_type}' does not exist");
        }
        
        $posts = get_posts([
            'post_type' => $post_type,
            'numberposts' => 10,
            'post_status' => 'publish'
        ]);
        
        $contents = [];
        foreach ($posts as $post) {
            $contents[] = [
                'uri' => "wordpress://posts/{$post_type}/{$post->ID}",
                'mimeType' => 'text/plain',
                'text' => $post->post_content
            ];
        }
        
        return ['contents' => $contents];
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

    /**
     * Generate a new session ID
     *
     * @return string
     */
    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get session ID for external access
     *
     * @return string
     */
    public function getSessionId(): string
    {
        if (!$this->sessionId) {
            $this->sessionId = $this->generateSessionId();
        }
        return $this->sessionId;
    }
}