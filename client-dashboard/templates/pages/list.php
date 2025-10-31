<?php
/**
 * Pages List Template
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
                <h2><?php _e('Pages', 'client-dashboard'); ?></h2>
            </div>

            <div class="cd-filters">
                <div class="cd-search-box">
                    <input type="text" id="cd-search-pages" placeholder="<?php _e('Search pages...', 'client-dashboard'); ?>">
                    <button type="button" id="cd-search-btn" class="cd-btn cd-btn-secondary">
                        <?php _e('Search', 'client-dashboard'); ?>
                    </button>
                </div>
            </div>

            <div id="cd-pages-container" class="cd-pages-list">
                <div class="cd-loading"><?php _e('Loading pages...', 'client-dashboard'); ?></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    function loadPages() {
        const search = $('#cd-search-pages').val();

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_get_pages',
                nonce: clientDashboard.nonce,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderPages(response.data.pages);
                } else {
                    $('#cd-pages-container').html('<p>' + response.data.message + '</p>');
                }
            }
        });
    }

    function renderPages(pages) {
        let html = '';

        if (pages.length === 0) {
            html = '<p><?php _e('No pages found.', 'client-dashboard'); ?></p>';
        } else {
            html = '<table class="cd-table"><thead><tr>';
            html += '<th><?php _e('Title', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Status', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Last Modified', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Elementor', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Actions', 'client-dashboard'); ?></th>';
            html += '</tr></thead><tbody>';

            pages.forEach(function(page) {
                html += '<tr>';
                html += '<td><strong>' + page.title + '</strong></td>';
                html += '<td><span class="cd-status cd-status-' + page.status + '">' + page.status + '</span></td>';
                html += '<td>' + page.modified + '</td>';
                html += '<td>' + (page.is_elementor ? '<span class="dashicons dashicons-yes"></span>' : '-') + '</td>';
                html += '<td class="cd-actions">';

                if (page.is_elementor) {
                    html += '<a href="' + page.edit_url + '" class="cd-btn-link"><?php _e('Edit with Elementor', 'client-dashboard'); ?></a> ';
                } else {
                    html += '<a href="' + page.edit_url + '" class="cd-btn-link"><?php _e('Edit', 'client-dashboard'); ?></a> ';
                }

                html += '<a href="' + page.view_url + '" target="_blank" class="cd-btn-link"><?php _e('View', 'client-dashboard'); ?></a>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
        }

        $('#cd-pages-container').html(html);
    }

    $('#cd-search-btn').on('click', function() {
        loadPages();
    });

    $('#cd-search-pages').on('keypress', function(e) {
        if (e.which === 13) {
            loadPages();
        }
    });

    loadPages();
});
</script>
