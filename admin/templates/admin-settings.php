<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'assets/icon-128x128.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php _e('RSL Licensing Settings', 'rsl-licensing'); ?>
    </h1>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully.', 'rsl-licensing'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('rsl_settings');
        do_settings_sections('rsl_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rsl_global_license_id"><?php _e('Global License', 'rsl-licensing'); ?></label>
                </th>
                <td>
                    <select name="rsl_global_license_id" id="rsl_global_license_id">
                        <option value="0"><?php _e('No global license', 'rsl-licensing'); ?></option>
                        <?php foreach ($licenses as $license) : ?>
                            <option value="<?php echo esc_attr($license['id']); ?>" 
                                    <?php selected($global_license_id, $license['id']); ?>>
                                <?php echo esc_html($license['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select a license to apply site-wide. Individual posts/pages can override this.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('HTML Injection', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_html_injection" value="1" 
                               <?php checked(get_option('rsl_enable_html_injection', 1)); ?>>
                        <?php _e('Enable HTML head injection', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Automatically inject RSL license information into HTML head section.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('HTTP Headers', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_http_headers" value="1" 
                               <?php checked(get_option('rsl_enable_http_headers', 1)); ?>>
                        <?php _e('Enable HTTP Link headers', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Add RSL license information to HTTP response headers.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('robots.txt Integration', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_robots_txt" value="1" 
                               <?php checked(get_option('rsl_enable_robots_txt', 1)); ?>>
                        <?php _e('Enable robots.txt license directive', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Add License directive to robots.txt file.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('RSS Feed Integration', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_rss_feed" value="1" 
                               <?php checked(get_option('rsl_enable_rss_feed', 1)); ?>>
                        <?php _e('Enable RSS feed RSL integration', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Add RSL licensing information to RSS feeds.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Media Metadata', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_media_metadata" value="1" 
                               <?php checked(get_option('rsl_enable_media_metadata', 1)); ?>>
                        <?php _e('Enable media file metadata embedding', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Embed RSL license information in uploaded media files.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="rsl_default_namespace"><?php _e('RSL Namespace', 'rsl-licensing'); ?></label>
                </th>
                <td>
                    <input type="url" name="rsl_default_namespace" id="rsl_default_namespace" 
                           value="<?php echo esc_attr(get_option('rsl_default_namespace', 'https://rslstandard.org/rsl')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php _e('RSL XML namespace URI. Use default unless you have a custom implementation.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php _e('Quick Actions', 'rsl-licensing'); ?></h2>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>" class="button">
            <?php _e('View All Licenses', 'rsl-licensing'); ?>
        </a>
        
        <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" class="button button-primary">
            <?php _e('Create New License', 'rsl-licensing'); ?>
        </a>
        
        <a href="<?php echo admin_url('admin.php?page=rsl-licensing'); ?>" class="button">
            <?php _e('Back to Dashboard', 'rsl-licensing'); ?>
        </a>
    </p>
    
    <h2><?php _e('RSL Information', 'rsl-licensing'); ?></h2>
    
    <p>
        <?php _e('Really Simple Licensing (RSL) is a machine-readable format for defining licensing terms for digital content. It enables content owners to specify how their content can be used by AI systems, search engines, and other automated tools.', 'rsl-licensing'); ?>
    </p>
    
    <p>
        <strong><?php _e('Key Features:', 'rsl-licensing'); ?></strong>
    </p>
    
    <ul>
        <li><?php _e('Define permitted and prohibited uses', 'rsl-licensing'); ?></li>
        <li><?php _e('Set payment and compensation terms', 'rsl-licensing'); ?></li>
        <li><?php _e('Specify user type restrictions', 'rsl-licensing'); ?></li>
        <li><?php _e('Geographical usage limitations', 'rsl-licensing'); ?></li>
        <li><?php _e('Integration with license servers', 'rsl-licensing'); ?></li>
    </ul>
    
    <p>
        <a href="https://rslstandard.org" target="_blank" class="button">
            <?php _e('Learn More About RSL', 'rsl-licensing'); ?>
        </a>
    </p>
</div>