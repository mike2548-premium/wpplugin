<?php
/**
 * Plugin Name: Client Dashboard
 * Plugin URI: https://github.com/mike2548/client-dashboard
 * Description: A comprehensive frontend dashboard for clients to manage posts, pages, and Elementor content without accessing the WordPress admin.
 * Version: 1.0.0
 * Author: Your Agency Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: client-dashboard
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLIENT_DASHBOARD_VERSION', '1.0.0');
define('CLIENT_DASHBOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLIENT_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLIENT_DASHBOARD_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Client Dashboard Class
 */
class Client_Dashboard {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-role-manager.php';
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-security.php';
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-post-manager.php';
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-page-manager.php';
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-elementor-forms.php';
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-media-manager.php';
        require_once CLIENT_DASHBOARD_PLUGIN_DIR . 'includes/class-dashboard-manager.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add settings link on plugin page
        add_filter('plugin_action_links_' . CLIENT_DASHBOARD_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom role
        CD_Role_Manager::create_client_role();

        // Create dashboard page
        $this->create_dashboard_page();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('client_dashboard_activated', true);
        update_option('client_dashboard_version', CLIENT_DASHBOARD_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('client-dashboard', false, dirname(CLIENT_DASHBOARD_PLUGIN_BASENAME) . '/languages');

        // Initialize managers
        CD_Role_Manager::get_instance();
        CD_Security::get_instance();
        CD_Post_Manager::get_instance();
        CD_Page_Manager::get_instance();
        CD_Elementor_Forms::get_instance();
        CD_Media_Manager::get_instance();
        CD_Dashboard_Manager::get_instance();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        global $post;

        // Check if current page has the shortcode
        $has_shortcode = false;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'client_dashboard')) {
            $has_shortcode = true;
        }

