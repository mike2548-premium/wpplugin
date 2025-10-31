<?php
/**
 * Security Class
 * Handles security, sanitization, and validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Security {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'prevent_plugin_deactivation'));
        add_action('init', array($this, 'prevent_theme_changes'));
    }

    /**
     * Verify nonce for AJAX requests
     */
    public static function verify_ajax_nonce() {
        if (!check_ajax_referer('client_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'client-dashboard')));
            wp_die();
        }
    }

    /**
     * Verify user capabilities
     */
    public static function verify_capability($capability) {
        if (!current_user_can($capability)) {
            return false;
        }
        return true;
    }

    /**
     * Sanitize post data
     */
    public static function sanitize_post_data($data) {
        $sanitized = array();

        if (isset($data['post_title'])) {
            $sanitized['post_title'] = sanitize_text_field($data['post_title']);
        }

        if (isset($data['post_content'])) {
            $sanitized['post_content'] = wp_kses_post($data['post_content']);
        }

        if (isset($data['post_excerpt'])) {
            $sanitized['post_excerpt'] = sanitize_textarea_field($data['post_excerpt']);
        }

        if (isset($data['post_status'])) {
            $allowed_statuses = array('draft', 'publish', 'pending');
            $sanitized['post_status'] = in_array($data['post_status'], $allowed_statuses) ? $data['post_status'] : 'draft';
        }

        if (isset($data['post_category'])) {
            $sanitized['post_category'] = array_map('intval', (array) $data['post_category']);
        }

        if (isset($data['tags_input'])) {
            $sanitized['tags_input'] = sanitize_text_field($data['tags_input']);
        }

        return $sanitized;
    }

    /**
     * Sanitize meta data
     */
    public static function sanitize_meta_data($meta_key, $meta_value) {
        // Prevent system meta keys from being modified
        $protected_keys = array(
            '_wp_page_template',
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            '_wp_old_date',
        );

        if (in_array($meta_key, $protected_keys)) {
            return false;
        }

        // Sanitize based on type
        if (is_array($meta_value)) {
            return array_map('sanitize_text_field', $meta_value);
        }

        return sanitize_text_field($meta_value);
    }

    /**
     * Prevent client users from deactivating plugins
     */
    public function prevent_plugin_deactivation() {
        if (current_user_can('client_user') && !current_user_can('activate_plugins')) {
            remove_action('admin_init', 'wp_plugin_update_rows');
        }
    }

    /**
     * Prevent client users from changing themes
     */
    public function prevent_theme_changes() {
        if (current_user_can('client_user') && !current_user_can('switch_themes')) {
            remove_action('admin_init', 'wp_theme_update_rows');
        }
    }

    /**
     * Check if user owns the post
     */
    public static function user_owns_post($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $current_user = wp_get_current_user();
        return ($post->post_author == $current_user->ID) || current_user_can('edit_others_posts');
    }

    /**
     * Log activity (for future implementation)
     */
    public static function log_activity($action, $post_id = 0, $details = '') {
        // This can be expanded to log activities to database
        $log_entry = array(
            'user_id' => get_current_user_id(),
            'action' => $action,
            'post_id' => $post_id,
            'details' => $details,
            'timestamp' => current_time('mysql'),
        );

        // Store in option or custom table
        $logs = get_option('client_dashboard_logs', array());
        $logs[] = $log_entry;

        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('client_dashboard_logs', $logs);
    }

    /**
     * Validate file upload
     */
    public static function validate_file_upload($file) {
        // Check file size (max 10MB)
        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('File size exceeds maximum allowed size.', 'client-dashboard'));
        }

        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf');
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', __('File type not allowed.', 'client-dashboard'));
        }

        return true;
    }

    /**
     * Escape output safely
     */
    public static function escape_output($value, $type = 'text') {
        switch ($type) {
            case 'html':
                return wp_kses_post($value);
            case 'url':
                return esc_url($value);
            case 'attr':
                return esc_attr($value);
            case 'text':
            default:
                return esc_html($value);
        }
    }
}
