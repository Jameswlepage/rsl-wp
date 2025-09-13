jQuery(document).ready(function($) {
    
    // Global variables
    var rslAdmin = {
        init: function() {
            this.bindEvents();
            this.initializeFields();
            this.initializePostboxes();
        },
        
        bindEvents: function() {
            // Payment type change
            $(document).on('change', '#payment_type', this.togglePaymentFields);
            
            // Server option change
            $(document).on('change', 'input[name="server_option"]', this.toggleServerFields);
            
            // Form submission
            $(document).on('submit', '#rsl-license-form', this.handleFormSubmission);
            
            // Generate XML button
            $(document).on('click', '.rsl-generate-xml', this.generateXML);
            
            // Delete license button
            $(document).on('click', '.rsl-delete-license', this.deleteLicense);
            
            // Modal events
            $(document).on('click', '.rsl-modal-close', this.closeModal);
            $(document).on('click', '.rsl-modal', this.handleModalBackdropClick);
            
            // Copy XML button
            $(document).on('click', '#rsl-copy-xml', this.copyXMLToClipboard);
            
            // Download XML button
            $(document).on('click', '#rsl-download-xml', this.downloadXML);
            
            // Multiselect handling
            $(document).on('change', '.rsl-multiselect', this.updateMultiselectValues);
        },
        
        initializeFields: function() {
            this.togglePaymentFields();
            this.toggleServerFields();
            this.styleMultiselects();
        },
        
        togglePaymentFields: function() {
            var paymentType = $('#payment_type').val();
            var typesWithAmounts = ['purchase', 'subscription', 'training', 'crawl', 'inference', 'royalty'];
            
            if (typesWithAmounts.indexOf(paymentType) !== -1) {
                $('#payment_amount_row').show();
            } else {
                $('#payment_amount_row').hide();
            }
        },
        
        toggleServerFields: function() {
            var serverOption = $('input[name="server_option"]:checked').val();
            
            if (serverOption === 'external') {
                $('#external_server_url_field').show();
            } else {
                $('#external_server_url_field').hide();
                $('#server_url').val(''); // Clear external URL when using built-in
            }
        },
        
        validateWooCommerceRequirement: function() {
            var amount = parseFloat($('#amount').val()) || 0;
            var paymentType = $('#payment_type').val();
            var hasWooCommerce = typeof rsl_ajax.woocommerce_active !== 'undefined' ? rsl_ajax.woocommerce_active : false;
            var hasPaymentCapability = typeof rsl_ajax.has_payment_capability !== 'undefined' ? rsl_ajax.has_payment_capability : false;
            
            // Allow any payment type with $0 amount (including attribution)
            if (amount === 0) {
                return { valid: true };
            }
            
            // Block any amount > 0 without payment capability
            if (amount > 0 && !hasPaymentCapability) {
                var message = 'Payment processing is required for paid licensing (amount > $0). ';
                
                if (!hasWooCommerce) {
                    if (paymentType === 'attribution') {
                        message += 'For paid attribution licenses, please install and activate WooCommerce, then set up your preferred payment gateway (Stripe, PayPal, etc.).';
                    } else {
                        message += 'Please install and activate WooCommerce to enable payment processing.';
                    }
                } else {
                    message += 'WooCommerce is installed but may not support this payment type.';
                }
                
                return {
                    valid: false,
                    message: message
                };
            }
            
            return { valid: true };
        },
        
        styleMultiselects: function() {
            $('.rsl-multiselect').css({
                'width': '400px',
                'height': '100px'
            });
        },
        
        updateMultiselectValues: function() {
            $('.rsl-multiselect').each(function() {
                var values = $(this).val();
                if (values && values.length > 0) {
                    // Store comma-separated values in a hidden field
                    var hiddenField = $('input[name="' + $(this).attr('name') + '_hidden"]');
                    if (hiddenField.length === 0) {
                        hiddenField = $('<input type="hidden" name="' + $(this).attr('name') + '_hidden">');
                        $(this).after(hiddenField);
                    }
                    hiddenField.val(values.join(','));
                }
            });
        },
        
        handleFormSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var originalText = $submitButton.val();
            
            // Validate WooCommerce requirement before submission
            var wooValidation = rslAdmin.validateWooCommerceRequirement();
            if (!wooValidation.valid) {
                rslAdmin.showMessage(wooValidation.message, 'error');
                return;
            }
            
            // Update submit button
            $submitButton.val(rsl_ajax.strings.saving).prop('disabled', true);
            
            // Prepare form data
            var formData = $form.serialize();
            
            // Handle server option - set server_url based on radio selection
            var serverOption = $('input[name="server_option"]:checked').val();
            if (serverOption === 'builtin') {
                // Remove any existing server_url parameter to prevent duplicates
                formData = formData.replace(/&?server_url=[^&]*/g, '');
                formData += '&server_url=' + encodeURIComponent(rsl_ajax.rest_url);
            }
            // External option uses the URL field value (already in formData)
            
            // Handle multiselect fields (convert to comma-separated values)
            $('.rsl-multiselect').each(function() {
                var fieldName = $(this).attr('name');
                var values = $(this).val();
                
                // Remove existing field from formData to prevent duplicates
                var regex = new RegExp('&?' + encodeURIComponent(fieldName) + '=[^&]*', 'g');
                formData = formData.replace(regex, '');
                
                // Add the multiselect field with proper comma-separated values
                if (values && values.length > 0) {
                    formData += '&' + fieldName + '=' + encodeURIComponent(values.join(','));
                } else {
                    formData += '&' + fieldName + '=';
                }
            });
            
            // Add action and nonce
            formData += '&action=rsl_save_license&nonce=' + rsl_ajax.nonce;
            
            
            $.ajax({
                url: rsl_ajax.url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        rslAdmin.showMessage(response.data.message, 'success');
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = rsl_ajax.redirect_url;
                        }, 1500);
                    } else {
                        rslAdmin.showMessage(response.data.message, 'error');
                        $submitButton.val(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    rslAdmin.showMessage(rsl_ajax.strings.error_occurred, 'error');
                    $submitButton.val(originalText).prop('disabled', false);
                }
            });
        },
        
        generateXML: function() {
            var licenseId = $(this).data('license-id');
            
            $.ajax({
                url: rsl_ajax.url,
                type: 'POST',
                data: {
                    action: 'rsl_generate_xml',
                    license_id: licenseId,
                    nonce: rsl_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#rsl-xml-content').val(response.data.xml);
                        $('#rsl-xml-modal').show();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(rsl_ajax.strings.error_generating_xml);
                }
            });
        },
        
        deleteLicense: function() {
            var licenseId = $(this).data('license-id');
            var licenseName = $(this).data('license-name');
            
            if (!confirm(rsl_ajax.strings.delete_confirm.replace('%s', licenseName))) {
                return;
            }
            
            $.ajax({
                url: rsl_ajax.url,
                type: 'POST',
                data: {
                    action: 'rsl_delete_license',
                    license_id: licenseId,
                    nonce: rsl_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(rsl_ajax.strings.error_deleting);
                }
            });
        },
        
        closeModal: function() {
            $('.rsl-modal').hide();
        },
        
        handleModalBackdropClick: function(e) {
            if ($(e.target).hasClass('rsl-modal')) {
                $('.rsl-modal').hide();
            }
        },
        
        copyXMLToClipboard: function() {
            var textarea = document.getElementById('rsl-xml-content');
            if (textarea) {
                textarea.select();
                textarea.setSelectionRange(0, 99999); // For mobile devices
                
                try {
                    document.execCommand('copy');
                    rslAdmin.showTempMessage(rsl_ajax.strings.xml_copied);
                } catch (err) {
                    console.error('Failed to copy: ', err);
                    alert(rsl_ajax.strings.copy_failed);
                }
            }
        },
        
        downloadXML: function() {
            var content = $('#rsl-xml-content').val();
            var blob = new Blob([content], { type: 'application/xml' });
            var url = window.URL.createObjectURL(blob);
            
            var a = document.createElement('a');
            a.href = url;
            a.download = 'rsl-license.xml';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        
        showMessage: function(message, type) {
            var className = 'notice-' + (type === 'success' ? 'success' : 'error');
            var $message = $('#rsl-message');
            
            $message
                .removeClass('notice-success notice-error rsl-hidden')
                .addClass('notice ' + className)
                .html('<p>' + message + '</p>')
                .css('display', 'block');
            
            $('html, body').animate({scrollTop: 0}, 500);
        },
        
        showTempMessage: function(message) {
            var $temp = $('<div class="notice notice-success" style="position: fixed; top: 32px; right: 20px; z-index: 999999; padding: 10px 15px;"><p>' + message + '</p></div>');
            $('body').append($temp);
            
            setTimeout(function() {
                $temp.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 2000);
        },
        
        initializePostboxes: function() {
            // Initialize WordPress meta box collapse functionality
            if (typeof postboxes !== 'undefined') {
                postboxes.add_postbox_toggles('rsl-dashboard');
            }
        }
    };
    
    // Initialize admin functionality
    rslAdmin.init();
    
    // Handle escape key for modals
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // Escape key
            $('.rsl-modal').hide();
        }
    });
    
    // Validation helpers
    window.rslValidation = {
        validateURL: function (value) {
            if (!value) return false;
            value = value.trim();

            // Absolute http(s) URL
            var abs = /^(https?:\/\/)((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(:\d+)?(\/[-a-z\d%_.~+*]*)*(\?[;&a-z\d%_.~+=-]*)?(#[-a-z\d_]*)?$/i;
            if (abs.test(value)) return true;

            // Server-relative RFC 9309-style pattern: starts with '/', allows *, $
            var rel = /^\/[A-Za-z0-9._~!'()*+,;=:@\/\-%]*\*?\$?$/;
            return rel.test(value);
        },
        
        validateEmail: function(email) {
            var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return pattern.test(email);
        }
    };
    
    // Add real-time validation for payment fields
    $('#amount, #payment_type').on('change blur', function() {
        var validation = rslAdmin.validateWooCommerceRequirement();
        var $amountField = $('#amount');
        var $paymentField = $('#payment_type');
        
        if (!validation.valid) {
            // Highlight both amount and payment type fields
            $amountField.css('border-color', '#dc3545');
            $paymentField.css('border-color', '#dc3545');
            
            // Show error message under amount field
            $amountField.next('.validation-error').remove();
            $amountField.after('<div class="validation-error" style="color: #dc3545; font-size: 12px; margin-top: 5px; padding: 8px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">' + validation.message + '</div>');
            
            // Disable submit button
            $('input[type="submit"]').prop('disabled', true).css('opacity', '0.6');
        } else {
            // Clear validation errors
            $amountField.css('border-color', '');
            $paymentField.css('border-color', '');
            $amountField.next('.validation-error').remove();
            
            // Re-enable submit button
            $('input[type="submit"]').prop('disabled', false).css('opacity', '1');
        }
    });
    
    // Add real-time validation for URLs
    $('#content_url, #server_url, #standard_url, #custom_url, #schema_url, #contact_url, #terms_url').on('blur', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (value && !rslValidation.validateURL(value)) {
            $field.css('border-color', '#dc3545');
            if ($field.next('.validation-error').length === 0) {
                $field.after('<span class="validation-error" style="color: #dc3545; font-size: 12px;">' + rsl_ajax.strings.validate_url + '</span>');
            }
        } else {
            $field.css('border-color', '');
            $field.next('.validation-error').remove();
        }
    });
    
    $('#contact_email').on('blur', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (value && !rslValidation.validateEmail(value)) {
            $field.css('border-color', '#dc3545');
            if ($field.next('.validation-error').length === 0) {
                $field.after('<span class="validation-error" style="color: #dc3545; font-size: 12px;">' + rsl_ajax.strings.validate_email + '</span>');
            }
        } else {
            $field.css('border-color', '');
            $field.next('.validation-error').remove();
        }
    });
});