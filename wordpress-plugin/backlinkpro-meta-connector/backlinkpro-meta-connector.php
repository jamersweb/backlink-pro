<?php
/**
 * Plugin Name: BacklinkPro Meta Connector
 * Plugin URI: https://backlinkpro.com
 * Description: Connect your WordPress site to BacklinkPro for SEO meta tag management
 * Version: 1.0.0
 * Author: BacklinkPro
 * Author URI: https://backlinkpro.com
 * License: GPL v2 or later
 * Text Domain: backlinkpro
 */

if (!defined('ABSPATH')) {
    exit;
}

class BacklinkPro_Meta_Connector {
    
    private $namespace = 'backlinkpro/v1';
    private $option_name = 'backlinkpro_api_token';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/ping', [
            'methods' => 'GET',
            'callback' => [$this, 'ping'],
            'permission_callback' => [$this, 'check_token'],
        ]);
        
        register_rest_route($this->namespace, '/resources', [
            'methods' => 'GET',
            'callback' => [$this, 'list_resources'],
            'permission_callback' => [$this, 'check_token'],
        ]);
        
        register_rest_route($this->namespace, '/meta/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_meta'],
            'permission_callback' => [$this, 'check_token'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
        
        register_rest_route($this->namespace, '/meta/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_meta'],
            'permission_callback' => [$this, 'check_token'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }
    
    /**
     * Check Bearer token
     */
    public function check_token($request) {
        $token = $this->get_token_from_header($request);
        $saved_token = get_option($this->option_name);
        
        if (empty($saved_token)) {
            return new WP_Error('no_token', 'API token not configured', ['status' => 401]);
        }
        
        if ($token !== $saved_token) {
            return new WP_Error('invalid_token', 'Invalid API token', ['status' => 401]);
        }
        
        return true;
    }
    
    /**
     * Extract token from Authorization header
     */
    private function get_token_from_header($request) {
        $auth_header = $request->get_header('Authorization');
        
        if (empty($auth_header)) {
            return null;
        }
        
        // Support both "Bearer token" and "token" formats
        if (preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }
        
        return trim($auth_header);
    }
    
    /**
     * Ping endpoint
     */
    public function ping($request) {
        return new WP_REST_Response([
            'ok' => true,
            'message' => 'BacklinkPro connector is active',
            'site_url' => get_site_url(),
        ], 200);
    }
    
    /**
     * List resources (pages and posts)
     */
    public function list_resources($request) {
        $items = [];
        
        // Get pages
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        foreach ($pages as $page) {
            $items[] = [
                'id' => $page->ID,
                'type' => 'page',
                'url' => get_permalink($page->ID),
                'title' => $page->post_title,
            ];
        }
        
        // Get posts
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        foreach ($posts as $post) {
            $items[] = [
                'id' => $post->ID,
                'type' => 'post',
                'url' => get_permalink($post->ID),
                'title' => $post->post_title,
            ];
        }
        
        return new WP_REST_Response([
            'items' => $items,
            'total' => count($items),
        ], 200);
    }
    
    /**
     * Get meta for a post/page
     */
    public function get_meta($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }
        
        $meta = [
            'title' => get_post_meta($post_id, '_backlinkpro_title', true) ?: get_the_title($post_id),
            'description' => get_post_meta($post_id, '_backlinkpro_description', true) ?: '',
            'og_title' => get_post_meta($post_id, '_backlinkpro_og_title', true) ?: '',
            'og_description' => get_post_meta($post_id, '_backlinkpro_og_description', true) ?: '',
            'og_image' => get_post_meta($post_id, '_backlinkpro_og_image', true) ?: '',
            'canonical' => get_post_meta($post_id, '_backlinkpro_canonical', true) ?: '',
            'robots' => get_post_meta($post_id, '_backlinkpro_robots', true) ?: 'index,follow',
        ];
        
        // Try to get from Yoast if available
        if (class_exists('WPSEO_Meta')) {
            $yoast_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
            $yoast_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
            $yoast_canonical = get_post_meta($post_id, '_yoast_wpseo_canonical', true);
            
            if ($yoast_title) $meta['title'] = $yoast_title;
            if ($yoast_desc) $meta['description'] = $yoast_desc;
            if ($yoast_canonical) $meta['canonical'] = $yoast_canonical;
        }
        
        // Try to get from RankMath if available
        if (class_exists('RankMath')) {
            $rm_title = get_post_meta($post_id, 'rank_math_title', true);
            $rm_desc = get_post_meta($post_id, 'rank_math_description', true);
            $rm_canonical = get_post_meta($post_id, 'rank_math_canonical_url', true);
            
            if ($rm_title) $meta['title'] = $rm_title;
            if ($rm_desc) $meta['description'] = $rm_desc;
            if ($rm_canonical) $meta['canonical'] = $rm_canonical;
        }
        
        return new WP_REST_Response($meta, 200);
    }
    
    /**
     * Update meta for a post/page
     */
    public function update_meta($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }
        
        $body = $request->get_json_params();
        
        // Store in custom meta
        if (isset($body['title'])) {
            update_post_meta($post_id, '_backlinkpro_title', sanitize_text_field($body['title']));
        }
        if (isset($body['description'])) {
            update_post_meta($post_id, '_backlinkpro_description', sanitize_textarea_field($body['description']));
        }
        if (isset($body['og_title'])) {
            update_post_meta($post_id, '_backlinkpro_og_title', sanitize_text_field($body['og_title']));
        }
        if (isset($body['og_description'])) {
            update_post_meta($post_id, '_backlinkpro_og_description', sanitize_textarea_field($body['og_description']));
        }
        if (isset($body['og_image'])) {
            update_post_meta($post_id, '_backlinkpro_og_image', esc_url_raw($body['og_image']));
        }
        if (isset($body['canonical'])) {
            update_post_meta($post_id, '_backlinkpro_canonical', esc_url_raw($body['canonical']));
        }
        if (isset($body['robots'])) {
            update_post_meta($post_id, '_backlinkpro_robots', sanitize_text_field($body['robots']));
        }
        
        // Update Yoast if available
        if (class_exists('WPSEO_Meta')) {
            if (isset($body['title'])) {
                update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($body['title']));
            }
            if (isset($body['description'])) {
                update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($body['description']));
            }
            if (isset($body['canonical'])) {
                update_post_meta($post_id, '_yoast_wpseo_canonical', esc_url_raw($body['canonical']));
            }
        }
        
        // Update RankMath if available
        if (class_exists('RankMath')) {
            if (isset($body['title'])) {
                update_post_meta($post_id, 'rank_math_title', sanitize_text_field($body['title']));
            }
            if (isset($body['description'])) {
                update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($body['description']));
            }
            if (isset($body['canonical'])) {
                update_post_meta($post_id, 'rank_math_canonical_url', esc_url_raw($body['canonical']));
            }
        }
        
        return new WP_REST_Response([
            'ok' => true,
            'message' => 'Meta updated successfully',
        ], 200);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'BacklinkPro Settings',
            'BacklinkPro',
            'manage_options',
            'backlinkpro',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('backlinkpro_settings', $this->option_name);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $token = get_option($this->option_name, '');
        
        if (isset($_POST['generate_token']) && check_admin_referer('backlinkpro_generate_token')) {
            $new_token = bin2hex(random_bytes(32));
            update_option($this->option_name, $new_token);
            $token = $new_token;
            echo '<div class="notice notice-success"><p>API token generated successfully!</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>BacklinkPro Meta Connector Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('backlinkpro_generate_token'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_token">API Token</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="api_token" 
                                name="api_token" 
                                value="<?php echo esc_attr($token); ?>" 
                                class="regular-text" 
                                readonly
                            />
                            <p class="description">
                                Copy this token and paste it into BacklinkPro when connecting your WordPress site.
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="generate_token" class="button button-primary">
                        Generate New Token
                    </button>
                </p>
            </form>
            <h2>API Endpoints</h2>
            <p>The following REST API endpoints are available:</p>
            <ul>
                <li><code>GET <?php echo rest_url($this->namespace . '/ping'); ?></code> - Test connection</li>
                <li><code>GET <?php echo rest_url($this->namespace . '/resources'); ?></code> - List pages and posts</li>
                <li><code>GET <?php echo rest_url($this->namespace . '/meta/{id}'); ?></code> - Get meta for a post/page</li>
                <li><code>POST <?php echo rest_url($this->namespace . '/meta/{id}'); ?></code> - Update meta for a post/page</li>
            </ul>
            <p><strong>Authentication:</strong> Include the API token in the Authorization header as <code>Bearer {token}</code></p>
        </div>
        <?php
    }
}

// Initialize plugin
new BacklinkPro_Meta_Connector();


