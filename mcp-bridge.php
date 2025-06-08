<?php
/**
 * Plugin Name: MCP Bridge
 * Description: MCP (Model Context Protocol) interface for WordPress with Application Password authentication.
 * Version: 1.1.5
 * Author: benridane
 * Author URI: https://github.com/benridane
 * Plugin URI: https://github.com/benridane/mcp-bridge-wp
 * Text Domain: mcp-bridge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin constants
require_once plugin_dir_path(__FILE__) . 'includes/constants.php';

// Load autoloader
require_once MCP_BRIDGE_INCLUDES_PATH . 'autoloader.php';

// Initialize plugin
use McpBridge\Core\Plugin;

// Start the plugin
Plugin::getInstance();