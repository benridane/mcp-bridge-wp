<?php
/**
 * Pages Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Pages
 */

namespace McpBridge\Tools\Pages;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Pages Tools Class - Handles WordPress pages operations
 */
class PagesTools extends ToolBase
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
     * Initialize all pages-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Pages tools');

        $tools = [
            [
                'name' => 'wp_pages_search',
                'description' => 'Search and filter WordPress pages with pagination',
                'type' => 'read',
                'rest_alias' => [
                    'route' => '/wp/v2/pages',
                    'method' => 'GET'
                ],
                'parameters' => [
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of pages to retrieve',
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
                        'description' => 'Search term to filter pages'
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Page status (publish, draft, private, etc.)',
                        'default' => 'publish'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'description' => 'Sort pages by field',
                        'enum' => ['date', 'title', 'modified', 'menu_order'],
                        'default' => 'date'
                    ],
                    'order' => [
                        'type' => 'string',
                        'description' => 'Sort order',
                        'enum' => ['asc', 'desc'],
                        'default' => 'desc'
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Filter pages by parent page ID'
                    ]
                ]
            ],
            [
                'name' => 'wp_get_page',
                'description' => 'Get a WordPress page by ID',
                'type' => 'read',
                'rest_alias' => [
                    'route' => '/wp/v2/pages/(?P<id>[\d]+)',
                    'method' => 'GET'
                ],
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Page ID to retrieve',
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
                'name' => 'wp_add_page',
                'description' => 'Create a new WordPress page',
                'type' => 'create',
                'rest_alias' => [
                    'route' => '/wp/v2/pages',
                    'method' => 'POST'
                ],
                'parameters' => [
                    'title' => [
                        'type' => 'string',
                        'description' => 'Page title',
                        'required' => true
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'Page content (HTML or blocks)',
                        'required' => true
                    ],
                    'excerpt' => [
                        'type' => 'string',
                        'description' => 'Page excerpt'
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Page status',
                        'enum' => ['publish', 'draft', 'private'],
                        'default' => 'draft'
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Parent page ID for hierarchical pages'
                    ],
                    'menu_order' => [
                        'type' => 'integer',
                        'description' => 'Menu order for page ordering',
                        'default' => 0
                    ],
                    'template' => [
                        'type' => 'string',
                        'description' => 'Page template filename'
                    ]
                ]
            ],
            [
                'name' => 'wp_update_page',
                'description' => 'Update an existing WordPress page',
                'type' => 'update',
                'rest_alias' => [
                    'route' => '/wp/v2/pages/(?P<id>[\d]+)',
                    'method' => 'POST'
                ],
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Page ID to update',
                        'required' => true
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'Page title'
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'Page content (HTML or blocks)'
                    ],
                    'excerpt' => [
                        'type' => 'string',
                        'description' => 'Page excerpt'
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Page status',
                        'enum' => ['publish', 'draft', 'private', 'pending']
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Parent page ID'
                    ],
                    'menu_order' => [
                        'type' => 'integer',
                        'description' => 'Menu order for page ordering'
                    ],
                    'template' => [
                        'type' => 'string',
                        'description' => 'Page template filename'
                    ]
                ]
            ],
            [
                'name' => 'wp_delete_page',
                'description' => 'Delete a WordPress page',
                'type' => 'delete',
                'rest_alias' => [
                    'route' => '/wp/v2/pages/(?P<id>[\d]+)',
                    'method' => 'DELETE'
                ],
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Page ID to delete',
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
        Logger::info('Pages tools registered successfully');
    }
}