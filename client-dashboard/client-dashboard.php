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
        // Only load on client dashboard pages
        if (is_page('client-dashboard') || (isset($_GET['client_dashboard']) && $_GET['client_dashboard'] == '1')) {
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
        ?>
        <div class="wrap">
            <h1><?php _e('Client Dashboard', 'client-dashboard'); ?></h1>
            <div class="card">
                <h2><?php _e('Welcome to Client Dashboard', 'client-dashboard'); ?></h2>
                <p><?php _e('This plugin provides a frontend dashboard for your clients to manage their content safely.', 'client-dashboard'); ?></p>

                <h3><?php _e('Features', 'client-dashboard'); ?></h3>
                <ul>
                    <li><?php _e('Create and manage posts from the frontend', 'client-dashboard'); ?></li>
                    <li><?php _e('Edit pages with Elementor integration', 'client-dashboard'); ?></li>
                    <li><?php _e('View Elementor form submissions', 'client-dashboard'); ?></li>
                    <li><?php _e('Media library management', 'client-dashboard'); ?></li>
                    <li><?php _e('Secure with custom capabilities', 'client-dashboard'); ?></li>
                </ul>

                <h3><?php _e('Getting Started', 'client-dashboard'); ?></h3>
                <ol>
                    <li><?php _e('Assign the "Client User" role to your client users', 'client-dashboard'); ?></li>
                    <li><?php _e('Share the Client Dashboard page URL with your clients', 'client-dashboard'); ?></li>
                    <li><?php printf(__('Dashboard URL: <strong>%s</strong>', 'client-dashboard'), home_url('/client-dashboard/')); ?></li>
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
