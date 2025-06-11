<?php
/**
 * MCP Bridge Admin Menu Debug Helper
 * 
 * This file is for development/debugging purposes only.
 * It should not be loaded in production environments.
 * 
 * @package McpBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only load debug helpers in development mode or when explicitly enabled
if (!defined('MCP_BRIDGE_DEBUG_ADMIN') || !MCP_BRIDGE_DEBUG_ADMIN) {
    return;
}

// Debug admin menu registration
add_action('admin_menu', function() {
    global $menu, $submenu;
    
    // Only log if WP_DEBUG is enabled
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    error_log('=== MCP Bridge Admin Menu Debug ===');
    error_log('Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
    error_log('Current user ID: ' . get_current_user_id());
    error_log('Is admin: ' . (is_admin() ? 'YES' : 'NO'));
    
    // Check if our menu item exists
    $mcp_menu_found = false;
    if (isset($submenu['options-general.php'])) {
        foreach ($submenu['options-general.php'] as $item) {
            if (strpos($item[2], 'mcp-bridge-settings') !== false) {
                $mcp_menu_found = true;
                error_log('MCP Bridge menu found in submenu: ' . print_r($item, true));
                break;
            }
        }
    }
    
    if (!$mcp_menu_found) {
        error_log('MCP Bridge menu NOT found in submenu');
        if (isset($submenu['options-general.php'])) {
            error_log('Available settings submenus: ' . print_r(array_column($submenu['options-general.php'], 2), true));
        } else {
            error_log('No settings submenus found at all');
        }
    }
    
    error_log('=== End MCP Bridge Admin Menu Debug ===');
}, 999); // Late priority to ensure other menus are registered

// Debug WordPress admin initialization
add_action('admin_init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MCP Bridge: admin_init executed - can manage options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
    }
}, 999);

// Debug when admin notices might be shown
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MCP Bridge: admin_notices - user can manage options');
    }
}, 1);