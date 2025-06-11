<?php
/**
 * Post Meta Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Posts
 */

namespace McpBridge\Tools\Posts;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Post Meta Tools Class - Handles WordPress post metadata operations
 */
class PostMetaTools extends ToolBase
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
     * Initialize all post meta-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Post Meta tools');

        $tools = [
            [
                'name' => 'wp_get_post_meta',
                'description' => 'Get metadata for a WordPress post',
                'type' => 'read',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<parent>[\d]+)/meta',
                    'method' => 'GET'
                ],
                'parameters' => [
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Post ID to get metadata for',
                        'required' => true
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Specific meta key to retrieve'
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
                'name' => 'wp_get_post_meta_value',
                'description' => 'Get a specific post meta value by meta ID',
                'type' => 'read',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<parent>[\d]+)/meta/(?P<id>[\d]+)',
                    'method' => 'GET'
                ],
                'parameters' => [
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Post ID',
                        'required' => true
                    ],
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Meta ID to retrieve',
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
                'name' => 'wp_add_post_meta',
                'description' => 'Add metadata to a WordPress post',
                'type' => 'create',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<parent>[\d]+)/meta',
                    'method' => 'POST'
                ],
                'parameters' => [
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Post ID to add metadata to',
                        'required' => true
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Meta key name',
                        'required' => true
                    ],
                    'value' => [
                        'type' => ['string', 'number', 'boolean', 'array', 'object'],
                        'description' => 'Meta value to store',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'wp_update_post_meta',
                'description' => 'Update post metadata by meta ID',
                'type' => 'update',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<parent>[\d]+)/meta/(?P<id>[\d]+)',
                    'method' => 'PUT'
                ],
                'parameters' => [
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Post ID',
                        'required' => true
                    ],
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Meta ID to update',
                        'required' => true
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Meta key name'
                    ],
                    'value' => [
                        'type' => ['string', 'number', 'boolean', 'array', 'object'],
                        'description' => 'New meta value'
                    ]
                ]
            ],
            [
                'name' => 'wp_delete_post_meta',
                'description' => 'Delete post metadata by meta ID',
                'type' => 'delete',
                'rest_alias' => [
                    'route' => '/wp/v2/posts/(?P<parent>[\d]+)/meta/(?P<id>[\d]+)',
                    'method' => 'DELETE'
                ],
                'parameters' => [
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Post ID',
                        'required' => true
                    ],
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Meta ID to delete',
                        'required' => true
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Whether to permanently delete the meta',
                        'default' => false
                    ]
                ]
            ]
        ];

        $this->registerTools($tools);
        Logger::info('Post Meta tools registered successfully');
    }
}