<?php
/**
 * Edit Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;

if (!$page_id || !current_user_can('edit_page', $page_id)) {
    echo '<p>' . __('Invalid page or you do not have permission to edit this page.', 'client-dashboard') . '</p>';
    return;
}

$page = get_post($page_id);
$is_elementor = get_post_meta($page_id, '_elementor_edit_mode', true) === 'builder';

if ($is_elementor) {
    // Redirect to Elementor editor
    $elementor_url = add_query_arg(array(
        'post' => $page_id,
        'action' => 'elementor',
    ), admin_url('post.php'));

    wp_redirect($elementor_url);
    exit;
}
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
                <h2><?php _e('Edit Page', 'client-dashboard'); ?></h2>
                <a href="?view=pages" class="cd-btn cd-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Pages', 'client-dashboard'); ?>
                </a>
            </div>

            <form id="cd-edit-page-form" class="cd-form">
                <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">

                <div class="cd-form-group">
                    <label for="post_title"><?php _e('Title', 'client-dashboard'); ?> *</label>
                    <input type="text" id="post_title" name="post_title" class="cd-form-control" value="<?php echo esc_attr($page->post_title); ?>" required>
                </div>

                <div class="cd-form-group">
                    <label for="post_content"><?php _e('Content', 'client-dashboard'); ?> *</label>
                    <?php
                    wp_editor($page->post_content, 'post_content', array(
                        'textarea_name' => 'post_content',
                        'textarea_rows' => 15,
                        'teeny' => false,
                        'media_buttons' => true,
                    ));
                    ?>
                </div>

                <div class="cd-form-actions">
                    <button type="submit" class="cd-btn cd-btn-primary">
                        <?php _e('Update Page', 'client-dashboard'); ?>
                    </button>
                    <a href="<?php echo get_permalink($page_id); ?>" target="_blank" class="cd-btn cd-btn-secondary">
                        <?php _e('Preview', 'client-dashboard'); ?>
                    </a>
                    <a href="?view=pages" class="cd-btn cd-btn-secondary">
                        <?php _e('Cancel', 'client-dashboard'); ?>
                    </a>
                </div>

                <div id="cd-form-message" class="cd-message" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#cd-edit-page-form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            action: 'cd_update_page',
            nonce: clientDashboard.nonce,
            page_id: $('input[name="page_id"]').val(),
            post_title: $('#post_title').val(),
            post_content: tinyMCE.get('post_content').getContent()
        };

        $.ajax({
            url: clientDashboard.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#cd-form-message')
                        .removeClass('cd-error')
                        .addClass('cd-success')
                        .html(response.data.message)
                        .show();
                } else {
                    $('#cd-form-message')
                        .removeClass('cd-success')
                        .addClass('cd-error')
                        .html(response.data.message)
                        .show();
                }
            }
        });
    });
});
</script>
