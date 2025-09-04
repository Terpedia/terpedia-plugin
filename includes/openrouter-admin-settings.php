<?php
/**
 * OpenRouter Admin Settings
 * Admin interface for configuring OpenRouter API key and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaOpenRouterAdminSettings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_test_openrouter_connection', array($this, 'ajax_test_connection'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-settings',
            'OpenRouter Settings',
            'OpenRouter API',
            'manage_options',
            'terpedia-openrouter-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('terpedia_openrouter_settings', 'terpedia_openrouter_api_key');
        register_setting('terpedia_openrouter_settings', 'terpedia_openrouter_model');
        register_setting('terpedia_openrouter_settings', 'terpedia_openrouter_temperature');
        register_setting('terpedia_openrouter_settings', 'terpedia_openrouter_max_tokens');
        
        add_settings_section(
            'terpedia_openrouter_main',
            'OpenRouter Configuration',
            array($this, 'settings_section_callback'),
            'terpedia_openrouter_settings'
        );
        
        add_settings_field(
            'terpedia_openrouter_api_key',
            'API Key',
            array($this, 'api_key_field'),
            'terpedia_openrouter_settings',
            'terpedia_openrouter_main'
        );
        
        add_settings_field(
            'terpedia_openrouter_model',
            'Model',
            array($this, 'model_field'),
            'terpedia_openrouter_settings',
            'terpedia_openrouter_main'
        );
        
        add_settings_field(
            'terpedia_openrouter_temperature',
            'Temperature',
            array($this, 'temperature_field'),
            'terpedia_openrouter_settings',
            'terpedia_openrouter_main'
        );
        
        add_settings_field(
            'terpedia_openrouter_max_tokens',
            'Max Tokens',
            array($this, 'max_tokens_field'),
            'terpedia_openrouter_settings',
            'terpedia_openrouter_main'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure OpenRouter.ai integration for Terpedia AI agents. OpenRouter provides access to various AI models including the free GPT-OSS-120B model.</p>';
    }
    
    /**
     * API Key field
     */
    public function api_key_field() {
        $api_key = get_option('terpedia_openrouter_api_key', '');
        $env_key = $_ENV['OPENROUTER_API_KEY'] ?? '';
        
        echo '<input type="password" id="terpedia_openrouter_api_key" name="terpedia_openrouter_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenRouter API key. <a href="https://openrouter.ai/keys" target="_blank">Get your API key here</a></p>';
        
        if (!empty($env_key)) {
            echo '<p style="color: green;"><strong>✅ API Key detected in environment variables</strong></p>';
        }
        
        echo '<p><button type="button" id="test-openrouter-connection" class="button">Test Connection</button></p>';
        echo '<div id="connection-test-result"></div>';
    }
    
    /**
     * Model field
     */
    public function model_field() {
        $model = get_option('terpedia_openrouter_model', 'openai/gpt-oss-120b:free');
        
        $models = array(
            'openai/gpt-oss-120b:free' => 'GPT-OSS-120B (Free)',
            'meta-llama/llama-3.2-3b-instruct:free' => 'Llama 3.2 3B (Free)',
            'meta-llama/llama-3.2-1b-instruct:free' => 'Llama 3.2 1B (Free)',
            'qwen/qwen-2-7b-instruct:free' => 'Qwen 2 7B (Free)',
            'microsoft/phi-3-medium-128k-instruct:free' => 'Phi 3 Medium (Free)',
            'openai/gpt-4o-mini' => 'GPT-4o Mini',
            'openai/gpt-4o' => 'GPT-4o',
            'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet'
        );
        
        echo '<select id="terpedia_openrouter_model" name="terpedia_openrouter_model">';
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($model, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select the AI model to use. Free models have rate limits but no cost.</p>';
    }
    
    /**
     * Temperature field
     */
    public function temperature_field() {
        $temperature = get_option('terpedia_openrouter_temperature', '0.7');
        echo '<input type="number" id="terpedia_openrouter_temperature" name="terpedia_openrouter_temperature" value="' . esc_attr($temperature) . '" min="0" max="2" step="0.1" class="small-text" />';
        echo '<p class="description">Controls randomness in responses (0.0 = deterministic, 2.0 = very creative)</p>';
    }
    
    /**
     * Max tokens field
     */
    public function max_tokens_field() {
        $max_tokens = get_option('terpedia_openrouter_max_tokens', '1000');
        echo '<input type="number" id="terpedia_openrouter_max_tokens" name="terpedia_openrouter_max_tokens" value="' . esc_attr($max_tokens) . '" min="100" max="4000" step="100" class="small-text" />';
        echo '<p class="description">Maximum tokens in AI responses (100-4000)</p>';
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('terpedia_openrouter_messages', 'terpedia_openrouter_message', 'Settings Saved', 'updated');
        }
        
        settings_errors('terpedia_openrouter_messages');
        
        ?>
        <div class="wrap">
            <h1>🤖 OpenRouter AI Configuration</h1>
            
            <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
                <h3>🎯 Quick Setup</h3>
                <ol>
                    <li><strong>Get API Key:</strong> Visit <a href="https://openrouter.ai/keys" target="_blank">OpenRouter.ai</a> to get your free API key</li>
                    <li><strong>Enter Key:</strong> Paste it in the field below</li>
                    <li><strong>Test Connection:</strong> Click "Test Connection" to verify</li>
                    <li><strong>Save Settings:</strong> Click "Save Changes"</li>
                </ol>
            </div>
            
            <!-- Current Status -->
            <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0;">
                <?php
                $api_key = get_option('terpedia_openrouter_api_key', '') ?: ($_ENV['OPENROUTER_API_KEY'] ?? '');
                $api_status = !empty($api_key) ? '✅ Configured' : '❌ Missing';
                
                echo '<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">';
                echo '<h4>🔑 API Status</h4>';
                echo '<p><strong>' . $api_status . '</strong></p>';
                echo '</div>';
                
                echo '<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">';
                echo '<h4>🤖 Current Model</h4>';
                echo '<p><strong>' . get_option('terpedia_openrouter_model', 'openai/gpt-oss-120b:free') . '</strong></p>';
                echo '</div>';
                
                echo '<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">';
                echo '<h4>💬 Agent Integration</h4>';
                echo '<p><strong>Ready for 20 agents</strong></p>';
                echo '</div>';
                ?>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('terpedia_openrouter_settings');
                do_settings_sections('terpedia_openrouter_settings');
                submit_button();
                ?>
            </form>
            
            <!-- Usage Information -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 20px;">
                <h3>📊 Model Information</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Cost</th>
                            <th>Context</th>
                            <th>Best For</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background: #e7f7e7;">
                            <td><strong>openai/gpt-oss-120b:free</strong></td>
                            <td>🆓 Free</td>
                            <td>8K tokens</td>
                            <td>General agent responses, terpene Q&A</td>
                        </tr>
                        <tr>
                            <td>meta-llama/llama-3.2-3b-instruct:free</td>
                            <td>🆓 Free</td>
                            <td>128K tokens</td>
                            <td>Long-form content, research papers</td>
                        </tr>
                        <tr>
                            <td>openai/gpt-4o-mini</td>
                            <td>💰 Paid</td>
                            <td>128K tokens</td>
                            <td>High-quality responses, complex queries</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-openrouter-connection').on('click', function() {
                var $btn = $(this);
                var $result = $('#connection-test-result');
                
                $btn.prop('disabled', true).text('Testing...');
                $result.html('<p>Testing OpenRouter connection...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_openrouter_connection',
                        nonce: '<?php echo wp_create_nonce("terpedia_admin_nonce"); ?>'
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $result.html('<div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;"><strong>✅ Connection Successful!</strong><br>Model: ' + data.model + '<br>Response: ' + data.response + '</div>');
                        } else {
                            $result.html('<div style="background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;"><strong>❌ Connection Failed</strong><br>Error: ' + data.error + '</div>');
                        }
                    },
                    error: function() {
                        $result.html('<div style="background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;"><strong>❌ Request Failed</strong><br>Please check your network connection.</div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Connection');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        // Test with current settings
        if (class_exists('TerpediaOpenRouterHandler')) {
            $openrouter = new TerpediaOpenRouterHandler();
            $result = $openrouter->test_connection();
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('error' => 'OpenRouter handler not available')));
    }
}

// Initialize OpenRouter admin settings
new TerpediaOpenRouterAdminSettings();