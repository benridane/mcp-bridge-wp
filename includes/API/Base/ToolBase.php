<?php
/**
 * Tool Base Class
 *
 * @package McpBridge\API\Base
 */

namespace McpBridge\API\Base;

use McpBridge\Core\Logger;
use McpBridge\Core\RegisterMcpTool;

/**
 * Base class for all MCP tools
 */
abstract class ToolBase
{
    /**
     * Tool configuration
     */
    protected array $config = [];

    /**
     * Constructor
     *
     * @param array $config Tool configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->init();
    }

    /**
     * Initialize the tool
     */
    protected function init(): void
    {
        // Override in child classes
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'capability' => MCP_BRIDGE_CAPABILITY_REQUIRED,
            'parameters' => [],
        ];
    }

    /**
     * Register multiple tools
     *
     * @param array $tools Array of tool configurations
     */
    protected function registerTools(array $tools): void
    {
        foreach ($tools as $toolConfig) {
            try {
                new RegisterMcpTool($toolConfig);
                Logger::debug("Registered tool: {$toolConfig['name']}", ['tool' => $toolConfig['name']]);
            } catch (\Exception $e) {
                Logger::error("Failed to register tool: {$toolConfig['name']}", [
                    'error' => $e->getMessage(),
                    'tool' => $toolConfig['name']
                ]);
            }
        }
    }

    /**
     * Validate required parameters
     *
     * @param array $params
     * @param array $required
     * @throws \InvalidArgumentException
     */
    protected function validateRequiredParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new \InvalidArgumentException("Required parameter '{$param}' is missing or empty");
            }
        }
    }

    /**
     * Format MCP response
     *
     * @param mixed $data
     * @param string $type
     * @return array
     */
    protected function formatMcpResponse($data, string $type = 'text'): array
    {
        if (is_array($data) && isset($data['content'])) {
            // Already in MCP format
            return $data;
        }

        $content = [];
        
        switch ($type) {
            case 'json':
                $content[] = [
                    'type' => 'text',
                    'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ];
                break;
            
            case 'text':
            default:
                if (is_string($data)) {
                    $content[] = [
                        'type' => 'text',
                        'text' => $data
                    ];
                } else {
                    $content[] = [
                        'type' => 'text',
                        'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    ];
                }
                break;
        }

        return ['content' => $content];
    }

    /**
     * Handle REST API errors
     *
     * @param \WP_Error|\WP_REST_Response $response
     * @throws \Exception
     */
    protected function handleRestError($response): void
    {
        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            $data = $response->get_error_data();
            
            Logger::error('REST API error', [
                'message' => $message,
                'data' => $data
            ]);
            
            throw new \Exception("REST API error: {$message}");
        }

        if ($response instanceof \WP_REST_Response && $response->is_error()) {
            $data = $response->get_data();
            $message = $data['message'] ?? 'Unknown REST API error';
            
            Logger::error('REST API response error', [
                'status' => $response->get_status(),
                'data' => $data
            ]);
            
            throw new \Exception("REST API error: {$message}");
        }
    }

    /**
     * Sanitize parameters
     *
     * @param array $params
     * @param array $schema
     * @return array
     */
    protected function sanitizeParams(array $params, array $schema): array
    {
        $sanitized = [];

        foreach ($schema as $key => $rules) {
            if (!isset($params[$key])) {
                if (isset($rules['default'])) {
                    $sanitized[$key] = $rules['default'];
                }
                continue;
            }

            $value = $params[$key];
            $type = $rules['type'] ?? 'string';

            switch ($type) {
                case 'integer':
                    $sanitized[$key] = intval($value);
                    if (isset($rules['minimum']) && $sanitized[$key] < $rules['minimum']) {
                        $sanitized[$key] = $rules['minimum'];
                    }
                    if (isset($rules['maximum']) && $sanitized[$key] > $rules['maximum']) {
                        $sanitized[$key] = $rules['maximum'];
                    }
                    break;

                case 'string':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;

                case 'boolean':
                    $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'array':
                    $sanitized[$key] = is_array($value) ? $value : [];
                    break;

                default:
                    $sanitized[$key] = $value;
                    break;
            }
        }

        return $sanitized;
    }

    /**
     * Log tool execution
     *
     * @param string $toolName
     * @param array $params
     * @param string $action
     */
    protected function logToolExecution(string $toolName, array $params, string $action = 'executed'): void
    {
        $user = wp_get_current_user();
        
        Logger::info("Tool {$action}: {$toolName}", [
            'tool' => $toolName,
            'user' => $user->user_login ?? 'anonymous',
            'user_id' => $user->ID ?? 0,
            'params_count' => count($params),
            'action' => $action
        ]);
    }
}