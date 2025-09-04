<?php
/**
 * Custom Agent Voice Management System
 * Maps ElevenLabs Voice Design voices to all Terpedia agents
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaCustomAgentVoices {
    
    private $voice_mappings = [];
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_update_agent_voice_id', array($this, 'ajax_update_agent_voice_id'));
        add_action('wp_ajax_test_agent_voice', array($this, 'ajax_test_agent_voice'));
        add_action('wp_ajax_bulk_update_agent_voices', array($this, 'ajax_bulk_update_agent_voices'));
        
        // Initialize default voice mappings
        $this->init_voice_mappings();
    }
    
    public function init() {
        // Add admin menu for voice management
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Override default TTS voices with custom ElevenLabs voices
        add_filter('terpedia_agent_tts_voice', array($this, 'get_custom_agent_voice'), 10, 2);
        add_filter('terpedia_podcast_agent_voice', array($this, 'get_custom_agent_voice'), 10, 2);
    }
    
    /**
     * Initialize default voice mappings for all agents
     */
    private function init_voice_mappings() {
        // Get saved voice mappings from database
        $saved_mappings = get_option('terpedia_custom_agent_voices', array());
        
        // Default placeholders - these will be replaced with actual ElevenLabs Voice IDs
        $default_mappings = array(
            // TERSONAE (Personified Terpenes)
            'myrcene' => array(
                'voice_id' => '', // To be filled with ElevenLabs Voice Design ID
                'name' => 'Agt. Myrcene',
                'description' => 'Calm, soothing female voice with relaxation specialist tone',
                'voice_prompt' => 'A deeply calm, soothing female voice with a warm, earthy tone. Speaks slowly and deliberately with a gentle, relaxed delivery that embodies tranquility.',
                'agent_type' => 'tersona'
            ),
            'limonene' => array(
                'voice_id' => '',
                'name' => 'Agt. Limonene', 
                'description' => 'Bright, energetic female voice with uplifting citrusy quality',
                'voice_prompt' => 'A bright, energetic female voice with an uplifting citrusy quality. Speaks with enthusiasm and optimism, naturally upbeat without being rushed.',
                'agent_type' => 'tersona'
            ),
            'pinene' => array(
                'voice_id' => '',
                'name' => 'Agt. Pinene',
                'description' => 'Crisp, clear male voice with mental clarity expert precision',
                'voice_prompt' => 'A crisp, clear male voice with precise articulation and natural authority. Speaks with the freshness of mountain air - clean, sharp, and focused.',
                'agent_type' => 'tersona'
            ),
            'linalool' => array(
                'voice_id' => '',
                'name' => 'Agt. Linalool',
                'description' => 'Gentle, nurturing female voice with lavender healer qualities',
                'voice_prompt' => 'A gentle, nurturing female voice with soft, healing qualities. Speaks with compassionate warmth and floral grace.',
                'agent_type' => 'tersona'
            ),
            'caryophyllene' => array(
                'voice_id' => '',
                'name' => 'Agt. Caryophyllene',
                'description' => 'Bold, confident male voice with CB2 activator strength',
                'voice_prompt' => 'A bold, confident male voice with strength and therapeutic authority. Speaks with the confidence of proven medical efficacy.',
                'agent_type' => 'tersona'
            ),
            'humulene' => array(
                'voice_id' => '',
                'name' => 'Agt. Humulene',
                'description' => 'Balanced, earthy male voice with traditional brewing wisdom',
                'voice_prompt' => 'A balanced, earthy male voice with traditional wisdom. Speaks with the grounded knowledge of ancient brewing traditions.',
                'agent_type' => 'tersona'
            ),
            'terpinolene' => array(
                'voice_id' => '',
                'name' => 'Agt. Terpinolene',
                'description' => 'Fresh, protective female voice with antioxidant protector vitality',
                'voice_prompt' => 'A fresh, protective female voice with vitality and cellular wisdom. Speaks with health-conscious clarity and protective care.',
                'agent_type' => 'tersona'
            ),
            
            // EXPERT AGENTS (Specialists)
            'chemist' => array(
                'voice_id' => '',
                'name' => 'Agt. Molecule Maven',
                'description' => 'Precise, analytical male voice with molecular chemistry expertise',
                'voice_prompt' => 'A precise, analytical male voice with intellectual authority. Speaks with methodical clarity and technical precision.',
                'agent_type' => 'expert'
            ),
            'pharmacologist' => array(
                'voice_id' => '',
                'name' => 'Agt. Pharmakin',
                'description' => 'Clinical, authoritative male voice with pharmacokinetics expertise',
                'voice_prompt' => 'A clinical, authoritative male voice with medical expertise. Speaks with professional confidence and therapeutic knowledge.',
                'agent_type' => 'expert'
            ),
            'literature' => array(
                'voice_id' => '',
                'name' => 'Agt. Citeswell',
                'description' => 'Scholarly, knowledgeable female voice with research expertise',
                'voice_prompt' => 'A scholarly, knowledgeable female voice with research expertise. Speaks with academic authority and evidence-based confidence.',
                'agent_type' => 'expert'
            ),
            'regulatory' => array(
                'voice_id' => '',
                'name' => 'Agt. Regulatory',
                'description' => 'Formal, professional male voice with legal authority',
                'voice_prompt' => 'A formal, professional male voice with legal and regulatory authority. Speaks with compliance expertise and official precision.',
                'agent_type' => 'expert'
            ),
            'veterinarian' => array(
                'voice_id' => '',
                'name' => 'Agt. Veterinarian',
                'description' => 'Caring, professional female voice with veterinary expertise',
                'voice_prompt' => 'A caring, professional female voice with veterinary expertise. Speaks with compassionate authority about animal health.',
                'agent_type' => 'expert'
            ),
            'naturopath' => array(
                'voice_id' => '',
                'name' => 'Agt. Naturopath',
                'description' => 'Holistic, gentle female voice with natural healing wisdom',
                'voice_prompt' => 'A holistic, gentle female voice with natural healing wisdom. Speaks with integrative health knowledge and herbal expertise.',
                'agent_type' => 'expert'
            ),
            'botanist' => array(
                'voice_id' => '',
                'name' => 'Agt. Botanist',
                'description' => 'Knowledgeable, earth-connected male voice with plant science expertise',
                'voice_prompt' => 'A knowledgeable, earth-connected male voice with botanical expertise. Speaks with plant science authority and natural wisdom.',
                'agent_type' => 'expert'
            ),
            'aromatherapist' => array(
                'voice_id' => '',
                'name' => 'Agt. Aromatherapist',
                'description' => 'Soothing, sensory-aware female voice with aromatherapy expertise',
                'voice_prompt' => 'A soothing, sensory-aware female voice with aromatherapy expertise. Speaks with therapeutic knowledge about scents and essential oils.',
                'agent_type' => 'expert'
            ),
            'formulator' => array(
                'voice_id' => '',
                'name' => 'Agt. Formulator',
                'description' => 'Practical, innovative male voice with product development expertise',
                'voice_prompt' => 'A practical, innovative male voice with formulation expertise. Speaks with product development authority and creation knowledge.',
                'agent_type' => 'expert'
            ),
            'patient' => array(
                'voice_id' => '',
                'name' => 'Agt. Patient',
                'description' => 'Relatable, empathetic female voice with patient experience perspective',
                'voice_prompt' => 'A relatable, empathetic female voice with patient perspective. Speaks with lived experience and user insights.',
                'agent_type' => 'expert'
            ),
            'reporter' => array(
                'voice_id' => '',
                'name' => 'Agt. Reporter',
                'description' => 'Dynamic, informative male voice with journalism expertise',
                'voice_prompt' => 'A dynamic, informative male voice with journalism expertise. Speaks with news authority and industry insight.',
                'agent_type' => 'expert'
            ),
            'protein' => array(
                'voice_id' => '',
                'name' => 'Agt. Protein',
                'description' => 'Sophisticated, scientific female voice with molecular biology expertise',
                'voice_prompt' => 'A sophisticated, scientific female voice with protein expertise. Speaks with molecular biology authority and biochemical knowledge.',
                'agent_type' => 'expert'
            ),
            'prospector' => array(
                'voice_id' => '',
                'name' => 'Agt. Prospector',
                'description' => 'Adventurous, curious male voice with discovery expertise',
                'voice_prompt' => 'An adventurous, curious male voice with discovery expertise. Speaks with exploration spirit and research innovation.',
                'agent_type' => 'expert'
            )
        );
        
        // Merge saved mappings with defaults
        $this->voice_mappings = wp_parse_args($saved_mappings, $default_mappings);
    }
    
    /**
     * Add admin menu for voice management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-settings',
            'Agent Voice Management',
            'Agent Voices',
            'manage_options',
            'terpedia-agent-voices',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page for managing agent voices
     */
    public function admin_page() {
        if (isset($_POST['save_voices'])) {
            $this->save_voice_mappings($_POST['voices']);
            echo '<div class="notice notice-success"><p>Voice mappings saved successfully!</p></div>';
        }
        
        $voices = $this->voice_mappings;
        ?>
        <div class="wrap">
            <h1>Agent Voice Management</h1>
            <p>Map ElevenLabs Voice Design voices to each Terpedia agent. Use the Voice Design dashboard to generate custom voices, then enter the Voice IDs here.</p>
            
            <form method="post">
                <?php wp_nonce_field('terpedia_voice_management'); ?>
                
                <h2>Tersonae (Personified Terpenes)</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Voice ID</th>
                            <th>Description</th>
                            <th>Voice Design Prompt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voices as $key => $voice): ?>
                            <?php if ($voice['agent_type'] === 'tersona'): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($voice['name']); ?></strong></td>
                                    <td>
                                        <input type="text" 
                                               name="voices[<?php echo esc_attr($key); ?>][voice_id]" 
                                               value="<?php echo esc_attr($voice['voice_id']); ?>" 
                                               placeholder="ElevenLabs Voice ID"
                                               class="regular-text" />
                                    </td>
                                    <td><?php echo esc_html($voice['description']); ?></td>
                                    <td>
                                        <details>
                                            <summary>View Prompt</summary>
                                            <em>"<?php echo esc_html($voice['voice_prompt']); ?>"</em>
                                        </details>
                                    </td>
                                    <td>
                                        <button type="button" class="button test-voice" 
                                                data-agent="<?php echo esc_attr($key); ?>">Test Voice</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h2>Expert Agents (Specialists)</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Voice ID</th>
                            <th>Description</th>
                            <th>Voice Design Prompt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voices as $key => $voice): ?>
                            <?php if ($voice['agent_type'] === 'expert'): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($voice['name']); ?></strong></td>
                                    <td>
                                        <input type="text" 
                                               name="voices[<?php echo esc_attr($key); ?>][voice_id]" 
                                               value="<?php echo esc_attr($voice['voice_id']); ?>" 
                                               placeholder="ElevenLabs Voice ID"
                                               class="regular-text" />
                                    </td>
                                    <td><?php echo esc_html($voice['description']); ?></td>
                                    <td>
                                        <details>
                                            <summary>View Prompt</summary>
                                            <em>"<?php echo esc_html($voice['voice_prompt']); ?>"</em>
                                        </details>
                                    </td>
                                    <td>
                                        <button type="button" class="button test-voice" 
                                                data-agent="<?php echo esc_attr($key); ?>">Test Voice</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_voices" class="button-primary" value="Save Voice Mappings" />
                    <button type="button" id="bulk-test-voices" class="button">Test All Voices</button>
                </p>
            </form>
            
            <div id="voice-test-results"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.test-voice').on('click', function() {
                var agent = $(this).data('agent');
                var voiceId = $('input[name="voices[' + agent + '][voice_id]"]').val();
                
                if (!voiceId) {
                    alert('Please enter a Voice ID first.');
                    return;
                }
                
                testAgentVoice(agent, voiceId);
            });
            
            $('#bulk-test-voices').on('click', function() {
                $('.test-voice').each(function() {
                    var $btn = $(this);
                    setTimeout(function() {
                        $btn.click();
                    }, Math.random() * 2000); // Stagger tests to avoid rate limits
                });
            });
            
            function testAgentVoice(agent, voiceId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_agent_voice',
                        agent: agent,
                        voice_id: voiceId,
                        nonce: '<?php echo wp_create_nonce("terpedia_voice_test"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#voice-test-results').append(
                                '<div class="notice notice-success"><p><strong>' + agent + 
                                ':</strong> Voice test successful! <audio controls src="' + 
                                response.data.audio_url + '"></audio></p></div>'
                            );
                        } else {
                            $('#voice-test-results').append(
                                '<div class="notice notice-error"><p><strong>' + agent + 
                                ':</strong> ' + response.data.error + '</p></div>'
                            );
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save voice mappings to database
     */
    private function save_voice_mappings($voices) {
        check_admin_referer('terpedia_voice_management');
        
        $sanitized_voices = array();
        foreach ($voices as $key => $voice) {
            $sanitized_voices[sanitize_key($key)] = array(
                'voice_id' => sanitize_text_field($voice['voice_id']),
                'name' => $this->voice_mappings[$key]['name'],
                'description' => $this->voice_mappings[$key]['description'],
                'voice_prompt' => $this->voice_mappings[$key]['voice_prompt'],
                'agent_type' => $this->voice_mappings[$key]['agent_type']
            );
        }
        
        update_option('terpedia_custom_agent_voices', $sanitized_voices);
        $this->voice_mappings = $sanitized_voices;
    }
    
    /**
     * Get custom voice ID for agent
     */
    public function get_custom_agent_voice($default_voice, $agent_key) {
        if (isset($this->voice_mappings[$agent_key]['voice_id']) && 
            !empty($this->voice_mappings[$agent_key]['voice_id'])) {
            return $this->voice_mappings[$agent_key]['voice_id'];
        }
        
        return $default_voice;
    }
    
    /**
     * Get all voice mappings
     */
    public function get_voice_mappings() {
        return $this->voice_mappings;
    }
    
    /**
     * Update single agent voice ID
     */
    public function ajax_update_agent_voice_id() {
        check_ajax_referer('terpedia_voice_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $agent_key = sanitize_key($_POST['agent_key']);
        $voice_id = sanitize_text_field($_POST['voice_id']);
        
        if (isset($this->voice_mappings[$agent_key])) {
            $this->voice_mappings[$agent_key]['voice_id'] = $voice_id;
            update_option('terpedia_custom_agent_voices', $this->voice_mappings);
            
            wp_die(json_encode(array('success' => true, 'message' => 'Voice ID updated')));
        }
        
        wp_die(json_encode(array('error' => 'Agent not found')));
    }
    
    /**
     * Test agent voice
     */
    public function ajax_test_agent_voice() {
        check_ajax_referer('terpedia_voice_test', 'nonce');
        
        $agent_key = sanitize_key($_POST['agent']);
        $voice_id = sanitize_text_field($_POST['voice_id']);
        
        if (!isset($this->voice_mappings[$agent_key])) {
            wp_die(json_encode(array('error' => 'Agent not found')));
        }
        
        $agent_data = $this->voice_mappings[$agent_key];
        $test_text = "Hello, I am " . $agent_data['name'] . ". " . $agent_data['description'] . " This is a voice test for the Terpedia platform.";
        
        // Use ElevenLabs API handler to generate test audio
        if (class_exists('Terpedia_ElevenLabs_API_Handler')) {
            $api_handler = new Terpedia_ElevenLabs_API_Handler();
            $result = $api_handler->generate_speech($voice_id, $test_text);
            
            if (is_wp_error($result)) {
                wp_die(json_encode(array('error' => $result->get_error_message())));
            }
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => $result
            )));
        }
        
        wp_die(json_encode(array('error' => 'ElevenLabs API handler not available')));
    }
    
    /**
     * Bulk update agent voices
     */
    public function ajax_bulk_update_agent_voices() {
        check_ajax_referer('terpedia_voice_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $voice_updates = $_POST['voice_updates'];
        $updated_count = 0;
        
        foreach ($voice_updates as $agent_key => $voice_id) {
            $agent_key = sanitize_key($agent_key);
            $voice_id = sanitize_text_field($voice_id);
            
            if (isset($this->voice_mappings[$agent_key]) && !empty($voice_id)) {
                $this->voice_mappings[$agent_key]['voice_id'] = $voice_id;
                $updated_count++;
            }
        }
        
        if ($updated_count > 0) {
            update_option('terpedia_custom_agent_voices', $this->voice_mappings);
            wp_die(json_encode(array(
                'success' => true, 
                'message' => "Updated {$updated_count} agent voices"
            )));
        }
        
        wp_die(json_encode(array('error' => 'No valid voice updates provided')));
    }
}

// Initialize the class
new TerpediaCustomAgentVoices();