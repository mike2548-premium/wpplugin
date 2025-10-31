<?php
/**
 * Form Submissions Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$form_names = CD_Elementor_Forms::get_form_names();
?>

<div class="client-dashboard-wrapper">
    <div class="cd-header">
        <div class="cd-header-content">
            <h1 class="cd-title"><?php _e('Client Dashboard', 'client-dashboard'); ?></h1>
            <div class="cd-user-info">
                <span class="cd-user-avatar"><?php echo get_avatar($current_user->ID, 32); ?></span>
                <span class="cd-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="cd-logout"><?php _e('Logout', 'client-dashboard'); ?></a>
            </div>
        </div>
    </div>

    <div class="cd-container">
        <div class="cd-sidebar">
            <?php CD_Dashboard_Manager::render_navigation(); ?>
        </div>

        <div class="cd-main-content">
            <div class="cd-content-header">
                <h2><?php _e('Form Submissions', 'client-dashboard'); ?></h2>
                <button type="button" id="cd-export-submissions" class="cd-btn cd-btn-primary">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export to CSV', 'client-dashboard'); ?>
                </button>
            </div>

            <?php if (!CD_Elementor_Forms::is_elementor_pro_active()) : ?>
                <div class="cd-notice cd-notice-warning">
                    <p><?php _e('Elementor Pro is required to view form submissions.', 'client-dashboard'); ?></p>
                </div>
            <?php else : ?>
                <div class="cd-filters">
                    <div class="cd-filter-form">
                        <label for="cd-filter-form-name"><?php _e('Filter by Form:', 'client-dashboard'); ?></label>
                        <select id="cd-filter-form-name" class="cd-form-control">
                            <option value=""><?php _e('All Forms', 'client-dashboard'); ?></option>
                            <?php foreach ($form_names as $form_name) : ?>
                                <option value="<?php echo esc_attr($form_name); ?>"><?php echo esc_html($form_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="cd-submissions-container" class="cd-submissions-list">
                    <div class="cd-loading"><?php _e('Loading submissions...', 'client-dashboard'); ?></div>
                </div>

                <div id="cd-pagination" class="cd-pagination"></div>

                <!-- Submission Detail Modal -->
                <div id="cd-submission-modal" class="cd-modal" style="display:none;">
                    <div class="cd-modal-content">
                        <div class="cd-modal-header">
                            <h3><?php _e('Submission Details', 'client-dashboard'); ?></h3>
                            <button type="button" class="cd-modal-close">&times;</button>
                        </div>
                        <div class="cd-modal-body" id="cd-submission-details">
                            <div class="cd-loading"><?php _e('Loading...', 'client-dashboard'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentPage = 1;

    function loadSubmissions() {
        const formName = $('#cd-filter-form-name').val();

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_get_form_submissions',
                nonce: clientDashboard.nonce,
                page: currentPage,
                per_page: 20,
                form_name: formName
            },
            success: function(response) {
                if (response.success) {
                    renderSubmissions(response.data.submissions);
                    renderPagination(response.data.pages);
                } else {
                    $('#cd-submissions-container').html('<p>' + response.data.message + '</p>');
                }
            }
        });
    }

    function renderSubmissions(submissions) {
        let html = '';

        if (submissions.length === 0) {
            html = '<p><?php _e('No submissions found.', 'client-dashboard'); ?></p>';
        } else {
            html = '<table class="cd-table"><thead><tr>';
            html += '<th><?php _e('Form', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Name', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Email', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Date', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Actions', 'client-dashboard'); ?></th>';
            html += '</tr></thead><tbody>';

            submissions.forEach(function(submission) {
                html += '<tr>';
                html += '<td>' + submission.form_name + '</td>';
                html += '<td>' + (submission.name || '-') + '</td>';
                html += '<td>' + (submission.email || '-') + '</td>';
                html += '<td>' + submission.created_at + '</td>';
                html += '<td class="cd-actions">';
                html += '<a href="#" class="cd-btn-link cd-view-submission" data-id="' + submission.id + '"><?php _e('View Details', 'client-dashboard'); ?></a>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
        }

        $('#cd-submissions-container').html(html);
    }

    function renderPagination(totalPages) {
        if (totalPages <= 1) {
            $('#cd-pagination').html('');
            return;
        }

        let html = '<div class="cd-pagination-buttons">';

        if (currentPage > 1) {
            html += '<button class="cd-btn cd-btn-secondary cd-page-btn" data-page="' + (currentPage - 1) + '"><?php _e('Previous', 'client-dashboard'); ?></button>';
        }

        html += '<span class="cd-page-info"><?php _e('Page', 'client-dashboard'); ?> ' + currentPage + ' <?php _e('of', 'client-dashboard'); ?> ' + totalPages + '</span>';

        if (currentPage < totalPages) {
            html += '<button class="cd-btn cd-btn-secondary cd-page-btn" data-page="' + (currentPage + 1) + '"><?php _e('Next', 'client-dashboard'); ?></button>';
        }

        html += '</div>';
        $('#cd-pagination').html(html);
    }

    // Event handlers
    $('#cd-filter-form-name').on('change', function() {
        currentPage = 1;
        loadSubmissions();
    });

    $(document).on('click', '.cd-page-btn', function() {
        currentPage = $(this).data('page');
        loadSubmissions();
    });

    $(document).on('click', '.cd-view-submission', function(e) {
        e.preventDefault();
        const submissionId = $(this).data('id');

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_get_submission_details',
                nonce: clientDashboard.nonce,
                submission_id: submissionId
            },
            success: function(response) {
                if (response.success) {
                    let html = '<table class="cd-details-table">';
                    html += '<tr><th><?php _e('Form Name', 'client-dashboard'); ?></th><td>' + response.data.submission.form_name + '</td></tr>';
                    html += '<tr><th><?php _e('Submitted On', 'client-dashboard'); ?></th><td>' + response.data.submission.created_at + '</td></tr>';

                    $.each(response.data.submission.values, function(key, value) {
                        html += '<tr><th>' + key + '</th><td>' + value + '</td></tr>';
                    });

                    html += '</table>';
                    $('#cd-submission-details').html(html);
                    $('#cd-submission-modal').fadeIn();
                }
            }
        });
    });

    $('.cd-modal-close').on('click', function() {
        $('#cd-submission-modal').fadeOut();
    });

    $('#cd-export-submissions').on('click', function() {
        const formName = $('#cd-filter-form-name').val();

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_export_submissions',
                nonce: clientDashboard.nonce,
                form_name: formName
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.download_url;
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Initial load
    loadSubmissions();
});
</script>
