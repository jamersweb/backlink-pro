<?php
/**
 * Plugin Name: BacklinkPro Meta Bridge
 * Plugin URI: https://backlinkpro.com
 * Description: Allows BacklinkPro to manage meta tags (title, description, OG tags, canonical) for your WordPress site.
 * Version: 1.0.0
 * Author: BacklinkPro
 * Author URI: https://backlinkpro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: backlinkpro-meta-bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BLP_META_BRIDGE_VERSION', '1.0.0');
define('BLP_META_BRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BLP_META_BRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class BacklinkPro_Meta_Bridge
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_head', [$this, 'output_meta_tags'], 1);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        register_rest_route('backlinkpro/v1', '/ping', [
            'methods' => 'GET',
            'callback' => [$this, 'ping'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('backlinkpro/v1', '/meta/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_meta'],
            'permission_callback' => [$this, 'check_edit_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        register_rest_route('backlinkpro/v1', '/meta/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_meta'],
            'permission_callback' => [$this, 'check_edit_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Ping endpoint for connection testing
     */
    public function ping($request)
    {
        return new WP_REST_Response([
            'status' => 'ok',
            'message' => 'BacklinkPro Meta Bridge is active',
            'version' => BLP_META_BRIDGE_VERSION,
        ], 200);
    }

    /**
     * Get meta for a post/page
     */
    public function get_meta($request)
    {
        $post_id = (int) $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }

        $meta = [
            'title' => get_post_meta($post_id, '_blp_title', true) ?: get_the_title($post_id),
            'description' => get_post_meta($post_id, '_blp_description', true) ?: get_the_excerpt($post_id),
            'og_title' => get_post_meta($post_id, '_blp_og_title', true) ?: get_the_title($post_id),
            'og_description' => get_post_meta($post_id, '_blp_og_description', true) ?: get_the_excerpt($post_id),
            'og_image' => get_post_meta($post_id, '_blp_og_image', true) ?: '',
            'canonical' => get_post_meta($post_id, '_blp_canonical', true) ?: get_permalink($post_id),
            'robots' => get_post_meta($post_id, '_blp_robots', true) ?: 'index,follow',
        ];

        return new WP_REST_Response($meta, 200);
    }

    /**
     * Update meta for a post/page
     */
    public function update_meta($request)
    {
        $post_id = (int) $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }

        $body = $request->get_json_params();

        // Update meta fields
        if (isset($body['title'])) {
            update_post_meta($post_id, '_blp_title', sanitize_text_field($body['title']));
        }
        if (isset($body['description'])) {
            update_post_meta($post_id, '_blp_description', sanitize_textarea_field($body['description']));
        }
        if (isset($body['og_title'])) {
            update_post_meta($post_id, '_blp_og_title', sanitize_text_field($body['og_title']));
        }
        if (isset($body['og_description'])) {
            update_post_meta($post_id, '_blp_og_description', sanitize_textarea_field($body['og_description']));
        }
        if (isset($body['og_image'])) {
            update_post_meta($post_id, '_blp_og_image', esc_url_raw($body['og_image']));
        }
        if (isset($body['canonical'])) {
            update_post_meta($post_id, '_blp_canonical', esc_url_raw($body['canonical']));
        }
        if (isset($body['robots'])) {
            update_post_meta($post_id, '_blp_robots', sanitize_text_field($body['robots']));
        }

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Meta updated successfully',
        ], 200);
    }

    /**
     * Check if current user can edit the post
     */
    public function check_edit_permission($request)
    {
        $post_id = (int) $request->get_param('id');
        return current_user_can('edit_post', $post_id);
    }

    /**
     * Output meta tags in head
     */
    public function output_meta_tags()
    {
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!$post) {
            return;
        }

        $title = get_post_meta($post->ID, '_blp_title', true);
        $description = get_post_meta($post->ID, '_blp_description', true);
        $og_title = get_post_meta($post->ID, '_blp_og_title', true);
        $og_description = get_post_meta($post->ID, '_blp_og_description', true);
        $og_image = get_post_meta($post->ID, '_blp_og_image', true);
        $canonical = get_post_meta($post->ID, '_blp_canonical', true);
        $robots = get_post_meta($post->ID, '_blp_robots', true);

        // Document title (override wp_title if set)
        if ($title) {
            echo '<title>' . esc_html($title) . '</title>' . "\n";
        }

        // Meta description
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        }

        // Canonical URL
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        }

        // Robots
        if ($robots) {
            echo '<meta name="robots" content="' . esc_attr($robots) . '" />' . "\n";
        }

        // Open Graph tags
        if ($og_title || $og_description || $og_image) {
            if ($og_title) {
                echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
            }
            if ($og_description) {
                echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
            }
            if ($og_image) {
                echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
            }
            echo '<meta property="og:type" content="article" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '" />' . "\n";
        }
    }
}

// Initialize plugin
BacklinkPro_Meta_Bridge::get_instance();


