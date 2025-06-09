<?php
/**
 * MCP Tool Registration Class
 *
 * @package McpBridge\Core
 */

namespace McpBridge\Core;

/**
 * RegisterMcpTool Class
 */
class RegisterMcpTool
{
    /**
     * Registered tools
     */
    private static array $tools = [];

    /**
     * Tool configuration
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Tool configuration
     */
    public function __construct(array $config)
    {
        $this->config = $this->validateConfig($config);
        $this->register();
    }

    /**
     * Validate tool configuration
     *
     * @param array $config
     * @return array
     * @throws \InvalidArgumentException
     */
    private function validateConfig(array $config): array
    {
        // Required fields
        $required = ['name', 'description', 'type'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
        }

        // Validate type
        $validTypes = ['read', 'create', 'update', 'delete'];
        if (!in_array($config['type'], $validTypes)) {
            throw new \InvalidArgumentException("Invalid type '{$config['type']}'. Must be one of: " . implode(', ', $validTypes));
        }

        // Set default values
        $defaults = [
            'enabled' => true,
            'capability' => MCP_BRIDGE_CAPABILITY_REQUIRED,
            'parameters' => [],
            'rest_alias' => null,
            'callback' => null,
        ];

        return array_merge($defaults, $config);
    }

    /**
     * Register the tool
     */
    private function register(): void
    {
        $toolName = $this->config['name'];
        
        if (isset(self::$tools[$toolName])) {
            Logger::warning("Tool '{$toolName}' is already registered", ['tool' => $toolName]);
            return;
        }

        self::$tools[$toolName] = $this->config;
        Logger::debug("Registered MCP tool: {$toolName}", ['config' => $this->config]);
    }

    /**
     * Get all registered tools
     *
     * @return array
     */
    public static function getTools(): array
    {
        return self::$tools;
    }

    /**
     * Get a specific tool
     *
     * @param string $name Tool name
     * @return array|null
     */
    public static function getTool(string $name): ?array
    {
        return self::$tools[$name] ?? null;
    }

    /**
     * Check if tool exists
     *
     * @param string $name Tool name
     * @return bool
     */
    public static function toolExists(string $name): bool
    {
        return isset(self::$tools[$name]);
    }

    /**
     * Execute a tool
     *
     * @param string $name Tool name
     * @param array $params Parameters
     * @return mixed
     */
    public static function executeTool(string $name, array $params = [])
    {
        $tool = self::getTool($name);
        
        if (!$tool) {
            throw new \Exception("Tool '{$name}' not found");
        }

        if (!$tool['enabled']) {
            throw new \Exception("Tool '{$name}' is disabled");
        }

        // Check user capabilities - use current user ID if set
        $currentUser = wp_get_current_user();
        if (!$currentUser || !$currentUser->exists()) {
            throw new \Exception("No authenticated user found");
        }

        if (!user_can($currentUser, $tool['capability'])) {
            Logger::warning("Permission denied for user", [
                'user' => $currentUser->user_login,
                'user_id' => $currentUser->ID,
                'required_capability' => $tool['capability'],
                'tool' => $name
            ]);
            throw new \Exception("Insufficient permissions to execute tool '{$name}'");
        }

        Logger::info("Executing tool: {$name}", [
            'params' => $params,
            'user' => $currentUser->user_login,
            'user_id' => $currentUser->ID
        ]);

        // If tool has a REST alias, use that
        if ($tool['rest_alias']) {
            return self::executeRestAlias($tool, $params);
        }

        // If tool has a callback, use that
        if ($tool['callback'] && is_callable($tool['callback'])) {
            return call_user_func($tool['callback'], $params);
        }

        throw new \Exception("Tool '{$name}' has no executable handler");
    }

