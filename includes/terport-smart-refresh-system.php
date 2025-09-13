<?php
/**
 * Terport Smart Refresh System
 * 
 * Intelligently updates Terports with new information while preserving existing content.
 * Uses WordPress cron for scheduling and AI to identify and integrate only new findings.
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Terport_Smart_Refresh_System {
    
    private $openrouter_api;
    
    public function __construct() {
        // WordPress cron hooks
        add_action('terpedia_terport_smart_refresh', array($this, 'execute_smart_refresh'));
        add_action('init', array($this, 'schedule_refresh_checks'));
        
        // Admin interface
        add_action('add_meta_boxes', array($this, 'add_refresh_meta_boxes'));
        add_action('save_post', array($this, 'save_refresh_settings'));
        add_action('wp_ajax_terpedia_trigger_manual_refresh', array($this, 'handle_manual_refresh'));
        
        // Daily cron to check for due refreshes
        if (!wp_next_scheduled('terpedia_daily_refresh_check')) {
            wp_schedule_event(time(), 'daily', 'terpedia_daily_refresh_check');
        }
        add_action('terpedia_daily_refresh_check', array($this, 'check_due_refreshes'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Add refresh settings meta box
     */
    public function add_refresh_meta_boxes() {
        add_meta_box(
            'terpedia_terport_refresh_settings',
            '🔄 Smart Refresh Settings',
            array($this, 'refresh_settings_callback'),
            'terport',
            'side',
            'default'
        );
        
        add_meta_box(
            'terpedia_terport_refresh_log',
            '📋 Refresh History',
            array($this, 'refresh_log_callback'),
            'terport',
            'normal',
            'low'
        );
    }
    
    /**
     * Refresh settings meta box callback
     */
    public function refresh_settings_callback($post) {
        wp_nonce_field('terpedia_refresh_nonce', 'terpedia_refresh_nonce');
        
        $refresh_enabled = get_post_meta($post->ID, '_terport_refresh_enabled', true);
        $refresh_frequency = get_post_meta($post->ID, '_terport_refresh_frequency', true) ?: 'monthly';
        $last_refresh = get_post_meta($post->ID, '_terport_last_refresh', true);
        $next_refresh = get_post_meta($post->ID, '_terport_next_refresh', true);
        
        ?>
        <div class="terport-refresh-settings">
            <style>
                .refresh-setting {
                    margin-bottom: 15px;
                    padding: 12px;
                    background: #f8f9fa;
                    border-radius: 4px;
                }
                
                .refresh-frequency-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                    margin-top: 8px;
                }
                
                .frequency-option {
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    text-align: center;
                    cursor: pointer;
                    font-size: 12px;
                }
                
                .frequency-option.selected {
                    border-color: #ff69b4;
                    background: #fff5f8;
                    font-weight: bold;
                }
                
                .refresh-status {
                    background: #e7f3ff;
                    border: 1px solid #b8daff;
                    padding: 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    margin-top: 10px;
                }
                
                .manual-refresh-btn {
                    width: 100%;
                    padding: 10px;
                    background: #ff69b4;
                    border: none;
                    color: white;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: bold;
                    margin-top: 10px;
                }
                
                .manual-refresh-btn:hover {
                    background: #ff1493;
                }
                
                .manual-refresh-btn:disabled {
                    background: #ccc;
                    cursor: not-allowed;
                }
            </style>
            
            <div class="refresh-setting">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <input type="checkbox" name="terport_refresh_enabled" value="1" <?php checked($refresh_enabled, '1'); ?>>
                    🔄 Enable Smart Refresh
                </label>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                    Automatically update this Terport with new research findings
                </p>
            </div>
            
            <?php if ($refresh_enabled): ?>
            <div class="refresh-setting">
                <label style="font-weight: 600; display: block; margin-bottom: 8px;">📅 Refresh Frequency</label>
                <div class="refresh-frequency-grid">
                    <div class="frequency-option <?php echo $refresh_frequency === 'daily' ? 'selected' : ''; ?>" data-freq="daily">
                        Daily
                    </div>
                    <div class="frequency-option <?php echo $refresh_frequency === 'weekly' ? 'selected' : ''; ?>" data-freq="weekly">
                        Weekly  
                    </div>
                    <div class="frequency-option <?php echo $refresh_frequency === 'monthly' ? 'selected' : ''; ?>" data-freq="monthly">
                        Monthly
                    </div>
                    <div class="frequency-option <?php echo $refresh_frequency === 'quarterly' ? 'selected' : ''; ?>" data-freq="quarterly">
                        Quarterly
                    </div>
                    <div class="frequency-option <?php echo $refresh_frequency === 'biannually' ? 'selected' : ''; ?>" data-freq="biannually">
                        Twice/Year
                    </div>
                    <div class="frequency-option <?php echo $refresh_frequency === 'yearly' ? 'selected' : ''; ?>" data-freq="yearly">
                        Yearly
                    </div>
                </div>
                <input type="hidden" name="terport_refresh_frequency" value="<?php echo esc_attr($refresh_frequency); ?>">
            </div>
            
            <div class="refresh-status">
                <strong>📊 Refresh Status</strong><br>
                <?php if ($last_refresh): ?>
                    Last updated: <?php echo date('M j, Y g:i A', $last_refresh); ?><br>
                <?php else: ?>
                    Never refreshed<br>
                <?php endif; ?>
                
                <?php if ($next_refresh): ?>
                    Next update: <?php echo date('M j, Y', $next_refresh); ?>
                <?php endif; ?>
            </div>
            
            <button type="button" class="manual-refresh-btn" id="manualRefreshBtn" data-terport-id="<?php echo $post->ID; ?>">
                🚀 Refresh Now
            </button>
            <div id="refreshStatus" style="margin-top: 8px; font-size: 12px;"></div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Frequency selection
            $('.frequency-option').on('click', function() {
                $('.frequency-option').removeClass('selected');
                $(this).addClass('selected');
                $('input[name="terport_refresh_frequency"]').val($(this).data('freq'));
            });
            
            // Manual refresh
            $('#manualRefreshBtn').on('click', function() {
                var $btn = $(this);
                var $status = $('#refreshStatus');
                var terportId = $btn.data('terport-id');
                
                $btn.prop('disabled', true).text('🔄 Refreshing...');
                $status.html('<span style="color: #007cba;">Analyzing current content and searching for updates...</span>');
                
                $.post(ajaxurl, {
                    action: 'terpedia_trigger_manual_refresh',
                    terport_id: terportId,
                    nonce: '<?php echo wp_create_nonce("manual_refresh_nonce"); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        $status.html('<span style="color: #46b450;">✅ ' + response.data.message + '</span>');
                        if (response.data.changes_made) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        }
                    } else {
                        $status.html('<span style="color: #dc3232;">❌ Error: ' + response.data + '</span>');
                    }
                })
                .fail(function() {
                    $status.html('<span style="color: #dc3232;">❌ Network error occurred</span>');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('🚀 Refresh Now');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Refresh log meta box callback
     */
    public function refresh_log_callback($post) {
        $refresh_log = get_post_meta($post->ID, '_terport_refresh_log', true) ?: array();
        $refresh_log = array_slice(array_reverse($refresh_log), 0, 10); // Last 10 entries
        
        ?>
        <div class="terport-refresh-log">
            <style>
                .refresh-log-entry {
                    padding: 10px;
                    margin-bottom: 10px;
                    background: #f8f9fa;
                    border-left: 4px solid #007cba;
                    border-radius: 0 4px 4px 0;
                }
                
                .log-entry-header {
                    font-weight: bold;
                    margin-bottom: 5px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .log-entry-type {
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 11px;
                    color: white;
                }
                
                .log-type-auto { background: #007cba; }
                .log-type-manual { background: #ff69b4; }
                .log-type-error { background: #dc3232; }
                
                .log-entry-changes {
                    font-size: 13px;
                    color: #555;
                    margin-top: 5px;
                }
                
                .log-empty {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                    font-style: italic;
                }
            </style>
            
            <?php if (empty($refresh_log)): ?>
                <div class="log-empty">
                    📝 No refresh history yet<br>
                    <small>Refresh logs will appear here once the smart refresh system runs</small>
                </div>
            <?php else: ?>
                <?php foreach ($refresh_log as $entry): ?>
                    <div class="refresh-log-entry">
                        <div class="log-entry-header">
                            <span><?php echo date('M j, Y g:i A', $entry['timestamp']); ?></span>
                            <span class="log-entry-type log-type-<?php echo esc_attr($entry['type']); ?>">
                                <?php echo strtoupper($entry['type']); ?>
                            </span>
                        </div>
                        <div style="font-size: 13px; color: #333;">
                            <?php echo esc_html($entry['message']); ?>
                        </div>
                        <?php if (!empty($entry['changes'])): ?>
                            <div class="log-entry-changes">
                                <strong>Changes made:</strong>
                                <ul style="margin: 5px 0 0 20px;">
                                    <?php foreach ($entry['changes'] as $change): ?>
                                        <li><?php echo esc_html($change); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save refresh settings
     */
    public function save_refresh_settings($post_id) {
        if (!isset($_POST['terpedia_refresh_nonce']) || 
            !wp_verify_nonce($_POST['terpedia_refresh_nonce'], 'terpedia_refresh_nonce')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'terport') {
            return;
        }
        
        // Save refresh enabled
        $refresh_enabled = isset($_POST['terport_refresh_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_terport_refresh_enabled', $refresh_enabled);
        
        // Save frequency
        if (isset($_POST['terport_refresh_frequency'])) {
            $frequency = sanitize_text_field($_POST['terport_refresh_frequency']);
            update_post_meta($post_id, '_terport_refresh_frequency', $frequency);
            
            // Calculate next refresh time
            if ($refresh_enabled === '1') {
                $next_refresh = $this->calculate_next_refresh_time($frequency);
                update_post_meta($post_id, '_terport_next_refresh', $next_refresh);
            } else {
                delete_post_meta($post_id, '_terport_next_refresh');
            }
        }
    }
    
    /**
     * Calculate next refresh time based on frequency
     */
    private function calculate_next_refresh_time($frequency) {
        $base_time = time();
        
        switch ($frequency) {
            case 'daily':
                return $base_time + (24 * 60 * 60);
            case 'weekly':
                return $base_time + (7 * 24 * 60 * 60);
            case 'monthly':
                return $base_time + (30 * 24 * 60 * 60);
            case 'quarterly':
                return $base_time + (90 * 24 * 60 * 60);
            case 'biannually':
                return $base_time + (180 * 24 * 60 * 60);
            case 'yearly':
                return $base_time + (365 * 24 * 60 * 60);
            default:
                return $base_time + (30 * 24 * 60 * 60); // Default to monthly
        }
    }
    
    /**
     * Check for Terports due for refresh
     */
    public function check_due_refreshes() {
        $current_time = time();
        
        // Find Terports due for refresh
        $due_terports = get_posts(array(
            'post_type' => 'terport',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_terport_refresh_enabled',
                    'value' => '1'
                ),
                array(
                    'key' => '_terport_next_refresh',
                    'value' => $current_time,
                    'compare' => '<='
                )
            ),
            'posts_per_page' => 10 // Limit to prevent overload
        ));
        
        foreach ($due_terports as $terport) {
            wp_schedule_single_event(time() + 60, 'terpedia_terport_smart_refresh', array($terport->ID));
        }
    }
    
    /**
     * Execute smart refresh for a specific Terport
     */
    public function execute_smart_refresh($terport_id) {
        $terport = get_post($terport_id);
        if (!$terport || $terport->post_type !== 'terport') {
            return;
        }
        
        $this->log_refresh_activity($terport_id, 'auto', 'Starting automatic smart refresh...');
        
        try {
            $result = $this->perform_intelligent_update($terport_id);
            
            if (is_wp_error($result)) {
                $this->log_refresh_activity($terport_id, 'error', 'Refresh failed: ' . $result->get_error_message());
                return;
            }
            
            // Update refresh timestamps
            update_post_meta($terport_id, '_terport_last_refresh', time());
            $frequency = get_post_meta($terport_id, '_terport_refresh_frequency', true) ?: 'monthly';
            $next_refresh = $this->calculate_next_refresh_time($frequency);
            update_post_meta($terport_id, '_terport_next_refresh', $next_refresh);
            
            $this->log_refresh_activity($terport_id, 'auto', 'Smart refresh completed successfully', $result['changes']);
            
        } catch (Exception $e) {
            $this->log_refresh_activity($terport_id, 'error', 'Refresh exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Perform intelligent update that only adds new information
     */
    private function perform_intelligent_update($terport_id) {
        $terport = get_post($terport_id);
        $template_type = get_post_meta($terport_id, '_terpedia_field_template_type', true);
        $last_refresh = get_post_meta($terport_id, '_terport_last_refresh', true) ?: get_post_time('U', false, $terport_id);
        
        if (!$template_type) {
            return new WP_Error('no_template', 'No template type found');
        }
        
        // Get current structured data
        $field_system = new Terpedia_Field_Based_Template_System();
        $field_definitions = $field_system->get_template_field_definitions($template_type);
        $current_content = array();
        
        foreach ($field_definitions as $field_name => $field_config) {
            $content = get_post_meta($terport_id, '_structured_field_' . $field_name, true);
            $current_content[$field_name] = array(
                'label' => $field_config['label'],
                'content' => $content,
                'last_updated' => $last_refresh
            );
        }
        
        // Generate intelligent update request
        $update_result = $this->generate_intelligent_update($terport, $template_type, $current_content, $last_refresh);
        
        if (is_wp_error($update_result)) {
            return $update_result;
        }
        
        // Apply updates to fields
        $changes_made = array();
        if (!empty($update_result['field_updates'])) {
            foreach ($update_result['field_updates'] as $field_name => $new_content) {
                $old_content = get_post_meta($terport_id, '_structured_field_' . $field_name, true);
                if ($old_content !== $new_content) {
                    update_post_meta($terport_id, '_structured_field_' . $field_name, wp_kses_post($new_content));
                    $changes_made[] = "Updated {$current_content[$field_name]['label']} section";
                }
            }
        }
        
        return array(
            'changes' => $changes_made,
            'summary' => $update_result['update_summary'] ?? 'Content updated with latest research'
        );
    }
    
    /**
     * Generate intelligent update using AI
     */
    private function generate_intelligent_update($terport, $template_type, $current_content, $last_refresh_timestamp) {
        $last_refresh_date = date('Y-m-d', $last_refresh_timestamp);
        $current_date = date('Y-m-d');
        
        $system_prompt = "You are an expert scientific researcher tasked with updating a terpene research report with ONLY NEW INFORMATION that has become available since the last update.

CRITICAL INSTRUCTIONS:
1. DO NOT rewrite or rephrase existing content
2. ONLY add genuinely NEW information published after {$last_refresh_date}
3. Preserve all existing structure, formatting, and references
4. Add new findings seamlessly to existing sections
5. If no significant new information is available, indicate this clearly
6. Focus on peer-reviewed research, clinical studies, and credible scientific sources
7. Add proper citations with dates for new information

The report was last updated on: {$last_refresh_date}
Today's date: {$current_date}
Report title: {$terport->post_title}
Template type: {$template_type}

Current content structure:
" . $this->format_current_content_for_ai($current_content);

        $user_prompt = "Please review the current content and identify any NEW scientific information, research findings, clinical studies, or regulatory updates about this topic that have been published since {$last_refresh_date}. 

For each section, either:
1. Add new information if available (preserving existing content)
2. Indicate \"No significant updates since {$last_refresh_date}\" if no new information

Focus on:
- Recent clinical trials and studies
- New regulatory approvals or changes  
- Updated safety information
- Novel therapeutic applications
- Recent market developments
- Emerging research trends

Be conservative - only include information you're confident is both new and credible.";

        // Build response schema
        $schema_properties = array();
        foreach ($current_content as $field_name => $field_data) {
            $schema_properties[$field_name] = array(
                'type' => 'string',
                'description' => "Updated content for {$field_data['label']} - ADD new information to existing content, don't rewrite"
            );
        }
        
        $schema_properties['update_summary'] = array(
            'type' => 'string',
            'description' => 'Brief summary of what new information was added, or indication that no updates were needed'
        );
        
        $schema_properties['has_significant_updates'] = array(
            'type' => 'boolean',
            'description' => 'Whether significant new information was found and added'
        );
        
        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'intelligent_terport_update',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => $schema_properties,
                    'required' => array_merge(array_keys($current_content), array('update_summary', 'has_significant_updates')),
                    'additionalProperties' => false
                )
            )
        );
        
        $response = $this->openrouter_api->make_api_request($system_prompt, $user_prompt, $response_format);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Extract field updates (excluding metadata)
        $field_updates = array();
        foreach ($current_content as $field_name => $field_data) {
            if (isset($response[$field_name])) {
                $field_updates[$field_name] = $response[$field_name];
            }
        }
        
        return array(
            'field_updates' => $field_updates,
            'update_summary' => $response['update_summary'] ?? 'Content updated',
            'has_significant_updates' => $response['has_significant_updates'] ?? false
        );
    }
    
    /**
     * Format current content for AI analysis
     */
    private function format_current_content_for_ai($current_content) {
        $formatted = "";
        foreach ($current_content as $field_name => $field_data) {
            $content_preview = strip_tags($field_data['content']);
            $content_preview = substr($content_preview, 0, 500) . (strlen($content_preview) > 500 ? '...' : '');
            $last_updated = date('Y-m-d', $field_data['last_updated']);
            
            $formatted .= "- {$field_data['label']} (last updated: {$last_updated}):\n";
            $formatted .= "  " . ($content_preview ?: '[Empty section]') . "\n\n";
        }
        return $formatted;
    }
    
    /**
     * Handle manual refresh trigger
     */
    public function handle_manual_refresh() {
        check_ajax_referer('manual_refresh_nonce', 'nonce');
        
        $terport_id = intval($_POST['terport_id'] ?? 0);
        
        if (!current_user_can('edit_post', $terport_id)) {
            wp_send_json_error('Access denied');
        }
        
        $this->log_refresh_activity($terport_id, 'manual', 'Manual refresh triggered by user');
        
        $result = $this->perform_intelligent_update($terport_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Update last refresh time
        update_post_meta($terport_id, '_terport_last_refresh', time());
        
        $message = empty($result['changes']) 
            ? 'No new information found since last update' 
            : 'Successfully updated with ' . count($result['changes']) . ' changes';
            
        $this->log_refresh_activity($terport_id, 'manual', $message, $result['changes']);
        
        wp_send_json_success(array(
            'message' => $message,
            'changes_made' => !empty($result['changes']),
            'changes' => $result['changes']
        ));
    }
    
    /**
     * Log refresh activity
     */
    private function log_refresh_activity($terport_id, $type, $message, $changes = array()) {
        $log = get_post_meta($terport_id, '_terport_refresh_log', true) ?: array();
        
        $log[] = array(
            'timestamp' => time(),
            'type' => $type,
            'message' => $message,
            'changes' => $changes
        );
        
        // Keep only last 50 entries
        $log = array_slice($log, -50);
        
        update_post_meta($terport_id, '_terport_refresh_log', $log);
    }
    
    /**
     * Schedule refresh checks
     */
    public function schedule_refresh_checks() {
        // This runs on init to ensure the cron is scheduled
    }
}

// Initialize the smart refresh system
new Terpedia_Terport_Smart_Refresh_System();