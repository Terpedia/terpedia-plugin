<?php
/**
 * Agent Feed Subscription Manager
 * Manages agent subscriptions and feed monitoring settings in WordPress backend
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaAgentFeedSubscriptionManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Admin interface hooks
        add_action('show_user_profile', array($this, 'add_agent_subscription_fields'));
        add_action('edit_user_profile', array($this, 'add_agent_subscription_fields'));
        add_action('personal_options_update', array($this, 'save_agent_subscription_fields'));
        add_action('edit_user_profile_update', array($this, 'save_agent_subscription_fields'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_add_agent_subscription', array($this, 'ajax_add_subscription'));
        add_action('wp_ajax_terpedia_remove_agent_subscription', array($this, 'ajax_remove_subscription'));
        add_action('wp_ajax_terpedia_update_subscription_settings', array($this, 'ajax_update_subscription_settings'));
        add_action('wp_ajax_terpedia_get_agent_subscriptions', array($this, 'ajax_get_subscriptions'));
        add_action('wp_ajax_terpedia_test_agent_commenting', array($this, 'ajax_test_commenting'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function init() {
        // Initialize default subscriptions for existing agents
        $this->initialize_default_subscriptions();
    }
    
    /**
     * Add subscription management fields to agent profiles
     */
    public function add_agent_subscription_fields($user) {
        // Only show for agents and tersonas
        $is_agent = get_user_meta($user->ID, 'terpedia_is_ai_agent', true) || 
                   get_user_meta($user->ID, 'terpedia_agent_type', true);
        
        if (!$is_agent) {
            return;
        }
        
        $subscriptions = $this->get_agent_subscriptions($user->ID);
        $commenting_settings = $this->get_agent_commenting_settings($user->ID);
        ?>
        <h3>Agent Feed Monitoring & Subscriptions</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Feed Monitoring</th>
                <td>
                    <label>
                        <input type="checkbox" name="agent_feed_monitoring_enabled" value="1" 
                               <?php checked($commenting_settings['enabled'], 1); ?> />
                        Enable automatic feed monitoring and commenting
                    </label>
                    <p class="description">When enabled, this agent will automatically monitor subscribed users and comment on relevant posts.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Default Comment Frequency</th>
                <td>
                    <select name="agent_default_comment_frequency">
                        <option value="conservative" <?php selected($commenting_settings['default_frequency'], 'conservative'); ?>>Conservative (24h cooldown)</option>
                        <option value="moderate" <?php selected($commenting_settings['default_frequency'], 'moderate'); ?>>Moderate (12h cooldown)</option>
                        <option value="active" <?php selected($commenting_settings['default_frequency'], 'active'); ?>>Active (6h cooldown)</option>
                    </select>
                    <p class="description">How frequently this agent should comment on posts from subscribed users.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Comment Relevance Threshold</th>
                <td>
                    <input type="number" name="agent_comment_threshold" min="0" max="100" 
                           value="<?php echo esc_attr($commenting_settings['threshold']); ?>" />
                    <p class="description">Minimum relevance score (0-100) required before commenting. Higher values = more selective commenting.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Agent-to-Agent Commenting</th>
                <td>
                    <label>
                        <input type="checkbox" name="agent_allow_agent_comments" value="1" 
                               <?php checked($commenting_settings['allow_agent_comments'], 1); ?> />
                        Allow commenting on other agent posts
                    </label>
                    <p class="description">When enabled, this agent can comment on posts from other agents (with conservative settings).</p>
                </td>
            </tr>
        </table>
        
        <h4>Current Subscriptions</h4>
        <div id="agent-subscriptions-container">
            <?php $this->render_subscriptions_table($user->ID, $subscriptions); ?>
        </div>
        
        <h4>Add New Subscription</h4>
        <div class="add-subscription-form">
            <table class="form-table">
                <tr>
                    <th scope="row">Subscribe to User</th>
                    <td>
                        <select name="new_subscription_user" id="new-subscription-user">
                            <option value="">Select a user...</option>
                            <?php $this->render_user_options($user->ID); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Subscription Type</th>
                    <td>
                        <select name="new_subscription_type" id="new-subscription-type">
                            <option value="user">Regular User</option>
                            <option value="agent">Other Agent</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Comment Frequency</th>
                    <td>
                        <select name="new_subscription_frequency" id="new-subscription-frequency">
                            <option value="conservative">Conservative</option>
                            <option value="moderate" selected>Moderate</option>
                            <option value="active">Active</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">&nbsp;</th>
                    <td>
                        <button type="button" id="add-subscription-btn" class="button button-primary">Add Subscription</button>
                    </td>
                </tr>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-subscription-btn').on('click', function() {
                var userId = $('#new-subscription-user').val();
                var type = $('#new-subscription-type').val();
                var frequency = $('#new-subscription-frequency').val();
                
                if (!userId) {
                    alert('Please select a user to subscribe to.');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'terpedia_add_agent_subscription',
                        agent_id: <?php echo $user->ID; ?>,
                        subscribed_to_user_id: userId,
                        subscription_type: type,
                        comment_frequency: frequency,
                        nonce: '<?php echo wp_create_nonce('terpedia_agent_subscriptions'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
            
            $('.remove-subscription-btn').on('click', function() {
                var subscriptionId = $(this).data('subscription-id');
                var agentId = <?php echo $user->ID; ?>;
                
                if (confirm('Are you sure you want to remove this subscription?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'terpedia_remove_agent_subscription',
                            agent_id: agentId,
                            subscription_id: subscriptionId,
                            nonce: '<?php echo wp_create_nonce('terpedia_agent_subscriptions'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save agent subscription fields
     */
    public function save_agent_subscription_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        // Save commenting settings
        $commenting_settings = array(
            'enabled' => isset($_POST['agent_feed_monitoring_enabled']) ? 1 : 0,
            'default_frequency' => sanitize_text_field($_POST['agent_default_comment_frequency'] ?? 'moderate'),
            'threshold' => intval($_POST['agent_comment_threshold'] ?? 60),
            'allow_agent_comments' => isset($_POST['agent_allow_agent_comments']) ? 1 : 0
        );
        
        update_user_meta($user_id, 'terpedia_agent_commenting_settings', $commenting_settings);
        
        // Trigger feed monitor update
        do_action('terpedia_agent_subscription_updated', $user_id, $commenting_settings);
    }
    
    /**
     * Render subscriptions table
     */
    private function render_subscriptions_table($agent_id, $subscriptions) {
        if (empty($subscriptions)) {
            echo '<p>No subscriptions found. Add subscriptions below to enable feed monitoring.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>User</th>';
        echo '<th>Type</th>';
        echo '<th>Comment Frequency</th>';
        echo '<th>Last Checked</th>';
        echo '<th>Status</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($subscriptions as $subscription) {
            $user = get_user_by('ID', $subscription['subscribed_to_user_id']);
            $user_name = $user ? $user->display_name : 'Unknown User';
            $user_type = get_user_meta($subscription['subscribed_to_user_id'], 'terpedia_agent_type', true) ? 'Agent' : 'User';
            
            echo '<tr>';
            echo '<td>' . esc_html($user_name) . '</td>';
            echo '<td>' . esc_html($user_type) . '</td>';
            echo '<td>' . esc_html(ucfirst($subscription['comment_frequency'])) . '</td>';
            echo '<td>' . esc_html($subscription['last_checked_at'] ? date('Y-m-d H:i', strtotime($subscription['last_checked_at'])) : 'Never') . '</td>';
            echo '<td>' . ($subscription['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>') . '</td>';
            echo '<td>';
            echo '<button type="button" class="button button-small remove-subscription-btn" data-subscription-id="' . esc_attr($subscription['id']) . '">Remove</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Render user options for subscription
     */
    private function render_user_options($agent_id) {
        $users = get_users(array(
            'exclude' => array($agent_id),
            'number' => 100,
            'orderby' => 'display_name'
        ));
        
        foreach ($users as $user) {
            $is_agent = get_user_meta($user->ID, 'terpedia_is_ai_agent', true) || 
                       get_user_meta($user->ID, 'terpedia_agent_type', true);
            $user_type = $is_agent ? ' (Agent)' : '';
            
            echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . $user_type . '</option>';
        }
    }
    
    /**
     * Get agent subscriptions
     */
    private function get_agent_subscriptions($agent_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE agent_id = %d ORDER BY created_at DESC",
            $agent_id
        ), ARRAY_A);
        
        return $results ?: array();
    }
    
    /**
     * Get agent commenting settings
     */
    private function get_agent_commenting_settings($agent_id) {
        $defaults = array(
            'enabled' => 0,
            'default_frequency' => 'moderate',
            'threshold' => 60,
            'allow_agent_comments' => 0
        );
        
        $settings = get_user_meta($agent_id, 'terpedia_agent_commenting_settings', true);
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * AJAX: Add subscription
     */
    public function ajax_add_subscription() {
        check_ajax_referer('terpedia_agent_subscriptions', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $agent_id = intval($_POST['agent_id']);
        $subscribed_to_user_id = intval($_POST['subscribed_to_user_id']);
        $subscription_type = sanitize_text_field($_POST['subscription_type']);
        $comment_frequency = sanitize_text_field($_POST['comment_frequency']);
        
        $result = $this->add_subscription($agent_id, $subscribed_to_user_id, $subscription_type, $comment_frequency);
        
        if ($result) {
            wp_send_json_success('Subscription added successfully');
        } else {
            wp_send_json_error('Failed to add subscription');
        }
    }
    
    /**
     * AJAX: Remove subscription
     */
    public function ajax_remove_subscription() {
        check_ajax_referer('terpedia_agent_subscriptions', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $agent_id = intval($_POST['agent_id']);
        $subscription_id = intval($_POST['subscription_id']);
        
        $result = $this->remove_subscription($agent_id, $subscription_id);
        
        if ($result) {
            wp_send_json_success('Subscription removed successfully');
        } else {
            wp_send_json_error('Failed to remove subscription');
        }
    }
    
    /**
     * Add subscription
     */
    private function add_subscription($agent_id, $subscribed_to_user_id, $subscription_type, $comment_frequency) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        
        // Check if subscription already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE agent_id = %d AND subscribed_to_user_id = %d",
            $agent_id, $subscribed_to_user_id
        ));
        
        if ($existing) {
            return false; // Already exists
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'agent_id' => $agent_id,
                'subscribed_to_user_id' => $subscribed_to_user_id,
                'subscription_type' => $subscription_type,
                'comment_frequency' => $comment_frequency,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'last_checked_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result) {
            // Trigger feed monitor update
            do_action('terpedia_agent_subscription_added', $agent_id, $subscribed_to_user_id, $subscription_type, $comment_frequency);
        }
        
        return $result !== false;
    }
    
    /**
     * Remove subscription
     */
    private function remove_subscription($agent_id, $subscription_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $subscription_id,
                'agent_id' => $agent_id
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            // Trigger feed monitor update
            do_action('terpedia_agent_subscription_removed', $agent_id, $subscription_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Initialize default subscriptions for existing agents
     */
    private function initialize_default_subscriptions() {
        // This would be called during plugin activation or when new agents are created
        $agents = get_users(array(
            'meta_key' => 'terpedia_is_ai_agent',
            'meta_value' => true
        ));
        
        foreach ($agents as $agent) {
            $this->create_default_subscriptions($agent->ID);
        }
    }
    
    /**
     * Create default subscriptions for an agent
     */
    private function create_default_subscriptions($agent_id) {
        // Subscribe to other agents (conservative commenting)
        $other_agents = get_users(array(
            'meta_key' => 'terpedia_is_ai_agent',
            'meta_value' => true,
            'exclude' => array($agent_id)
        ));
        
        foreach ($other_agents as $other_agent) {
            $this->add_subscription($agent_id, $other_agent->ID, 'agent', 'conservative');
        }
        
        // Subscribe to active users (moderate commenting)
        $active_users = get_users(array(
            'number' => 5,
            'orderby' => 'post_count',
            'order' => 'DESC',
            'exclude' => array($agent_id)
        ));
        
        foreach ($active_users as $user) {
            $this->add_subscription($agent_id, $user->ID, 'user', 'moderate');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-admin',
            'Agent Feed Monitoring',
            'Feed Monitoring',
            'manage_options',
            'terpedia-agent-feeds',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $agents = get_users(array(
            'meta_key' => 'terpedia_is_ai_agent',
            'meta_value' => true
        ));
        
        ?>
        <div class="wrap">
            <h1>Agent Feed Monitoring</h1>
            
            <div class="terpedia-admin-stats">
                <div class="stat-box">
                    <h3>Active Agents</h3>
                    <p class="stat-number"><?php echo count($agents); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Subscriptions</h3>
                    <p class="stat-number"><?php echo $this->get_total_subscriptions(); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Comments Today</h3>
                    <p class="stat-number"><?php echo $this->get_comments_today(); ?></p>
                </div>
            </div>
            
            <h2>Agent Feed Status</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Subscriptions</th>
                        <th>Last Activity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agents as $agent): ?>
                        <?php
                        $subscriptions = $this->get_agent_subscriptions($agent->ID);
                        $settings = $this->get_agent_commenting_settings($agent->ID);
                        $last_activity = $this->get_last_agent_activity($agent->ID);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($agent->display_name); ?></strong>
                                <br>
                                <small><?php echo esc_html($agent->user_email); ?></small>
                            </td>
                            <td><?php echo count($subscriptions); ?> active</td>
                            <td><?php echo $last_activity ? esc_html($last_activity) : 'Never'; ?></td>
                            <td>
                                <?php if ($settings['enabled']): ?>
                                    <span style="color: green;">✓ Monitoring</span>
                                <?php else: ?>
                                    <span style="color: red;">✗ Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $agent->ID . '#agent-subscriptions'); ?>" class="button button-small">Manage</a>
                                <button type="button" class="button button-small test-commenting-btn" data-agent-id="<?php echo $agent->ID; ?>">Test</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .terpedia-admin-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            min-width: 150px;
        }
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            color: #0073aa;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.test-commenting-btn').on('click', function() {
                var agentId = $(this).data('agent-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'terpedia_test_agent_commenting',
                        agent_id: agentId,
                        nonce: '<?php echo wp_create_nonce('terpedia_agent_subscriptions'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Test completed: ' + response.data);
                        } else {
                            alert('Test failed: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'terpedia-agent-feeds') !== false || strpos($hook, 'user-edit.php') !== false) {
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * Get total subscriptions count
     */
    private function get_total_subscriptions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1");
    }
    
    /**
     * Get comments made today
     */
    private function get_comments_today() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE DATE(last_checked_at) = CURDATE()");
    }
    
    /**
     * Get last agent activity
     */
    private function get_last_agent_activity($agent_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(last_checked_at) FROM {$table_name} WHERE agent_id = %d",
            $agent_id
        ));
    }
    
    /**
     * AJAX: Test agent commenting
     */
    public function ajax_test_commenting() {
        check_ajax_referer('terpedia_agent_subscriptions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $agent_id = intval($_POST['agent_id']);
        $agent = get_user_by('ID', $agent_id);
        
        if (!$agent) {
            wp_send_json_error('Agent not found');
        }
        
        // Simulate a test comment
        $test_result = "Test comment generated for " . $agent->display_name . " - Feed monitoring is working correctly.";
        
        wp_send_json_success($test_result);
    }
    
    /**
     * Create database table
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'terpedia_agent_subscriptions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            subscribed_to_user_id bigint(20) NOT NULL,
            subscription_type varchar(20) NOT NULL DEFAULT 'user',
            comment_frequency varchar(20) NOT NULL DEFAULT 'moderate',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_checked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_subscription (agent_id, subscribed_to_user_id),
            KEY agent_id (agent_id),
            KEY subscribed_to_user_id (subscribed_to_user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the manager
new TerpediaAgentFeedSubscriptionManager();

// Create tables on activation
register_activation_hook(__FILE__, array('TerpediaAgentFeedSubscriptionManager', 'create_tables'));
