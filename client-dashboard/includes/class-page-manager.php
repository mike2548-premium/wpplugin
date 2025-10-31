<?php
/**
 * Page Manager Class
 * Handles page editing from frontend (no creation/deletion for safety)
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Page_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_cd_get_pages', array($this, 'ajax_get_pages'));
        add_action('wp_ajax_cd_get_page', array($this, 'ajax_get_page'));
        add_action('wp_ajax_cd_update_page', array($this, 'ajax_update_page'));
    }

    /**
     * AJAX: Get pages list
     */
    public function ajax_get_pages() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_edit_pages()) {
            wp_send_json_error(array('message' => __('You do not have permission to view pages.', 'client-dashboard')));
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $query = new WP_Query($args);
        $pages = array();

        foreach ($query->posts as $page) {
            // Check if page is built with Elementor
            $is_elementor = get_post_meta($page->ID, '_elementor_edit_mode', true) === 'builder';

            $pages[] = array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'status' => $page->post_status,
                'date' => get_the_date('', $page->ID),
                'modified' => get_the_modified_date('', $page->ID),
                'is_elementor' => $is_elementor,
                'edit_url' => $is_elementor ? $this->get_elementor_edit_url($page->ID) : '?view=edit-page&page_id=' . $page->ID,
                'view_url' => get_permalink($page->ID),
                'thumbnail' => get_the_post_thumbnail_url($page->ID, 'thumbnail'),
            );
        }

        wp_send_json_success(array(
            'pages' => $pages,
            'total' => $query->found_posts,
        ));
    }

    /**
     * AJAX: Get single page
     */
    public function ajax_get_page() {
        CD_Security::verify_ajax_nonce();

        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;

        if (!$page_id) {
            wp_send_json_error(array('message' => __('Invalid page ID.', 'client-dashboard')));
        }

        if (!current_user_can('edit_page', $page_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this page.', 'client-dashboard')));
        }

        $page = get_post($page_id);

        if (!$page || $page->post_type !== 'page') {
            wp_send_json_error(array('message' => __('Page not found.', 'client-dashboard')));
        }

        $is_elementor = get_post_meta($page_id, '_elementor_edit_mode', true) === 'builder';

        wp_send_json_success(array(
            'page' => array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'content' => $page->post_content,
                'status' => $page->post_status,
                'is_elementor' => $is_elementor,
                'elementor_edit_url' => $is_elementor ? $this->get_elementor_edit_url($page_id) : '',
                'featured_image_id' => get_post_thumbnail_id($page_id),
                'featured_image_url' => get_the_post_thumbnail_url($page_id, 'medium'),
            )
        ));
    }

    /**
     * AJAX: Update page
     */
    public function ajax_update_page() {
        CD_Security::verify_ajax_nonce();

        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;

        if (!$page_id) {
            wp_send_json_error(array('message' => __('Invalid page ID.', 'client-dashboard')));
        }

        if (!current_user_can('edit_page', $page_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this page.', 'client-dashboard')));
        }

        $page_data = array(
            'ID' => $page_id,
        );

        if (isset($_POST['post_title'])) {
            $page_data['post_title'] = sanitize_text_field($_POST['post_title']);
        }

        if (isset($_POST['post_content'])) {
            $page_data['post_content'] = wp_kses_post($_POST['post_content']);
        }

        $result = wp_update_post($page_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Handle featured image
        if (isset($_POST['featured_image_id'])) {
            if (!empty($_POST['featured_image_id'])) {
                set_post_thumbnail($page_id, intval($_POST['featured_image_id']));
            } else {
                delete_post_thumbnail($page_id);
            }
        }

        // Log activity
        CD_Security::log_activity('update_page', $page_id, 'Updated page: ' . $page_data['post_title']);

        wp_send_json_success(array(
            'message' => __('Page updated successfully.', 'client-dashboard'),
            'page_id' => $page_id,
            'view_url' => get_permalink($page_id),
        ));
    }

    /**
     * Get Elementor edit URL
     */
    private function get_elementor_edit_url($page_id) {
        if (!defined('ELEMENTOR_VERSION')) {
            return '';
        }

        return add_query_arg(array(
            'post' => $page_id,
            'action' => 'elementor',
        ), admin_url('post.php'));
    }

    /**
     * Check if Elementor is active
     */
    public static function is_elementor_active() {
        return defined('ELEMENTOR_VERSION');
    }

    /**
     * Get all pages
     */
    public static function get_all_pages() {
        return get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'post_title',
            'sort_order' => 'ASC',
        ));
    }
}
