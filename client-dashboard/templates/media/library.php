<?php
/**
 * Media Library Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
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
                <h2><?php _e('Media Library', 'client-dashboard'); ?></h2>
                <div class="cd-upload-area">
                    <form id="cd-upload-form" enctype="multipart/form-data">
                        <input type="file" id="cd-file-input" name="file" accept="image/*,application/pdf" style="display:none;">
                        <button type="button" id="cd-upload-btn" class="cd-btn cd-btn-primary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Upload File', 'client-dashboard'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="cd-filters">
                <div class="cd-search-box">
                    <input type="text" id="cd-search-media" placeholder="<?php _e('Search media...', 'client-dashboard'); ?>">
                    <button type="button" id="cd-search-btn" class="cd-btn cd-btn-secondary">
                        <?php _e('Search', 'client-dashboard'); ?>
                    </button>
                </div>
                <div class="cd-filter-type">
                    <select id="cd-filter-type">
                        <option value=""><?php _e('All Types', 'client-dashboard'); ?></option>
                        <option value="image"><?php _e('Images', 'client-dashboard'); ?></option>
                        <option value="application"><?php _e('Documents', 'client-dashboard'); ?></option>
                    </select>
                </div>
            </div>

            <div id="cd-media-container" class="cd-media-grid">
                <div class="cd-loading"><?php _e('Loading media...', 'client-dashboard'); ?></div>
            </div>

            <div id="cd-pagination" class="cd-pagination"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentPage = 1;

    function loadMedia() {
        const search = $('#cd-search-media').val();
        const mimeType = $('#cd-filter-type').val();

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_get_media',
                nonce: clientDashboard.nonce,
                page: currentPage,
                per_page: 20,
                search: search,
                mime_type: mimeType
            },
            success: function(response) {
                if (response.success) {
                    renderMedia(response.data.media);
                    renderPagination(response.data.pages);
                } else {
                    $('#cd-media-container').html('<p>' + response.data.message + '</p>');
                }
            }
        });
    }

    function renderMedia(media) {
        let html = '';

        if (media.length === 0) {
            html = '<p><?php _e('No media files found.', 'client-dashboard'); ?></p>';
        } else {
            media.forEach(function(item) {
                html += '<div class="cd-media-item">';

                if (item.type.startsWith('image/')) {
                    html += '<div class="cd-media-thumbnail"><img src="' + item.thumbnail + '" alt="' + item.title + '"></div>';
                } else {
                    html += '<div class="cd-media-thumbnail cd-media-file"><span class="dashicons dashicons-media-document"></span></div>';
                }

                html += '<div class="cd-media-info">';
                html += '<div class="cd-media-title">' + item.title + '</div>';
                html += '<div class="cd-media-meta">' + item.size + ' • ' + item.date + '</div>';
                html += '</div>';

                html += '<div class="cd-media-actions">';
                html += '<a href="' + item.url + '" target="_blank" class="cd-btn-link"><?php _e('View', 'client-dashboard'); ?></a> ';
                html += '<a href="#" class="cd-btn-link cd-delete-media" data-id="' + item.id + '"><?php _e('Delete', 'client-dashboard'); ?></a>';
                html += '</div>';

                html += '</div>';
            });
        }

        $('#cd-media-container').html(html);
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

    // Upload button
    $('#cd-upload-btn').on('click', function() {
        $('#cd-file-input').click();
    });

    $('#cd-file-input').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'cd_upload_media');
        formData.append('nonce', clientDashboard.nonce);
        formData.append('file', file);

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#cd-file-input').val('');
                    currentPage = 1;
                    loadMedia();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Event handlers
    $('#cd-search-btn').on('click', function() {
        currentPage = 1;
        loadMedia();
    });

    $('#cd-filter-type').on('change', function() {
        currentPage = 1;
        loadMedia();
    });

    $(document).on('click', '.cd-page-btn', function() {
        currentPage = $(this).data('page');
        loadMedia();
    });

    $(document).on('click', '.cd-delete-media', function(e) {
        e.preventDefault();

        if (!confirm(clientDashboard.strings.confirm_delete)) {
            return;
        }

        const attachmentId = $(this).data('id');

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_delete_media',
                nonce: clientDashboard.nonce,
                attachment_id: attachmentId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadMedia();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Initial load
    loadMedia();
});
</script>
