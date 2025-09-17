<?php
/**
 * Automatic Terport Generator
 * 
 * Automatically generates comprehensive veterinary terports when the plugin updates
 * Uses existing SPARQL integration and OpenRouter API for content generation
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Automatic_Terport_Generator {
    
    private $sparql_integration;
    private $openrouter_api;
    private $generated_terports_option = 'terpedia_auto_generated_terports';
    private $current_version;
    
    public function __construct() {
        // Get current plugin version
        $this->current_version = defined('TERPEDIA_AI_VERSION') ? TERPEDIA_AI_VERSION : '3.11.64';
        
        // Initialize integrations
        if (class_exists('Terpedia_Terport_SPARQL_Integration')) {
            $this->sparql_integration = new Terpedia_Terport_SPARQL_Integration();
        }
        
        if (class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_api = new TerpediaOpenRouterHandler();
        }
        
        // CRITICAL FIX: Register cron callback in constructor so it's always available
        add_action('terpedia_generate_terports_background', array($this, 'generate_terports_background'), 10, 2);
        
        // Hook into plugin activation and updates
        add_action('terpedia_plugin_activated', array($this, 'generate_initial_terports'));
        add_action('terpedia_plugin_updated', array($this, 'generate_version_terports'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'show_generation_notices'));
        
        // AJAX handlers for manual triggering
        add_action('wp_ajax_terpedia_trigger_auto_generation', array($this, 'ajax_trigger_generation'));
        add_action('wp_ajax_terpedia_check_generation_status', array($this, 'ajax_check_status'));
    }
    
    /**
     * Generate initial terports on plugin activation
     */
    public function generate_initial_terports() {
        $this->schedule_background_generation('initial');
    }
    
    /**
     * Generate new terports when plugin is updated
     */
    public function generate_version_terports($old_version = null) {
        $generated_terports = get_option($this->generated_terports_option, array());
        
        // Check if we've already generated terports for this version
        if (isset($generated_terports[$this->current_version])) {
            return; // Already generated for this version
        }
        
        $this->schedule_background_generation('update', $old_version);
    }
    
    /**
     * Schedule background generation to prevent blocking activation
     */
    private function schedule_background_generation($type, $old_version = null) {
        // Use WordPress cron for background processing
        if (!wp_next_scheduled('terpedia_generate_terports_background')) {
            wp_schedule_single_event(time() + 30, 'terpedia_generate_terports_background', array($type, $old_version));
        }
        
        // Set generation status
        update_option('terpedia_terport_generation_status', array(
            'status' => 'scheduled',
            'type' => $type,
            'version' => $this->current_version,
            'started_at' => current_time('mysql')
        ));
    }
    
    /**
     * Background terport generation
     */
    public function generate_terports_background($type, $old_version = null) {
        // Update status
        update_option('terpedia_terport_generation_status', array(
            'status' => 'generating',
            'type' => $type,
            'version' => $this->current_version,
            'started_at' => current_time('mysql')
        ));
        
        try {
            $generated_count = 0;
            
            // Get terports to generate based on type
            $terports_config = $this->get_terports_configuration($type);
            
            foreach ($terports_config as $terport_config) {
                if ($this->should_generate_terport($terport_config, $type)) {
                    $result = $this->generate_single_terport($terport_config);
                    if ($result && !is_wp_error($result)) {
                        $generated_count++;
                        
                        // Store generation record
                        $this->record_generated_terport($result['terport_id'], $terport_config, $this->current_version);
                        
                        // Small delay to prevent server overload
                        sleep(2);
                    }
                }
            }
            
            // Update completion status
            update_option('terpedia_terport_generation_status', array(
                'status' => 'completed',
                'type' => $type,
                'version' => $this->current_version,
                'generated_count' => $generated_count,
                'completed_at' => current_time('mysql')
            ));
            
            // Update version tracking
            $generated_terports = get_option($this->generated_terports_option, array());
            $generated_terports[$this->current_version] = array(
                'generated_at' => current_time('mysql'),
                'count' => $generated_count,
                'type' => $type
            );
            update_option($this->generated_terports_option, $generated_terports);
            
        } catch (Exception $e) {
            // Log error and update status
            error_log('Terpedia Auto-Generation Error: ' . $e->getMessage());
            
            update_option('terpedia_terport_generation_status', array(
                'status' => 'error',
                'type' => $type,
                'version' => $this->current_version,
                'error' => $e->getMessage(),
                'failed_at' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Get terports configuration based on generation type
     */
    private function get_terports_configuration($type) {
        $base_terports = array(
            array(
                'title' => 'Physiological Doses for Dogs, Cats, and Horses',
                'type' => 'Veterinary Dosing Research',
                'research_questions' => array(
                    'What are the safe physiological dose ranges for major terpenes in dogs?',
                    'What are the safe physiological dose ranges for major terpenes in cats?',
                    'What are the safe physiological dose ranges for major terpenes in horses?',
                    'How do terpene metabolism rates differ between dogs, cats, and horses?',
                    'What are the species-specific contraindications for terpene administration?'
                ),
                'priority' => 1
            ),
            array(
                'title' => 'Topical Terpene Applications and Disease Treatment',
                'type' => 'Topical Terpene Applications',
                'research_questions' => array(
                    'Which terpenes have demonstrated efficacy in topical veterinary applications?',
                    'What are the optimal concentrations for topical terpene formulations?',
                    'How do different carrier oils affect terpene penetration in animal skin?',
                    'What dermatological conditions respond best to topical terpene treatment?',
                    'What are the safety considerations for topical terpene applications in pets?'
                ),
                'priority' => 2
            ),
            array(
                'title' => 'Oral Terpene Usage, Safety, and Dosing',
                'type' => 'Oral Terpene Safety & Dosing',
                'research_questions' => array(
                    'What are the bioavailability rates of major terpenes when administered orally?',
                    'How do different oral delivery methods affect terpene absorption?',
                    'What are the hepatic considerations for oral terpene administration?',
                    'Which terpenes have the best safety profiles for long-term oral use?',
                    'What drug interactions should be considered with oral terpene supplementation?'
                ),
                'priority' => 3
            ),
            array(
                'title' => 'Pain and Inflammation Management with Terpenes',
                'type' => 'Veterinary Pain & Inflammation Treatment',
                'research_questions' => array(
                    'Which terpenes demonstrate the strongest anti-inflammatory properties in veterinary studies?',
                    'What are the optimal dosing protocols for terpene-based pain management in dogs, cats, and horses?',
                    'How do β-caryophyllene, linalool, and limonene compare for chronic pain relief?',
                    'What are the mechanisms of action for terpene anti-inflammatory effects?',
                    'How do terpenes interact with conventional NSAIDs and pain medications?',
                    'What are the long-term safety considerations for terpene pain management protocols?'
                ),
                'priority' => 3
            ),
            array(
                'title' => 'Terpene-Based Seizure Management in Veterinary Medicine',
                'type' => 'Veterinary Seizure Management',
                'research_questions' => array(
                    'Which terpenes demonstrate anticonvulsant properties in veterinary studies?',
                    'How do linalool, limonene, and β-caryophyllene compare for seizure control?',
                    'What are the optimal dosing protocols for seizure management in dogs and cats?',
                    'How do terpenes interact with conventional anticonvulsant medications?',
                    'What safety monitoring is required for terpene-based seizure treatment?'
                ),
                'priority' => 4
            ),
            array(
                'title' => 'Cancer-Fighting Terpenes: Veterinary Oncology Applications',
                'type' => 'Veterinary Cancer Research',
                'research_questions' => array(
                    'Which terpenes show the strongest anticancer activity in veterinary oncology?',
                    'How do different cancer types respond to specific terpene treatments?',
                    'What are the optimal dosing strategies for anticancer terpenes in pets?',
                    'How do terpenes enhance conventional chemotherapy protocols?',
                    'What quality of life improvements are seen with terpene adjunct therapy?'
                ),
                'priority' => 5
            ),
            array(
                'title' => 'Anxiety and Behavioral Disorders: Terpene Interventions',
                'type' => 'Veterinary Behavioral Medicine',
                'research_questions' => array(
                    'Which terpenes are most effective for anxiety reduction in dogs and cats?',
                    'How do different terpenes affect stress hormones in veterinary patients?',
                    'What are the optimal protocols for terpene-based behavioral intervention?',
                    'How do terpenes compare to conventional anxiolytic medications?',
                    'What behavioral conditions respond best to terpene therapy?'
                ),
                'priority' => 6
            )
        );
        
        if ($type === 'initial') {
            // Generate all terports on initial activation
            return $base_terports;
        } else {
            // For updates, generate high-priority terports or new ones
            return array_filter($base_terports, function($terport) {
                return $terport['priority'] <= 3 || !$this->terport_exists($terport['title']);
            });
        }
    }
    
    /**
     * Check if we should generate a specific terport
     */
    private function should_generate_terport($config, $type) {
        // Don't regenerate existing terports unless it's a major update
        if ($this->terport_exists($config['title']) && $type !== 'major_update') {
            return false;
        }
        
        // Check if we have required integrations
        if (!$this->sparql_integration || !$this->openrouter_api) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a terport with the given title already exists
     */
    private function terport_exists($title) {
        $existing = get_posts(array(
            'post_type' => 'terpedia_terport',
            'post_status' => 'any',
            'title' => $title,
            'numberposts' => 1
        ));
        
        return !empty($existing);
    }
    
    /**
     * Generate a single terport using SPARQL integration
     */
    private function generate_single_terport($config) {
        if (!$this->sparql_integration) {
            return new WP_Error('missing_sparql', 'SPARQL integration not available');
        }
        
        try {
            // Use the existing SPARQL integration to generate comprehensive terport
            $result = $this->sparql_integration->generate_comprehensive_terport(
                $config['title'],
                $config['type'],
                $config['research_questions']
            );
            
            if (isset($result['error'])) {
                return new WP_Error('generation_failed', $result['error']);
            }
            
            // Update post meta with auto-generation information
            if (isset($result['terport_id'])) {
                update_post_meta($result['terport_id'], '_terpedia_auto_generated', true);
                update_post_meta($result['terport_id'], '_terpedia_generated_version', $this->current_version);
                update_post_meta($result['terport_id'], '_terpedia_generation_date', current_time('mysql'));
                update_post_meta($result['terport_id'], '_terpedia_terport_priority', $config['priority']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('generation_exception', $e->getMessage());
        }
    }
    
    /**
     * Record generated terport for tracking
     */
    private function record_generated_terport($terport_id, $config, $version) {
        global $wpdb;
        
        // Store in database for detailed tracking
        $wpdb->insert(
            $wpdb->prefix . 'terpedia_terports',
            array(
                'title' => $config['title'],
                'research_topic' => $config['type'],
                'status' => 'published',
                'visibility' => 'public',
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Show admin notices about terport generation
     */
    public function show_generation_notices() {
        $status = get_option('terpedia_terport_generation_status');
        
        if (!$status) {
            return;
        }
        
        $current_screen = get_current_screen();
        if (!$current_screen || $current_screen->base !== 'dashboard') {
            return; // Only show on dashboard
        }
        
        switch ($status['status']) {
            case 'scheduled':
                echo '<div class="notice notice-info is-dismissible">
                    <p><strong>Terpedia:</strong> Comprehensive veterinary terports are scheduled for generation. This may take a few minutes.</p>
                </div>';
                break;
                
            case 'generating':
                echo '<div class="notice notice-warning">
                    <p><strong>Terpedia:</strong> Currently generating comprehensive veterinary research terports using federated databases. Please be patient.</p>
                </div>';
                break;
                
            case 'completed':
                $count = isset($status['generated_count']) ? $status['generated_count'] : 0;
                echo '<div class="notice notice-success is-dismissible">
                    <p><strong>Terpedia:</strong> Successfully generated ' . $count . ' comprehensive veterinary terports! 
                    <a href="' . admin_url('edit.php?post_type=terpedia_terport') . '">View Terports</a></p>
                </div>';
                // Clear the notice after showing it
                delete_option('terpedia_terport_generation_status');
                break;
                
            case 'error':
                $error = isset($status['error']) ? $status['error'] : 'Unknown error';
                echo '<div class="notice notice-error is-dismissible">
                    <p><strong>Terpedia:</strong> Error generating terports: ' . esc_html($error) . '</p>
                </div>';
                break;
        }
    }
    
    /**
     * AJAX handler for manual generation triggering
     */
    public function ajax_trigger_generation() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'terpedia_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'manual';
        
        $this->schedule_background_generation($type);
        
        wp_send_json_success(array(
            'message' => 'Terport generation scheduled successfully',
            'status' => 'scheduled'
        ));
    }
    
    /**
     * AJAX handler for checking generation status
     */
    public function ajax_check_status() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Security improvement: verify nonce for all AJAX requests
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'terpedia_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $status = get_option('terpedia_terport_generation_status', array('status' => 'none'));
        
        wp_send_json_success($status);
    }
    
    /**
     * Get generation history
     */
    public function get_generation_history() {
        return get_option($this->generated_terports_option, array());
    }
    
    /**
     * Clear generation history (for debugging)
     */
    public function clear_generation_history() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        delete_option($this->generated_terports_option);
        delete_option('terpedia_terport_generation_status');
        
        return true;
    }
}