<?php
/**
 * Post Manager Class
 * Handles post creation, editing, and deletion from frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Post_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_cd_create_post', array($this, 'ajax_create_post'));
        add_action('wp_ajax_cd_update_post', array($this, 'ajax_update_post'));
        add_action('wp_ajax_cd_delete_post', array($this, 'ajax_delete_post'));
        add_action('wp_ajax_cd_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_cd_get_post', array($this, 'ajax_get_post'));
    }

    /**
     * AJAX: Create new post
     */
    public function ajax_create_post() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_edit_posts()) {
            wp_send_json_error(array('message' => __('You do not have permission to create posts.', 'client-dashboard')));
        }

        $post_data = CD_Security::sanitize_post_data($_POST);

        // Set post author to current user
        $post_data['post_author'] = get_current_user_id();
        $post_data['post_type'] = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';

        // Validate post type
        $allowed_post_types = explode(',', get_option('cd_allowed_post_types', 'post'));
        if (!in_array($post_data['post_type'], $allowed_post_types)) {
            wp_send_json_error(array('message' => __('Invalid post type.', 'client-dashboard')));
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }

        // Handle featured image
        if (isset($_POST['featured_image_id']) && !empty($_POST['featured_image_id'])) {
            set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
        }

        // Log activity
        CD_Security::log_activity('create_post', $post_id, 'Created post: ' . $post_data['post_title']);

        wp_send_json_success(array(
            'message' => __('Post created successfully.', 'client-dashboard'),
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
        ));
    }

    /**
     * AJAX: Update existing post
     */
    public function ajax_update_post() {
        CD_Security::verify_ajax_nonce();

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'client-dashboard')));
        }

        // Check ownership
        if (!CD_Security::user_owns_post($post_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this post.', 'client-dashboard')));
        }

        $post_data = CD_Security::sanitize_post_data($_POST);
        $post_data['ID'] = $post_id;

        $result = wp_update_post($post_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Handle featured image
        if (isset($_POST['featured_image_id'])) {
            if (!empty($_POST['featured_image_id'])) {
                set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
            } else {
                delete_post_thumbnail($post_id);
            }
        }

        // Log activity
        CD_Security::log_activity('update_post', $post_id, 'Updated post: ' . $post_data['post_title']);

        wp_send_json_success(array(
            'message' => __('Post updated successfully.', 'client-dashboard'),
            'post_id' => $post_id,
            'view_url' => get_permalink($post_id),
        ));
    }

    /**
     * AJAX: Delete post
     */
    public function ajax_delete_post() {
        CD_Security::verify_ajax_nonce();

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'client-dashboard')));
        }

        // Check ownership
        if (!CD_Security::user_owns_post($post_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this post.', 'client-dashboard')));
        }

        $post = get_post($post_id);
        $result = wp_trash_post($post_id);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete post.', 'client-dashboard')));
        }

        // Log activity
        CD_Security::log_activity('delete_post', $post_id, 'Deleted post: ' . $post->post_title);

        wp_send_json_success(array('message' => __('Post deleted successfully.', 'client-dashboard')));
    }

    /**
     * AJAX: Get posts list
     */
    public function ajax_get_posts() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_edit_posts()) {
            wp_send_json_error(array('message' => __('You do not have permission to view posts.', 'client-dashboard')));
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'any';

        $args = array(
            'post_type' => 'post',
            'post_status' => $status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        // Limit to user's own posts for client users
        if (!current_user_can('edit_others_posts')) {
            $args['author'] = get_current_user_id();
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $query = new WP_Query($args);
        $posts = array();

        foreach ($query->posts as $post) {
            $posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_content, 20),
                'status' => $post->post_status,
                'date' => get_the_date('', $post->ID),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'edit_url' => '?view=edit-post&post_id=' . $post->ID,
                'view_url' => get_permalink($post->ID),
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
            );
        }

        wp_send_json_success(array(
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ));
    }

    /**
     * AJAX: Get single post
     */
    public function ajax_get_post() {
        CD_Security::verify_ajax_nonce();

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'client-dashboard')));
        }

        // Check ownership
        if (!CD_Security::user_owns_post($post_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to view this post.', 'client-dashboard')));
        }

        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found.', 'client-dashboard')));
        }

        $categories = wp_get_post_categories($post_id);
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));

        wp_send_json_success(array(
            'post' => array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'categories' => $categories,
                'tags' => implode(', ', $tags),
                'featured_image_id' => get_post_thumbnail_id($post_id),
                'featured_image_url' => get_the_post_thumbnail_url($post_id, 'medium'),
            )
        ));
    }

    /**
     * Get user's posts
     */
    public static function get_user_posts($args = array()) {
        $defaults = array(
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'author' => get_current_user_id(),
        );

        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }
}
