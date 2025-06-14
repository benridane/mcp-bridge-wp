<?php
/**
 * Tags Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Taxonomy
 */

namespace McpBridge\Tools\Taxonomy;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Tags Tools Class - Handles WordPress tag operations
 */
class TagsTools extends ToolBase
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
     * Initialize all tag-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Tags tools');

        // wp_list_tags
        new RegisterMcpTool([
            'name' => 'wp_list_tags',
            'description' => 'List WordPress tags with optional filtering',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/tags',
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
                    'offset' => [
                        'type' => 'integer',
                        'description' => 'Offset the result set by a specific number of items'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'enum' => ['id', 'include', 'name', 'slug', 'include_slugs', 'term_group', 'description', 'count'],
                        'default' => 'name',
                        'description' => 'Sort collection by attribute'
                    ],
                    'order' => [
                        'type' => 'string',
                        'enum' => ['asc', 'desc'],
                        'default' => 'asc',
                        'description' => 'Order sort attribute ascending or descending'
                    ],
                    'hide_empty' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Whether to hide terms not assigned to any posts'
                    ],
                    'post' => [
                        'type' => 'integer',
                        'description' => 'Limit result set to terms assigned to a specific post'
                    ],
                    'slug' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Limit result set to terms with one or more specific slugs'
                    ]
                ]
            ]
        ]);

        // wp_get_tag
        new RegisterMcpTool([
            'name' => 'wp_get_tag',
            'description' => 'Get a specific WordPress tag by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/tags/(?P<id>[\d]+)',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Tag ID',
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

        // wp_add_tag
        new RegisterMcpTool([
            'name' => 'wp_add_tag',
            'description' => 'Create a new WordPress tag',
            'type' => 'create',
            'rest_alias' => [
                'route' => '/wp/v2/tags',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Tag name',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Tag description'
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'An alphanumeric identifier for the tag unique to its type'
                    ],
                    'meta' => [
                        'type' => 'object',
                        'description' => 'Meta fields'
                    ]
                ],
                'required' => ['name']
            ]
        ]);

        // wp_update_tag
        new RegisterMcpTool([
            'name' => 'wp_update_tag',
            'description' => 'Update an existing WordPress tag',
            'type' => 'update',
            'rest_alias' => [
                'route' => '/wp/v2/tags/(?P<id>[\d]+)',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Tag ID',
                        'required' => true
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Tag name'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Tag description'
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'An alphanumeric identifier for the tag unique to its type'
                    ],
                    'meta' => [
                        'type' => 'object',
                        'description' => 'Meta fields'
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        // wp_delete_tag
        new RegisterMcpTool([
            'name' => 'wp_delete_tag',
            'description' => 'Delete a WordPress tag',
            'type' => 'delete',
            'rest_alias' => [
                'route' => '/wp/v2/tags/(?P<id>[\d]+)',
                'method' => 'DELETE'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Tag ID',
                        'required' => true
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Required to be true, as tags do not support trashing',
                        'default' => false
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        Logger::info('Tags tools registered successfully');
    }
}