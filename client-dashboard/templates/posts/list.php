<?php
/**
 * Posts List Template
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
                <h2><?php _e('Posts', 'client-dashboard'); ?></h2>
                <a href="?view=create-post" class="cd-btn cd-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create New Post', 'client-dashboard'); ?>
                </a>
            </div>

            <div class="cd-filters">
                <div class="cd-search-box">
                    <input type="text" id="cd-search-posts" placeholder="<?php _e('Search posts...', 'client-dashboard'); ?>">
                    <button type="button" id="cd-search-btn" class="cd-btn cd-btn-secondary">
                        <?php _e('Search', 'client-dashboard'); ?>
                    </button>
                </div>
                <div class="cd-filter-status">
                    <select id="cd-filter-status">
                        <option value="any"><?php _e('All Status', 'client-dashboard'); ?></option>
                        <option value="publish"><?php _e('Published', 'client-dashboard'); ?></option>
                        <option value="draft"><?php _e('Draft', 'client-dashboard'); ?></option>
                        <option value="pending"><?php _e('Pending', 'client-dashboard'); ?></option>
                    </select>
                </div>
            </div>

            <div id="cd-posts-container" class="cd-posts-list">
                <div class="cd-loading"><?php _e('Loading posts...', 'client-dashboard'); ?></div>
            </div>

            <div id="cd-pagination" class="cd-pagination"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentPage = 1;

    function loadPosts() {
        const search = $('#cd-search-posts').val();
        const status = $('#cd-filter-status').val();

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_get_posts',
                nonce: clientDashboard.nonce,
                page: currentPage,
                per_page: 10,
                search: search,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    renderPosts(response.data.posts);
                    renderPagination(response.data.pages);
                } else {
                    $('#cd-posts-container').html('<p>' + response.data.message + '</p>');
                }
            }
        });
    }

    function renderPosts(posts) {
        let html = '';

        if (posts.length === 0) {
            html = '<p><?php _e('No posts found.', 'client-dashboard'); ?></p>';
        } else {
            html = '<table class="cd-table"><thead><tr>';
            html += '<th><?php _e('Title', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Status', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Date', 'client-dashboard'); ?></th>';
            html += '<th><?php _e('Actions', 'client-dashboard'); ?></th>';
            html += '</tr></thead><tbody>';

            posts.forEach(function(post) {
                html += '<tr>';
                html += '<td><strong>' + post.title + '</strong></td>';
                html += '<td><span class="cd-status cd-status-' + post.status + '">' + post.status + '</span></td>';
                html += '<td>' + post.date + '</td>';
                html += '<td class="cd-actions">';
                html += '<a href="' + post.edit_url + '" class="cd-btn-link"><?php _e('Edit', 'client-dashboard'); ?></a> ';
                html += '<a href="' + post.view_url + '" target="_blank" class="cd-btn-link"><?php _e('View', 'client-dashboard'); ?></a> ';
                html += '<a href="#" class="cd-btn-link cd-delete-post" data-post-id="' + post.id + '"><?php _e('Delete', 'client-dashboard'); ?></a>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
        }

        $('#cd-posts-container').html(html);
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
    $('#cd-search-btn').on('click', function() {
        currentPage = 1;
        loadPosts();
    });

    $('#cd-filter-status').on('change', function() {
        currentPage = 1;
        loadPosts();
    });

    $(document).on('click', '.cd-page-btn', function() {
        currentPage = $(this).data('page');
        loadPosts();
    });

    $(document).on('click', '.cd-delete-post', function(e) {
        e.preventDefault();

        if (!confirm(clientDashboard.strings.confirm_delete)) {
            return;
        }

        const postId = $(this).data('post-id');

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'cd_delete_post',
                nonce: clientDashboard.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadPosts();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Initial load
    loadPosts();
});
</script>
