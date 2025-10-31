<?php
/**
 * Role Manager Class
 * Handles custom roles and capabilities for client users
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Role_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // No hooks needed in constructor for now
    }

    /**
     * Create custom client role with limited capabilities
     */
    public static function create_client_role() {
        // Remove role if it exists (for updates)
        remove_role('client_user');

        // Add custom role with specific capabilities
        add_role('client_user', __('Client User', 'client-dashboard'), array(
            // Reading capabilities
            'read' => true,

            // Post capabilities (create and edit own posts only)
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,

            // Page capabilities (edit existing pages only, no create/delete)
            'edit_pages' => true,
            'edit_published_pages' => true,

            // Upload files
            'upload_files' => true,

            // Custom capabilities
            'access_client_dashboard' => true,
            'view_elementor_forms' => true,
            'edit_with_elementor' => true,
        ));

        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('access_client_dashboard');
            $admin_role->add_cap('view_elementor_forms');
            $admin_role->add_cap('manage_client_dashboard');
        }
    }

    /**
     * Remove custom role (cleanup on uninstall)
     */
    public static function remove_client_role() {
        remove_role('client_user');
    }

    /**
     * Check if current user can access client dashboard
     */
    public static function can_access_dashboard() {
        return current_user_can('access_client_dashboard');
    }

    /**
     * Check if current user can view Elementor forms
     */
    public static function can_view_forms() {
        return current_user_can('view_elementor_forms');
    }

    /**
     * Check if current user can edit posts
     */
    public static function can_edit_posts() {
        return current_user_can('edit_posts');
    }

    /**
     * Check if current user can edit pages
     */
    public static function can_edit_pages() {
        return current_user_can('edit_pages');
    }

    /**
     * Check if current user can upload files
     */
    public static function can_upload_files() {
        return current_user_can('upload_files');
    }

    /**
     * Restrict access to WordPress admin for client users
     */
    public static function restrict_admin_access() {
        if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax()) {
            // Allow access to profile page and media library
            global $pagenow;
            $allowed_pages = array('profile.php', 'upload.php', 'media-new.php', 'async-upload.php');

            if (!in_array($pagenow, $allowed_pages)) {
                wp_redirect(home_url('/client-dashboard/'));
                exit;
            }
        }
    }
}

// Restrict admin access for client users
add_action('admin_init', array('CD_Role_Manager', 'restrict_admin_access'));

// Hide admin bar for client users
add_action('after_setup_theme', function() {
    if (current_user_can('client_user') && !current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});
