<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Robots {
    
    public function __construct() {
        add_filter('robots_txt', array($this, 'add_rsl_to_robots'), 10, 2);
        add_action('template_redirect', array($this, 'handle_robots_txt'));
    }
    
    public function add_rsl_to_robots($output, $public) {
        if (!get_option('rsl_enable_robots_txt', 1)) {
            return $output;
        }
        
        // Only add RSL directive if site is public
        if ('1' != $public) {
            return $output;
        }
        
        $global_license_id = get_option('rsl_global_license_id', 0);
        
        if ($global_license_id > 0) {
            $license_handler = new RSL_License();
            $license_data = $license_handler->get_license($global_license_id);
            
            if ($license_data && $license_data['active']) {
                $license_xml_url = $this->get_license_xml_url($global_license_id);
                $output .= "\n# RSL Licensing Directive\n";
                $output .= "License: " . $license_xml_url . "\n";
                
                // Add AI Preferences compatibility
                $ai_preferences = $this->get_ai_preferences_from_license($license_data);
                if (!empty($ai_preferences)) {
                    $output .= "\n# AI Content Usage Preferences\n";
                    $output .= "Content-Usage: " . $ai_preferences . "\n";
                }
                
                $output .= "\n";
            }
        }
        
        // Add RSL feed URL
        $rsl_feed_url = $this->get_rsl_feed_url();
        $output .= "# RSL License Feed\n";
        $output .= "# " . $rsl_feed_url . "\n";
        
        return $output;
    }
    
    public function handle_robots_txt() {
        // Only handle if this is a robots.txt request and we have custom handling enabled
        if (!is_robots() || !get_option('rsl_enable_robots_txt', 1)) {
            return;
        }
        
        // WordPress will handle the basic robots.txt generation
        // Our filter will add the RSL directives
    }
    
    private function get_license_xml_url($license_id) {
        return add_query_arg('rsl_license', $license_id, home_url());
    }
    
    private function get_rsl_feed_url() {
        return add_query_arg('rsl_feed', '1', home_url());
    }
    
    private function get_ai_preferences_from_license($license_data) {
        $preferences = array();
        
        // Convert RSL permits/prohibits to AI Preferences format
        $prohibited_usage = !empty($license_data['prohibits_usage']) ? 
            explode(',', $license_data['prohibits_usage']) : array();
        
        $permitted_usage = !empty($license_data['permits_usage']) ? 
            explode(',', $license_data['permits_usage']) : array();
        
        // Default AI preference mappings
        $ai_preference_map = array(
            'train-ai' => 'train-ai',
            'train-genai' => 'train-ai', // Map to generic train-ai
            'ai-use' => 'ai-use',
            'ai-summarize' => 'ai-summarize',
            'search' => 'search'
        );
        
        // If specific usage is prohibited, set to 'n'
        foreach ($prohibited_usage as $usage) {
            $usage = trim($usage);
            if (isset($ai_preference_map[$usage])) {
                $preferences[] = $ai_preference_map[$usage] . '=n';
            }
        }
        
        // If 'all' is prohibited, set all to 'n'
        if (in_array('all', $prohibited_usage)) {
            $preferences = array('train-ai=n', 'ai-use=n', 'ai-summarize=n', 'search=n');
        }
        
        // If only specific usage is permitted (and others are implicitly prohibited)
        if (!empty($permitted_usage) && !in_array('all', $permitted_usage)) {
            $all_usage_types = array_keys($ai_preference_map);
            $implicitly_prohibited = array_diff($all_usage_types, $permitted_usage);
            
            foreach ($implicitly_prohibited as $usage) {
                if (isset($ai_preference_map[$usage])) {
                    $pref = $ai_preference_map[$usage] . '=n';
                    if (!in_array($pref, $preferences)) {
                        $preferences[] = $pref;
                    }
                }
            }
        }
        
        return implode(', ', array_unique($preferences));
    }
    
    public function generate_robots_txt_with_rsl() {
        $output = "User-agent: *\n";
        $output .= "Allow: /\n";
        
        // Add standard WordPress disallows
        $output .= "Disallow: /wp-admin/\n";
        $output .= "Allow: /wp-admin/admin-ajax.php\n";
        
        // Add RSL licensing information
        $rsl_output = $this->add_rsl_to_robots('', '1');
        $output .= $rsl_output;
        
        return $output;
    }
    
    public function get_current_robots_txt() {
        // Get the current robots.txt content
        $robots_url = home_url('robots.txt');
        
        $response = wp_remote_get($robots_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'RSL-Robots-Checker/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_robots_txt_with_rsl();
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    public function validate_robots_txt() {
        $robots_content = $this->get_current_robots_txt();
        
        $validation = array(
            'has_license_directive' => false,
            'has_content_usage' => false,
            'license_url' => '',
            'ai_preferences' => '',
            'issues' => array()
        );
        
        // Check for License directive
        if (preg_match('/^License:\s*(.+)$/m', $robots_content, $matches)) {
            $validation['has_license_directive'] = true;
            $validation['license_url'] = trim($matches[1]);
            
            // Validate license URL
            if (!filter_var($validation['license_url'], FILTER_VALIDATE_URL)) {
                $validation['issues'][] = __('License URL is not valid', 'rsl-wp');
            }
        } else {
            $validation['issues'][] = __('No License directive found', 'rsl-wp');
        }
        
        // Check for Content-Usage directive
        if (preg_match('/^Content-Usage:\s*(.+)$/m', $robots_content, $matches)) {
            $validation['has_content_usage'] = true;
            $validation['ai_preferences'] = trim($matches[1]);
        }
        
        return $validation;
    }
}