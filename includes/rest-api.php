<?php
/**
 * REST API initialization for MCP Bridge
 *
 * @package McpBridge
 */

add_action('rest_api_init', function () {
    // Debug log: Starting endpoint registration
    \McpBridge\Core\Logger::log('info', 'rest_api_init action executed - Starting endpoint registration process');
    
    // MCP over HTTP compliant endpoint - registered as mcp/v1/mcp
    $mcp_result = register_rest_route('mcp/v1', '/mcp', [
        'methods' => ['POST', 'OPTIONS'],
        'callback' => function ($request) {
            // Handle preflight OPTIONS request
            if ($request->get_method() === 'OPTIONS') {
                $response = new \WP_REST_Response();
                $response->set_status(200);
                $response->header('Access-Control-Allow-Origin', '*');
                $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS, GET');
                $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key, X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version, X-Session-Status');
                return $response;
            }
            
            \McpBridge\Core\Logger::log('info', '/mcp/v1/mcp endpoint accessed');
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            
            // Pre-generate session ID for immediate availability
            $sessionId = $wpMcp->getSessionId();
            
            // Log MCP Inspector specific debugging information
            \McpBridge\Core\Logger::info('MCP request received with enhanced tracking', [
                'session_id' => $sessionId,
                'method' => $request->get_method(),
                'content_type' => $request->get_header('Content-Type'),
                'user_agent' => $request->get_header('User-Agent'),
                'client_session_id' => $request->get_header('X-MCP-Session-ID'),
                'request_body_length' => strlen($request->get_body()),
                'timestamp' => current_time('mysql')
            ]);
            
            $response = $wpMcp->handleRpcRequest($request);
            
            // Enhanced CORS headers for actual response
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS, GET');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key, X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version, X-Session-Status');
            $response->header('Access-Control-Expose-Headers', 'X-MCP-Session-ID, X-MCP-Transport, X-MCP-Protocol-Version, X-Session-Status, X-Server-Name');
            
            // Log response for MCP Inspector debugging
            \McpBridge\Core\Logger::info('Response sent with enhanced session tracking', [
                'session_id' => $sessionId,
                'status_code' => $response->get_status(),
                'response_headers' => [
                    'X-MCP-Session-ID' => $response->get_headers()['X-MCP-Session-ID'] ?? 'not-set',
                    'X-MCP-Transport' => $response->get_headers()['X-MCP-Transport'] ?? 'not-set',
                    'X-Session-Status' => $response->get_headers()['X-Session-Status'] ?? 'not-set'
                ]
            ]);
            
            return $response;
        },
        'permission_callback' => function ($request) {
            // Skip authentication for initialization methods
            $body = $request->get_body();
            if (!empty($body)) {
                $data = json_decode($body, true);
                $method = $data['method'] ?? null;
                
                // Allow MCP initialization methods without authentication
                $allowed_methods = ['initialize', 'ping', 'notifications/initialized', 'initialized'];
                if (in_array($method, $allowed_methods)) {
                    \McpBridge\Core\Logger::info('Allowing method without authentication', ['method' => $method]);
                    return true;
                }
            }
            
            // All other methods require authentication
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            return $wpMcp->checkPermissions($request);
        },
    ]);
    
    // Log registration result
    \McpBridge\Core\Logger::log('info', '/mcp/v1/mcp endpoint registration result: ' . ($mcp_result ? 'SUCCESS' : 'FAILED'));
    
    // Add simple test endpoint for debugging
    $test_result = register_rest_route('mcp/v1', '/test', [
        'methods' => ['GET', 'POST'],
        'callback' => function ($request) {
            \McpBridge\Core\Logger::log('info', '/mcp/v1/test endpoint accessed');
            return new \WP_REST_Response([
                'status' => 'success',
                'message' => 'MCP Bridge is working',
                'timestamp' => current_time('mysql'),
                'version' => MCP_BRIDGE_VERSION
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);
    
    \McpBridge\Core\Logger::log('info', '/mcp/v1/test endpoint registration result: ' . ($test_result ? 'SUCCESS' : 'FAILED'));
    
    // Maintain existing endpoints for backward compatibility
    $rpc_result = register_rest_route('mcp/v1', '/rpc', [
        'methods' => ['POST', 'OPTIONS'],
        'callback' => function ($request) {
            // Handle preflight OPTIONS request
            if ($request->get_method() === 'OPTIONS') {
                $response = new \WP_REST_Response();
                $response->set_status(200);
                $response->header('Access-Control-Allow-Origin', '*');
                $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS');
                $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key');
                return $response;
            }
            
            \McpBridge\Core\Logger::log('info', '/mcp/v1/rpc endpoint accessed');
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            $response = $wpMcp->handleRpcRequest($request);
            
            // Enhanced CORS headers for actual response
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key');
            
            return $response;
        },
        'permission_callback' => function ($request) {
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            return $wpMcp->checkPermissions($request);
        },
    ]);
    
    // Log registration result
    \McpBridge\Core\Logger::log('info', '/mcp/v1/rpc endpoint registration result: ' . ($rpc_result ? 'SUCCESS' : 'FAILED'));
    
    // Debug log: Check registered routes
    $registered_routes = rest_get_server()->get_routes();
    $mcp_routes = array_filter(array_keys($registered_routes), function($route) {
        return strpos($route, '/mcp/v1/') === 0;
    });
    \McpBridge\Core\Logger::log('info', 'Registered MCP routes: ' . implode(', ', $mcp_routes));
    
    // Debug log: Endpoint registration complete
    \McpBridge\Core\Logger::log('info', 'rest_api_init action completed - All endpoint registration processes finished');
});

// wp_loadedでの追加確認
add_action('wp_loaded', function() {
    \McpBridge\Core\Logger::log('info', 'wp_loaded action executed');
    
    global $wp_rest_server;
    if ($wp_rest_server) {
        $routes = $wp_rest_server->get_routes();
        $mcp_routes = array_filter(array_keys($routes), function($route) {
            return strpos($route, '/mcp/v1') !== false;
        });
        \McpBridge\Core\Logger::log('info', 'MCP routes at wp_loaded: ' . json_encode($mcp_routes));
    } else {
        \McpBridge\Core\Logger::log('info', 'wp_loaded: $wp_rest_server is not available');
    }
    
    // 以前のリライトルールフラグを削除
    delete_option('mcp_bridge_rewrite_rules_flushed');
    \McpBridge\Core\Logger::log('info', 'Rewrite rules flag cleanup complete');
});

// init時の追加確認
add_action('init', function() {
    \McpBridge\Core\Logger::log('info', 'init action executed - REST API related checks');
});