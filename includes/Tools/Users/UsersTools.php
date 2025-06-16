<?php
/**
 * Users Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Users
 */

namespace McpBridge\Tools\Users;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Users Tools Class - Handles WordPress user operations
 */
class UsersTools extends ToolBase
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
     * Initialize all user-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Users tools');

        // wp_list_users
        new RegisterMcpTool([
            'name' => 'wp_list_users',
            'description' => 'List WordPress users with optional filtering',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/users',
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
                    'order' => [
                        'type' => 'string',
                        'enum' => ['asc', 'desc'],
                        'default' => 'asc',
                        'description' => 'Order sort attribute ascending or descending'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'enum' => ['id', 'include', 'name', 'registered_date', 'slug', 'include_slugs', 'email', 'url'],
                        'default' => 'name',
                        'description' => 'Sort collection by user attribute'
                    ],
                    'slug' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Limit result set to users with one or more specific slugs'
                    ],
                    'roles' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Limit result set to users matching at least one specific role'
                    ],
                    'capabilities' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Limit result set to users matching at least one specific capability'
                    ],
                    'who' => [
                        'type' => 'string',
                        'enum' => ['authors'],
                        'description' => 'Limit result set to users who are authors'
                    ],
                    'has_published_posts' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Limit result set to users who have published posts in this post type'
                    ]
                ]
            ]
        ]);

        // wp_get_user
        new RegisterMcpTool([
            'name' => 'wp_get_user',
            'description' => 'Get a specific WordPress user by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/users/(?P<id>[\d]+)',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'User ID',
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

        // wp_add_user
        new RegisterMcpTool([
            'name' => 'wp_add_user',
            'description' => 'Create a new WordPress user',
            'type' => 'create',
            'rest_alias' => [
                'route' => '/wp/v2/users',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'username' => [
                        'type' => 'string',
                        'description' => 'Login name for the user',
                        'required' => true
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'The email address for the user',
                        'required' => true
                    ],
                    'password' => [
                        'type' => 'string',
                        'description' => 'Password for the user',
                        'required' => true
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Display name for the user'
                    ],
                    'first_name' => [
                        'type' => 'string',
                        'description' => 'First name for the user'
                    ],
                    'last_name' => [
                        'type' => 'string',
                        'description' => 'Last name for the user'
                    ],
                    'url' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'URL of the user'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Description of the user'
                    ],
                    'locale' => [
                        'type' => 'string',
                        'description' => 'Locale for the user',
                        'enum' => ['', 'en_US', 'ja']
                    ],
                    'nickname' => [
                        'type' => 'string',
                        'description' => 'The nickname for the user'
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'An alphanumeric identifier for the user'
                    ],
                    'roles' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Roles assigned to the user'
                    ],
                    'meta' => [
                        'type' => 'object',
                        'description' => 'Meta fields'
                    ]
                ],
                'required' => ['username', 'email', 'password']
            ]
        ]);

        // wp_update_user
        new RegisterMcpTool([
            'name' => 'wp_update_user',
            'description' => 'Update an existing WordPress user',
            'type' => 'update',
            'rest_alias' => [
                'route' => '/wp/v2/users/(?P<id>[\d]+)',
                'method' => 'POST'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'User ID',
                        'required' => true
                    ],
                    'username' => [
                        'type' => 'string',
                        'description' => 'Login name for the user'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'The email address for the user'
                    ],
                    'password' => [
                        'type' => 'string',
                        'description' => 'Password for the user'
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Display name for the user'
                    ],
                    'first_name' => [
                        'type' => 'string',
                        'description' => 'First name for the user'
                    ],
                    'last_name' => [
                        'type' => 'string',
                        'description' => 'Last name for the user'
                    ],
                    'url' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'URL of the user'
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Description of the user'
                    ],
                    'locale' => [
                        'type' => 'string',
                        'description' => 'Locale for the user',
                        'enum' => ['', 'en_US', 'ja']
                    ],
                    'nickname' => [
                        'type' => 'string',
                        'description' => 'The nickname for the user'
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'An alphanumeric identifier for the user'
                    ],
                    'roles' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Roles assigned to the user'
                    ],
                    'meta' => [
                        'type' => 'object',
                        'description' => 'Meta fields'
                    ]
                ],
                'required' => ['id']
            ]
        ]);

        // wp_delete_user
        new RegisterMcpTool([
            'name' => 'wp_delete_user',
            'description' => 'Delete a WordPress user',
            'type' => 'delete',
            'rest_alias' => [
                'route' => '/wp/v2/users/(?P<id>[\d]+)',
                'method' => 'DELETE'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'User ID',
                        'required' => true
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Required to be true, as users do not support trashing',
                        'default' => false
                    ],
                    'reassign' => [
                        'type' => 'integer',
                        'description' => 'User ID to reassign posts and links to',
                        'required' => true
                    ]
                ],
                'required' => ['id', 'reassign']
            ]
        ]);

        // wp_get_current_user
        new RegisterMcpTool([
            'name' => 'wp_get_current_user',
            'description' => 'Get the currently authenticated user',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/users/me',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'context' => [
                        'type' => 'string',
                        'enum' => ['view', 'embed', 'edit'],
                        'default' => 'view',
                        'description' => 'Scope under which the request is made'
                    ]
                ]
            ]
        ]);

        Logger::info('Users tools registered successfully');
    }
}