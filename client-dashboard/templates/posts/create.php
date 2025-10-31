<?php
/**
 * Create Post Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$categories = get_categories(array('hide_empty' => false));
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
                <h2><?php _e('Create New Post', 'client-dashboard'); ?></h2>
                <a href="?view=posts" class="cd-btn cd-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Posts', 'client-dashboard'); ?>
                </a>
            </div>

            <form id="cd-create-post-form" class="cd-form">
                <div class="cd-form-group">
                    <label for="post_title"><?php _e('Title', 'client-dashboard'); ?> *</label>
                    <input type="text" id="post_title" name="post_title" class="cd-form-control" required>
                </div>

                <div class="cd-form-group">
                    <label for="post_content"><?php _e('Content', 'client-dashboard'); ?> *</label>
                    <?php
                    wp_editor('', 'post_content', array(
                        'textarea_name' => 'post_content',
                        'textarea_rows' => 15,
                        'teeny' => false,
                        'media_buttons' => true,
                        'tinymce' => array(
                            'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,blockquote,alignleft,aligncenter,alignright,undo,redo',
                        ),
                    ));
                    ?>
                </div>

                <div class="cd-form-group">
                    <label for="post_excerpt"><?php _e('Excerpt', 'client-dashboard'); ?></label>
                    <textarea id="post_excerpt" name="post_excerpt" class="cd-form-control" rows="3"></textarea>
                </div>

                <div class="cd-form-row">
                    <div class="cd-form-group">
                        <label for="post_category"><?php _e('Category', 'client-dashboard'); ?></label>
                        <select id="post_category" name="post_category[]" class="cd-form-control" multiple>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo $category->term_id; ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cd-form-group">
                        <label for="tags_input"><?php _e('Tags', 'client-dashboard'); ?></label>
                        <input type="text" id="tags_input" name="tags_input" class="cd-form-control" placeholder="<?php _e('Separate tags with commas', 'client-dashboard'); ?>">
                    </div>
                </div>

                <div class="cd-form-group">
                    <label for="post_status"><?php _e('Status', 'client-dashboard'); ?></label>
                    <select id="post_status" name="post_status" class="cd-form-control">
                        <option value="draft"><?php _e('Draft', 'client-dashboard'); ?></option>
                        <option value="publish"><?php _e('Publish', 'client-dashboard'); ?></option>
                        <option value="pending"><?php _e('Pending Review', 'client-dashboard'); ?></option>
                    </select>
                </div>

                <div class="cd-form-group">
                    <label><?php _e('Featured Image', 'client-dashboard'); ?></label>
                    <div id="cd-featured-image-preview"></div>
                    <input type="hidden" id="featured_image_id" name="featured_image_id" value="">
                    <button type="button" id="cd-upload-featured-image" class="cd-btn cd-btn-secondary">
                        <?php _e('Set Featured Image', 'client-dashboard'); ?>
                    </button>
                    <button type="button" id="cd-remove-featured-image" class="cd-btn cd-btn-danger" style="display:none;">
                        <?php _e('Remove Featured Image', 'client-dashboard'); ?>
                    </button>
                </div>

                <div class="cd-form-actions">
                    <button type="submit" class="cd-btn cd-btn-primary">
                        <?php _e('Create Post', 'client-dashboard'); ?>
                    </button>
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

    // Featured image upload
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

    // Form submission
    $('#cd-create-post-form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            action: 'cd_create_post',
            nonce: clientDashboard.nonce,
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

                    setTimeout(function() {
                        window.location.href = '?view=posts';
                    }, 1500);
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
