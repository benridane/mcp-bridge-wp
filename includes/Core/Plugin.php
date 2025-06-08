<?php
/**
 * Main Plugin Class
 *
 * @package McpBridge\Core
 */

namespace McpBridge\Core;

/**
 * Plugin Class - Singleton
 */
class Plugin
{
    /**
     * Plugin instance
     */
    private static ?self $instance = null;

    /**
     * Plugin initialization flag
     */
    private bool $initialized = false;

    /**
     * Get plugin instance
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
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the plugin
     */
    private function init(): void
    {
        if ($this->initialized) {
            return;
        }

        // Initialize logger
        Logger::init();
        Logger::info('MCP Bridge plugin initializing', ['version' => MCP_BRIDGE_VERSION]);

        // Register WordPress hooks
        $this->registerHooks();

        // Initialize core components
        $this->initializeComponents();

        $this->initialized = true;
        Logger::info('MCP Bridge plugin initialized successfully');
    }

    /**
     * Register WordPress hooks
     */
    private function registerHooks(): void
    {
        // Plugin activation/deactivation
        register_activation_hook(MCP_BRIDGE_PATH . 'mcp-bridge.php', [$this, 'activate']);
        register_deactivation_hook(MCP_BRIDGE_PATH . 'mcp-bridge.php', [$this, 'deactivate']);

        // Initialize REST API
        add_action('rest_api_init', [$this, 'initRestApi']);

        // Admin initialization
        add_action('admin_init', [$this, 'initAdmin']);

        // Plugin loaded
        add_action('plugins_loaded', [$this, 'pluginsLoaded']);
    }

    /**
     * Initialize core components
     */
    private function initializeComponents(): void
    {
        // Initialize WpMcp core
        WpMcp::getInstance();

        // Register default tools
        $this->registerDefaultTools();
    }

    /**
     * Plugin activation hook
     */
    public function activate(): void
    {
        Logger::info('MCP Bridge plugin activated');

        // Create necessary database tables if needed
        $this->createTables();

        // Set default options
        $this->setDefaultOptions();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate(): void
    {
        Logger::info('MCP Bridge plugin deactivated');

        // Clear any cached data
        $this->clearCache();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize REST API
     */
    public function initRestApi(): void
    {
        // Register main MCP RPC endpoint
        register_rest_route(MCP_BRIDGE_REST_NAMESPACE, MCP_BRIDGE_REST_ROUTE_RPC, [
            'methods' => 'POST',
            'callback' => [WpMcp::getInstance(), 'handleRpcRequest'],
            'permission_callback' => [WpMcp::getInstance(), 'checkPermissions'],
        ]);

        // Register tools manifest endpoint
        register_rest_route(MCP_BRIDGE_REST_NAMESPACE, '/tools', [
            'methods' => 'GET',
            'callback' => [$this, 'getToolsManifest'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        Logger::debug('REST API endpoints registered');
    }

    /**
     * Initialize admin interface
     */
    public function initAdmin(): void
    {
        if (!is_admin()) {
            return;
        }

        // Initialize admin components will be implemented in next phase
        Logger::debug('Admin interface initialized');
    }

    /**
     * Plugins loaded hook
     */
    public function pluginsLoaded(): void
    {
        // Load textdomain for translations
        load_plugin_textdomain(
            'mcp-bridge',
            false,
            dirname(MCP_BRIDGE_BASENAME) . '/languages'
        );

        Logger::debug('Text domain loaded');
    }

    /**
     * Register default MCP tools
     */
    private function registerDefaultTools(): void
    {
        // Basic WordPress information tool
        new RegisterMcpTool([
            'name' => 'wp_get_site_info',
            'description' => 'Get basic WordPress site information',
            'type' => 'read',
            'callback' => [$this, 'getSiteInfo'],
            'parameters' => []
        ]);

        // Get posts tool (migrated from existing code)
        new RegisterMcpTool([
            'name' => 'wp_get_posts',
            'description' => 'Get WordPress posts with optional filtering',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/posts',
                'method' => 'GET'
            ],
            'parameters' => [
                'per_page' => ['type' => 'integer', 'description' => 'Number of posts to retrieve'],
                'page' => ['type' => 'integer', 'description' => 'Page number'],
                'search' => ['type' => 'string', 'description' => 'Search term'],
                'status' => ['type' => 'string', 'description' => 'Post status']
            ]
        ]);

        // Create post tool (migrated from existing code)
        new RegisterMcpTool([
            'name' => 'wp_create_post',
            'description' => 'Create a new WordPress post',
            'type' => 'create',
            'rest_alias' => [
                'route' => '/posts',
                'method' => 'POST'
            ],
            'parameters' => [
                'title' => ['type' => 'string', 'description' => 'Post title'],
                'content' => ['type' => 'string', 'description' => 'Post content'],
                'status' => ['type' => 'string', 'description' => 'Post status'],
                'excerpt' => ['type' => 'string', 'description' => 'Post excerpt']
            ]
        ]);

        Logger::info('Default MCP tools registered', ['count' => count(RegisterMcpTool::getTools())]);
    }

    /**
     * Get site information
     *
     * @param array $params
     * @return array
     */
    public function getSiteInfo(array $params = []): array
    {
        return [
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => get_bloginfo('url'),
            'admin_email' => get_bloginfo('admin_email'),
            'version' => get_bloginfo('version'),
            'language' => get_bloginfo('language'),
            'timezone' => wp_timezone_string(),
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
            'start_of_week' => get_option('start_of_week'),
            'plugin_version' => MCP_BRIDGE_VERSION,
        ];
    }

    /**
     * Get tools manifest for MCP
     *
     * @return \WP_REST_Response
     */
    public function getToolsManifest(): \WP_REST_Response
    {
        $manifest = RegisterMcpTool::getManifest();
        return new \WP_REST_Response($manifest, 200);
    }

    /**
     * Create database tables
     */
    private function createTables(): void
    {
        // Future implementation for any custom tables
        Logger::debug('Database tables creation completed');
    }

    /**
     * Set default plugin options
     */
    private function setDefaultOptions(): void
    {
        $defaultOptions = [
            'mcp_bridge_version' => MCP_BRIDGE_VERSION,
            'mcp_bridge_enabled_tools' => [],
            'mcp_bridge_rate_limit' => MCP_BRIDGE_RATE_LIMIT_ENABLED,
            'mcp_bridge_logging' => MCP_BRIDGE_LOG_ENABLED,
        ];

        foreach ($defaultOptions as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }

        Logger::debug('Default options set');
    }

    /**
     * Clear plugin cache
     */
    private function clearCache(): void
    {
        // Clear any transients or cached data
        delete_transient('mcp_bridge_tools_cache');
        Logger::debug('Plugin cache cleared');
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return MCP_BRIDGE_VERSION;
    }

    /**
     * Check if plugin is initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}