<?php
/**
 * Plugin Name: MCP Bridge
 * Description: MCP (Model Context Protocol) interface for WordPress with Application Password authentication.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: mcp-bridge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 基本定数の読み込み
require_once plugin_dir_path(__FILE__) . 'includes/constants.php';

// オートローダーの読み込み
require_once MCP_BRIDGE_INCLUDES_PATH . 'autoloader.php';

// プラグインメインクラスの初期化
use McpBridge\Core\Plugin;

// プラグインの初期化
Plugin::getInstance();