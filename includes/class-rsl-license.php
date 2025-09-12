<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_License {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rsl_licenses';
    }
    
    public function create_license($data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'content_url' => '',
            'server_url' => '',
            'encrypted' => 0,
            'lastmod' => current_time('mysql'),
            'permits_usage' => '',
            'permits_user' => '',
            'permits_geo' => '',
            'prohibits_usage' => '',
            'prohibits_user' => '',
            'prohibits_geo' => '',
            'payment_type' => 'free',
            'standard_url' => '',
            'custom_url' => '',
            'amount' => 0,
            'currency' => 'USD',
            'warranty' => '',
            'disclaimer' => '',
            'schema_url' => '',
            'copyright_holder' => '',
            'copyright_type' => '',
            'contact_email' => '',
            'contact_url' => '',
            'terms_url' => '',
            'active' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['content_url'])) {
            error_log('RSL: Cannot create license - name and content_url are required');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
            )
        );
        
        if ($result === false) {
            error_log('RSL: Database error creating license: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public function get_license($id) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            return null;
        }
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            error_log('RSL: Database error getting license: ' . $wpdb->last_error);
            return null;
        }
        
        return $result;
    }
    
    public function get_licenses($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'active' => 1,
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Validate and sanitize orderby to prevent SQL injection
        $allowed_orderby = array('id', 'name', 'created_at', 'updated_at', 'lastmod', 'payment_type');
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'name';
        }
        
        // Validate order parameter
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        // Validate numeric parameters
        $args['limit'] = max(-1, intval($args['limit']));
        $args['offset'] = max(0, intval($args['offset']));
        
        $where = array();
        $values = array();
        
        if ($args['active'] !== null) {
            $where[] = "active = %d";
            $values[] = intval($args['active']);
        }
        
        $sql = "SELECT * FROM {$this->table_name}";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY `{$args['orderby']}` {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $values[] = $args['limit'];
            
            if ($args['offset'] > 0) {
                $sql .= " OFFSET %d";
                $values[] = $args['offset'];
            }
        }
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Add error handling
        if ($wpdb->last_error) {
            error_log('RSL License Query Error: ' . $wpdb->last_error);
            return array();
        }
        
        return $results ? $results : array();
    }
    
    public function update_license($id, $data) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            error_log('RSL: Invalid license ID for update: ' . $id);
            return false;
        }
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result === false) {
            error_log('RSL: Database error updating license ID ' . $id . ': ' . $wpdb->last_error);
            return false;
        }
        
        return $result !== false;
    }
    
    public function delete_license($id) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            error_log('RSL: Invalid license ID for deletion: ' . $id);
            return false;
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            error_log('RSL: Database error deleting license ID ' . $id . ': ' . $wpdb->last_error);
            return false;
        }
        
        return $result !== false;
    }
    
    public function generate_rsl_xml($license_data, $options = array()) {
        $defaults = array(
            'namespace' => 'https://rslstandard.org/rsl',
            'standalone' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $xml = '';
        
        if ($options['standalone']) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        }
        
        $xml .= '<rsl xmlns="' . esc_attr($options['namespace']) . '">' . "\n";
        $xml .= '  <content url="' . esc_attr($license_data['content_url']) . '"';
        
        if (!empty($license_data['server_url'])) {
            $xml .= ' server="' . esc_attr($license_data['server_url']) . '"';
        }
        
        if (!empty($license_data['encrypted']) && $license_data['encrypted'] == 1) {
            $xml .= ' encrypted="true"';
        }
        
        if (!empty($license_data['lastmod'])) {
            $xml .= ' lastmod="' . esc_attr(date('c', strtotime($license_data['lastmod']))) . '"';
        }
        
        $xml .= '>' . "\n";
        
        if (!empty($license_data['schema_url'])) {
            $xml .= '    <schema>' . esc_html($license_data['schema_url']) . '</schema>' . "\n";
        }
        
        if (!empty($license_data['copyright_holder'])) {
            $xml .= '    <copyright';
            if (!empty($license_data['copyright_type'])) {
                $xml .= ' type="' . esc_attr($license_data['copyright_type']) . '"';
            }
            if (!empty($license_data['contact_email'])) {
                $xml .= ' contactEmail="' . esc_attr($license_data['contact_email']) . '"';
            }
            if (!empty($license_data['contact_url'])) {
                $xml .= ' contactUrl="' . esc_attr($license_data['contact_url']) . '"';
            }
            $xml .= '>' . esc_html($license_data['copyright_holder']) . '</copyright>' . "\n";
        }
        
        if (!empty($license_data['terms_url'])) {
            $xml .= '    <terms>' . esc_html($license_data['terms_url']) . '</terms>' . "\n";
        }
        
        $xml .= '    <license>' . "\n";
        
        if (!empty($license_data['permits_usage'])) {
            $xml .= '      <permits type="usage">' . esc_html($license_data['permits_usage']) . '</permits>' . "\n";
        }
        
        if (!empty($license_data['permits_user'])) {
            $xml .= '      <permits type="user">' . esc_html($license_data['permits_user']) . '</permits>' . "\n";
        }
        
        if (!empty($license_data['permits_geo'])) {
            $xml .= '      <permits type="geo">' . esc_html($license_data['permits_geo']) . '</permits>' . "\n";
        }
        
        if (!empty($license_data['prohibits_usage'])) {
            $xml .= '      <prohibits type="usage">' . esc_html($license_data['prohibits_usage']) . '</prohibits>' . "\n";
        }
        
        if (!empty($license_data['prohibits_user'])) {
            $xml .= '      <prohibits type="user">' . esc_html($license_data['prohibits_user']) . '</prohibits>' . "\n";
        }
        
        if (!empty($license_data['prohibits_geo'])) {
            $xml .= '      <prohibits type="geo">' . esc_html($license_data['prohibits_geo']) . '</prohibits>' . "\n";
        }
        
        if (!empty($license_data['payment_type']) && $license_data['payment_type'] !== 'free') {
            $xml .= '      <payment type="' . esc_attr($license_data['payment_type']) . '">' . "\n";
            
            if (!empty($license_data['standard_url'])) {
                $xml .= '        <standard>' . esc_html($license_data['standard_url']) . '</standard>' . "\n";
            }
            
            if (!empty($license_data['custom_url'])) {
                $xml .= '        <custom>' . esc_html($license_data['custom_url']) . '</custom>' . "\n";
            }
            
            if (!empty($license_data['amount']) && $license_data['amount'] > 0) {
                $xml .= '        <amount currency="' . esc_attr($license_data['currency']) . '">' . 
                        esc_html($license_data['amount']) . '</amount>' . "\n";
            }
            
            $xml .= '      </payment>' . "\n";
        } else {
            $xml .= '      <payment type="free"/>' . "\n";
        }
        
        if (!empty($license_data['warranty'])) {
            $xml .= '      <legal type="warranty">' . esc_html($license_data['warranty']) . '</legal>' . "\n";
        }
        
        if (!empty($license_data['disclaimer'])) {
            $xml .= '      <legal type="disclaimer">' . esc_html($license_data['disclaimer']) . '</legal>' . "\n";
        }
        
        $xml .= '    </license>' . "\n";
        $xml .= '  </content>' . "\n";
        $xml .= '</rsl>';
        
        return $xml;
    }
    
    public function get_usage_options() {
        return array(
            'all' => __('All automated processing', 'rsl-licensing'),
            'train-ai' => __('Train AI model', 'rsl-licensing'),
            'train-genai' => __('Train generative AI model', 'rsl-licensing'),
            'ai-use' => __('Use as AI input (RAG)', 'rsl-licensing'),
            'ai-summarize' => __('AI summarization', 'rsl-licensing'),
            'search' => __('Search indexing', 'rsl-licensing')
        );
    }
    
    public function get_user_options() {
        return array(
            'commercial' => __('Commercial use', 'rsl-licensing'),
            'non-commercial' => __('Non-commercial use', 'rsl-licensing'),
            'education' => __('Educational use', 'rsl-licensing'),
            'government' => __('Government use', 'rsl-licensing'),
            'personal' => __('Personal use', 'rsl-licensing')
        );
    }
    
    public function get_payment_options() {
        return array(
            'free' => __('Free', 'rsl-licensing'),
            'purchase' => __('One-time purchase', 'rsl-licensing'),
            'subscription' => __('Subscription', 'rsl-licensing'),
            'training' => __('Per training use', 'rsl-licensing'),
            'crawl' => __('Per crawl', 'rsl-licensing'),
            'inference' => __('Per inference', 'rsl-licensing'),
            'attribution' => __('Attribution required', 'rsl-licensing'),
            'royalty' => __('Royalty', 'rsl-licensing')
        );
    }
    
    public function get_warranty_options() {
        return array(
            'ownership' => __('Ownership rights', 'rsl-licensing'),
            'authority' => __('Authorization to license', 'rsl-licensing'),
            'no-infringement' => __('No third-party infringement', 'rsl-licensing'),
            'privacy-consent' => __('Privacy consents obtained', 'rsl-licensing'),
            'no-malware' => __('Free from malware', 'rsl-licensing')
        );
    }
    
    public function get_disclaimer_options() {
        return array(
            'as-is' => __('Provided "as is"', 'rsl-licensing'),
            'no-warranty' => __('No warranties', 'rsl-licensing'),
            'no-liability' => __('No liability', 'rsl-licensing'),
            'no-indemnity' => __('No indemnification', 'rsl-licensing')
        );
    }
}