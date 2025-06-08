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
     * @return mixed
     */
    private static function executeRestAlias(array $tool, array $params)
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

        // Create internal REST request
        $request = new \WP_REST_Request($method, '/wp/v2' . $processedRoute);
        
        // Set parameters based on method
        if ($method === 'GET') {
            foreach ($params as $key => $value) {
                if (!str_contains($route, "{{$key}}")) {
                    $request->set_query_params([$key => $value]);
                }
            }
        } else {
            $request->set_body_params($params);
        }

        // Execute the request
        $server = rest_get_server();
        $response = $server->dispatch($request);

        if ($response->is_error()) {
            throw new \Exception("REST API error: " . $response->get_error_message());
        }

        return $response->get_data();
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

            $manifest['tools'][] = [
                'name' => $name,
                'description' => $tool['description'],
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $tool['parameters'],
                ]
            ];
        }

        return $manifest;
    }
}