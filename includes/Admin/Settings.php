<?php
/**
 * Admin Settings Page
 *
 * @package McpBridge\Admin
 */

namespace McpBridge\Admin;

use McpBridge\Core\Logger;
use McpBridge\Core\RegisterMcpTool;

/**
 * Settings Class
 */
class Settings
{
    /**
     * Instance
     */
    private static ?self $instance = null;

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
        $this->init();
    }

    /**
     * Initialize
     */
    private function init(): void
    {
        Logger::info('Settings class initializing', [
            'is_admin' => is_admin(),
            'current_user_can_manage_options' => current_user_can('manage_options'),
            'current_user_id' => get_current_user_id(),
            'doing_action' => doing_action(),
            'current_filter' => current_filter()
        ]);
        
        // メニュー登録を直接実行（フックタイミング問題を回避）
        if (is_admin() && current_user_can('manage_options')) {
            add_action('admin_menu', [$this, 'addAdminMenu'], 10);
            Logger::debug('Admin menu hook registered with priority 10');
        } else {
            Logger::warning('Admin menu hook not registered - user lacks permissions or not in admin');
        }
        
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        
        Logger::debug('Settings hooks registered');
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu(): void
    {
        Logger::info('addAdminMenu method called', [
            'is_admin' => is_admin(),
            'current_user_can_manage_options' => current_user_can('manage_options'),
            'current_user_id' => get_current_user_id()
        ]);

        $page_hook = add_options_page(
            __('MCP Bridge Settings', 'mcp-bridge'),
            __('MCP Bridge', 'mcp-bridge'),
            'manage_options',
            'mcp-bridge-settings',
            [$this, 'renderSettingsPage']
        );
        
        Logger::info('Admin menu page added', [
            'page_hook' => $page_hook,
            'capability_check' => current_user_can('manage_options'),
            'user_id' => get_current_user_id(),
            'function_exists_add_options_page' => function_exists('add_options_page')
        ]);
        
        if (!$page_hook) {
            Logger::error('Failed to add admin menu page');
        }
    }

    /**
     * Register settings
     */
    public function registerSettings(): void
    {
        register_setting('mcp_bridge_settings', 'mcp_bridge_enabled_tools', [
            'type' => 'array',
            'default' => [],
            'sanitize_callback' => [$this, 'sanitizeEnabledTools']
        ]);

        register_setting('mcp_bridge_settings', 'mcp_bridge_logging_enabled', [
            'type' => 'boolean',
            'default' => true
        ]);

        register_setting('mcp_bridge_settings', 'mcp_bridge_debug_mode', [
            'type' => 'boolean',
            'default' => false
        ]);

        // Security Settings with sanitization
        register_setting('mcp_bridge_settings', 'mcp_bridge_allowed_origins', [
            'type' => 'array',
            'default' => [],
            'sanitize_callback' => [$this, 'sanitizeAllowedOrigins']
        ]);

        register_setting('mcp_bridge_settings', 'mcp_bridge_allowed_ips', [
            'type' => 'array',
            'default' => [],
            'sanitize_callback' => [$this, 'sanitizeAllowedIps']
        ]);

        register_setting('mcp_bridge_settings', 'mcp_bridge_rate_limit_enabled', [
            'type' => 'boolean',
            'default' => true
        ]);

        add_settings_section(
            'mcp_bridge_general',
            __('General Settings', 'mcp-bridge'),
            [$this, 'renderGeneralSection'],
            'mcp-bridge-settings'
        );

        add_settings_section(
            'mcp_bridge_tools',
            __('MCP Tools', 'mcp-bridge'),
            [$this, 'renderToolsSection'],
            'mcp-bridge-settings'
        );

        add_settings_section(
            'mcp_security_settings',
            __('Security Settings', 'mcp-bridge'),
            [$this, 'renderSecuritySectionDescription'],
            'mcp-bridge-settings'
        );

        add_settings_field(
            'mcp_bridge_logging_enabled',
            __('Enable Logging', 'mcp-bridge'),
            [$this, 'renderLoggingField'],
            'mcp-bridge-settings',
            'mcp_bridge_general'
        );

        add_settings_field(
            'mcp_bridge_debug_mode',
            __('Debug Mode', 'mcp-bridge'),
            [$this, 'renderDebugField'],
            'mcp-bridge-settings',
            'mcp_bridge_general'
        );

        add_settings_field(
            'mcp_bridge_enabled_tools',
            __('Enabled Tools', 'mcp-bridge'),
            [$this, 'renderToolsField'],
            'mcp-bridge-settings',
            'mcp_bridge_tools'
        );

        add_settings_field(
            'allowed_origins',
            __('Allowed CORS Origins', 'mcp-bridge'),
            [$this, 'renderAllowedOriginsField'],
            'mcp-bridge-settings',
            'mcp_security_settings'
        );

        add_settings_field(
            'allowed_ips',
            __('Allowed IP Addresses', 'mcp-bridge'),
            [$this, 'renderAllowedIpsField'],
            'mcp-bridge-settings',
            'mcp_security_settings'
        );

        add_settings_field(
            'rate_limit_enabled',
            __('Enable Rate Limiting', 'mcp-bridge'),
            [$this, 'renderRateLimitField'],
            'mcp-bridge-settings',
            'mcp_security_settings'
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueueScripts($hook): void
    {
        if ($hook !== 'settings_page_mcp-bridge-settings') {
            return;
        }

        wp_enqueue_style('mcp-bridge-admin', MCP_BRIDGE_ASSETS_URL . 'css/admin.css', [], MCP_BRIDGE_VERSION);
        wp_enqueue_script('mcp-bridge-admin', MCP_BRIDGE_ASSETS_URL . 'js/admin.js', ['jquery'], MCP_BRIDGE_VERSION, true);
    }

    /**
     * Render settings page
     */
    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle settings update
        if (isset($_GET['settings-updated'])) {
            add_settings_error('mcp_bridge_messages', 'mcp_bridge_message', 
                __('Settings Saved', 'mcp-bridge'), 'updated');
        }

        settings_errors('mcp_bridge_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="mcp-bridge-info">
                <h2><?php _e('MCP Bridge Status', 'mcp-bridge'); ?></h2>
                <p><strong><?php _e('Version:', 'mcp-bridge'); ?></strong> <?php echo MCP_BRIDGE_VERSION; ?></p>
                <p><strong><?php _e('Endpoint:', 'mcp-bridge'); ?></strong> 
                    <code><?php echo home_url('/wp-json/mcp/v1/mcp'); ?></code>
                </p>
                <p><strong><?php _e('Registered Tools:', 'mcp-bridge'); ?></strong> 
                    <?php echo count(RegisterMcpTool::getTools()); ?>
                </p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields('mcp_bridge_settings');
                do_settings_sections('mcp-bridge-settings');
                submit_button(__('Save Settings', 'mcp-bridge'));
                ?>
            </form>

            <div class="mcp-bridge-logs">
                <h2><?php _e('Recent Logs', 'mcp-bridge'); ?></h2>
                <div class="log-container">
                    <?php $this->renderRecentLogs(); ?>
                </div>
                <p>
                    <button type="button" class="button" id="refresh-logs">
                        <?php _e('Refresh Logs', 'mcp-bridge'); ?>
                    </button>
                    <button type="button" class="button" id="clear-logs">
                        <?php _e('Clear Logs', 'mcp-bridge'); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render general section
     */
    public function renderGeneralSection(): void
    {
        echo '<p>' . __('Configure general MCP Bridge settings.', 'mcp-bridge') . '</p>';
    }

    /**
     * Render tools section
     */
    public function renderToolsSection(): void
    {
        echo '<p>' . __('Enable or disable specific MCP tools.', 'mcp-bridge') . '</p>';
    }

    /**
     * Render security section description
     */
    public function renderSecuritySectionDescription(): void
    {
        echo '<p>' . __('Configure security settings for MCP Bridge access control.', 'mcp-bridge') . '</p>';
        echo '<div class="notice notice-warning inline">';
        echo '<p><strong>' . __('Warning:', 'mcp-bridge') . '</strong> ' . 
             __('Incorrect security settings may block legitimate access. Test thoroughly before deploying to production.', 'mcp-bridge') . '</p>';
        echo '</div>';
    }

    /**
     * Render logging field
     */
    public function renderLoggingField(): void
    {
        $value = get_option('mcp_bridge_logging_enabled', true);
        ?>
        <input type="checkbox" id="mcp_bridge_logging_enabled" 
               name="mcp_bridge_logging_enabled" value="1" <?php checked($value); ?>>
        <label for="mcp_bridge_logging_enabled">
            <?php _e('Enable logging of MCP requests and responses', 'mcp-bridge'); ?>
        </label>
        <?php
    }

    /**
     * Render debug field
     */
    public function renderDebugField(): void
    {
        $value = get_option('mcp_bridge_debug_mode', false);
        ?>
        <input type="checkbox" id="mcp_bridge_debug_mode" 
               name="mcp_bridge_debug_mode" value="1" <?php checked($value); ?>>
        <label for="mcp_bridge_debug_mode">
            <?php _e('Enable debug mode (verbose logging)', 'mcp-bridge'); ?>
        </label>
        <?php
    }

    /**
     * Render tools field
     */
    public function renderToolsField(): void
    {
        $enabledTools = get_option('mcp_bridge_enabled_tools', []);
        $allTools = RegisterMcpTool::getTools();

        if (empty($allTools)) {
            echo '<p>' . __('No tools are currently registered.', 'mcp-bridge') . '</p>';
            return;
        }

        echo '<div class="tools-list">';
        foreach ($allTools as $toolName => $tool) {
            $checked = in_array($toolName, $enabledTools) || $tool['enabled'];
            $disabled = $tool['type'] === 'read' ? 'disabled' : '';
            ?>
            <label class="tool-item">
                <input type="checkbox" name="mcp_bridge_enabled_tools[]" 
                       value="<?php echo esc_attr($toolName); ?>" 
                       <?php checked($checked); ?> <?php echo $disabled; ?>>
                <span class="tool-name"><?php echo esc_html($toolName); ?></span>
                <span class="tool-type tool-type-<?php echo esc_attr($tool['type']); ?>">
                    <?php echo esc_html($tool['type']); ?>
                </span>
                <span class="tool-description"><?php echo esc_html($tool['description']); ?></span>
                <?php if ($tool['type'] === 'read'): ?>
                    <small class="tool-note"><?php _e('(Always enabled)', 'mcp-bridge'); ?></small>
                <?php endif; ?>
            </label>
            <?php
        }
        echo '</div>';
    }

    /**
     * Render allowed origins field
     */
    public function renderAllowedOriginsField(): void
    {
        $origins = get_option('mcp_bridge_allowed_origins', []);
        $originsText = is_array($origins) ? implode("\n", $origins) : '';
        ?>
        <textarea name="mcp_bridge_allowed_origins" id="mcp_bridge_allowed_origins" 
                  rows="5" cols="50" class="regular-text"><?php echo esc_textarea($originsText); ?></textarea>
        <p class="description">
            <?php _e('Enter allowed origins, one per line. Examples:', 'mcp-bridge'); ?><br>
            <code>https://your-domain.com</code><br>
            <code>http://localhost:3000</code><br>
            <?php _e('Leave empty to allow all origins (not recommended for production).', 'mcp-bridge'); ?>
        </p>
        <?php
    }

    /**
     * Render allowed IPs field
     */
    public function renderAllowedIpsField(): void
    {
        $ips = get_option('mcp_bridge_allowed_ips', []);
        $ipsText = is_array($ips) ? implode("\n", $ips) : '';
        ?>
        <textarea name="mcp_bridge_allowed_ips" id="mcp_bridge_allowed_ips" 
                  rows="5" cols="50" class="regular-text"><?php echo esc_textarea($ipsText); ?></textarea>
        <p class="description">
            <?php _e('Enter allowed IP addresses or CIDR ranges, one per line. Examples:', 'mcp-bridge'); ?><br>
            <code>192.168.1.100</code><br>
            <code>10.0.0.0/8</code><br>
            <?php _e('Leave empty to allow all IPs.', 'mcp-bridge'); ?>
        </p>
        <?php
    }

    /**
     * Render rate limit field
     */
    public function renderRateLimitField(): void
    {
        $enabled = get_option('mcp_bridge_rate_limit_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="mcp_bridge_rate_limit_enabled" value="1" 
                   <?php checked($enabled); ?> />
            <?php _e('Enable rate limiting (100 requests per hour per IP)', 'mcp-bridge'); ?>
        </label>
        <p class="description">
            <?php _e('Recommended for production environments to prevent abuse.', 'mcp-bridge'); ?>
        </p>
        <?php
    }

    /**
     * Render recent logs
     */
    private function renderRecentLogs(): void
    {
        $logs = Logger::getRecentLogs(20);
        
        if (empty($logs)) {
            echo '<p>' . __('No logs available.', 'mcp-bridge') . '</p>';
            return;
        }

        echo '<pre class="log-content">';
        foreach (array_reverse($logs) as $log) {
            echo esc_html($log) . "\n";
        }
        echo '</pre>';
    }

    /**
     * Sanitize enabled tools
     */
    public function sanitizeEnabledTools($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $allTools = RegisterMcpTool::getTools();
        $sanitized = [];

        foreach ($input as $toolName) {
            $toolName = sanitize_text_field($toolName);
            if (isset($allTools[$toolName])) {
                $sanitized[] = $toolName;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize allowed origins
     */
    public function sanitizeAllowedOrigins($input): array
    {
        if (is_string($input)) {
            $input = explode("\n", $input);
        }
        
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];
        foreach ($input as $origin) {
            $origin = trim(sanitize_text_field($origin));
            if (!empty($origin)) {
                // Validate URL format
                if (filter_var($origin, FILTER_VALIDATE_URL) || 
                    preg_match('/^https?:\/\/[a-zA-Z0-9.-]+(?::[0-9]+)?$/', $origin)) {
                    $sanitized[] = $origin;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize allowed IPs
     */
    public function sanitizeAllowedIps($input): array
    {
        if (is_string($input)) {
            $input = explode("\n", $input);
        }
        
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];
        foreach ($input as $ip) {
            $ip = trim(sanitize_text_field($ip));
            if (!empty($ip)) {
                // Validate IP or CIDR format
                if (filter_var($ip, FILTER_VALIDATE_IP) || 
                    preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip)) {
                    $sanitized[] = $ip;
                }
            }
        }

        return $sanitized;
    }
}