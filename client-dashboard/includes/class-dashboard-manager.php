<?php
/**
 * Dashboard Manager Class
 * Handles the main dashboard display and navigation
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Dashboard_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register shortcode
        add_shortcode('client_dashboard', array($this, 'render_dashboard'));

        // Redirect non-logged-in users
        add_action('template_redirect', array($this, 'check_dashboard_access'));
    }

    /**
     * Check dashboard access
     */
    public function check_dashboard_access() {
        if (is_page('client-dashboard') && !is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }

    /**
     * Render dashboard shortcode
     */
    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the dashboard.', 'client-dashboard') . '</p>';
        }

        if (!CD_Role_Manager::can_access_dashboard()) {
            return '<p>' . __('You do not have permission to access this dashboard.', 'client-dashboard') . '</p>';
        }

        ob_start();

        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'home';

        // Load template
        $this->load_template($view);

        return ob_get_clean();
    }

    /**
     * Load template file
     */
    private function load_template($view) {
        $template_path = CLIENT_DASHBOARD_PLUGIN_DIR . 'templates/';

        // Map views to template files
        $templates = array(
            'home' => 'dashboard.php',
            'posts' => 'posts/list.php',
            'create-post' => 'posts/create.php',
            'edit-post' => 'posts/edit.php',
            'pages' => 'pages/list.php',
            'edit-page' => 'pages/edit.php',
            'forms' => 'forms/submissions.php',
            'media' => 'media/library.php',
        );

        $template = isset($templates[$view]) ? $templates[$view] : 'dashboard.php';
        $template_file = $template_path . $template;

        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>' . __('Template not found.', 'client-dashboard') . '</p>';
        }
    }

    /**
     * Get navigation menu
     */
    public static function get_navigation() {
        $menu_items = array();

        $menu_items['home'] = array(
            'title' => __('Dashboard', 'client-dashboard'),
            'url' => add_query_arg('view', 'home', get_permalink()),
            'icon' => 'dashicons-dashboard',
        );

        if (get_option('cd_enable_posts', 1) && CD_Role_Manager::can_edit_posts()) {
            $menu_items['posts'] = array(
                'title' => __('Posts', 'client-dashboard'),
                'url' => add_query_arg('view', 'posts', get_permalink()),
                'icon' => 'dashicons-admin-post',
            );
        }

        if (get_option('cd_enable_pages', 1) && CD_Role_Manager::can_edit_pages()) {
            $menu_items['pages'] = array(
                'title' => __('Pages', 'client-dashboard'),
                'url' => add_query_arg('view', 'pages', get_permalink()),
                'icon' => 'dashicons-admin-page',
            );
        }

        if (get_option('cd_enable_forms', 1) && CD_Role_Manager::can_view_forms()) {
            $menu_items['forms'] = array(
                'title' => __('Form Submissions', 'client-dashboard'),
                'url' => add_query_arg('view', 'forms', get_permalink()),
                'icon' => 'dashicons-feedback',
            );
        }

        if (get_option('cd_enable_media', 1) && CD_Role_Manager::can_upload_files()) {
            $menu_items['media'] = array(
                'title' => __('Media Library', 'client-dashboard'),
                'url' => add_query_arg('view', 'media', get_permalink()),
                'icon' => 'dashicons-format-gallery',
            );
        }

        return apply_filters('client_dashboard_menu_items', $menu_items);
    }

    /**
     * Get current view
     */
    public static function get_current_view() {
        return isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'home';
    }

    /**
     * Render navigation
     */
    public static function render_navigation() {
        $menu_items = self::get_navigation();
        $current_view = self::get_current_view();

        echo '<nav class="cd-navigation">';
        echo '<ul class="cd-nav-menu">';

        foreach ($menu_items as $key => $item) {
            $active_class = ($current_view === $key) ? ' active' : '';
            echo '<li class="cd-nav-item' . $active_class . '">';
            echo '<a href="' . esc_url($item['url']) . '" class="cd-nav-link">';
            echo '<span class="dashicons ' . esc_attr($item['icon']) . '"></span>';
            echo '<span class="cd-nav-text">' . esc_html($item['title']) . '</span>';
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</nav>';
    }

    /**
     * Get dashboard statistics
     */
    public static function get_statistics() {
        $stats = array();

        if (CD_Role_Manager::can_edit_posts()) {
            $posts = wp_count_posts('post');
            $user_posts = count(CD_Post_Manager::get_user_posts());

            $stats['posts'] = array(
                'total' => current_user_can('edit_others_posts') ? $posts->publish : $user_posts,
                'label' => __('Posts', 'client-dashboard'),
                'icon' => 'dashicons-admin-post',
            );
        }

        if (CD_Role_Manager::can_edit_pages()) {
            $pages = wp_count_posts('page');

            $stats['pages'] = array(
                'total' => $pages->publish,
                'label' => __('Pages', 'client-dashboard'),
                'icon' => 'dashicons-admin-page',
            );
        }

        if (CD_Role_Manager::can_upload_files()) {
            $media = count(CD_Media_Manager::get_user_media());

            $stats['media'] = array(
                'total' => $media,
                'label' => __('Media Files', 'client-dashboard'),
                'icon' => 'dashicons-format-gallery',
            );
        }

        if (CD_Role_Manager::can_view_forms() && CD_Elementor_Forms::is_elementor_pro_active()) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'e_submissions';

            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

                $stats['forms'] = array(
                    'total' => $submissions,
                    'label' => __('Form Submissions', 'client-dashboard'),
                    'icon' => 'dashicons-feedback',
                );
            }
        }

        return apply_filters('client_dashboard_statistics', $stats);
    }
}
