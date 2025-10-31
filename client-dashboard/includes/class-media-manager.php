<?php
/**
 * Media Manager Class
 * Handles media library access and uploads from frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Media_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_cd_get_media', array($this, 'ajax_get_media'));
        add_action('wp_ajax_cd_upload_media', array($this, 'ajax_upload_media'));
        add_action('wp_ajax_cd_delete_media', array($this, 'ajax_delete_media'));
    }

    /**
     * AJAX: Get media library items
     */
    public function ajax_get_media() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_upload_files()) {
            wp_send_json_error(array('message' => __('You do not have permission to access media.', 'client-dashboard')));
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $mime_type = isset($_POST['mime_type']) ? sanitize_text_field($_POST['mime_type']) : '';

        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        // Limit to user's own uploads for client users
        if (!current_user_can('edit_others_posts')) {
            $args['author'] = get_current_user_id();
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($mime_type)) {
            $args['post_mime_type'] = $mime_type;
        }

        $query = new WP_Query($args);
        $media_items = array();

        foreach ($query->posts as $attachment) {
            $media_items[] = array(
                'id' => $attachment->ID,
                'title' => $attachment->post_title,
                'filename' => basename(get_attached_file($attachment->ID)),
                'url' => wp_get_attachment_url($attachment->ID),
                'thumbnail' => wp_get_attachment_image_url($attachment->ID, 'thumbnail'),
                'type' => $attachment->post_mime_type,
                'date' => get_the_date('', $attachment->ID),
                'size' => size_format(filesize(get_attached_file($attachment->ID))),
            );
        }

        wp_send_json_success(array(
            'media' => $media_items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ));
    }

    /**
     * AJAX: Upload media file
     */
    public function ajax_upload_media() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_upload_files()) {
            wp_send_json_error(array('message' => __('You do not have permission to upload files.', 'client-dashboard')));
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'client-dashboard')));
        }

        // Validate file
        $validation = CD_Security::validate_file_upload($_FILES['file']);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()));
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        // Log activity
        CD_Security::log_activity('upload_media', $attachment_id, 'Uploaded file: ' . basename(get_attached_file($attachment_id)));

        wp_send_json_success(array(
            'message' => __('File uploaded successfully.', 'client-dashboard'),
            'attachment' => array(
                'id' => $attachment_id,
                'url' => wp_get_attachment_url($attachment_id),
                'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                'title' => get_the_title($attachment_id),
            )
        ));
    }

    /**
     * AJAX: Delete media file
     */
    public function ajax_delete_media() {
        CD_Security::verify_ajax_nonce();

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

        if (!$attachment_id) {
            wp_send_json_error(array('message' => __('Invalid attachment ID.', 'client-dashboard')));
        }

        // Check ownership
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            wp_send_json_error(array('message' => __('Attachment not found.', 'client-dashboard')));
        }

        $current_user = wp_get_current_user();
        if ($attachment->post_author != $current_user->ID && !current_user_can('delete_others_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this file.', 'client-dashboard')));
        }

        $result = wp_delete_attachment($attachment_id, true);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete file.', 'client-dashboard')));
        }

        // Log activity
        CD_Security::log_activity('delete_media', $attachment_id, 'Deleted file');

        wp_send_json_success(array('message' => __('File deleted successfully.', 'client-dashboard')));
    }

    /**
     * Get user's media items
     */
    public static function get_user_media($args = array()) {
        $defaults = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'author' => get_current_user_id(),
        );

        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }
}
