<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php _e('RSL Licensing', 'rsl-licensing'); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" class="page-title-action">
        <?php _e('Add New License', 'rsl-licensing'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully.', 'rsl-licensing'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="rsl-dashboard-wrap">
        <!-- At A Glance with Quick Actions -->
        <div class="postbox-container rsl-full-width">
            <div class="meta-box-sortables">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php _e('At a Glance', 'rsl-licensing'); ?></h2>
                    </div>
                    <div class="inside">
                        <div class="rsl-at-glance-wrapper">
                            <div class="rsl-stats-section">
                                <div class="main">
                                    <ul>
                                        <li>
                                            <strong><?php echo $total_licenses; ?></strong> 
                                            <span><?php _e('Total Licenses', 'rsl-licensing'); ?></span>
                                        </li>
                                        <li>
                                            <strong><?php echo $active_licenses; ?></strong> 
                                            <span><?php _e('Active Licenses', 'rsl-licensing'); ?></span>
                                        </li>
                                        <li>
                                            <span class="<?php echo $global_license_id > 0 ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                                <?php echo $global_license_id > 0 ? '✓' : '×'; ?>
                                            </span>
                                            <span><?php _e('Global License Configured', 'rsl-licensing'); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="rsl-quick-actions-section">
                                <div class="rsl-actions-inline">
                                    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" 
                                       class="button button-primary">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php _e('Create License', 'rsl-licensing'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>" 
                                       class="button button-secondary">
                                        <span class="dashicons dashicons-list-view"></span>
                                        <?php _e('Manage Licenses', 'rsl-licensing'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=rsl-settings'); ?>" 
                                       class="button button-secondary">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <?php _e('Settings', 'rsl-licensing'); ?>
                                    </a>
                                    <a href="https://rslstandard.org" target="_blank" class="button button-secondary">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php _e('RSL Standard', 'rsl-licensing'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integration Status -->
        <div class="postbox-container rsl-full-width">
            <div class="meta-box-sortables">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <?php _e('Integration Status', 'rsl-licensing'); ?>
                            <a href="<?php echo admin_url('admin.php?page=rsl-settings'); ?>" class="rsl-header-link">
                                <?php _e('Go to Settings', 'rsl-licensing'); ?>
                            </a>
                        </h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <td><?php _e('HTML Head Injection', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_html_injection', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_html_injection', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('HTTP Link Headers', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_http_headers', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_http_headers', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('robots.txt Integration', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_robots_txt', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_robots_txt', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('RSS Feed Enhancement', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_rss_feed', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_rss_feed', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('Media Metadata', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_media_metadata', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_media_metadata', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Row 1 -->
        
        <!-- Row 2 -->
        <div class="rsl-dashboard-row">
            <div class="postbox-container rsl-half-width">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('Recent Licenses', 'rsl-licensing'); ?></h2>
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
                                    <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>">
                                        <?php _e('View all licenses →', 'rsl-licensing'); ?>
                                    </a>
                                </p>
                            <?php else : ?>
                                <p>
                                    <?php _e('No licenses created yet.', 'rsl-licensing'); ?>
                                    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>">
                                        <?php _e('Create your first license', 'rsl-licensing'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="postbox-container rsl-half-width">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('About RSL', 'rsl-licensing'); ?></h2>
                        </div>
                        <div class="inside">
                            <p>
                                <?php _e('Really Simple Licensing (RSL) is a machine-readable format for defining licensing terms for digital content. It enables content owners to specify how their content can be used by AI systems, search engines, and other automated tools.', 'rsl-licensing'); ?>
                            </p>
                            
                            <p>
                                <a href="https://rslstandard.org" target="_blank" class="button">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php _e('RSL Standard', 'rsl-licensing'); ?>
                                </a>
                                <a href="https://rslcollective.org" target="_blank" class="button rsl-button-gap">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php _e('RSL Collective', 'rsl-licensing'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('System Status', 'rsl-licensing'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td><?php _e('WordPress', 'rsl-licensing'); ?></td>
                                        <td><?php echo get_bloginfo('version'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('PHP', 'rsl-licensing'); ?></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('RSL Plugin', 'rsl-licensing'); ?></td>
                                        <td><?php echo RSL_PLUGIN_VERSION; ?></td>
                                    </tr>
                                    <?php if (function_exists('curl_version')) : ?>
                                    <tr>
                                        <td><?php _e('cURL Support', 'rsl-licensing'); ?></td>
                                        <td><span class="rsl-enabled">✓</span></td>
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