<?php
/**
 * Plugin Constants
 *
 * @package McpBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin core constants
define('MCP_BRIDGE_VERSION', '1.2.2');
define('MCP_BRIDGE_PATH', plugin_dir_path(dirname(__FILE__)));
define('MCP_BRIDGE_URL', plugin_dir_url(dirname(__FILE__)));
define('MCP_BRIDGE_BASENAME', plugin_basename(dirname(__FILE__) . '/mcp-bridge.php'));

// Directory path constants
define('MCP_BRIDGE_INCLUDES_PATH', MCP_BRIDGE_PATH . 'includes/');
define('MCP_BRIDGE_ASSETS_URL', MCP_BRIDGE_URL . 'assets/');
define('MCP_BRIDGE_TEMPLATES_PATH', MCP_BRIDGE_PATH . 'templates/');
define('MCP_BRIDGE_LOGS_PATH', MCP_BRIDGE_PATH . 'logs/');
define('MCP_BRIDGE_CONFIG_PATH', MCP_BRIDGE_PATH . 'config/');

// API related constants
define('MCP_BRIDGE_REST_NAMESPACE', 'mcp/v1');
define('MCP_BRIDGE_REST_ROUTE_RPC', '/rpc');

// Debug and logging configuration
define('MCP_BRIDGE_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
define('MCP_BRIDGE_LOG_ENABLED', true);

// Security configuration
define('MCP_BRIDGE_CAPABILITY_REQUIRED', 'edit_posts');
define('MCP_BRIDGE_RATE_LIMIT_ENABLED', true);
define('MCP_BRIDGE_RATE_LIMIT_REQUESTS', 100);
define('MCP_BRIDGE_RATE_LIMIT_WINDOW', 3600); // 1 hour