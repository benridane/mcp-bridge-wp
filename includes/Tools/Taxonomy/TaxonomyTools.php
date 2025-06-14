<?php
/**
 * Taxonomy Tools for MCP Bridge
 *
 * @package McpBridge\Tools\Taxonomy
 */

namespace McpBridge\Tools\Taxonomy;

use McpBridge\API\Base\ToolBase;
use McpBridge\Core\RegisterMcpTool;
use McpBridge\Core\Logger;

/**
 * Taxonomy Tools Class - Handles WordPress taxonomy operations
 */
class TaxonomyTools extends ToolBase
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
     * Initialize all taxonomy-related tools
     */
    public function initializeTools(): void
    {
        Logger::info('Registering Taxonomy tools');

        // wp_list_taxonomies
        new RegisterMcpTool([
            'name' => 'wp_list_taxonomies',
            'description' => 'List all registered taxonomies',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/taxonomies',
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
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Limit results to taxonomies associated with a specific post type'
                    ]
                ]
            ]
        ]);

        // wp_get_taxonomy
        new RegisterMcpTool([
            'name' => 'wp_get_taxonomy',
            'description' => 'Get a specific taxonomy by slug',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wp/v2/taxonomies/(?P<taxonomy>[\w-]+)',
                'method' => 'GET'
            ],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'taxonomy' => [
                        'type' => 'string',
                        'description' => 'Taxonomy slug (e.g., category, post_tag)',
                        'required' => true
                    ],
                    'context' => [
                        'type' => 'string',
                        'enum' => ['view', 'embed', 'edit'],
                        'default' => 'view',
                        'description' => 'Scope under which the request is made'
                    ]
                ],
                'required' => ['taxonomy']
            ]
        ]);

        // wp_get_taxonomy_terms
        new RegisterMcpTool([
            'name' => 'wp_get_taxonomy_terms',
            'description' => 'Get terms for a specific taxonomy',
            'type' => 'read',
            'handler' => [$this, 'getTaxonomyTerms'],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'taxonomy' => [
                        'type' => 'string',
                        'description' => 'Taxonomy slug (e.g., category, post_tag, or custom taxonomy)',
                        'required' => true
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of items to be returned',
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
                    'hide_empty' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Whether to hide terms not assigned to any posts'
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Limit result set to terms assigned to a specific parent'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'enum' => ['id', 'name', 'slug', 'count', 'term_group'],
                        'default' => 'name',
                        'description' => 'Sort collection by attribute'
                    ],
                    'order' => [
                        'type' => 'string',
                        'enum' => ['asc', 'desc'],
                        'default' => 'asc',
                        'description' => 'Order sort attribute'
                    ]
                ],
                'required' => ['taxonomy']
            ]
        ]);

        // wp_get_post_taxonomies
        new RegisterMcpTool([
            'name' => 'wp_get_post_taxonomies',
            'description' => 'Get all taxonomies and terms for a specific post',
            'type' => 'read',
            'handler' => [$this, 'getPostTaxonomies'],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'post_id' => [
                        'type' => 'integer',
                        'description' => 'Post ID',
                        'required' => true
                    ]
                ],
                'required' => ['post_id']
            ]
        ]);

        Logger::info('Taxonomy tools registered successfully');
    }

    /**
     * Get terms for a specific taxonomy
     *
     * @param array $arguments Tool arguments
     * @return array
     */
    public function getTaxonomyTerms($arguments)
    {
        $taxonomy = $arguments['taxonomy'] ?? '';
        if (empty($taxonomy)) {
            throw new \Exception('Taxonomy slug is required');
        }

        // Check if taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            throw new \Exception("Taxonomy '{$taxonomy}' does not exist");
        }

        // Build query args
        $args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => $arguments['hide_empty'] ?? false,
            'number' => $arguments['per_page'] ?? 10,
            'offset' => (($arguments['page'] ?? 1) - 1) * ($arguments['per_page'] ?? 10),
            'orderby' => $arguments['orderby'] ?? 'name',
            'order' => strtoupper($arguments['order'] ?? 'ASC')
        ];

        if (isset($arguments['search'])) {
            $args['search'] = $arguments['search'];
        }

        if (isset($arguments['parent'])) {
            $args['parent'] = intval($arguments['parent']);
        }

        // Get terms
        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            throw new \Exception('Failed to retrieve terms: ' . $terms->get_error_message());
        }

        // Get total count for pagination
        $count_args = $args;
        unset($count_args['number'], $count_args['offset']);
        $total_terms = wp_count_terms($count_args);

        // Format response
        $formatted_terms = array_map(function($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $term->parent,
                'count' => $term->count,
                'taxonomy' => $term->taxonomy,
                'link' => get_term_link($term)
            ];
        }, $terms);

        return [
            'terms' => $formatted_terms,
            'total' => $total_terms,
            'page' => $arguments['page'] ?? 1,
            'per_page' => $arguments['per_page'] ?? 10,
            'pages' => ceil($total_terms / ($arguments['per_page'] ?? 10))
        ];
    }

    /**
     * Get all taxonomies and terms for a specific post
     *
     * @param array $arguments Tool arguments
     * @return array
     */
    public function getPostTaxonomies($arguments)
    {
        $post_id = intval($arguments['post_id'] ?? 0);
        if (!$post_id) {
            throw new \Exception('Post ID is required');
        }

        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            throw new \Exception('Post not found');
        }

        // Get all taxonomies for this post type
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        $result = [];

        foreach ($taxonomies as $taxonomy_slug => $taxonomy) {
            // Get terms for this taxonomy
            $terms = wp_get_post_terms($post_id, $taxonomy_slug);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $result[$taxonomy_slug] = [
                    'taxonomy' => [
                        'name' => $taxonomy->label,
                        'slug' => $taxonomy_slug,
                        'hierarchical' => $taxonomy->hierarchical,
                        'public' => $taxonomy->public
                    ],
                    'terms' => array_map(function($term) {
                        return [
                            'id' => $term->term_id,
                            'name' => $term->name,
                            'slug' => $term->slug,
                            'description' => $term->description,
                            'parent' => $term->parent,
                            'count' => $term->count,
                            'link' => get_term_link($term)
                        ];
                    }, $terms)
                ];
            }
        }

        return [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'taxonomies' => $result
        ];
    }
}