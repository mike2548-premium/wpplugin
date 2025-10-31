<?php
/**
 * Edit Post Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$categories = get_categories(array('hide_empty' => false));

if (!$post_id || !CD_Security::user_owns_post($post_id)) {
    echo '<p>' . __('Invalid post or you do not have permission to edit this post.', 'client-dashboard') . '</p>';
    return;
}

$post = get_post($post_id);
$post_categories = wp_get_post_categories($post_id);
$post_tags = wp_get_post_tags($post_id, array('fields' => 'names'));
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
                <h2><?php _e('Edit Post', 'client-dashboard'); ?></h2>
                <a href="?view=posts" class="cd-btn cd-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Posts', 'client-dashboard'); ?>
                </a>
            </div>

            <form id="cd-edit-post-form" class="cd-form">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                <div class="cd-form-group">
                    <label for="post_title"><?php _e('Title', 'client-dashboard'); ?> *</label>
                    <input type="text" id="post_title" name="post_title" class="cd-form-control" value="<?php echo esc_attr($post->post_title); ?>" required>
                </div>

                <div class="cd-form-group">
                    <label for="post_content"><?php _e('Content', 'client-dashboard'); ?> *</label>
                    <?php
                    wp_editor($post->post_content, 'post_content', array(
                        'textarea_name' => 'post_content',
                        'textarea_rows' => 15,
                        'teeny' => false,
                        'media_buttons' => true,
                    ));
                    ?>
                </div>

                <div class="cd-form-group">
                    <label for="post_excerpt"><?php _e('Excerpt', 'client-dashboard'); ?></label>
                    <textarea id="post_excerpt" name="post_excerpt" class="cd-form-control" rows="3"><?php echo esc_textarea($post->post_excerpt); ?></textarea>
                </div>

                <div class="cd-form-row">
                    <div class="cd-form-group">
                        <label for="post_category"><?php _e('Category', 'client-dashboard'); ?></label>
                        <select id="post_category" name="post_category[]" class="cd-form-control" multiple>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo $category->term_id; ?>" <?php echo in_array($category->term_id, $post_categories) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cd-form-group">
                        <label for="tags_input"><?php _e('Tags', 'client-dashboard'); ?></label>
                        <input type="text" id="tags_input" name="tags_input" class="cd-form-control" value="<?php echo esc_attr(implode(', ', $post_tags)); ?>" placeholder="<?php _e('Separate tags with commas', 'client-dashboard'); ?>">
                    </div>
                </div>

                <div class="cd-form-group">
                    <label for="post_status"><?php _e('Status', 'client-dashboard'); ?></label>
                    <select id="post_status" name="post_status" class="cd-form-control">
                        <option value="draft" <?php selected($post->post_status, 'draft'); ?>><?php _e('Draft', 'client-dashboard'); ?></option>
                        <option value="publish" <?php selected($post->post_status, 'publish'); ?>><?php _e('Publish', 'client-dashboard'); ?></option>
                        <option value="pending" <?php selected($post->post_status, 'pending'); ?>><?php _e('Pending Review', 'client-dashboard'); ?></option>
                    </select>
                </div>

                <div class="cd-form-group">
                    <label><?php _e('Featured Image', 'client-dashboard'); ?></label>
                    <div id="cd-featured-image-preview">
                        <?php
                        $thumbnail_id = get_post_thumbnail_id($post_id);
                        if ($thumbnail_id) {
                            echo '<img src="' . wp_get_attachment_image_url($thumbnail_id, 'medium') . '" style="max-width: 300px;">';
                        }
                        ?>
                    </div>
                    <input type="hidden" id="featured_image_id" name="featured_image_id" value="<?php echo $thumbnail_id; ?>">
                    <button type="button" id="cd-upload-featured-image" class="cd-btn cd-btn-secondary">
                        <?php _e('Set Featured Image', 'client-dashboard'); ?>
                    </button>
                    <button type="button" id="cd-remove-featured-image" class="cd-btn cd-btn-danger" <?php echo $thumbnail_id ? '' : 'style="display:none;"'; ?>>
                        <?php _e('Remove Featured Image', 'client-dashboard'); ?>
                    </button>
                </div>

                <div class="cd-form-actions">
                    <button type="submit" class="cd-btn cd-btn-primary">
                        <?php _e('Update Post', 'client-dashboard'); ?>
                    </button>
                    <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="cd-btn cd-btn-secondary">
                        <?php _e('Preview', 'client-dashboard'); ?>
                    </a>
                    <a href="?view=posts" class="cd-btn cd-btn-secondary">
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
    let mediaUploader;

    $('#cd-upload-featured-image').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: '<?php _e('Select Featured Image', 'client-dashboard'); ?>',
            button: {
                text: '<?php _e('Use this image', 'client-dashboard'); ?>'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#featured_image_id').val(attachment.id);
            $('#cd-featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px;">');
            $('#cd-remove-featured-image').show();
        });

        mediaUploader.open();
    });

    $('#cd-remove-featured-image').on('click', function(e) {
        e.preventDefault();
        $('#featured_image_id').val('');
        $('#cd-featured-image-preview').html('');
        $(this).hide();
    });

    $('#cd-edit-post-form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            action: 'cd_update_post',
            nonce: clientDashboard.nonce,
            post_id: $('input[name="post_id"]').val(),
            post_title: $('#post_title').val(),
            post_content: tinyMCE.get('post_content').getContent(),
            post_excerpt: $('#post_excerpt').val(),
            post_category: $('#post_category').val(),
            tags_input: $('#tags_input').val(),
            post_status: $('#post_status').val(),
            featured_image_id: $('#featured_image_id').val()
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