    /**
     * Execute tool via REST API alias
     *
     * @param array $tool Tool configuration
     * @param array $params Parameters
     * @return array MCP-compliant response format
     */
    private static function executeRestAlias(array $tool, array $params): array
    {
        $restAlias = $tool['rest_alias'];
        $route = $restAlias['route'];
        $method = strtoupper($restAlias['method'] ?? 'GET');

        Logger::debug("Executing REST alias", [
            'route' => $route,
            'method' => $method,
            'params' => $params
        ]);

        // Replace route parameters
        $processedRoute = self::processRouteParameters($route, $params);
        
        // Normalize route - ensure it starts with /wp/v2 if not present
        if (!str_starts_with($processedRoute, '/wp/v2')) {
            if (str_starts_with($processedRoute, '/')) {
                $processedRoute = '/wp/v2' . $processedRoute;
            } else {
                $processedRoute = '/wp/v2/' . $processedRoute;
            }
        }

        Logger::debug("Processed route", [
            'original_route' => $route,
            'processed_route' => $processedRoute,
            'method' => $method
        ]);

        // Create internal REST request
        $request = new \WP_REST_Request($method, $processedRoute);
        
        // Set the current user context for authentication
        $currentUser = wp_get_current_user();
        if ($currentUser && $currentUser->exists()) {
            wp_set_current_user($currentUser->ID);
        }
        
        // Set parameters based on method
        if ($method === 'GET') {
            foreach ($params as $key => $value) {
                if (!str_contains($route, "{{$key}}")) {
                    $request->set_query_params([$key => $value]);
                }
            }
        } else {
            // For POST/PUT/PATCH, set body parameters and also set JSON body
            $request->set_body_params($params);
            $request->set_header('Content-Type', 'application/json');
            
            // Also set as JSON body for proper REST API processing
            $jsonBody = json_encode($params);
            $request->set_body($jsonBody);
            
            Logger::debug("Request body set", [
                'params' => $params,
                'json_body' => $jsonBody
            ]);
        }

        // Execute the request
        $server = rest_get_server();
        
        try {
            $response = $server->dispatch($request);
            
            Logger::debug("REST response received", [
                'status' => $response->get_status(),
                'is_error' => $response->is_error()
            ]);
            
            if ($response->is_error()) {
                $errorData = $response->get_error_data();
                $errorMessage = $response->get_error_message();
                
                Logger::error("REST API error details", [
                    'message' => $errorMessage,
                    'data' => $errorData,
                    'route' => $processedRoute,
                    'method' => $method,
                    'params' => $params
                ]);
                
                throw new \Exception("REST API error: " . $errorMessage);
            }

            $data = $response->get_data();
            
            Logger::info("REST API success", [
                'route' => $processedRoute,
                'method' => $method,
                'response_status' => $response->get_status()
            ]);
            
            // Return MCP-compliant format
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($data, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            Logger::error("Exception during REST API execution", [
                'exception' => $e->getMessage(),
                'route' => $processedRoute,
                'method' => $method,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Process route parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    private static function processRouteParameters(string $route, array $params): string
    {
        // Replace named parameters like {id} with actual values
        return preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($params) {
            $paramName = $matches[1];
            return $params[$paramName] ?? $matches[0];
        }, $route);
    }

    /**
     * Get tools by type
     *
     * @param string $type Tool type
     * @return array
     */
    public static function getToolsByType(string $type): array
    {
        return array_filter(self::$tools, function ($tool) use ($type) {
            return $tool['type'] === $type;
        });
    }

    /**
     * Enable/disable a tool
     *
     * @param string $name Tool name
     * @param bool $enabled Enabled status
     */
    public static function setToolEnabled(string $name, bool $enabled): void
    {
        if (isset(self::$tools[$name])) {
            self::$tools[$name]['enabled'] = $enabled;
            Logger::info("Tool '{$name}' " . ($enabled ? 'enabled' : 'disabled'));
        }
    }

    /**
     * Get MCP tools manifest
     *
     * @return array
     */
    public static function getManifest(): array
    {
        $manifest = [
            'version' => MCP_BRIDGE_VERSION,
            'tools' => []
        ];

        foreach (self::$tools as $name => $tool) {
            if (!$tool['enabled']) {
                continue;
            }

            // Ensure properties is always an object (not array) for Zod validation
            $properties = [];
            $required = [];
            
            if (isset($tool['parameters']) && is_array($tool['parameters'])) {
                foreach ($tool['parameters'] as $paramName => $paramSchema) {
                    // Validate parameter schema structure
                    if (!isset($paramSchema['type'])) {
                        Logger::warning('Tool parameter missing type in manifest', [
                            'tool' => $name,
                            'parameter' => $paramName
                        ]);
                        continue;
                    }
                    
                    $properties[$paramName] = [
                        'type' => $paramSchema['type'],
                        'description' => $paramSchema['description'] ?? ''
                    ];
                    
                    // Add additional schema properties if present
                    if (isset($paramSchema['enum'])) {
                        $properties[$paramName]['enum'] = $paramSchema['enum'];
                    }
                    if (isset($paramSchema['default'])) {
                        $properties[$paramName]['default'] = $paramSchema['default'];
                    }
                    if (isset($paramSchema['minimum'])) {
                        $properties[$paramName]['minimum'] = $paramSchema['minimum'];
                    }
                    if (isset($paramSchema['maximum'])) {
                        $properties[$paramName]['maximum'] = $paramSchema['maximum'];
                    }
                    
                    // Collect required parameters
                    if (isset($paramSchema['required']) && $paramSchema['required'] === true) {
                        $required[] = $paramName;
                    }
                }
            }

            $inputSchema = [
                'type' => 'object',
                'properties' => (object)$properties, // Explicitly cast to object for Zod validation
                'additionalProperties' => true, // Allow additional properties for better MCP client compatibility
                '$schema' => 'http://json-schema.org/draft-07/schema#' // Add JSON Schema metadata
            ];
            
            // Only add required array if there are required fields
            if (!empty($required)) {
                $inputSchema['required'] = $required;
            }

            $manifest['tools'][] = [
                'name' => $name,
                'description' => $tool['description'],
                'inputSchema' => $inputSchema
            ];
        }

        return $manifest;
    }
}