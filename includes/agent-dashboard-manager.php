<?php
/**
 * Agent Dashboard Manager
 * Main admin interface for managing Tersonae and Experts
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaAgentDashboardManager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_update_agent_profile', array($this, 'ajax_update_agent_profile'));
        add_action('wp_ajax_update_agent_prompts', array($this, 'ajax_update_agent_prompts'));
        add_action('wp_ajax_update_agent_voice', array($this, 'ajax_update_agent_voice'));
        add_action('wp_ajax_create_new_agent', array($this, 'ajax_create_new_agent'));
        add_action('wp_ajax_delete_agent', array($this, 'ajax_delete_agent'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main Terpedia menu
        add_menu_page(
            'Terpedia Agents',
            'Terpedia',
            'manage_options',
            'terpedia-agents',
            array($this, 'agents_dashboard'),
            'dashicons-groups',
            30
        );
        
        // Tersonae submenu
        add_submenu_page(
            'terpedia-agents',
            'Tersonae Management',
            'Tersonae',
            'manage_options',
            'terpedia-tersonae',
            array($this, 'tersonae_dashboard')
        );
        
        // Experts submenu
        add_submenu_page(
            'terpedia-agents',
            'Experts Management',
            'Experts',
            'manage_options',
            'terpedia-experts',
            array($this, 'experts_dashboard')
        );
        
        // Agent Settings submenu
        add_submenu_page(
            'terpedia-agents',
            'Agent Settings',
            'Settings',
            'manage_options',
            'terpedia-agent-settings',
            array($this, 'agent_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'terpedia-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'terpedia-agent-dashboard',
            plugin_dir_url(__FILE__) . '../assets/css/agent-dashboard.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'terpedia-agent-dashboard',
            plugin_dir_url(__FILE__) . '../assets/js/agent-dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('terpedia-agent-dashboard', 'terpedia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_agent_nonce')
        ));
    }
    
    /**
     * Main agents dashboard
     */
    public function agents_dashboard() {
        $tersonae_count = $this->get_agent_count('terpene');
        $experts_count = $this->get_agent_count('expert');
        $active_agents = $this->get_active_agents_count();
        
        ?>
        <div class="wrap">
            <h1>🤖 Terpedia Agents Dashboard</h1>
            
            <!-- Overview Cards -->
            <div class="terpedia-dashboard-cards">
                <div class="terpedia-card">
                    <div class="terpedia-card-header">
                        <h3>🧬 Tersonae</h3>
                        <span class="terpedia-count"><?php echo $tersonae_count; ?></span>
                    </div>
                    <p>Terpene-based AI agents representing specific terpenes</p>
                    <a href="<?php echo admin_url('admin.php?page=terpedia-tersonae'); ?>" class="button button-primary">Manage Tersonae</a>
                </div>
                
                <div class="terpedia-card">
                    <div class="terpedia-card-header">
                        <h3>👨‍🔬 Experts</h3>
                        <span class="terpedia-count"><?php echo $experts_count; ?></span>
                    </div>
                    <p>Expert AI agents with specialized knowledge areas</p>
                    <a href="<?php echo admin_url('admin.php?page=terpedia-experts'); ?>" class="button button-primary">Manage Experts</a>
                </div>
                
                <div class="terpedia-card">
                    <div class="terpedia-card-header">
                        <h3>🟢 Active Agents</h3>
                        <span class="terpedia-count"><?php echo $active_agents; ?></span>
                    </div>
                    <p>Currently active and available agents</p>
                    <a href="<?php echo admin_url('admin.php?page=terpedia-agent-settings'); ?>" class="button">View Settings</a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="terpedia-activity-section">
                <h2>📊 Recent Activity</h2>
                <?php $this->render_recent_activity(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tersonae dashboard
     */
    public function tersonae_dashboard() {
        $tersonae = $this->get_agents_by_type('terpene');
        
        ?>
        <div class="wrap">
            <h1>🧬 Tersonae Management</h1>
            <p class="description">Manage terpene-based AI agents and their profiles, prompts, and voices.</p>
            
            <div class="terpedia-actions">
                <button type="button" id="add-new-tersona" class="button button-primary">
                    ➕ Add New Tersona
                </button>
                <button type="button" id="bulk-edit-tersonae" class="button">
                    ✏️ Bulk Edit
                </button>
            </div>
            
            <div class="terpedia-agents-grid">
                <?php foreach ($tersonae as $agent): ?>
                    <?php $this->render_agent_card($agent, 'tersona'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Add/Edit Tersona Modal -->
        <div id="tersona-modal" class="terpedia-modal" style="display: none;">
            <div class="terpedia-modal-content">
                <div class="terpedia-modal-header">
                    <h2 id="modal-title">Add New Tersona</h2>
                    <span class="terpedia-modal-close">&times;</span>
                </div>
                <div class="terpedia-modal-body">
                    <?php $this->render_agent_form('terpene'); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Experts dashboard
     */
    public function experts_dashboard() {
        $experts = $this->get_agents_by_type('expert');
        
        ?>
        <div class="wrap">
            <h1>👨‍🔬 Experts Management</h1>
            <p class="description">Manage expert AI agents and their specializations, prompts, and voices.</p>
            
            <div class="terpedia-actions">
                <button type="button" id="add-new-expert" class="button button-primary">
                    ➕ Add New Expert
                </button>
                <button type="button" id="bulk-edit-experts" class="button">
                    ✏️ Bulk Edit
                </button>
            </div>
            
            <div class="terpedia-agents-grid">
                <?php foreach ($experts as $agent): ?>
                    <?php $this->render_agent_card($agent, 'expert'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Add/Edit Expert Modal -->
        <div id="expert-modal" class="terpedia-modal" style="display: none;">
            <div class="terpedia-modal-content">
                <div class="terpedia-modal-header">
                    <h2 id="modal-title">Add New Expert</h2>
                    <span class="terpedia-modal-close">&times;</span>
                </div>
                <div class="terpedia-modal-body">
                    <?php $this->render_agent_form('expert'); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Agent settings page
     */
    public function agent_settings_page() {
        ?>
        <div class="wrap">
            <h1>⚙️ Agent Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('terpedia_agent_settings');
                do_settings_sections('terpedia_agent_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Voice Provider</th>
                        <td>
                            <select name="terpedia_default_voice_provider">
                                <option value="openrouter" <?php selected(get_option('terpedia_default_voice_provider'), 'openrouter'); ?>>OpenRouter</option>
                                <option value="elevenlabs" <?php selected(get_option('terpedia_default_voice_provider'), 'elevenlabs'); ?>>ElevenLabs</option>
                                <option value="azure" <?php selected(get_option('terpedia_default_voice_provider'), 'azure'); ?>>Azure Speech</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Response Style</th>
                        <td>
                            <select name="terpedia_default_response_style">
                                <option value="professional" <?php selected(get_option('terpedia_default_response_style'), 'professional'); ?>>Professional</option>
                                <option value="friendly" <?php selected(get_option('terpedia_default_response_style'), 'friendly'); ?>>Friendly</option>
                                <option value="scientific" <?php selected(get_option('terpedia_default_response_style'), 'scientific'); ?>>Scientific</option>
                                <option value="casual" <?php selected(get_option('terpedia_default_response_style'), 'casual'); ?>>Casual</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto-Response Enabled</th>
                        <td>
                            <input type="checkbox" name="terpedia_auto_response" value="1" <?php checked(get_option('terpedia_auto_response'), 1); ?> />
                            <label>Enable automatic responses from agents</label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render agent card
     */
    private function render_agent_card($agent, $type) {
        $agent_type = get_user_meta($agent->ID, 'terpedia_agent_type', true);
        $is_active = get_user_meta($agent->ID, 'terpedia_is_available', true);
        $avatar_url = get_user_meta($agent->ID, 'terpedia_avatar_url', true);
        $specialization = get_user_meta($agent->ID, 'terpedia_expertise', true);
        $terpene_name = get_user_meta($agent->ID, 'terpedia_terpene_name', true);
        
        ?>
        <div class="terpedia-agent-card" data-agent-id="<?php echo $agent->ID; ?>">
            <div class="terpedia-agent-avatar">
                <?php if ($avatar_url): ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($agent->display_name); ?>" />
                <?php else: ?>
                    <div class="terpedia-avatar-placeholder">
                        <?php echo strtoupper(substr($agent->display_name, 0, 2)); ?>
                    </div>
                <?php endif; ?>
                <div class="terpedia-agent-status <?php echo $is_active ? 'active' : 'inactive'; ?>"></div>
            </div>
            
            <div class="terpedia-agent-info">
                <h3><?php echo esc_html($agent->display_name); ?></h3>
                <p class="terpedia-agent-type">
                    <?php if ($type === 'tersona'): ?>
                        🧬 <?php echo esc_html($terpene_name ?: 'Terpene Agent'); ?>
                    <?php else: ?>
                        👨‍🔬 <?php echo esc_html($specialization ?: 'Expert Agent'); ?>
                    <?php endif; ?>
                </p>
                <p class="terpedia-agent-description"><?php echo esc_html($agent->description); ?></p>
                
                <div class="terpedia-agent-stats">
                    <span class="terpedia-stat">
                        <strong><?php echo get_user_meta($agent->ID, 'terpedia_consultation_count', true) ?: 0; ?></strong>
                        consultations
                    </span>
                    <span class="terpedia-stat">
                        <strong><?php echo get_user_meta($agent->ID, 'terpedia_rating', true) ?: '5.0'; ?></strong>
                        rating
                    </span>
                </div>
            </div>
            
            <div class="terpedia-agent-actions">
                <button type="button" class="button button-small edit-agent" data-agent-id="<?php echo $agent->ID; ?>">
                    ✏️ Edit
                </button>
                <button type="button" class="button button-small view-profile" data-agent-id="<?php echo $agent->ID; ?>">
                    👤 Profile
                </button>
                <button type="button" class="button button-small toggle-status" data-agent-id="<?php echo $agent->ID; ?>" data-status="<?php echo $is_active ? 'active' : 'inactive'; ?>">
                    <?php echo $is_active ? '⏸️ Deactivate' : '▶️ Activate'; ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render agent form
     */
    private function render_agent_form($type) {
        ?>
        <form id="agent-form" method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Display Name</th>
                    <td><input type="text" name="display_name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row">Username</th>
                    <td><input type="text" name="user_login" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                    <td><textarea name="description" rows="3" class="large-text"></textarea></td>
                </tr>
                
                <?php if ($type === 'terpene'): ?>
                <tr>
                    <th scope="row">Terpene Name</th>
                    <td><input type="text" name="terpene_name" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Molecular Formula</th>
                    <td><input type="text" name="molecular_formula" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Aroma Profile</th>
                    <td><input type="text" name="aroma_profile" class="regular-text" /></td>
                </tr>
                <?php else: ?>
                <tr>
                    <th scope="row">Expertise Area</th>
                    <td><input type="text" name="expertise" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Specializations</th>
                    <td><input type="text" name="specializations" class="large-text" placeholder="Comma-separated list" /></td>
                </tr>
                <tr>
                    <th scope="row">Experience (Years)</th>
                    <td><input type="number" name="experience_years" class="small-text" min="0" /></td>
                </tr>
                <?php endif; ?>
                
                <tr>
                    <th scope="row">System Prompt</th>
                    <td>
                        <textarea name="system_prompt" rows="5" class="large-text" placeholder="Define the agent's personality and behavior..."></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Voice Settings</th>
                    <td>
                        <select name="voice_provider">
                            <option value="openrouter">OpenRouter</option>
                            <option value="elevenlabs">ElevenLabs</option>
                            <option value="azure">Azure Speech</option>
                        </select>
                        <input type="text" name="voice_id" class="regular-text" placeholder="Voice ID" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Avatar URL</th>
                    <td><input type="url" name="avatar_url" class="large-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_available" value="1" checked />
                            Available for consultations
                        </label>
                    </td>
                </tr>
            </table>
            
            <div class="terpedia-modal-footer">
                <button type="submit" class="button button-primary">Save Agent</button>
                <button type="button" class="button terpedia-modal-close">Cancel</button>
            </div>
        </form>
        <?php
    }
    
    /**
     * Get agents by type
     */
    private function get_agents_by_type($type) {
        return get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_value' => $type,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
    }
    
    /**
     * Get agent count by type
     */
    private function get_agent_count($type) {
        $users = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_value' => $type,
            'count_total' => true
        ));
        return count($users);
    }
    
    /**
     * Get active agents count
     */
    private function get_active_agents_count() {
        $users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'terpedia_agent_type',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'terpedia_is_available',
                    'value' => '1'
                )
            ),
            'count_total' => true
        ));
        return count($users);
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        // This would show recent agent interactions, consultations, etc.
        ?>
        <div class="terpedia-activity-feed">
            <p>Recent agent activity will be displayed here...</p>
        </div>
        <?php
    }
    
    /**
     * AJAX: Update agent profile
     */
    public function ajax_update_agent_profile() {
        check_ajax_referer('terpedia_agent_nonce', 'nonce');
        
        $agent_id = intval($_POST['agent_id']);
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_text_field($_POST['value']);
        
        update_user_meta($agent_id, $field, $value);
        
        wp_send_json_success(array(
            'message' => 'Agent profile updated successfully'
        ));
    }
    
    /**
     * AJAX: Update agent prompts
     */
    public function ajax_update_agent_prompts() {
        check_ajax_referer('terpedia_agent_nonce', 'nonce');
        
        $agent_id = intval($_POST['agent_id']);
        $system_prompt = sanitize_textarea_field($_POST['system_prompt']);
        
        update_user_meta($agent_id, 'terpedia_system_prompt', $system_prompt);
        
        wp_send_json_success(array(
            'message' => 'Agent prompts updated successfully'
        ));
    }
    
    /**
     * AJAX: Update agent voice
     */
    public function ajax_update_agent_voice() {
        check_ajax_referer('terpedia_agent_nonce', 'nonce');
        
        $agent_id = intval($_POST['agent_id']);
        $voice_provider = sanitize_text_field($_POST['voice_provider']);
        $voice_id = sanitize_text_field($_POST['voice_id']);
        
        update_user_meta($agent_id, 'terpedia_voice_provider', $voice_provider);
        update_user_meta($agent_id, 'terpedia_voice_id', $voice_id);
        
        wp_send_json_success(array(
            'message' => 'Agent voice settings updated successfully'
        ));
    }
    
    /**
     * AJAX: Create new agent
     */
    public function ajax_create_new_agent() {
        check_ajax_referer('terpedia_agent_nonce', 'nonce');
        
        $agent_data = array(
            'user_login' => sanitize_user($_POST['user_login']),
            'display_name' => sanitize_text_field($_POST['display_name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'agent_type' => sanitize_text_field($_POST['agent_type'])
        );
        
        // Create user
        $user_id = wp_create_user(
            $agent_data['user_login'],
            wp_generate_password(),
            $agent_data['user_login'] . '@terpedia.ai'
        );
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => 'Failed to create agent: ' . $user_id->get_error_message()
            ));
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $agent_data['display_name'],
            'description' => $agent_data['description']
        ));
        
        update_user_meta($user_id, 'terpedia_agent_type', $agent_data['agent_type']);
        update_user_meta($user_id, 'terpedia_is_available', 1);
        update_user_meta($user_id, 'terpedia_consultation_count', 0);
        update_user_meta($user_id, 'terpedia_rating', 5.0);
        
        // Set role
        $user = new WP_User($user_id);
        $user->set_role('terpedia_agent');
        
        wp_send_json_success(array(
            'message' => 'Agent created successfully',
            'agent_id' => $user_id
        ));
    }
    
    /**
     * AJAX: Delete agent
     */
    public function ajax_delete_agent() {
        check_ajax_referer('terpedia_agent_nonce', 'nonce');
        
        $agent_id = intval($_POST['agent_id']);
        
        if (wp_delete_user($agent_id)) {
            wp_send_json_success(array(
                'message' => 'Agent deleted successfully'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to delete agent'
            ));
        }
    }
}

// Initialize the dashboard manager
new TerpediaAgentDashboardManager();