        // Also check for specific page slug or query parameter
        if ($has_shortcode || is_page('client-dashboard') || (isset($_GET['client_dashboard']) && $_GET['client_dashboard'] == '1')) {
            wp_enqueue_style('client-dashboard-frontend', CLIENT_DASHBOARD_PLUGIN_URL . 'assets/css/frontend.css', array(), CLIENT_DASHBOARD_VERSION);
            wp_enqueue_script('client-dashboard-frontend', CLIENT_DASHBOARD_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CLIENT_DASHBOARD_VERSION, true);

            // Localize script
            wp_localize_script('client-dashboard-frontend', 'clientDashboard', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('client_dashboard_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'client-dashboard'),
                    'error' => __('An error occurred. Please try again.', 'client-dashboard'),
                    'success' => __('Action completed successfully.', 'client-dashboard'),
                )
            ));
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'client-dashboard') !== false) {
            wp_enqueue_style('client-dashboard-admin', CLIENT_DASHBOARD_PLUGIN_URL . 'assets/css/admin.css', array(), CLIENT_DASHBOARD_VERSION);
            wp_enqueue_script('client-dashboard-admin', CLIENT_DASHBOARD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CLIENT_DASHBOARD_VERSION, true);
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Client Dashboard', 'client-dashboard'),
            __('Client Dashboard', 'client-dashboard'),
            'manage_options',
            'client-dashboard',
            array($this, 'admin_page'),
            'dashicons-admin-users',
            30
        );

        add_submenu_page(
            'client-dashboard',
            __('Settings', 'client-dashboard'),
            __('Settings', 'client-dashboard'),
            'manage_options',
            'client-dashboard-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Admin page callback
     */
    public function admin_page() {
        // Handle page creation
        if (isset($_POST['create_dashboard_page']) && check_admin_referer('cd_create_page')) {
            $this->create_dashboard_page();
            flush_rewrite_rules();
            echo '<div class="notice notice-success"><p>' . __('Dashboard page created successfully!', 'client-dashboard') . '</p></div>';
        }

        // Handle permalink flush
        if (isset($_POST['flush_permalinks']) && check_admin_referer('cd_flush_permalinks')) {
            flush_rewrite_rules();
            echo '<div class="notice notice-success"><p>' . __('Permalinks flushed successfully!', 'client-dashboard') . '</p></div>';
        }

        // Check if dashboard page exists
        $page_id = get_option('client_dashboard_page_id');
        $page_exists = false;
        $page_url = '';

        if ($page_id) {
            $page = get_post($page_id);
            if ($page && $page->post_status === 'publish') {
                $page_exists = true;
                $page_url = get_permalink($page_id);
            }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Client Dashboard', 'client-dashboard'); ?></h1>

            <!-- Shortcode Instructions -->
            <div class="card" style="background: #e7f3ff; border-left: 4px solid #2271b1; padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0;"><?php _e('📋 Shortcode Usage', 'client-dashboard'); ?></h2>
                <p style="font-size: 16px;"><?php _e('To display the client dashboard on any page, add this shortcode:', 'client-dashboard'); ?></p>
                <div style="background: #fff; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 18px; margin: 15px 0;">
                    <strong>[client_dashboard]</strong>
                </div>
                <p><?php _e('Copy and paste this shortcode into any page or post where you want the dashboard to appear.', 'client-dashboard'); ?></p>
            </div>

            <!-- Dashboard Page Status -->
            <div class="card">
                <h2><?php _e('Dashboard Page Status', 'client-dashboard'); ?></h2>
                <?php if ($page_exists) : ?>
                    <p style="color: #46b450; font-size: 16px;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 20px;"></span>
                        <?php _e('Dashboard page exists and is active!', 'client-dashboard'); ?>
                    </p>
                    <p>
                        <strong><?php _e('Page URL:', 'client-dashboard'); ?></strong>
                        <a href="<?php echo esc_url($page_url); ?>" target="_blank" style="font-size: 16px;">
                            <?php echo esc_html($page_url); ?>
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo admin_url('post.php?post=' . $page_id . '&action=edit'); ?>" class="button button-secondary">
                            <?php _e('Edit Dashboard Page', 'client-dashboard'); ?>
                        </a>
                        <a href="<?php echo esc_url($page_url); ?>" class="button button-secondary" target="_blank">
                            <?php _e('View Dashboard Page', 'client-dashboard'); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <p style="color: #dc3232; font-size: 16px;">
                        <span class="dashicons dashicons-warning" style="font-size: 20px;"></span>
                        <?php _e('Dashboard page not found!', 'client-dashboard'); ?>
                    </p>
                    <p><?php _e('Click the button below to create a dashboard page automatically, or create a page manually and add the shortcode.', 'client-dashboard'); ?></p>
                    <form method="post" style="margin: 15px 0;">
                        <?php wp_nonce_field('cd_create_page'); ?>
                        <button type="submit" name="create_dashboard_page" class="button button-primary button-large">
                            <?php _e('Create Dashboard Page', 'client-dashboard'); ?>
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Flush Permalinks -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h3><?php _e('Having Issues?', 'client-dashboard'); ?></h3>
                    <p><?php _e('If you\'re getting a "Page not found" error, try flushing the permalinks:', 'client-dashboard'); ?></p>
                    <form method="post">
                        <?php wp_nonce_field('cd_flush_permalinks'); ?>
                        <button type="submit" name="flush_permalinks" class="button button-secondary">
                            <?php _e('Flush Permalinks', 'client-dashboard'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Features -->
            <div class="card">
                <h2><?php _e('Features', 'client-dashboard'); ?></h2>
                <ul style="list-style: disc; padding-left: 25px; font-size: 15px;">
                    <li><?php _e('Create and manage posts from the frontend', 'client-dashboard'); ?></li>
                    <li><?php _e('Edit pages with Elementor integration', 'client-dashboard'); ?></li>
                    <li><?php _e('View Elementor form submissions', 'client-dashboard'); ?></li>
                    <li><?php _e('Media library management', 'client-dashboard'); ?></li>
                    <li><?php _e('Secure with custom capabilities', 'client-dashboard'); ?></li>
                </ul>
            </div>

            <!-- Getting Started -->
            <div class="card">
                <h2><?php _e('Getting Started', 'client-dashboard'); ?></h2>
                <ol style="font-size: 15px; line-height: 1.8;">
                    <li>
                        <strong><?php _e('Create a page:', 'client-dashboard'); ?></strong>
                        <?php _e('Create a new page or use the button above to auto-create one', 'client-dashboard'); ?>
                    </li>
                    <li>
                        <strong><?php _e('Add the shortcode:', 'client-dashboard'); ?></strong>
                        <?php _e('Add [client_dashboard] shortcode to the page content', 'client-dashboard'); ?>
                    </li>
                    <li>
                        <strong><?php _e('Create client users:', 'client-dashboard'); ?></strong>
                        <?php _e('Go to Users → Add New and assign the "Client User" role', 'client-dashboard'); ?>
                    </li>
                    <li>
                        <strong><?php _e('Share the URL:', 'client-dashboard'); ?></strong>
                        <?php _e('Share the dashboard page URL with your clients', 'client-dashboard'); ?>
                    </li>
                </ol>
            </div>

            <!-- Manual Setup Instructions -->
            <div class="card" style="background: #f9f9f9;">
                <h2><?php _e('Manual Setup Instructions', 'client-dashboard'); ?></h2>
                <p><?php _e('If the automatic page creation doesn\'t work, follow these steps:', 'client-dashboard'); ?></p>
                <ol style="font-size: 15px; line-height: 1.8;">
                    <li><?php _e('Go to Pages → Add New', 'client-dashboard'); ?></li>
                    <li><?php _e('Title: "Client Dashboard" (or any name you prefer)', 'client-dashboard'); ?></li>
                    <li><?php _e('Add the shortcode: <code>[client_dashboard]</code>', 'client-dashboard'); ?></li>
                    <li><?php _e('Publish the page', 'client-dashboard'); ?></li>
                    <li><?php _e('Go to Settings → Permalinks and click "Save Changes"', 'client-dashboard'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page callback
     */
    public function settings_page() {
        if (isset($_POST['client_dashboard_settings_submit'])) {
            check_admin_referer('client_dashboard_settings');

            // Save settings
            update_option('cd_enable_posts', isset($_POST['cd_enable_posts']) ? 1 : 0);
            update_option('cd_enable_pages', isset($_POST['cd_enable_pages']) ? 1 : 0);
            update_option('cd_enable_media', isset($_POST['cd_enable_media']) ? 1 : 0);
            update_option('cd_enable_forms', isset($_POST['cd_enable_forms']) ? 1 : 0);
            update_option('cd_allowed_post_types', isset($_POST['cd_allowed_post_types']) ? sanitize_text_field($_POST['cd_allowed_post_types']) : 'post');

            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'client-dashboard') . '</p></div>';
        }

        $enable_posts = get_option('cd_enable_posts', 1);
        $enable_pages = get_option('cd_enable_pages', 1);
        $enable_media = get_option('cd_enable_media', 1);
        $enable_forms = get_option('cd_enable_forms', 1);
        $allowed_post_types = get_option('cd_allowed_post_types', 'post');

        ?>
        <div class="wrap">
            <h1><?php _e('Client Dashboard Settings', 'client-dashboard'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('client_dashboard_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Features', 'client-dashboard'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="cd_enable_posts" value="1" <?php checked($enable_posts, 1); ?>>
                                    <?php _e('Posts Management', 'client-dashboard'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="cd_enable_pages" value="1" <?php checked($enable_pages, 1); ?>>
                                    <?php _e('Pages Management', 'client-dashboard'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="cd_enable_media" value="1" <?php checked($enable_media, 1); ?>>
                                    <?php _e('Media Library', 'client-dashboard'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="cd_enable_forms" value="1" <?php checked($enable_forms, 1); ?>>
                                    <?php _e('Elementor Forms', 'client-dashboard'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cd_allowed_post_types"><?php _e('Allowed Post Types', 'client-dashboard'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="cd_allowed_post_types" id="cd_allowed_post_types" value="<?php echo esc_attr($allowed_post_types); ?>" class="regular-text">
                            <p class="description"><?php _e('Comma-separated list of post types (e.g., post,news,portfolio)', 'client-dashboard'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'client-dashboard'), 'primary', 'client_dashboard_settings_submit'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add settings link on plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=client-dashboard-settings') . '">' . __('Settings', 'client-dashboard') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Create dashboard page
     */
    private function create_dashboard_page() {
        // Check if page already exists
        $page = get_page_by_path('client-dashboard');

        if (!$page) {
            $page_id = wp_insert_post(array(
                'post_title' => __('Client Dashboard', 'client-dashboard'),
                'post_content' => '[client_dashboard]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'client-dashboard',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ));

            if ($page_id) {
                update_option('client_dashboard_page_id', $page_id);
            }
        } else {
            update_option('client_dashboard_page_id', $page->ID);
        }
    }
}

// Initialize the plugin
function client_dashboard_init() {
    return Client_Dashboard::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'client_dashboard_init', 10);
