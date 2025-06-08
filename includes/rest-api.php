<?php
add_action('rest_api_init', function () {
    register_rest_route('mcp/v1', '/rpc', [
        'methods'             => 'POST',
        'callback'            => 'mcp_rpc_handler',
        'permission_callback' => 'mcp_permission_check',
    ]);
});

/**
 * Extract Bearer token and decode if it contains username:password
 *
 * @return array|null [username, password] or null
 */
function get_bearer_credentials() {
    $bearer_token = get_bearer_token();
    
    if (!$bearer_token) {
        return null;
    }
    
    // Base64デコードを試行
    $decoded = base64_decode($bearer_token, true);
    
    if ($decoded && strpos($decoded, ':') !== false) {
        $credentials = explode(':', $decoded, 2);
        return [
            'username' => $credentials[0],
            'password' => $credentials[1] ?? ''
        ];
    }
    
    // 単純なトークンとして扱う
    return ['token' => $bearer_token];
}

/**
 * Permission callback with custom header support
 *
 * @return bool
 */
function mcp_permission_check() {
    error_log('=== MCP Permission Check ===');
    
    // カスタムヘッダーX-API-Keyをチェック
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    
    if ($api_key) {
        error_log('Found X-API-Key header: ' . substr($api_key, 0, 10) . '...');
        
        // Base64デコードを試行
        $decoded = base64_decode($api_key, true);
        
        if ($decoded && strpos($decoded, ':') !== false) {
            $credentials = explode(':', $decoded, 2);
            $username = $credentials[0];
            $password = $credentials[1] ?? '';
            
            error_log('Attempting username:password authentication from X-API-Key');
            $user = validate_user_application_password($username, $password);
            
            if ($user && user_can($user, 'edit_posts')) {
                error_log('Authentication successful for user: ' . $user->user_login);
                return true;
            }
        } else {
            // 単純なAPIキーとして処理
            $user = validate_application_password_from_db($api_key);
            
            if ($user && user_can($user, 'edit_posts')) {
                error_log('Token authentication successful for user: ' . $user->user_login);
                return true;
            }
        }
    }
    
    // HTTPベーシック認証も試行（念のため）
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        error_log('Found PHP_AUTH credentials');
        $user = validate_user_application_password($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        
        if ($user && user_can($user, 'edit_posts')) {
            error_log('PHP_AUTH authentication successful for user: ' . $user->user_login);
            return true;
        }
    }
    
    error_log('All authentication methods failed');
    return false;
}

/**
 * Extract Bearer token from Authorization header
 *
 * @return string|null
 */
function get_bearer_token() {
    $auth_header = null;
    
    // すべての$_SERVERヘッダーをデバッグ出力
    error_log('All $_SERVER headers: ' . print_r($_SERVER, true));
    
    // 複数の方法でAuthorizationヘッダーを取得
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        error_log('Found HTTP_AUTHORIZATION: ' . $auth_header);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        error_log('Found REDIRECT_HTTP_AUTHORIZATION: ' . $auth_header);
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        error_log('Apache headers: ' . print_r($headers, true));
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            error_log('Found Apache Authorization: ' . $auth_header);
        }
    }
    
    if (!$auth_header) {
        error_log('No Authorization header found');
        return null;
    }
    
    // "Bearer " プレフィックスを確認して削除
    if (strpos($auth_header, 'Bearer ') === 0) {
        return substr($auth_header, 7);
    }
    
    error_log('Authorization header does not start with Bearer: ' . $auth_header);
    return null;
}

/**
 * Validate Application Password token
 *
 * @param string $token
 * @return bool
 */
function validate_application_password($token) {
    // 設定されたApplication Passwordと照合
    // 実際のApplication Passwordを設定してください
    $valid_tokens = [
        'OPMzeHH9okk1DpSpp5xnOi1Q', // スペースを削除したApplication Password
        // 複数のトークンを設定可能
    ];
    
    return in_array($token, $valid_tokens);
}

/**
 * Validate Application Password token against WordPress database
 *
 * @param string $token
 * @return WP_User|false
 */
