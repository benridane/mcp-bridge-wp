<?php
/**
 * Posts Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Posts
 */

namespace McpBridge\Tools\Posts;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Posts Tools Class - Handles WordPress posts operations
 */
class PostsTools extends ToolBase
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        add_action('init', [$this, 'initializeTools'], 20);
    }

    /**
     * Initialize all posts-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Posts tools');

        $tools = [
            [
                'name' => 'wp_posts_search',
                'description' => 'Search and filter WordPress posts with pagination',
                'type' => 'read',
                'rest_alias' => [
                    'route' => '/wp/v2/posts',
                    'method' => 'GET'
                ],
                'parameters' => [
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of posts to retrieve',
                        'minimum' => 1,
                        'maximum' => 100,
                        'default' => 10
                    ],
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Page number for pagination',
                        'minimum' => 1,
                        'default' => 1
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Search term to filter posts'
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Post status (publish, draft, private, etc.)',
                        'default' => 'publish'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'description' => 'Sort posts by field',
                        'enum' => ['date', 'title', 'modified', 'menu_order'],
                        'default' => 'date'
                    ],
                    'order' => [
                        'type' => 'string',
                        'description' => 'Sort order',
                        'enum' => ['asc', 'desc'],
                        'default' => 'desc'
                    ]
                ]
            ],
            [
                'name' => 'wp_get_post',
                'description' => 'Get a WordPress post by ID',
                'type' => 'read',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<id>[\d]+)',
                    'method' => 'GET'
                ],
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Post ID to retrieve',
                        'required' => true
                    ],
                    'context' => [
                        'type' => 'string',
                        'description' => 'Request context',
                        'enum' => ['view', 'edit'],
                        'default' => 'view'
                    ]
                ]
            ],
            [
                'name' => 'wp_update_post',
                'description' => 'Update an existing WordPress post',
                'type' => 'update',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<id>[\d]+)',
                    'method' => 'POST'  // ✅ WordPress uses POST for updates, not PUT
                ],
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Post ID to update',
                        'required' => true
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'Post title'
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'Post content (HTML or blocks)'
                    ],
                    'excerpt' => [
                        'type' => 'string',
                        'description' => 'Post excerpt'
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Post status',
                        'enum' => ['publish', 'draft', 'private', 'pending']
                    ],
                    'categories' => [
                        'type' => 'array',
                        'description' => 'Array of category IDs',
                        'items' => ['type' => 'integer']
                    ],
                    'tags' => [
                        'type' => 'array',
                        'description' => 'Array of tag IDs',
                        'items' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'wp_delete_post',
                'description' => 'Delete a WordPress post',
                'type' => 'delete',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<id>[\d]+)',
                    'method' => 'DELETE'
                ],
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Post ID to delete',
                        'required' => true
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Whether to permanently delete (true) or move to trash (false)',
                        'default' => false
                    ]
                ]
            ]
        ];

        $this->registerTools($tools);
        Logger::info('Posts tools registered successfully');
    }
}