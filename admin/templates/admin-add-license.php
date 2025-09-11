<?php
if (!defined('ABSPATH')) {
    exit;
}

$license_handler = new RSL_License();
$is_edit = !empty($license_data);
$title = $is_edit ? __('Edit RSL License', 'rsl-licensing') : __('Add RSL License', 'rsl-licensing');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php echo esc_html($title); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>" class="page-title-action">
        <?php _e('View All Licenses', 'rsl-licensing'); ?>
    </a>
    <hr class="wp-header-end">
    
    <div id="rsl-message" class="notice rsl-hidden"></div>
    
    <form id="rsl-license-form" method="post">
        <?php wp_nonce_field('rsl_license_form', 'rsl_nonce'); ?>
        
        <?php if ($is_edit) : ?>
            <input type="hidden" name="license_id" value="<?php echo esc_attr($license_id); ?>">
        <?php endif; ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="name"><?php _e('License Name', 'rsl-licensing'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" required
                               value="<?php echo esc_attr($license_data['name'] ?? ''); ?>">
                        <p class="description"><?php _e('A descriptive name for this license configuration.', 'rsl-licensing'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="description"><?php _e('Description', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea($license_data['description'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Optional description of this license.', 'rsl-licensing'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="content_url"><?php _e('Content URL', 'rsl-licensing'); ?> *</label>
                    </th>
                    <td>
                        <input type="url" id="content_url" name="content_url" class="regular-text" required
                               value="<?php echo esc_attr($license_data['content_url'] ?? ''); ?>"
                               placeholder="https://example.com/content/ or / for site root">
                        <p class="description">
                            <?php _e('URL pattern for licensed content. Use "/" for entire site, or specific paths like "/images/". Supports wildcards (* and $).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="server_url"><?php _e('License Server URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="server_url" name="server_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['server_url'] ?? ''); ?>"
                               placeholder="https://rslcollective.org/api">
                        <p class="description">
                            <?php _e('Optional RSL License Server URL for managed licensing.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Content Encryption', 'rsl-licensing'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="encrypted" value="1" 
                                   <?php checked($license_data['encrypted'] ?? 0, 1); ?>>
                            <?php _e('Content is encrypted', 'rsl-licensing'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Check if the licensed content requires decryption keys from the license server.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Permissions', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="permits_usage"><?php _e('Permitted Usage', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="permits_usage" name="permits_usage" multiple class="rsl-multiselect">
                            <?php 
                            $selected_usage = explode(',', $license_data['permits_usage'] ?? '');
                            foreach ($license_handler->get_usage_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_usage), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select permitted usage types. Leave empty to permit all usage types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="permits_user"><?php _e('Permitted Users', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="permits_user" name="permits_user" multiple class="rsl-multiselect">
                            <?php 
                            $selected_user = explode(',', $license_data['permits_user'] ?? '');
                            foreach ($license_handler->get_user_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_user), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select permitted user types. Leave empty to permit all user types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="permits_geo"><?php _e('Permitted Geography', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="permits_geo" name="permits_geo" class="regular-text"
                               value="<?php echo esc_attr($license_data['permits_geo'] ?? ''); ?>"
                               placeholder="US,EU,CA">
                        <p class="description">
                            <?php _e('Comma-separated list of permitted countries/regions (ISO 3166-1 alpha-2 codes).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Restrictions', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="prohibits_usage"><?php _e('Prohibited Usage', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="prohibits_usage" name="prohibits_usage" multiple class="rsl-multiselect">
                            <?php 
                            $selected_prohibited = explode(',', $license_data['prohibits_usage'] ?? '');
                            foreach ($license_handler->get_usage_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_prohibited), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select explicitly prohibited usage types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="prohibits_user"><?php _e('Prohibited Users', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="prohibits_user" name="prohibits_user" multiple class="rsl-multiselect">
                            <?php 
                            $selected_prohibited_user = explode(',', $license_data['prohibits_user'] ?? '');
                            foreach ($license_handler->get_user_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_prohibited_user), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select explicitly prohibited user types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="prohibits_geo"><?php _e('Prohibited Geography', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="prohibits_geo" name="prohibits_geo" class="regular-text"
                               value="<?php echo esc_attr($license_data['prohibits_geo'] ?? ''); ?>"
                               placeholder="CN,RU">
                        <p class="description">
                            <?php _e('Comma-separated list of prohibited countries/regions (ISO 3166-1 alpha-2 codes).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Payment & Compensation', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="payment_type"><?php _e('Payment Type', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="payment_type" name="payment_type" class="regular-text">
                            <?php foreach ($license_handler->get_payment_options() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected($license_data['payment_type'] ?? 'free', $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr id="payment_amount_row" style="display: none;">
                    <th scope="row">
                        <label for="amount"><?php _e('Amount', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" class="small-text"
                               value="<?php echo esc_attr($license_data['amount'] ?? '0'); ?>">
                        <select name="currency" class="regular-text">
                            <option value="USD" <?php selected($license_data['currency'] ?? 'USD', 'USD'); ?>>USD</option>
                            <option value="EUR" <?php selected($license_data['currency'] ?? 'USD', 'EUR'); ?>>EUR</option>
                            <option value="GBP" <?php selected($license_data['currency'] ?? 'USD', 'GBP'); ?>>GBP</option>
                            <option value="BTC" <?php selected($license_data['currency'] ?? 'USD', 'BTC'); ?>>BTC</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="standard_url"><?php _e('Standard License URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="standard_url" name="standard_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['standard_url'] ?? ''); ?>"
                               placeholder="https://creativecommons.org/licenses/by/4.0/">
                        <p class="description">
                            <?php _e('URL to standard license terms (e.g., Creative Commons, RSL Collective).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="custom_url"><?php _e('Custom License URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="custom_url" name="custom_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['custom_url'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('URL to custom licensing terms and contact information.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Legal Information', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="warranty"><?php _e('Warranties', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="warranty" name="warranty" multiple class="rsl-multiselect">
                            <?php 
                            $selected_warranty = explode(',', $license_data['warranty'] ?? '');
                            foreach ($license_handler->get_warranty_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_warranty), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select warranties provided with this license.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="disclaimer"><?php _e('Disclaimers', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="disclaimer" name="disclaimer" multiple class="rsl-multiselect">
                            <?php 
                            $selected_disclaimer = explode(',', $license_data['disclaimer'] ?? '');
                            foreach ($license_handler->get_disclaimer_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_disclaimer), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select disclaimers for this license.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Additional Information', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="schema_url"><?php _e('Schema.org URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="schema_url" name="schema_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['schema_url'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('URL to Schema.org CreativeWork metadata for this content.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="copyright_holder"><?php _e('Copyright Holder', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="copyright_holder" name="copyright_holder" class="regular-text"
                               value="<?php echo esc_attr($license_data['copyright_holder'] ?? ''); ?>">
                        
                        <select name="copyright_type" class="regular-text">
                            <option value=""><?php _e('Select Type', 'rsl-licensing'); ?></option>
                            <option value="person" <?php selected($license_data['copyright_type'] ?? '', 'person'); ?>>
                                <?php _e('Person', 'rsl-licensing'); ?>
                            </option>
                            <option value="organization" <?php selected($license_data['copyright_type'] ?? '', 'organization'); ?>>
                                <?php _e('Organization', 'rsl-licensing'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="contact_email"><?php _e('Contact Email', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="contact_email" name="contact_email" class="regular-text"
                               value="<?php echo esc_attr($license_data['contact_email'] ?? ''); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="contact_url"><?php _e('Contact URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="contact_url" name="contact_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['contact_url'] ?? ''); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="terms_url"><?php _e('Terms URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="terms_url" name="terms_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['terms_url'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('URL to additional legal information about the license.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Status', 'rsl-licensing'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="active" value="1" 
                                   <?php checked($license_data['active'] ?? 1, 1); ?>>
                            <?php _e('License is active', 'rsl-licensing'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button($is_edit ? __('Update License', 'rsl-licensing') : __('Create License', 'rsl-licensing')); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Show/hide payment amount based on payment type
    function togglePaymentAmount() {
        var paymentType = $('#payment_type').val();
        if (['purchase', 'subscription', 'training', 'crawl', 'inference'].indexOf(paymentType) !== -1) {
            $('#payment_amount_row').show();
        } else {
            $('#payment_amount_row').hide();
        }
    }
    
    $('#payment_type').on('change', togglePaymentAmount);
    togglePaymentAmount();
    
    // Handle form submission
    $('#rsl-license-form').on('submit', function(e) {
        e.preventDefault();
        
        // Serialize multiselect values
        $('.rsl-multiselect').each(function() {
            var values = $(this).val();
            if (values && values.length > 0) {
                $(this).attr('name', $(this).attr('name')).val(values.join(','));
            }
        });
        
        $.ajax({
            url: rsl_ajax.url,
            type: 'POST',
            data: $(this).serialize() + '&action=rsl_save_license&nonce=' + rsl_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    $('#rsl-message').removeClass('notice-error').addClass('notice-success')
                        .html('<p>' + response.data.message + '</p>').show();
                    
                    // Redirect to licenses page after a short delay
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=rsl-licenses'); ?>';
                    }, 1500);
                } else {
                    $('#rsl-message').removeClass('notice-success').addClass('notice-error')
                        .html('<p>' + response.data.message + '</p>').show();
                }
                
                $('html, body').animate({scrollTop: 0}, 500);
            }
        });
    });
    
    // Style multiselect dropdowns
    $('.rsl-multiselect').css({
        'width': '400px',
        'height': '100px'
    });
});
</script>