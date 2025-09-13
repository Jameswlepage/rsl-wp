<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo esc_url(RSL_PLUGIN_URL . 'admin/images/rsl-logo.png') ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php esc_html_e('RSL Licensing', 'rsl-wp'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-add-license')); ?>" class="page-title-action">
        <?php esc_html_e('Add New License', 'rsl-wp'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php
    // Display admin notices
    if (function_exists('settings_errors')) {
        settings_errors();
    }
    do_action('admin_notices');
    ?>
    
    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'rsl-wp'); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- WordPress Dashboard Layout -->
    <div id="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder">
            <!-- At A Glance Section -->
            <div class="postbox-container" style="width: 100%;">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php esc_html_e('At a Glance', 'rsl-wp'); ?></h2>
                        </div>
                        <div class="inside">
                            <!-- WordPress Dashboard-style Stats -->
                            <div class="main">
                                <ul>
                                    <li class="page-count">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-licenses')); ?>">
                                            <strong><?php echo esc_html($total_licenses); ?></strong>
                                        </a>
                                        <?php esc_html_e('Total Licenses', 'rsl-wp'); ?>
                                    </li>
                                    <li class="post-count">
                                        <strong><?php echo esc_html($active_licenses); ?></strong>
                                        <?php esc_html_e('Active Licenses', 'rsl-wp'); ?>
                                    </li>
                                    <li class="<?php echo $global_license_id > 0 ? 'page-count' : 'comment-count'; ?>">
                                        <span class="<?php echo $global_license_id > 0 ? 'approved' : 'pending'; ?>">
                                            <?php echo $global_license_id > 0 ? '✓' : '×'; ?>
                                        </span>
                                        <?php esc_html_e('Global License Configured', 'rsl-wp'); ?>
                                    </li>
                                </ul>
                                </div>
                            
                            <!-- Quick Actions -->
                            <div class="activity-block">
                                <h3><?php esc_html_e('Quick Actions', 'rsl-wp'); ?></h3>
                                <p class="sub">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-add-license')); ?>" 
                                       class="button button-primary">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php esc_html_e('Create License', 'rsl-wp'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-licenses')); ?>" 
                                       class="button button-secondary">
                                        <span class="dashicons dashicons-list-view"></span>
                                        <?php esc_html_e('Manage Licenses', 'rsl-wp'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-settings')); ?>" 
                                       class="button button-secondary">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <?php esc_html_e('Settings', 'rsl-wp'); ?>
                                    </a>
                                    <a href="https://rslstandard.org" target="_blank" class="button button-secondary">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php esc_html_e('RSL Standard', 'rsl-wp'); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Integration Status -->
            <div class="postbox-container" style="width: 49%; margin-right: 2%;">
                <div class="meta-box-sortables">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <?php esc_html_e('Integration Status', 'rsl-wp'); ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-settings')); ?>" class="rsl-header-link">
                                <?php esc_html_e('Go to Settings', 'rsl-wp'); ?>
                            </a>
                        </h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <td><?php esc_html_e('HTML Head Injection', 'rsl-wp'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_html_injection', 1) ? 'yes' : 'no'; ?>">
                                            <?php echo get_option('rsl_enable_html_injection', 1) ? esc_html__('Enabled', 'rsl-wp') : esc_html__('Disabled', 'rsl-wp'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('HTTP Link Headers', 'rsl-wp'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_http_headers', 1) ? 'yes' : 'no'; ?>">
                                            <?php echo get_option('rsl_enable_http_headers', 1) ? esc_html__('Enabled', 'rsl-wp') : esc_html__('Disabled', 'rsl-wp'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('robots.txt Integration', 'rsl-wp'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_robots_txt', 1) ? 'yes' : 'no'; ?>">
                                            <?php echo get_option('rsl_enable_robots_txt', 1) ? esc_html__('Enabled', 'rsl-wp') : esc_html__('Disabled', 'rsl-wp'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('RSS Feed Enhancement', 'rsl-wp'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_rss_feed', 1) ? 'yes' : 'no'; ?>">
                                            <?php echo get_option('rsl_enable_rss_feed', 1) ? esc_html__('Enabled', 'rsl-wp') : esc_html__('Disabled', 'rsl-wp'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Media Metadata', 'rsl-wp'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_media_metadata', 1) ? 'yes' : 'no'; ?>">
                                            <?php echo get_option('rsl_enable_media_metadata', 1) ? esc_html__('Enabled', 'rsl-wp') : esc_html__('Disabled', 'rsl-wp'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('WooCommerce', 'rsl-wp'); ?></td>
                                    <td>
                                        <?php if (class_exists('WooCommerce')) : ?>
                                            <span class="yes">✓ <?php esc_html_e('Active', 'rsl-wp'); ?></span>
                                            <?php if (class_exists('WC_Subscriptions')) : ?>
                                                <br><small><?php esc_html_e('Subscriptions: Available', 'rsl-wp'); ?></small>
                                            <?php else : ?>
                                                <br><small style="color: #856404;"><?php esc_html_e('Subscriptions: Extension needed', 'rsl-wp'); ?></small>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="no">✗ <?php esc_html_e('Not installed', 'rsl-wp'); ?></span>
                                            <br><small><?php esc_html_e('Required for paid licensing', 'rsl-wp'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
            <!-- Recent Licenses -->
            <div class="postbox-container" style="width: 49%; margin-right: 2%;">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php esc_html_e('Recent Licenses', 'rsl-wp'); ?></h2>
                        </div>
                        <div class="inside">
                            <?php if (!empty($licenses)) : ?>
                                <?php 
                                $recent_licenses = array_slice(array_reverse($licenses), 0, 5);
                                foreach ($recent_licenses as $license) : 
                                ?>
                                    <div class="rsl-recent-license">
                                        <div>
                                            <strong><?php echo esc_html($license['name']); ?></strong>
                                            <span class="rsl-payment-tag">
                                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $license['payment_type']))); ?>
                                            </span>
                                        </div>
                                        <div class="rsl-license-content-url">
                                            <?php echo esc_html($license['content_url']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <p class="rsl-view-all-link">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-licenses')); ?>">
                                        <?php esc_html_e('View all licenses →', 'rsl-wp'); ?>
                                    </a>
                                </p>
                            <?php else : ?>
                                <p>
                                    <?php esc_html_e('No licenses created yet.', 'rsl-wp'); ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=rsl-add-license')); ?>">
                                        <?php esc_html_e('Create your first license', 'rsl-wp'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="postbox-container" style="width: 49%;">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php esc_html_e('About RSL', 'rsl-wp'); ?></h2>
                        </div>
                        <div class="inside">
                            <p>
                                <?php esc_html_e('Really Simple Licensing (RSL) is a machine-readable format for defining licensing terms for digital content. It enables content owners to specify how their content can be used by AI systems, search engines, and other automated tools.', 'rsl-wp'); ?>
                            </p>
                            
                            <p>
                                <a href="https://rslstandard.org" target="_blank" class="button">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e('RSL Standard', 'rsl-wp'); ?>
                                </a>
                                <a href="https://rslcollective.org" target="_blank" class="button rsl-button-gap">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php esc_html_e('RSL Collective', 'rsl-wp'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php esc_html_e('System Status', 'rsl-wp'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e('WordPress', 'rsl-wp'); ?></td>
                                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('PHP', 'rsl-wp'); ?></td>
                                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('RSL Plugin', 'rsl-wp'); ?></td>
                                        <td><?php echo esc_html(RSL_PLUGIN_VERSION); ?></td>
                                    </tr>
                                    <?php if (function_exists('curl_version')) : ?>
                                    <tr>
                                        <td><?php esc_html_e('cURL Support', 'rsl-wp'); ?></td>
                                        <td><span class="yes">✓</span></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>