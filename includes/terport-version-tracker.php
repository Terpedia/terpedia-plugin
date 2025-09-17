<?php
/**
 * Terport Version Tracker
 * 
 * Tracks plugin versions and manages automatic terport generation
 * Prevents duplicate generation and provides detailed logging
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Terport_Version_Tracker {
    
    private $version_history_option = 'terpedia_version_history';
    private $generation_log_option = 'terpedia_generation_log';
    
    public function __construct() {
        add_action('init', array($this, 'check_version_change'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_terpedia_view_generation_log', array($this, 'ajax_view_generation_log'));
        add_action('wp_ajax_terpedia_force_regeneration', array($this, 'ajax_force_regeneration'));
    }
    
    /**
     * Check if plugin version has changed and trigger terport generation
     */
    public function check_version_change() {
        $current_version = defined('TERPEDIA_AI_VERSION') ? TERPEDIA_AI_VERSION : '3.11.64';
        $version_history = get_option($this->version_history_option, array());
        
        // Get the last recorded version
        $last_version = $this->get_last_version();
        
        if ($last_version !== $current_version) {
            // Version has changed, trigger terport generation
            $this->record_version_change($last_version, $current_version);
            do_action('terpedia_plugin_updated', $last_version);
        }
    }
    
    /**
     * Get the last recorded version
     */
    public function get_last_version() {
        $version_history = get_option($this->version_history_option, array());
        
        if (empty($version_history)) {
            return null;
        }
        
        // Get the most recent version entry
        $last_entry = end($version_history);
        return $last_entry['version'];
    }
    
    /**
     * Record version change
     */
    private function record_version_change($old_version, $new_version) {
        $version_history = get_option($this->version_history_option, array());
        
        $version_entry = array(
            'version' => $new_version,
            'previous_version' => $old_version,
            'changed_at' => current_time('mysql'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
            'is_initial' => empty($version_history)
        );
        
        $version_history[] = $version_entry;
        
        // Keep only last 50 version changes
        if (count($version_history) > 50) {
            $version_history = array_slice($version_history, -50);
        }
        
        update_option($this->version_history_option, $version_history);
        
        // Log the change
        $this->log_generation_event('version_change', array(
            'old_version' => $old_version,
            'new_version' => $new_version,
            'trigger' => $old_version ? 'update' : 'initial_activation'
        ));
    }
    
    /**
     * Log generation events for debugging and monitoring
     */
    public function log_generation_event($event_type, $data = array()) {
        $generation_log = get_option($this->generation_log_option, array());
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'data' => $data,
            'memory_usage' => memory_get_usage(true),
            'version' => defined('TERPEDIA_AI_VERSION') ? TERPEDIA_AI_VERSION : '3.11.64'
        );
        
        $generation_log[] = $log_entry;
        
        // Keep only last 200 log entries
        if (count($generation_log) > 200) {
            $generation_log = array_slice($generation_log, -200);
        }
        
        update_option($this->generation_log_option, $generation_log);
    }
    
    /**
     * Get generation statistics
     */
    public function get_generation_stats() {
        $generation_log = get_option($this->generation_log_option, array());
        $version_history = get_option($this->version_history_option, array());
        
        $stats = array(
            'total_versions' => count($version_history),
            'total_generation_events' => count($generation_log),
            'last_generation' => null,
            'successful_generations' => 0,
            'failed_generations' => 0,
            'total_terports_generated' => 0
        );
        
        foreach ($generation_log as $entry) {
            if ($entry['event_type'] === 'generation_completed') {
                $stats['successful_generations']++;
                $stats['total_terports_generated'] += isset($entry['data']['count']) ? $entry['data']['count'] : 0;
                $stats['last_generation'] = $entry['timestamp'];
            } elseif ($entry['event_type'] === 'generation_failed') {
                $stats['failed_generations']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if terports should be generated for current version
     */
    public function should_generate_for_version($version = null) {
        if (!$version) {
            $version = defined('TERPEDIA_AI_VERSION') ? TERPEDIA_AI_VERSION : '3.11.64';
        }
        
        $generated_terports = get_option('terpedia_auto_generated_terports', array());
        
        // Check if we've already generated for this version
        return !isset($generated_terports[$version]);
    }
    
    /**
     * Get versions that had terport generation
     */
    public function get_generation_versions() {
        return get_option('terpedia_auto_generated_terports', array());
    }
    
    /**
     * Add admin menu for version tracking
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-dashboard',
            'Terport Generation Log',
            'Generation Log',
            'manage_options',
            'terpedia-generation-log',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page for version tracking
     */
    public function render_admin_page() {
        $stats = $this->get_generation_stats();
        $version_history = get_option($this->version_history_option, array());
        $generation_versions = $this->get_generation_versions();
        $current_version = defined('TERPEDIA_AI_VERSION') ? TERPEDIA_AI_VERSION : '3.11.64';
        
        ?>
        <div class="wrap">
            <h1>Terpedia Terport Generation Log</h1>
            
            <div class="terpedia-generation-dashboard">
                <div class="generation-stats">
                    <h2>Generation Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Versions</h3>
                            <div class="stat-number"><?php echo $stats['total_versions']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Total Terports Generated</h3>
                            <div class="stat-number"><?php echo $stats['total_terports_generated']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Successful Generations</h3>
                            <div class="stat-number"><?php echo $stats['successful_generations']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Failed Generations</h3>
                            <div class="stat-number"><?php echo $stats['failed_generations']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="current-version-status">
                    <h2>Current Version Status</h2>
                    <p><strong>Current Version:</strong> <?php echo esc_html($current_version); ?></p>
                    <p><strong>Last Generation:</strong> <?php echo $stats['last_generation'] ? esc_html($stats['last_generation']) : 'Never'; ?></p>
                    <p><strong>Generation Status:</strong> 
                        <?php if ($this->should_generate_for_version()): ?>
                            <span style="color: orange;">Pending Generation</span>
                        <?php else: ?>
                            <span style="color: green;">Generated</span>
                        <?php endif; ?>
                    </p>
                    
                    <div class="generation-actions">
                        <button type="button" class="button button-primary" id="trigger-manual-generation">
                            Trigger Manual Generation
                        </button>
                        <button type="button" class="button" id="refresh-status">
                            Refresh Status
                        </button>
                        <?php if (current_user_can('manage_options')): ?>
                        <button type="button" class="button button-secondary" id="force-regeneration">
                            Force Regenerate All
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="version-history">
                    <h2>Version History</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Previous Version</th>
                                <th>Changed At</th>
                                <th>Type</th>
                                <th>Terports Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($version_history)): ?>
                            <tr>
                                <td colspan="5">No version history available</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($version_history) as $entry): ?>
                                <tr>
                                    <td><?php echo esc_html($entry['version']); ?></td>
                                    <td><?php echo esc_html($entry['previous_version'] ?: 'N/A'); ?></td>
                                    <td><?php echo esc_html($entry['changed_at']); ?></td>
                                    <td><?php echo $entry['is_initial'] ? 'Initial' : 'Update'; ?></td>
                                    <td>
                                        <?php if (isset($generation_versions[$entry['version']])): ?>
                                            <span style="color: green;">
                                                <?php echo esc_html($generation_versions[$entry['version']]['count']); ?> terports
                                            </span>
                                        <?php else: ?>
                                            <span style="color: orange;">Not generated</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="generation-log">
                    <h2>Recent Generation Events</h2>
                    <button type="button" class="button" id="view-full-log">View Full Log</button>
                    <div id="generation-log-content">
                        <p>Click "View Full Log" to see detailed generation events</p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .terpedia-generation-dashboard {
            max-width: 1200px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2271b1;
        }
        
        .generation-actions {
            margin: 15px 0;
        }
        
        .generation-actions button {
            margin-right: 10px;
        }
        
        .current-version-status,
        .version-history,
        .generation-log {
            margin: 30px 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        
        #generation-log-content {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 10px;
            background: #f9f9f9;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Manual generation trigger
            $('#trigger-manual-generation').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Triggering...');
                
                $.post(ajaxurl, {
                    action: 'terpedia_trigger_auto_generation',
                    type: 'manual',
                    nonce: '<?php echo wp_create_nonce('terpedia_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Terport generation triggered successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Trigger Manual Generation');
                });
            });
            
            // Force regeneration
            $('#force-regeneration').on('click', function() {
                if (!confirm('This will regenerate ALL terports. Continue?')) {
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('Force Regenerating...');
                
                $.post(ajaxurl, {
                    action: 'terpedia_force_regeneration',
                    nonce: '<?php echo wp_create_nonce('terpedia_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Force regeneration triggered!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Force Regenerate All');
                });
            });
            
            // View full log
            $('#view-full-log').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Loading...');
                
                $.post(ajaxurl, {
                    action: 'terpedia_view_generation_log'
                }, function(response) {
                    if (response.success) {
                        $('#generation-log-content').html(response.data);
                    } else {
                        $('#generation-log-content').html('<p style="color: red;">Error loading log</p>');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('View Full Log');
                });
            });
            
            // Refresh status
            $('#refresh-status').on('click', function() {
                location.reload();
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for viewing full generation log
     */
    public function ajax_view_generation_log() {
        // Security improvements: add capability check and nonce verification
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'terpedia_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $generation_log = get_option($this->generation_log_option, array());
        
        if (empty($generation_log)) {
            wp_send_json_success('<p>No generation events logged yet.</p>');
        }
        
        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr><th>Timestamp</th><th>Event</th><th>Data</th><th>Memory Usage</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach (array_reverse($generation_log) as $entry) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($entry['timestamp']) . '</td>';
            $html .= '<td>' . esc_html($entry['event_type']) . '</td>';
            $html .= '<td><pre>' . esc_html(json_encode($entry['data'], JSON_PRETTY_PRINT)) . '</pre></td>';
            $html .= '<td>' . esc_html(size_format($entry['memory_usage'])) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        wp_send_json_success($html);
    }
    
    /**
     * AJAX handler for force regeneration
     */
    public function ajax_force_regeneration() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'terpedia_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Clear generation history to force regeneration
        delete_option('terpedia_auto_generated_terports');
        
        // Trigger generation
        do_action('terpedia_plugin_updated', null);
        
        $this->log_generation_event('force_regeneration', array(
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));
        
        wp_send_json_success(array(
            'message' => 'Force regeneration triggered successfully'
        ));
    }
    
    /**
     * Clean up old logs
     */
    public function cleanup_old_logs() {
        $generation_log = get_option($this->generation_log_option, array());
        $version_history = get_option($this->version_history_option, array());
        
        // Remove logs older than 90 days
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        $generation_log = array_filter($generation_log, function($entry) use ($cutoff_date) {
            return $entry['timestamp'] > $cutoff_date;
        });
        
        $version_history = array_filter($version_history, function($entry) use ($cutoff_date) {
            return $entry['changed_at'] > $cutoff_date;
        });
        
        update_option($this->generation_log_option, $generation_log);
        update_option($this->version_history_option, $version_history);
    }
}

// Initialize version tracker
new Terpedia_Terport_Version_Tracker();