<?php
/**
 * Elementor Forms Class
 * Handles viewing Elementor form submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class CD_Elementor_Forms {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_cd_get_form_submissions', array($this, 'ajax_get_form_submissions'));
        add_action('wp_ajax_cd_get_submission_details', array($this, 'ajax_get_submission_details'));
        add_action('wp_ajax_cd_delete_submission', array($this, 'ajax_delete_submission'));
        add_action('wp_ajax_cd_export_submissions', array($this, 'ajax_export_submissions'));
    }

    /**
     * Check if Elementor Pro is active
     */
    public static function is_elementor_pro_active() {
        return defined('ELEMENTOR_PRO_VERSION');
    }

    /**
     * AJAX: Get form submissions
     */
    public function ajax_get_form_submissions() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_view_forms()) {
            wp_send_json_error(array('message' => __('You do not have permission to view form submissions.', 'client-dashboard')));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'e_submissions';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error(array('message' => __('Form submissions table not found. Please ensure Elementor Pro is installed and activated.', 'client-dashboard')));
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $form_name = isset($_POST['form_name']) ? sanitize_text_field($_POST['form_name']) : '';

        $offset = ($page - 1) * $per_page;

        // Build query
        $where = "WHERE 1=1";
        if (!empty($form_name)) {
            $where .= $wpdb->prepare(" AND form_name LIKE %s", '%' . $wpdb->esc_like($form_name) . '%');
        }

        $total_query = "SELECT COUNT(*) FROM $table_name $where";
        $total = $wpdb->get_var($total_query);

        $query = "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $submissions = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

        $formatted_submissions = array();
        foreach ($submissions as $submission) {
            $values = json_decode($submission->values, true);

            // Extract common fields
            $email = '';
            $name = '';
            foreach ($values as $key => $value) {
                if (stripos($key, 'email') !== false) {
                    $email = $value;
                }
                if (stripos($key, 'name') !== false && empty($name)) {
                    $name = $value;
                }
            }

            $formatted_submissions[] = array(
                'id' => $submission->id,
                'form_name' => $submission->form_name,
                'name' => $name,
                'email' => $email,
                'created_at' => $submission->created_at,
                'status' => isset($submission->status) ? $submission->status : 'unread',
            );
        }

        wp_send_json_success(array(
            'submissions' => $formatted_submissions,
            'total' => $total,
            'pages' => ceil($total / $per_page),
        ));
    }

    /**
     * AJAX: Get submission details
     */
    public function ajax_get_submission_details() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_view_forms()) {
            wp_send_json_error(array('message' => __('You do not have permission to view form submissions.', 'client-dashboard')));
        }

        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

        if (!$submission_id) {
            wp_send_json_error(array('message' => __('Invalid submission ID.', 'client-dashboard')));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'e_submissions';

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $submission_id
        ));

        if (!$submission) {
            wp_send_json_error(array('message' => __('Submission not found.', 'client-dashboard')));
        }

        $values = json_decode($submission->values, true);
        $meta = isset($submission->meta) ? json_decode($submission->meta, true) : array();

        wp_send_json_success(array(
            'submission' => array(
                'id' => $submission->id,
                'form_name' => $submission->form_name,
                'values' => $values,
                'meta' => $meta,
                'created_at' => $submission->created_at,
                'status' => isset($submission->status) ? $submission->status : 'unread',
            )
        ));
    }

    /**
     * AJAX: Delete submission
     */
    public function ajax_delete_submission() {
        CD_Security::verify_ajax_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete submissions.', 'client-dashboard')));
        }

        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

        if (!$submission_id) {
            wp_send_json_error(array('message' => __('Invalid submission ID.', 'client-dashboard')));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'e_submissions';

        $result = $wpdb->delete($table_name, array('id' => $submission_id), array('%d'));

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete submission.', 'client-dashboard')));
        }

        // Log activity
        CD_Security::log_activity('delete_submission', $submission_id, 'Deleted form submission');

        wp_send_json_success(array('message' => __('Submission deleted successfully.', 'client-dashboard')));
    }

    /**
     * AJAX: Export submissions to CSV
     */
    public function ajax_export_submissions() {
        CD_Security::verify_ajax_nonce();

        if (!CD_Role_Manager::can_view_forms()) {
            wp_send_json_error(array('message' => __('You do not have permission to export submissions.', 'client-dashboard')));
        }

        $form_name = isset($_POST['form_name']) ? sanitize_text_field($_POST['form_name']) : '';

        global $wpdb;
        $table_name = $wpdb->prefix . 'e_submissions';

        $where = "WHERE 1=1";
        if (!empty($form_name)) {
            $where .= $wpdb->prepare(" AND form_name = %s", $form_name);
        }

        $query = "SELECT * FROM $table_name $where ORDER BY created_at DESC";
        $submissions = $wpdb->get_results($query);

        if (empty($submissions)) {
            wp_send_json_error(array('message' => __('No submissions found.', 'client-dashboard')));
        }

        // Prepare CSV data
        $csv_data = array();
        $headers = array('ID', 'Form Name', 'Date');

        // Get all unique field names
        $all_fields = array();
        foreach ($submissions as $submission) {
            $values = json_decode($submission->values, true);
            if (is_array($values)) {
                $all_fields = array_merge($all_fields, array_keys($values));
            }
        }
        $all_fields = array_unique($all_fields);
        $headers = array_merge($headers, $all_fields);

        $csv_data[] = $headers;

        // Add rows
        foreach ($submissions as $submission) {
            $values = json_decode($submission->values, true);
            $row = array(
                $submission->id,
                $submission->form_name,
                $submission->created_at,
            );

            foreach ($all_fields as $field) {
                $row[] = isset($values[$field]) ? $values[$field] : '';
            }

            $csv_data[] = $row;
        }

        // Create CSV file
        $upload_dir = wp_upload_dir();
        $filename = 'form-submissions-' . date('Y-m-d-His') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        $fp = fopen($filepath, 'w');
        foreach ($csv_data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        wp_send_json_success(array(
            'message' => __('Submissions exported successfully.', 'client-dashboard'),
            'download_url' => $upload_dir['url'] . '/' . $filename,
        ));
    }

    /**
     * Get form names
     */
    public static function get_form_names() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'e_submissions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }

        $forms = $wpdb->get_col("SELECT DISTINCT form_name FROM $table_name ORDER BY form_name ASC");
        return $forms;
    }
}
