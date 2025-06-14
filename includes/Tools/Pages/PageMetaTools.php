<?php
/**
 * Page Meta Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Pages
 */

namespace McpBridge\Tools\Pages;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Page Meta Tools Class - Handles WordPress page metadata operations
 */
class PageMetaTools extends ToolBase
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // Initialize tools immediately instead of waiting for init hook
        $this->initializeTools();
    }

    /**
     * Initialize all page meta-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Page Meta tools');

        // wp_get_page_meta
        new RegisterMcpTool([
            'name' => 'wp_get_page_meta',
            'description' => 'Get metadata for a WordPress page',
            'type' => 'read',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'page_id' => [
                        'type' => 'integer',
                        'description' => 'Page ID to get metadata for',
                        'required' => true
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Specific meta key to retrieve (optional)'
                    ],
                    'single' => [
                        'type' => 'boolean',
                        'description' => 'Return single value',
                        'default' => false
                    ]
                ]
            ],
            'handler' => [$this, 'getPageMeta']
        ]);
        // wp_add_page_meta
        new RegisterMcpTool([
            'name' => 'wp_add_page_meta',
            'description' => 'Add metadata to a WordPress page',
            'type' => 'create',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'page_id' => [
                        'type' => 'integer',
                        'description' => 'Page ID to add metadata to',
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
                    ],
                    'unique' => [
                        'type' => 'boolean',
                        'description' => 'Whether the same key should not be added',
                        'default' => false
                    ]
                ]
            ],
            'handler' => [$this, 'addPageMeta']
        ]);
        // wp_update_page_meta
        new RegisterMcpTool([
            'name' => 'wp_update_page_meta',
            'description' => 'Update page metadata',
            'type' => 'update',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'page_id' => [
                        'type' => 'integer',
                        'description' => 'Page ID',
                        'required' => true
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Meta key name',
                        'required' => true
                    ],
                    'value' => [
                        'type' => ['string', 'number', 'boolean', 'array', 'object'],
                        'description' => 'New meta value',
                        'required' => true
                    ],
                    'prev_value' => [
                        'type' => ['string', 'number', 'boolean', 'array', 'object'],
                        'description' => 'Previous value to match before updating'
                    ]
                ]
            ],
            'handler' => [$this, 'updatePageMeta']
        ]);
        // wp_delete_page_meta
        new RegisterMcpTool([
            'name' => 'wp_delete_page_meta',
            'description' => 'Delete page metadata',
            'type' => 'delete',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'page_id' => [
                        'type' => 'integer',
                        'description' => 'Page ID',
                        'required' => true
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Meta key to delete',
                        'required' => true
                    ],
                    'value' => [
                        'type' => ['string', 'number', 'boolean', 'array', 'object'],
                        'description' => 'Value to match before deletion (optional)'
                    ]
                ]
            ],
            'handler' => [$this, 'deletePageMeta']
        ]);
        Logger::info('Page Meta tools registered successfully');
    }

    /**
     * Get page metadata
     */
    public function getPageMeta($arguments)
    {
        $page_id = intval($arguments['page_id'] ?? 0);
        $key = $arguments['key'] ?? '';
        $single = $arguments['single'] ?? false;

        if (!$page_id) {
            throw new \Exception('Page ID is required');
        }

        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            throw new \Exception('Page not found');
        }

        if ($key) {
            $meta_value = get_post_meta($page_id, $key, $single);
        } else {
            $meta_value = get_post_meta($page_id);
        }

        return [
            'page_id' => $page_id,
            'meta' => $meta_value
        ];
    }

    /**
     * Add page metadata
     */
    public function addPageMeta($arguments)
    {
        $page_id = intval($arguments['page_id'] ?? 0);
        $key = $arguments['key'] ?? '';
        $value = $arguments['value'] ?? '';
        $unique = $arguments['unique'] ?? false;

        if (!$page_id || !$key) {
            throw new \Exception('Page ID and key are required');
        }

        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            throw new \Exception('Page not found');
        }

        $result = add_post_meta($page_id, $key, $value, $unique);

        if ($result === false) {
            throw new \Exception('Failed to add page meta');
        }

        return [
            'page_id' => $page_id,
            'key' => $key,
            'value' => $value,
            'meta_id' => $result
        ];
    }

    /**
     * Update page metadata
     */
    public function updatePageMeta($arguments)
    {
        $page_id = intval($arguments['page_id'] ?? 0);
        $key = $arguments['key'] ?? '';
        $value = $arguments['value'] ?? '';
        $prev_value = $arguments['prev_value'] ?? '';

        if (!$page_id || !$key) {
            throw new \Exception('Page ID and key are required');
        }

        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            throw new \Exception('Page not found');
        }

        $result = update_post_meta($page_id, $key, $value, $prev_value);

        if ($result === false) {
            throw new \Exception('Failed to update page meta');
        }

        return [
            'page_id' => $page_id,
            'key' => $key,
            'value' => $value,
            'updated' => true
        ];
    }

    /**
     * Delete page metadata
     */
    public function deletePageMeta($arguments)
    {
        $page_id = intval($arguments['page_id'] ?? 0);
        $key = $arguments['key'] ?? '';
        $value = $arguments['value'] ?? '';

        if (!$page_id || !$key) {
            throw new \Exception('Page ID and key are required');
        }

        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            throw new \Exception('Page not found');
        }

        $result = delete_post_meta($page_id, $key, $value);

        if (!$result) {
            throw new \Exception('Failed to delete page meta');
        }

        return [
            'page_id' => $page_id,
            'key' => $key,
            'deleted' => true
        ];
    }
}