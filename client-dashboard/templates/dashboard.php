<?php
/**
 * Main Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$statistics = CD_Dashboard_Manager::get_statistics();
?>

<div class="client-dashboard-wrapper">
    <div class="cd-header">
        <div class="cd-header-content">
            <h1 class="cd-title"><?php _e('Client Dashboard', 'client-dashboard'); ?></h1>
            <div class="cd-user-info">
                <span class="cd-user-avatar">
                    <?php echo get_avatar($current_user->ID, 32); ?>
                </span>
                <span class="cd-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="cd-logout">
                    <?php _e('Logout', 'client-dashboard'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="cd-container">
        <div class="cd-sidebar">
            <?php CD_Dashboard_Manager::render_navigation(); ?>
        </div>

        <div class="cd-main-content">
            <div class="cd-welcome">
                <h2><?php printf(__('Welcome, %s!', 'client-dashboard'), $current_user->display_name); ?></h2>
                <p><?php _e('Manage your website content from this dashboard. You can create posts, edit pages, view form submissions, and manage your media library.', 'client-dashboard'); ?></p>
            </div>

            <div class="cd-stats-grid">
                <?php foreach ($statistics as $key => $stat) : ?>
                    <div class="cd-stat-card">
                        <div class="cd-stat-icon">
                            <span class="dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                        </div>
                        <div class="cd-stat-content">
                            <div class="cd-stat-number"><?php echo esc_html($stat['total']); ?></div>
                            <div class="cd-stat-label"><?php echo esc_html($stat['label']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cd-quick-actions">
                <h3><?php _e('Quick Actions', 'client-dashboard'); ?></h3>
                <div class="cd-action-buttons">
                    <?php if (get_option('cd_enable_posts', 1) && CD_Role_Manager::can_edit_posts()) : ?>
                        <a href="?view=create-post" class="cd-btn cd-btn-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Create New Post', 'client-dashboard'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (get_option('cd_enable_pages', 1) && CD_Role_Manager::can_edit_pages()) : ?>
                        <a href="?view=pages" class="cd-btn cd-btn-secondary">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php _e('Edit Pages', 'client-dashboard'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (get_option('cd_enable_media', 1) && CD_Role_Manager::can_upload_files()) : ?>
                        <a href="?view=media" class="cd-btn cd-btn-secondary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Upload Media', 'client-dashboard'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cd-recent-activity">
                <h3><?php _e('Recent Activity', 'client-dashboard'); ?></h3>
                <?php
                $recent_posts = CD_Post_Manager::get_user_posts(array('posts_per_page' => 5));
                if (!empty($recent_posts)) :
                ?>
                    <div class="cd-activity-list">
                        <?php foreach ($recent_posts as $post) : ?>
                            <div class="cd-activity-item">
                                <div class="cd-activity-icon">
                                    <span class="dashicons dashicons-admin-post"></span>
                                </div>
                                <div class="cd-activity-content">
                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                    <span class="cd-activity-meta">
                                        <?php echo esc_html(get_post_status_object($post->post_status)->label); ?>
                                        • <?php echo human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' ' . __('ago', 'client-dashboard'); ?>
                                    </span>
                                </div>
                                <div class="cd-activity-actions">
                                    <a href="?view=edit-post&post_id=<?php echo $post->ID; ?>" class="cd-btn-link">
                                        <?php _e('Edit', 'client-dashboard'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php _e('No recent activity.', 'client-dashboard'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
