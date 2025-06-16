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
            'description' => 'Upload a new media file to WordPress',
            'type' => 'create',
            'handler' => [$this, 'uploadMedia'],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'file_data' => [
                        'type' => 'string',
                        'description' => 'Base64 encoded file content'
                    ],
                    'file_path' => [
                        'type' => 'string',
                        'description' => 'Local file path (for server-side files only)'
                    ],
                    'filename' => [
                        'type' => 'string',
                        'description' => 'Filename for the uploaded file',
                        'required' => true
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
                'required' => ['filename']
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
        // Base64データまたはファイルパスを受け入れる
        $file_data = $arguments['file_data'] ?? null;
        $file_path = $arguments['file_path'] ?? null;
        $filename = $arguments['filename'] ?? null;
        $mime_type = $arguments['mime_type'] ?? null;
        
        if (empty($file_data) && empty($file_path)) {
            throw new \Exception('Either file_data (base64) or file_path is required');
        }
        
        if (empty($filename)) {
            throw new \Exception('Filename is required');
        }
        
        // Include WordPress media handling functions
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // 一時ファイルを作成
        $temp_file = null;
        
        try {
            if (!empty($file_data)) {
                // Base64データの場合
                $decoded = base64_decode($file_data, true);
                if ($decoded === false) {
                    throw new \Exception('Invalid base64 data');
                }
                
                // 一時ファイルに書き込み
                $temp_file = wp_tempnam($filename);
                if (file_put_contents($temp_file, $decoded) === false) {
                    throw new \Exception('Failed to write temporary file');
                }
                $file_path = $temp_file;
                
                // MIMEタイプを検出
                if (empty($mime_type)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $temp_file);
                    finfo_close($finfo);
                }
            } else {
                // ローカルファイルパスの場合（既存の動作）
                if (!file_exists($file_path)) {
                    throw new \Exception("File not found: {$file_path}");
                }
                
                if (!is_readable($file_path)) {
                    throw new \Exception("File is not readable: {$file_path}");
                }
                
                // ファイル名が指定されていない場合はパスから取得
                if (empty($filename)) {
                    $filename = basename($file_path);
                }
            }
            
            // ファイルタイプをチェック
            $file_type = wp_check_filetype($filename);
            
            if (!$mime_type && $file_type['type']) {
                $mime_type = $file_type['type'];
            }
            
            if (!$mime_type) {
                $mime_type = mime_content_type($file_path);
            }
            
            if (!$mime_type) {
                throw new \Exception('Could not determine file type');
            }
            
            // Prepare upload
            $upload_file = [
                'name' => $filename,
                'type' => $mime_type,
                'tmp_name' => $file_path,
                'error' => 0,
                'size' => filesize($file_path)
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