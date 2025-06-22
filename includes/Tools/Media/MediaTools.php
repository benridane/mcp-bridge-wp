<?php
/**
 * Media Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Media
 */

namespace McpBridge\Tools\Media;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Media Tools Class - Handles WordPress media library operations
 */
class MediaTools extends ToolBase
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->initializeTools();
    }

    /**
     * Initialize all media-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Media tools');

        // wp_list_media
        new RegisterMcpTool([
            'name' => 'wp_list_media',
            'description' => 'List media items from WordPress media library',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/media',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of items to be returned in result set',
                        'default' => 10,
                        'minimum' => 1,
                        'maximum' => 100
                    ],
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Current page of the collection',
                        'default' => 1,
                        'minimum' => 1
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Limit results to those matching a string'
                    ],
                    'after' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => 'Limit response to posts published after a given ISO8601 compliant date'
                    ],
                    'before' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => 'Limit response to posts published before a given ISO8601 compliant date'
                    ],
                    'exclude' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'description' => 'Ensure result set excludes specific IDs'
                    ],
                    'include' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'description' => 'Limit result set to specific IDs'
                    ],
                    'parent' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'description' => 'Limit result set to items with particular parent IDs'
                    ],
                    'parent_exclude' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'description' => 'Limit result set to all items except those of a particular parent ID'
                    ],
                    'mime_type' => [
                        'type' => 'string',
                        'description' => 'Limit result set to attachments of a particular MIME type'
                    ],
                    'media_type' => [
                        'type' => 'string',
                        'enum' => ['image', 'video', 'text', 'application', 'audio'],
                        'description' => 'Limit result set to attachments of a particular media type'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'enum' => ['date', 'relevance', 'id', 'include', 'title', 'slug', 'modified', 'menu_order'],
                        'default' => 'date',
                        'description' => 'Sort collection by attribute'
                    ],
                    'order' => [
                        'type' => 'string',
                        'enum' => ['asc', 'desc'],
                        'default' => 'desc',
                        'description' => 'Order sort attribute ascending or descending'
                    ],
                    'status' => [
                        'type' => 'string',
                        'default' => 'inherit',
                        'enum' => ['inherit', 'private', 'trash'],
                        'description' => 'Limit result set to items assigned one or more statuses'
                    ]
                ]
            ]
        ]);

        // wp_get_media
        new RegisterMcpTool([
            'name' => 'wp_get_media',
            'description' => 'Get a specific media item by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/media/(?P<id>[\d]+)',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Media item ID',
                        'required' => true
                    ],
                    'context' => [
                        'type' => 'string',
                        'enum' => ['view', 'embed', 'edit'],
                        'default' => 'view',
                        'description' => 'Scope under which the request is made'
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        // wp_upload_media
        new RegisterMcpTool([
            'name' => 'wp_upload_media',
            'description' => 'Upload a new media file to WordPress from file, base64 data, or URL',
            'type' => 'create',
            'handler' => [$this, 'uploadMedia'],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'source_type' => [
                        'type' => 'string',
                        'enum' => ['file', 'base64', 'url'],
                        'description' => 'Type of source: file (path), base64 (data), or url (auto-detected if not specified)'
                    ],
                    'source' => [
                        'type' => 'string',
                        'description' => 'Source data: file path, base64 encoded content, or URL',
                        'required' => true
                    ],
                    'file_data' => [
                        'type' => 'string',
                        'description' => '[Deprecated] Use source with source_type=base64 instead'
                    ],
                    'file_path' => [
                        'type' => 'string',
                        'description' => '[Deprecated] Use source with source_type=file instead'
                    ],
                    'filename' => [
                        'type' => 'string',
                        'description' => 'Filename for the uploaded file (optional for URLs)',
                        'required' => false
                    ],
                    'mime_type' => [
                        'type' => 'string',
                        'description' => 'MIME type of the file (e.g., image/png, image/jpeg)'
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'Title for the attachment'
                    ],
                    'caption' => [
                        'type' => 'string',
                        'description' => 'Caption for the attachment'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Description for the attachment'
                    ],
                    'alt_text' => [
                        'type' => 'string',
                        'description' => 'Alternative text for the attachment'
                    ],
                    'post' => [
                        'type' => 'integer',
                        'description' => 'The ID for the associated post of the attachment'
                    ]
                ],
                'required' => ['source']
            ]
        ]);

        // wp_update_media
        new RegisterMcpTool([
            'name' => 'wp_update_media',
            'description' => 'Update an existing media item',
            'type' => 'update',
            'rest_alias' => [
                'route' => '/wp/v2/media/(?P<id>[\d]+)',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Media item ID',
                        'required' => true
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'Title for the attachment'
                    ],
                    'caption' => [
                        'type' => 'string',
                        'description' => 'Caption for the attachment'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Description for the attachment'
                    ],
                    'alt_text' => [
                        'type' => 'string',
                        'description' => 'Alternative text for the attachment'
                    ],
                    'post' => [
                        'type' => 'integer',
                        'description' => 'The ID for the associated post of the attachment'
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        // wp_delete_media
        new RegisterMcpTool([
            'name' => 'wp_delete_media',
            'description' => 'Delete a media item from WordPress',
            'type' => 'delete',
            'rest_alias' => [
                'route' => '/wp/v2/media/(?P<id>[\d]+)',
                'method' => 'DELETE'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Media item ID',
                        'required' => true
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Whether to bypass trash and force deletion',
                        'default' => false
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        Logger::info('Media tools registered successfully');
    }

    /**
     * Upload media file to WordPress
     *
     * @param array $arguments Tool arguments
     * @return array
     */
    public function uploadMedia($arguments)
    {
        // 新しいパラメータをサポート
        $source = $arguments['source'] ?? null;
        $source_type = $arguments['source_type'] ?? null;
        
        // 後方互換性のため古いパラメータもサポート
        $file_data = $arguments['file_data'] ?? null;
        $file_path = $arguments['file_path'] ?? null;
        
        // sourceが指定されていない場合は、古いパラメータを使用
        if (empty($source)) {
            if (!empty($file_data)) {
                $source = $file_data;
                $source_type = 'base64';
            } elseif (!empty($file_path)) {
                $source = $file_path;
                $source_type = 'file';
            }
        }
        
        if (empty($source)) {
            throw new \Exception('Source parameter is required');
        }
        
        // source_typeが指定されていない場合、sourceの内容から自動判定
        if (empty($source_type)) {
            if (filter_var($source, FILTER_VALIDATE_URL)) {
                // URLの場合
                $source_type = 'url';
                Logger::info('Auto-detected source type as URL', ['url' => $source]);
            } elseif (file_exists($source) && is_readable($source)) {
                // ローカルファイルの場合
                $source_type = 'file';
                Logger::info('Auto-detected source type as file', ['path' => $source]);
            } else {
                // それ以外はbase64とみなす
                $source_type = 'base64';
                Logger::info('Auto-detected source type as base64');
            }
        }
        
        $filename = $arguments['filename'] ?? null;
        $mime_type = $arguments['mime_type'] ?? null;
        
        // Include WordPress media handling functions
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // 一時ファイルを作成
        $temp_file = null;
        $actual_file_path = null;
        
        try {
            switch ($source_type) {
                case 'url':
                    // URLから画像をダウンロード
                    Logger::info('Downloading media from URL', ['url' => $source]);
                    
                    // URLの検証
                    if (!filter_var($source, FILTER_VALIDATE_URL)) {
                        throw new \Exception('Invalid URL provided');
                    }
                    
                    // WordPressの関数を使用してリモートファイルを取得
                    $response = wp_remote_get($source, [
                        'timeout' => 30,
                        'sslverify' => true,
                        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                    ]);
                    
                    if (is_wp_error($response)) {
                        throw new \Exception('Failed to download file: ' . $response->get_error_message());
                    }
                    
                    $response_code = wp_remote_retrieve_response_code($response);
                    if ($response_code !== 200) {
                        throw new \Exception("HTTP error: {$response_code}");
                    }
                    
                    $file_content = wp_remote_retrieve_body($response);
                    if (empty($file_content)) {
                        throw new \Exception('Downloaded file is empty');
                    }
                    
                    // ファイル名が指定されていない場合はURLから取得
                    if (empty($filename)) {
                        $url_parts = parse_url($source);
                        $filename = basename($url_parts['path']);
                        
                        // ファイル名が取得できない場合は、タイムスタンプベースの名前を生成
                        if (empty($filename) || $filename === '/') {
                            $filename = 'download-' . time();
                        }
                    }
                    
                    // Content-TypeヘッダーからMIMEタイプを取得
                    if (empty($mime_type)) {
                        $content_type = wp_remote_retrieve_header($response, 'content-type');
                        if ($content_type) {
                            // charset情報を除去
                            $mime_type = explode(';', $content_type)[0];
                        }
                    }
                    
                    // 一時ファイルに書き込み
                    $temp_file = wp_tempnam($filename);
                    if (file_put_contents($temp_file, $file_content) === false) {
                        throw new \Exception('Failed to write temporary file');
                    }
                    $actual_file_path = $temp_file;
                    
                    Logger::info('URL download successful', [
                        'filename' => $filename,
                        'size' => strlen($file_content),
                        'mime_type' => $mime_type
                    ]);
                    break;
                    
                case 'base64':
                    // Base64データの場合
                    $decoded = base64_decode($source, true);
                    if ($decoded === false) {
                        throw new \Exception('Invalid base64 data');
                    }
                    
                    if (empty($filename)) {
                        throw new \Exception('Filename is required for base64 uploads');
                    }
                    
                    // 一時ファイルに書き込み
                    $temp_file = wp_tempnam($filename);
                    if (file_put_contents($temp_file, $decoded) === false) {
                        throw new \Exception('Failed to write temporary file');
                    }
                    $actual_file_path = $temp_file;
                    
                    // MIMEタイプを検出
                    if (empty($mime_type)) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $temp_file);
                        finfo_close($finfo);
                    }
                    break;
                    
                case 'file':
                    // ローカルファイルパスの場合
                    if (!file_exists($source)) {
                        throw new \Exception("File not found: {$source}");
                    }
                    
                    if (!is_readable($source)) {
                        throw new \Exception("File is not readable: {$source}");
                    }
                    
                    $actual_file_path = $source;
                    
                    // ファイル名が指定されていない場合はパスから取得
                    if (empty($filename)) {
                        $filename = basename($source);
                    }
                    break;
                    
                default:
                    throw new \Exception("Invalid source_type: {$source_type}");
            }
            
            // ファイルタイプをチェック
            $file_type = wp_check_filetype($filename);
            
            if (!$mime_type && $file_type['type']) {
                $mime_type = $file_type['type'];
            }
            
            if (!$mime_type) {
                $mime_type = mime_content_type($actual_file_path);
            }
            
            if (!$mime_type) {
                throw new \Exception('Could not determine file type');
            }
            
            // ファイル拡張子が無い場合、MIMEタイプから推測
            if (strpos($filename, '.') === false && $mime_type) {
                $mime_to_ext = [
                    'image/jpeg' => '.jpg',
                    'image/png' => '.png',
                    'image/gif' => '.gif',
                    'image/webp' => '.webp',
                    'image/svg+xml' => '.svg',
                    'video/mp4' => '.mp4',
                    'video/webm' => '.webm',
                    'audio/mpeg' => '.mp3',
                    'audio/wav' => '.wav',
                    'application/pdf' => '.pdf',
                ];
                
                if (isset($mime_to_ext[$mime_type])) {
                    $filename .= $mime_to_ext[$mime_type];
                }
            }
            
            // WordPressのアップロード制限をチェック
            $allowed_mime_types = get_allowed_mime_types();
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $is_allowed = false;
            
            foreach ($allowed_mime_types as $ext_pattern => $allowed_mime) {
                if (preg_match('!^(' . $ext_pattern . ')$!i', $file_ext)) {
                    $is_allowed = true;
                    break;
                }
            }
            
            if (!$is_allowed) {
                throw new \Exception("File type not allowed. Extension: {$file_ext}, MIME: {$mime_type}");
            }
            
            // Prepare upload
            $upload_file = [
                'name' => $filename,
                'type' => $mime_type,
                'tmp_name' => $actual_file_path,
                'error' => 0,
                'size' => filesize($actual_file_path)
            ];

            // Handle the upload
            $upload = wp_handle_sideload($upload_file, ['test_form' => false]);

            if (isset($upload['error'])) {
                throw new \Exception('Upload failed: ' . $upload['error']);
            }

            // Prepare attachment data
            $attachment_data = [
                'post_mime_type' => $upload['type'],
                'post_title' => $arguments['title'] ?? sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => $arguments['description'] ?? '',
                'post_excerpt' => $arguments['caption'] ?? '',
                'post_status' => 'inherit'
            ];

            if (isset($arguments['post'])) {
                $attachment_data['post_parent'] = intval($arguments['post']);
            }

            // Insert attachment
            $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);

            if (is_wp_error($attachment_id)) {
                throw new \Exception('Failed to create attachment: ' . $attachment_id->get_error_message());
            }

            // Generate attachment metadata
            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);

            // Set alt text if provided
            if (isset($arguments['alt_text'])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $arguments['alt_text']);
            }

            // Get the created attachment
            $attachment = get_post($attachment_id);
            
            // Get attachment URL and metadata
            $attachment_url = wp_get_attachment_url($attachment_id);
            $metadata = wp_get_attachment_metadata($attachment_id);

            return [
                'id' => $attachment_id,
                'title' => $attachment->post_title,
                'caption' => $attachment->post_excerpt,
                'description' => $attachment->post_content,
                'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                'mime_type' => $attachment->post_mime_type,
                'media_type' => wp_attachment_is('image', $attachment_id) ? 'image' : 
                              (wp_attachment_is('video', $attachment_id) ? 'video' : 
                              (wp_attachment_is('audio', $attachment_id) ? 'audio' : 'file')),
                'source_url' => $attachment_url,
                'media_details' => $metadata,
                'post' => $attachment->post_parent,
                'date' => $attachment->post_date,
                'modified' => $attachment->post_modified,
                'slug' => $attachment->post_name,
                'status' => $attachment->post_status,
                'link' => get_attachment_link($attachment_id)
            ];
            
        } catch (\Exception $e) {
            // エラーが発生した場合も一時ファイルをクリーンアップ
            if ($temp_file && file_exists($temp_file)) {
                @unlink($temp_file);
            }
            throw $e;
            
        } finally {
            // 一時ファイルをクリーンアップ
            if ($temp_file && file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }
    }
}