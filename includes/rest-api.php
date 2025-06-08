<?php
// デバッグ用: ファイルが読み込まれているかの確認
\McpBridge\Core\Logger::log('info', 'rest-api.php ファイルが読み込まれました');

add_action('rest_api_init', function () {
    // デバッグログ: エンドポイント登録開始
    \McpBridge\Core\Logger::log('info', 'rest_api_init アクションが実行されました - エンドポイント登録処理開始');
    
    // MCP over HTTP仕様に準拠したエンドポイント - mcp/v1/mcp として登録
    $mcp_result = register_rest_route('mcp/v1', '/mcp', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            \McpBridge\Core\Logger::log('info', '/mcp/v1/mcp エンドポイントにアクセスされました');
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            return $wpMcp->handleRpcRequest($request);
        },
        'permission_callback' => function ($request) {
            \McpBridge\Core\Logger::log('info', '/mcp/v1/mcp 権限チェックが実行されました');
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            return $wpMcp->checkPermissions($request);
        },
    ]);
    
    // 登録結果をログ出力
    \McpBridge\Core\Logger::log('info', '/mcp/v1/mcp エンドポイント登録結果: ' . ($mcp_result ? 'SUCCESS' : 'FAILED'));
    
    // 後方互換性のため既存エンドポイントも維持
    $rpc_result = register_rest_route('mcp/v1', '/rpc', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            \McpBridge\Core\Logger::log('info', '/mcp/v1/rpc エンドポイントにアクセスされました');
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            return $wpMcp->handleRpcRequest($request);
        },
        'permission_callback' => function ($request) {
            \McpBridge\Core\Logger::log('info', '/mcp/v1/rpc 権限チェックが実行されました');
            $wpMcp = \McpBridge\Core\WpMcp::getInstance();
            return $wpMcp->checkPermissions($request);
        },
    ]);
    
    // 登録結果をログ出力
    \McpBridge\Core\Logger::log('info', '/mcp/v1/rpc エンドポイント登録結果: ' . ($rpc_result ? 'SUCCESS' : 'FAILED'));
    
    // デバッグログ: 登録済みルートの確認
    $registered_routes = rest_get_server()->get_routes();
    $mcp_routes = array_filter(array_keys($registered_routes), function($route) {
        return strpos($route, '/mcp/v1/') === 0;
    });
    \McpBridge\Core\Logger::log('info', '登録済みMCPルート: ' . implode(', ', $mcp_routes));
    
    // デバッグログ: エンドポイント登録完了
    \McpBridge\Core\Logger::log('info', 'rest_api_init アクション完了 - 全エンドポイント登録処理終了');
});

// wp_loadedでの追加確認
add_action('wp_loaded', function() {
    \McpBridge\Core\Logger::log('info', 'wp_loaded アクションが実行されました');
    
    global $wp_rest_server;
    if ($wp_rest_server) {
        $routes = $wp_rest_server->get_routes();
        $mcp_routes = array_filter(array_keys($routes), function($route) {
            return strpos($route, '/mcp/v1') !== false;
        });
        \McpBridge\Core\Logger::log('info', 'wp_loaded時点でのMCPルート: ' . json_encode($mcp_routes));
    } else {
        \McpBridge\Core\Logger::log('info', 'wp_loaded時点でも$wp_rest_server が利用できません');
    }
    
    // 以前のリライトルールフラグを削除
    delete_option('mcp_bridge_rewrite_rules_flushed');
    \McpBridge\Core\Logger::log('info', 'リライトルールフラグクリーンアップ完了');
});

// init時の追加確認
add_action('init', function() {
    \McpBridge\Core\Logger::log('info', 'init アクションが実行されました - REST API関連チェック');
});