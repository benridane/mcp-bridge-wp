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

        // Load REST API configuration
        $this->loadRestApi();

        // Admin settings initialization - Settings クラスがadmin_menuフックを自分で管理
        add_action('admin_init', [$this, 'initAdmin']);

        // Plugin loaded
        add_action('plugins_loaded', [$this, 'pluginsLoaded']);
        
        // Initialize admin settings early
        if (is_admin()) {
            add_action('init', [$this, 'initAdminEarly']);
        }
    }

    /**
     * Load REST API configuration
     */
    private function loadRestApi(): void
    {
        $restApiFile = MCP_BRIDGE_INCLUDES_PATH . 'rest-api.php';
        if (file_exists($restApiFile)) {
            require_once $restApiFile;
            Logger::debug('rest-api.php ファイルが Plugin.php から読み込まれました');
        } else {
            Logger::error('rest-api.php ファイルが見つかりません: ' . $restApiFile);
        }
        
        // Load debug helper in development mode
        if (MCP_BRIDGE_DEBUG) {
            $debugFile = MCP_BRIDGE_INCLUDES_PATH . 'debug-admin-menu.php';
            if (file_exists($debugFile)) {
                require_once $debugFile;
                Logger::debug('Admin menu debug helper loaded');
            }
        }
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

        // Initialize Phase 2 Tools
        $this->initializePhase2Tools();
    }

    /**
     * Initialize Phase 2 Tools (Posts and Pages)
     */
    private function initializePhase2Tools(): void
    {
        // Initialize Posts tools
        new \McpBridge\Tools\Posts\PostsTools();
        new \McpBridge\Tools\Posts\PostMetaTools();

        // Initialize Pages tools  
        new \McpBridge\Tools\Pages\PagesTools();

        Logger::info('Phase 2 tools initialized (Posts and Pages)');
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
     * Initialize admin menu
     */
    public function initAdminMenu(): void
    {
        if (!is_admin()) {
            return;
        }

        Logger::info('initAdminMenu called', [
            'is_admin' => is_admin(),
            'current_user_can_manage_options' => current_user_can('manage_options'),
            'current_user_id' => get_current_user_id()
        ]);

        // Initialize admin settings page for menu creation
        \McpBridge\Admin\Settings::getInstance();
        
        Logger::debug('Admin menu initialized');
    }

    /**
     * Initialize admin interface
     */
    public function initAdmin(): void
    {
        if (!is_admin()) {
            return;
        }

        // Admin settings are already initialized in initAdminMenu
        Logger::debug('Admin settings initialized');
    }

    /**
     * Initialize admin early
     */
    public function initAdminEarly(): void
    {
        if (!is_admin()) {
            return;
        }

        Logger::info('initAdminEarly called', [
            'is_admin' => is_admin(),
            'current_user_can_manage_options' => current_user_can('manage_options'),
            'current_user_id' => get_current_user_id()
        ]);

        // Initialize admin settings page early
        \McpBridge\Admin\Settings::getInstance();
        
        Logger::debug('Admin early initialization completed');
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

        // Get posts tool (fixed route and improved parameters)
        new RegisterMcpTool([
            'name' => 'wp_get_posts',
            'description' => 'Get WordPress posts with optional filtering and pagination',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/posts',  // ✅ 修正: 正しいWordPress REST APIルート
                'method' => 'GET'
            ],
            'parameters' => [
                'per_page' => [
                    'type' => 'integer', 
                    'description' => 'Number of posts to retrieve',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 10
                ],
                'page' => [
                    'type' => 'integer', 
                    'description' => 'Page number for pagination',
                    'minimum' => 1,
                    'default' => 1
                ],
                'search' => [
                    'type' => 'string', 
                    'description' => 'Search term to filter posts'
                ],
                'status' => [
                    'type' => 'string', 
                    'description' => 'Post status to filter by',
                    'enum' => ['publish', 'draft', 'private', 'pending', 'future', 'any'],
                    'default' => 'publish'
                ],
                'orderby' => [
                    'type' => 'string',
                    'description' => 'Sort posts by field',
                    'enum' => ['date', 'title', 'modified', 'menu_order', 'author'],
                    'default' => 'date'
                ],
                'order' => [
                    'type' => 'string',
                    'description' => 'Sort order',
                    'enum' => ['asc', 'desc'],
                    'default' => 'desc'
                ]
            ]
        ]);

        // Create post tool with direct callback for better error handling
        new RegisterMcpTool([
            'name' => 'wp_create_post',
            'description' => 'Create a new WordPress post',
            'type' => 'create',
            'callback' => [$this, 'createPost'],
            'parameters' => [
                'title' => [
                    'type' => 'string', 
                    'description' => 'Post title',
                    'required' => true
                ],
                'content' => [
                    'type' => 'string', 
                    'description' => 'Post content',
                    'required' => true
                ],
                'status' => [
                    'type' => 'string', 
                    'description' => 'Post status',
                    'enum' => ['publish', 'draft', 'private', 'pending'],
                    'default' => 'draft'
                ],
                'excerpt' => [
                    'type' => 'string', 
                    'description' => 'Post excerpt'
                ]
            ]
        ]);

        Logger::info('Default MCP tools registered', ['count' => count(RegisterMcpTool::getTools())]);
    }

    /**
     * Get site information
     *
     * @param array $params
     * @return array MCP-compliant response format
     */
    public function getSiteInfo(array $params = []): array
    {
        $siteData = [
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

        // Return MCP-compliant format
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($siteData, JSON_PRETTY_PRINT)
                ]
            ]
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
     * Create a new WordPress post
     *
     * @param array $params
     * @return array MCP-compliant response format
     */
    public function createPost(array $params = []): array
    {
        try {
            Logger::info('Creating new post via MCP', [
                'params' => $params,
                'user' => wp_get_current_user()->user_login ?? 'unknown'
            ]);

            // Validate required parameters
            if (empty($params['title'])) {
                throw new \Exception('Post title is required');
            }

            if (empty($params['content'])) {
                throw new \Exception('Post content is required');
            }

            // Prepare post data
            $postData = [
                'post_title' => sanitize_text_field($params['title']),
                'post_content' => wp_kses_post($params['content']),
                'post_status' => $params['status'] ?? 'draft',
                'post_type' => 'post',
                'post_author' => get_current_user_id()
            ];

            // Add excerpt if provided
            if (!empty($params['excerpt'])) {
                $postData['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
            }

            // Validate post status
            $validStatuses = ['publish', 'draft', 'private', 'pending'];
            if (!in_array($postData['post_status'], $validStatuses)) {
                $postData['post_status'] = 'draft';
            }

            Logger::debug('Post data prepared', ['post_data' => $postData]);

            // Create the post
            $postId = wp_insert_post($postData, true);

            if (is_wp_error($postId)) {
                $errorMessage = $postId->get_error_message();
                Logger::error('Failed to create post', [
                    'error' => $errorMessage,
                    'post_data' => $postData
                ]);
                throw new \Exception('Failed to create post: ' . $errorMessage);
            }

            // Get the created post
            $createdPost = get_post($postId);
            if (!$createdPost) {
                throw new \Exception('Post created but could not retrieve post data');
            }

            Logger::info('Post created successfully', [
                'post_id' => $postId,
                'post_title' => $createdPost->post_title,
                'post_status' => $createdPost->post_status
            ]);

            // Prepare response data
            $responseData = [
                'id' => $postId,
                'title' => $createdPost->post_title,
                'content' => $createdPost->post_content,
                'excerpt' => $createdPost->post_excerpt,
                'status' => $createdPost->post_status,
                'date' => $createdPost->post_date,
                'modified' => $createdPost->post_modified,
                'author' => $createdPost->post_author,
                'slug' => $createdPost->post_name,
                'permalink' => get_permalink($postId),
                'edit_link' => get_edit_post_link($postId, 'raw')
            ];

            // Return MCP-compliant format
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Logger::error('Exception in createPost', [
                'exception' => $e->getMessage(),
                'params' => $params,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
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