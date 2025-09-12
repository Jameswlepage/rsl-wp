(function() {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { SelectControl, TextControl, ToggleControl, PanelRow } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;

    function RSLLicensePanel() {
        const { editPost } = useDispatch('core/editor');
        
        const { 
            rslLicenseId, 
            rslOverrideUrl,
            globalLicenseId,
            postType
        } = useSelect((select) => {
            const { getEditedPostAttribute } = select('core/editor');
            const { getPostType } = select('core');
            
            return {
                rslLicenseId: getEditedPostAttribute('meta')?._rsl_license_id || '',
                rslOverrideUrl: getEditedPostAttribute('meta')?._rsl_override_content_url || '',
                globalLicenseId: rslGutenberg?.globalLicenseId || 0,
                postType: getPostType(getEditedPostAttribute('type'))
            };
        });

        // Check if RSL is supported for this post type
        const supportedPostTypes = ['post', 'page'];
        if (!supportedPostTypes.includes(postType?.slug)) {
            return null;
        }

        const onLicenseChange = (value) => {
            editPost({
                meta: {
                    _rsl_license_id: value === '' ? 0 : parseInt(value)
                }
            });
        };

        const onOverrideUrlChange = (value) => {
            editPost({
                meta: {
                    _rsl_override_content_url: value
                }
            });
        };

        // Prepare license options
        const licenseOptions = [
            { 
                label: globalLicenseId > 0 
                    ? __('Use Global License (Default)', 'rsl-licensing')
                    : __('No License', 'rsl-licensing'), 
                value: '' 
            }
        ];

        if (rslGutenberg?.licenses) {
            rslGutenberg.licenses.forEach(license => {
                licenseOptions.push({
                    label: license.name + ' (' + license.payment_type + ')',
                    value: license.id.toString()
                });
            });
        }

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'rsl-license-panel',
                title: __('RSL License', 'rsl-licensing'),
                className: 'rsl-license-panel'
            },
            el(
                Fragment,
                null,
                el(
                    PanelRow,
                    null,
                    el(SelectControl, {
                        label: __('License', 'rsl-licensing'),
                        value: rslLicenseId.toString(),
                        options: licenseOptions,
                        onChange: onLicenseChange,
                        help: __('Select an RSL license for this content. Individual licenses override the global site license.', 'rsl-licensing')
                    })
                ),
                el(
                    PanelRow,
                    null,
                    el(TextControl, {
                        label: __('Override Content URL', 'rsl-licensing'),
                        value: rslOverrideUrl,
                        onChange: onOverrideUrlChange,
                        placeholder: __('Leave empty to use post URL', 'rsl-licensing'),
                        help: __('Override the content URL for this specific post/page. Useful for syndicated or cross-posted content.', 'rsl-licensing')
                    })
                ),
                globalLicenseId > 0 && el(
                    'div',
                    { 
                        style: { 
                            marginTop: '12px',
                            padding: '8px 12px', 
                            background: '#f0f6fc', 
                            border: '1px solid #c3c4c7',
                            borderRadius: '4px',
                            fontSize: '13px'
                        }
                    },
                    el('strong', null, __('Global License Active:', 'rsl-licensing')),
                    el('br'),
                    __('This site has a global RSL license configured. Leave the license selection empty to use the global license, or select a specific license to override it for this content.', 'rsl-licensing')
                )
            )
        );
    }

    registerPlugin('rsl-license-panel', {
        render: RSLLicensePanel,
        icon: 'clipboard'
    });
})();