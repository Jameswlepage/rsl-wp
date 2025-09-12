<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php _e('RSL Licenses', 'rsl-licensing'); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" class="page-title-action">
        <?php _e('Add New License', 'rsl-licensing'); ?>
    </a>
    <hr class="wp-header-end">
    
    <div id="rsl-message" class="notice rsl-hidden"></div>
    
    <?php if (empty($licenses)) : ?>
        <div class="notice notice-info">
            <p>
                <?php _e('No licenses found.', 'rsl-licensing'); ?>
                <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>">
                    <?php _e('Create your first license', 'rsl-licensing'); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Name', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Content URL', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Payment Type', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Usage Permits', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Status', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Actions', 'rsl-licensing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($license['name']); ?></strong>
                            <?php if (!empty($license['description'])) : ?>
                                <br><small><?php echo esc_html($license['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $content_url = $license['content_url'];
                            if (strlen($content_url) > 50) {
                                echo esc_html(substr($content_url, 0, 50) . '...');
                            } else {
                                echo esc_html($content_url);
                            }
                            ?>
                        </td>
                        <td>
                            <span class="rsl-payment-type rsl-payment-<?php echo esc_attr($license['payment_type']); ?>">
                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $license['payment_type']))); ?>
                            </span>
                            <?php if (!empty($license['amount']) && $license['amount'] > 0) : ?>
                                <br><small><?php echo esc_html($license['amount'] . ' ' . $license['currency']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $permits = array();
                            if (!empty($license['permits_usage'])) {
                                $permits[] = 'Usage: ' . $license['permits_usage'];
                            }
                            if (!empty($license['permits_user'])) {
                                $permits[] = 'User: ' . $license['permits_user'];
                            }
                            if (!empty($license['permits_geo'])) {
                                $permits[] = 'Geo: ' . $license['permits_geo'];
                            }
                            
                            if (!empty($permits)) {
                                echo '<small>' . esc_html(implode('; ', $permits)) . '</small>';
                            } else {
                                echo '<em>' . __('All permitted', 'rsl-licensing') . '</em>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($license['active']) : ?>
                                <span class="rsl-status-active"><?php _e('Active', 'rsl-licensing'); ?></span>
                            <?php else : ?>
                                <span class="rsl-status-inactive"><?php _e('Inactive', 'rsl-licensing'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=rsl-add-license&edit=' . $license['id']); ?>" 
                               class="button button-small">
                                <?php _e('Edit', 'rsl-licensing'); ?>
                            </a>
                            
                            <button type="button" class="button button-small rsl-generate-xml" 
                                    data-license-id="<?php echo esc_attr($license['id']); ?>">
                                <?php _e('Generate XML', 'rsl-licensing'); ?>
                            </button>
                            
                            <button type="button" class="button button-small button-link-delete rsl-delete-license" 
                                    data-license-id="<?php echo esc_attr($license['id']); ?>"
                                    data-license-name="<?php echo esc_attr($license['name']); ?>">
                                <?php _e('Delete', 'rsl-licensing'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="rsl-xml-modal" class="rsl-modal rsl-hidden">
    <div class="rsl-modal-content">
        <div class="rsl-modal-header">
            <h3><?php _e('Generated RSL XML', 'rsl-licensing'); ?></h3>
            <span class="rsl-modal-close">&times;</span>
        </div>
        <div class="rsl-modal-body">
            <textarea id="rsl-xml-content" rows="20" cols="80" readonly></textarea>
            <p>
                <button type="button" id="rsl-copy-xml" class="button button-primary">
                    <?php _e('Copy to Clipboard', 'rsl-licensing'); ?>
                </button>
                <button type="button" id="rsl-download-xml" class="button">
                    <?php _e('Download XML', 'rsl-licensing'); ?>
                </button>
            </p>
        </div>
    </div>
</div>

