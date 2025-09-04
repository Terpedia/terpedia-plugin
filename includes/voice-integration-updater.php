<?php
/**
 * Voice Integration Updater
 * Updates existing agent systems to use custom ElevenLabs voices
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaVoiceIntegrationUpdater {
    
    private $custom_voices;
    
    public function __construct() {
        add_action('init', array($this, 'init'), 20); // Load after other agent systems
        
        // Get custom voice mappings
        if (class_exists('TerpediaCustomAgentVoices')) {
            $voice_manager = new TerpediaCustomAgentVoices();
            $this->custom_voices = $voice_manager->get_voice_mappings();
        }
    }
    
    public function init() {
        // Update Terpene agents with custom voices
        add_filter('terpedia_terpene_agent_data', array($this, 'update_terpene_agent_voices'), 10, 2);
        
        // Update Expert agents with custom voices  
        add_filter('terpedia_expert_agent_personality', array($this, 'update_expert_agent_voices'), 10, 2);
        
        // Update podcast voice selection
        add_filter('terpedia_podcast_agent_voice_id', array($this, 'get_custom_voice_for_podcast'), 10, 2);
        
        // Update BuddyPress messaging voices
        add_filter('bp_message_agent_voice_id', array($this, 'get_custom_voice_for_message'), 10, 2);
        
        // Update ElevenLabs integration to use custom voices
        add_filter('elevenlabs_voice_selection', array($this, 'override_elevenlabs_voice'), 10, 2);
    }
    
    /**
     * Update terpene agent voices with custom ElevenLabs voices
     */
    public function update_terpene_agent_voices($agent_data, $agent_key) {
        if (isset($this->custom_voices[$agent_key]['voice_id']) && 
            !empty($this->custom_voices[$agent_key]['voice_id'])) {
            
            $agent_data['tts_voice'] = $this->custom_voices[$agent_key]['voice_id'];
            $agent_data['elevenlabs_voice_id'] = $this->custom_voices[$agent_key]['voice_id'];
            $agent_data['voice_provider'] = 'elevenlabs_custom';
            
            // Keep original voice settings for fallback
            $agent_data['original_tts_voice'] = $agent_data['tts_voice'] ?? 'neural';
        }
        
        return $agent_data;
    }
    
    /**
     * Update expert agent voices with custom ElevenLabs voices
     */
    public function update_expert_agent_voices($personality, $agent_type) {
        if (isset($this->custom_voices[$agent_type]['voice_id']) && 
            !empty($this->custom_voices[$agent_type]['voice_id'])) {
            
            $personality['tts_voice'] = $this->custom_voices[$agent_type]['voice_id'];
            $personality['elevenlabs_voice_id'] = $this->custom_voices[$agent_type]['voice_id'];
            $personality['voice_provider'] = 'elevenlabs_custom';
            
            // Keep original voice settings for fallback
            $personality['original_tts_voice'] = $personality['tts_voice'] ?? 'neural';
        }
        
        return $personality;
    }
    
    /**
     * Get custom voice for podcast episodes
     */
    public function get_custom_voice_for_podcast($default_voice_id, $agent_identifier) {
        // Extract agent key from identifier (could be username, agent type, etc.)
        $agent_key = $this->extract_agent_key($agent_identifier);
        
        if ($agent_key && isset($this->custom_voices[$agent_key]['voice_id']) && 
            !empty($this->custom_voices[$agent_key]['voice_id'])) {
            return $this->custom_voices[$agent_key]['voice_id'];
        }
        
        return $default_voice_id;
    }
    
    /**
     * Get custom voice for BuddyPress messages
     */
    public function get_custom_voice_for_message($default_voice_id, $user_id) {
        // Get agent type from user meta
        $agent_type = get_user_meta($user_id, 'terpedia_agent_type', true);
        $terpene_name = get_user_meta($user_id, 'terpedia_terpene_name', true);
        
        $agent_key = $terpene_name ?: $agent_type;
        
        if ($agent_key && isset($this->custom_voices[$agent_key]['voice_id']) && 
            !empty($this->custom_voices[$agent_key]['voice_id'])) {
            return $this->custom_voices[$agent_key]['voice_id'];
        }
        
        return $default_voice_id;
    }
    
    /**
     * Override ElevenLabs voice selection
     */
    public function override_elevenlabs_voice($voice_id, $context) {
        if (isset($context['agent_key']) && 
            isset($this->custom_voices[$context['agent_key']]['voice_id']) &&
            !empty($this->custom_voices[$context['agent_key']]['voice_id'])) {
            
            return $this->custom_voices[$context['agent_key']]['voice_id'];
        }
        
        if (isset($context['user_id'])) {
            $agent_type = get_user_meta($context['user_id'], 'terpedia_agent_type', true);
            $terpene_name = get_user_meta($context['user_id'], 'terpedia_terpene_name', true);
            $agent_key = $terpene_name ?: $agent_type;
            
            if ($agent_key && isset($this->custom_voices[$agent_key]['voice_id']) &&
                !empty($this->custom_voices[$agent_key]['voice_id'])) {
                return $this->custom_voices[$agent_key]['voice_id'];
            }
        }
        
        return $voice_id;
    }
    
    /**
     * Extract agent key from various identifier formats
     */
    private function extract_agent_key($identifier) {
        // Handle username format (e.g., 'terpedia-myrcene' -> 'myrcene')
        if (strpos($identifier, 'terpedia-') === 0) {
            $agent_key = str_replace('terpedia-', '', $identifier);
            
            // Map some specific usernames to agent keys
            $username_mappings = array(
                'molecule-maven' => 'chemist',
                'pharmakin' => 'pharmacologist',
                'citeswell' => 'literature'
            );
            
            return isset($username_mappings[$agent_key]) ? $username_mappings[$agent_key] : $agent_key;
        }
        
        // Handle direct agent type
        if (isset($this->custom_voices[$identifier])) {
            return $identifier;
        }
        
        return null;
    }
    
    /**
     * Get voice settings for agent
     */
    public function get_agent_voice_settings($agent_key) {
        if (!isset($this->custom_voices[$agent_key])) {
            return null;
        }
        
        return array(
            'voice_id' => $this->custom_voices[$agent_key]['voice_id'],
            'name' => $this->custom_voices[$agent_key]['name'],
            'description' => $this->custom_voices[$agent_key]['description'],
            'voice_prompt' => $this->custom_voices[$agent_key]['voice_prompt'],
            'agent_type' => $this->custom_voices[$agent_key]['agent_type'],
            'provider' => 'elevenlabs_custom'
        );
    }
    
    /**
     * Update existing agent profiles with new voice IDs
     */
    public function update_agent_profiles() {
        // Update Terpene agents
        $terpene_agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_value' => 'terpene'
        ));
        
        foreach ($terpene_agents as $user) {
            $terpene_name = get_user_meta($user->ID, 'terpedia_terpene_name', true);
            if ($terpene_name && isset($this->custom_voices[$terpene_name]['voice_id'])) {
                update_user_meta($user->ID, 'elevenlabs_voice_id', $this->custom_voices[$terpene_name]['voice_id']);
                update_user_meta($user->ID, 'voice_provider', 'elevenlabs_custom');
            }
        }
        
        // Update Expert agents
        $expert_agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_value' => 'expert'
        ));
        
        foreach ($expert_agents as $user) {
            $username = $user->user_login;
            $agent_key = $this->extract_agent_key($username);
            
            if ($agent_key && isset($this->custom_voices[$agent_key]['voice_id'])) {
                update_user_meta($user->ID, 'elevenlabs_voice_id', $this->custom_voices[$agent_key]['voice_id']);
                update_user_meta($user->ID, 'voice_provider', 'elevenlabs_custom');
            }
        }
    }
    
    /**
     * Generate test audio for all agents
     */
    public function generate_test_audio_for_all_agents() {
        if (!class_exists('Terpedia_ElevenLabs_API_Handler')) {
            return array('error' => 'ElevenLabs API handler not available');
        }
        
        $api_handler = new Terpedia_ElevenLabs_API_Handler();
        $results = array();
        
        foreach ($this->custom_voices as $agent_key => $voice_data) {
            if (empty($voice_data['voice_id'])) {
                continue;
            }
            
            $test_text = "Hello, I am " . $voice_data['name'] . ". I specialize in " . 
                        str_replace($voice_data['name'], '', $voice_data['description']) . 
                        " Welcome to Terpedia, where terpene science meets innovation.";
            
            $result = $api_handler->generate_speech($voice_data['voice_id'], $test_text);
            
            if (is_wp_error($result)) {
                $results[$agent_key] = array(
                    'success' => false,
                    'error' => $result->get_error_message()
                );
            } else {
                $results[$agent_key] = array(
                    'success' => true,
                    'audio_url' => $result['audio_url'],
                    'filename' => $result['filename']
                );
            }
        }
        
        return $results;
    }
}

// Initialize the voice integration updater
new TerpediaVoiceIntegrationUpdater();