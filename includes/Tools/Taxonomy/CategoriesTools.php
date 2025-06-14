<?php
/**
 * Categories Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Taxonomy
 */

namespace McpBridge\Tools\Taxonomy;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Categories Tools Class - Handles WordPress category operations
 */
class CategoriesTools extends ToolBase
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
     * Initialize all category-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Categories tools');

        // wp_list_categories
        new RegisterMcpTool([
            'name' => 'wp_list_categories',
            'description' => 'List WordPress categories with optional filtering',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/categories',
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
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Limit result set to terms assigned to a specific parent'
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

        // wp_get_category
        new RegisterMcpTool([
            'name' => 'wp_get_category',
            'description' => 'Get a specific WordPress category by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/categories/(?P<id>[\d]+)',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Category ID',
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

        // wp_add_category
        new RegisterMcpTool([
            'name' => 'wp_add_category',
            'description' => 'Create a new WordPress category',
            'type' => 'create',
            'rest_alias' => [
                'route' => '/wp/v2/categories',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Category name',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Category description'
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'An alphanumeric identifier for the category unique to its type'
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'The parent category ID',
                        'default' => 0
                    ],
                    'meta' => [
                        'type' => 'object',
                        'description' => 'Meta fields'
                    ]
                ],
                'required' => ['name']
            ]
        ]);

        // wp_update_category
        new RegisterMcpTool([
            'name' => 'wp_update_category',
            'description' => 'Update an existing WordPress category',
            'type' => 'update',
            'rest_alias' => [
                'route' => '/wp/v2/categories/(?P<id>[\d]+)',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Category ID',
                        'required' => true
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Category name'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Category description'
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'An alphanumeric identifier for the category unique to its type'
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'The parent category ID'
                    ],
                    'meta' => [
                        'type' => 'object',
                        'description' => 'Meta fields'
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        // wp_delete_category
        new RegisterMcpTool([
            'name' => 'wp_delete_category',
            'description' => 'Delete a WordPress category',
            'type' => 'delete',
            'rest_alias' => [
                'route' => '/wp/v2/categories/(?P<id>[\d]+)',
                'method' => 'DELETE'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Category ID',
                        'required' => true
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Required to be true, as categories do not support trashing',
                        'default' => false
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        Logger::info('Categories tools registered successfully');
    }
}