function validate_application_password_from_db($token) {
    global $wpdb;
    
    error_log('Validating token from database: ' . substr($token, 0, 10) . '...');
    
    // Application Passwordsメタデータを持つユーザーを検索
    $user_meta_table = $wpdb->usermeta;
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT user_id, meta_value 
        FROM {$user_meta_table} 
        WHERE meta_key = '_application_passwords'
    "));
    
    foreach ($results as $result) {
        $app_passwords = maybe_unserialize($result->meta_value);
        
        if (empty($app_passwords) || !is_array($app_passwords)) {
            continue;
        }
        
        foreach ($app_passwords as $app_password) {
            // Application Passwordはハッシュ化されて保存されている
            if (isset($app_password['password']) && wp_check_password($token, $app_password['password'])) {
                $user = get_user_by('ID', $result->user_id);
                error_log('Found matching application password for user: ' . $user->user_login);
                return $user;
            }
        }
    }
    
    error_log('No matching application password found in database');
    return false;
}

/**
 * Validate specific user's application password
 *
 * @param string $username
 * @param string $token
 * @return WP_User|false
 */
function validate_user_application_password($username, $token) {
    error_log('Validating user application password for username: ' . $username);
    error_log('Token to validate: ' . $token);
    
    $user = get_user_by('login', $username);
    
    if (!$user) {
        error_log('User not found: ' . $username);
        return false;
    }
    
    $app_passwords = get_user_meta($user->ID, '_application_passwords', true);
    
    if (empty($app_passwords) || !is_array($app_passwords)) {
        error_log('No application passwords found for user: ' . $username);
        return false;
    }
    
    error_log('Found ' . count($app_passwords) . ' application passwords for user: ' . $username);
    
    foreach ($app_passwords as $app_password) {
        error_log('Checking application password: ' . print_r($app_password, true));
        
        if (isset($app_password['password'])) {
            // WordPressのApplication Passwordは特殊な検証が必要
            $is_valid = wp_check_password($token, $app_password['password']);
            error_log('Password check result: ' . ($is_valid ? 'VALID' : 'INVALID'));
            error_log('Stored hash: ' . $app_password['password']);
            error_log('Input token: ' . $token);
            
            if ($is_valid) {
                error_log('Application password match found for user: ' . $username);
                return $user;
            }
        }
    }
    
    // WordPressの標準Application Password APIを使用
    error_log('Trying WordPress built-in application password validation');
    if (function_exists('wp_authenticate_application_password')) {
        $authenticated = wp_authenticate_application_password(null, $username, $token);
        if ($authenticated instanceof WP_User) {
            error_log('WordPress built-in validation successful');
            return $authenticated;
        }
    }
    
    error_log('No matching application password for user: ' . $username);
    return false;
}

/**
 * Handles MCP JSON-RPC requests.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function mcp_rpc_handler($request) {
    $json = json_decode($request->get_body(), true);
    if (!$json || !isset($json['method'])) {
        return new WP_REST_Response([
            'error' => 'Invalid JSON-RPC request',
        ], 400);
    }

    $method = $json['method'];
    $params = $json['params'] ?? [];

    switch ($method) {
        case 'getPosts':
            return new WP_REST_Response(mcp_get_posts($params));
        case 'createPost':
            return new WP_REST_Response(mcp_create_post($params));
        default:
            return new WP_REST_Response([
                'error' => "Unknown method: $method"
            ], 400);
    }
}

/**
 * Retrieves posts based on parameters.
 *
 * @param array $params
 * @return array
 */
function mcp_get_posts($params) {
    $args = [
        'numberposts' => $params['limit'] ?? 5,
        'post_type'   => $params['type'] ?? 'post',
    ];
    $posts = get_posts($args);
    $result = [];

    foreach ($posts as $post) {
        $result[] = [
            'id'      => $post->ID,
            'title'   => $post->post_title,
            'content' => $post->post_content,
            'date'    => $post->post_date,
            'status'  => $post->post_status,
            'type'    => $post->post_type,
        ];
    }

    return $result;
}

/**
 * Creates a new post based on parameters.
 *
 * @param array $params
 * @return array
 */
function mcp_create_post($params) {
    $post_data = [
        'post_title'   => $params['title'] ?? '(no title)',
        'post_content' => $params['content'] ?? '',
        'post_status'  => $params['status'] ?? 'draft',
        'post_type'    => $params['type'] ?? 'post',
    ];

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        return ['error' => $post_id->get_error_message()];
    }

    return ['post_id' => $post_id];
}