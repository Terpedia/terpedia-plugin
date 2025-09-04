<?php
/**
 * Agent DM OpenRouter Integration
 * Integrates OpenRouter GPT-OSS-120B for agent direct messages with per-thread history
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaAgentDMOpenRouter {
    
    private $openrouter_handler;
    private $conversation_histories = array();
    
    public function __construct() {
        add_action('init', array($this, 'init'), 25); // Load after OpenRouter handler
        
        // BuddyPress message hooks
        add_action('messages_message_sent', array($this, 'handle_message_sent'), 10, 1);
        add_filter('bp_messages_thread_current_threads', array($this, 'inject_agent_responses'), 10, 1);
        
        // AJAX handlers for real-time chat
        add_action('wp_ajax_send_agent_dm', array($this, 'ajax_send_agent_dm'));
        add_action('wp_ajax_get_thread_history', array($this, 'ajax_get_thread_history'));
        add_action('wp_ajax_clear_thread_history', array($this, 'ajax_clear_thread_history'));
    }
    
    public function init() {
        // Initialize OpenRouter handler
        if (class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_handler = new TerpediaOpenRouterHandler();
        }
        
        // Load conversation histories from database
        $this->load_conversation_histories();
    }
    
    /**
     * Handle when a message is sent to an agent
     */
    public function handle_message_sent($message) {
        if (!$this->openrouter_handler) {
            return;
        }
        
        // Check if message is sent to an agent
        $thread_id = $message->thread_id;
        $sender_id = $message->sender_id;
        $message_content = $message->message;
        
        // Get thread participants
        $thread = new BP_Messages_Thread($thread_id);
        $recipients = $thread->recipients;
        
        // Find if any recipient is an agent
        $agent_recipient = null;
        foreach ($recipients as $recipient) {
            $agent_type = get_user_meta($recipient->user_id, 'terpedia_agent_type', true);
            if (!empty($agent_type)) {
                $agent_recipient = $recipient->user_id;
                break;
            }
        }
        
        if (!$agent_recipient || $agent_recipient == $sender_id) {
            return; // No agent recipient or sender is the agent
        }
        
        // Generate agent response
        $this->generate_agent_dm_response($thread_id, $agent_recipient, $sender_id, $message_content);
    }
    
    /**
     * Generate agent DM response using OpenRouter
     */
    private function generate_agent_dm_response($thread_id, $agent_user_id, $sender_id, $user_message) {
        // Get agent data
        $agent_data = $this->get_agent_data($agent_user_id);
        if (!$agent_data) {
            return;
        }
        
        // Get or create conversation history for this thread
        $conversation_history = $this->get_thread_conversation_history($thread_id);
        
        // Add user message to history
        $conversation_history[] = array(
            'role' => 'user',
            'content' => $user_message,
            'timestamp' => current_time('mysql'),
            'user_id' => $sender_id
        );
        
        // Build messages for OpenRouter
        $messages = $this->build_openrouter_messages($agent_data, $conversation_history);
        
        // Generate response using OpenRouter GPT-OSS-120B
        $options = array(
            'model' => 'openai/gpt-oss-120b:free',
            'max_tokens' => 800,
            'temperature' => 0.7
        );
        
        $response = $this->openrouter_handler->chat_completion($messages, $options);
        
        if (is_wp_error($response)) {
            error_log('OpenRouter Error for Agent DM: ' . $response->get_error_message());
            return;
        }
        
        if (!isset($response['choices'][0]['message']['content'])) {
            return;
        }
        
        $ai_response = $response['choices'][0]['message']['content'];
        
        // Add AI response to conversation history
        $conversation_history[] = array(
            'role' => 'assistant',
            'content' => $ai_response,
            'timestamp' => current_time('mysql'),
            'user_id' => $agent_user_id,
            'model' => $response['model'] ?? 'openai/gpt-oss-120b:free'
        );
        
        // Save updated conversation history
        $this->save_thread_conversation_history($thread_id, $conversation_history);
        
        // Send response as agent
        $this->send_agent_message($thread_id, $agent_user_id, $ai_response);
    }
    
    /**
     * Get agent data for prompt building
     */
    private function get_agent_data($agent_user_id) {
        $agent_type = get_user_meta($agent_user_id, 'terpedia_agent_type', true);
        $terpene_name = get_user_meta($agent_user_id, 'terpedia_terpene_name', true);
        
        // Get terpene agent data
        if ($agent_type === 'terpene' && $terpene_name) {
            if (class_exists('TerpeneBuddyPressAgents')) {
                $terpene_agents = new TerpeneBuddyPressAgents();
                $agents = $terpene_agents->get_terpene_agents();
                return isset($agents[$terpene_name]) ? $agents[$terpene_name] : null;
            }
        }
        
        // Get expert agent data
        if ($agent_type === 'expert') {
            $username = get_userdata($agent_user_id)->user_login;
            $agent_key = str_replace('terpedia-', '', $username);
            
            // Map some specific usernames
            $username_mappings = array(
                'molecule-maven' => 'chemist',
                'pharmakin' => 'pharmacologist',
                'citeswell' => 'literature'
            );
            
            $agent_key = isset($username_mappings[$agent_key]) ? $username_mappings[$agent_key] : $agent_key;
            
            if (class_exists('TerpediaBuddyPressMessaging')) {
                $messaging = new TerpediaBuddyPressMessaging();
                return $messaging->get_agent_personality($agent_key);
            }
        }
        
        return null;
    }
    
    /**
     * Build OpenRouter messages from conversation history
     */
    private function build_openrouter_messages($agent_data, $conversation_history) {
        // System prompt for the agent
        $system_prompt = "You are " . $agent_data['name'] . ", " . $agent_data['description'] . "\n\n";
        $system_prompt .= "PERSONALITY:\n";
        $system_prompt .= "- Specialty: " . $agent_data['specialty'] . "\n";
        $system_prompt .= "- Voice Style: " . $agent_data['voice_style'] . "\n";
        $system_prompt .= "- Speech Pattern: " . $agent_data['speech_pattern'] . "\n\n";
        
        if (isset($agent_data['primary_effects'])) {
            $system_prompt .= "PRIMARY EFFECTS: " . implode(', ', $agent_data['primary_effects']) . "\n";
        }
        
        $system_prompt .= "\nDM CONVERSATION GUIDELINES:\n";
        $system_prompt .= "- This is a private direct message conversation\n";
        $system_prompt .= "- Stay in character and use your unique personality\n";
        $system_prompt .= "- Be helpful, friendly, and educational\n";
        $system_prompt .= "- Keep responses conversational but informative\n";
        $system_prompt .= "- Remember previous context from this conversation\n";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt)
        );
        
        // Add conversation history (limit to last 10 exchanges to stay within token limits)
        $recent_history = array_slice($conversation_history, -20); // Last 20 messages (10 exchanges)
        
        foreach ($recent_history as $msg) {
            if ($msg['role'] === 'user') {
                $messages[] = array(
                    'role' => 'user',
                    'content' => $msg['content']
                );
            } elseif ($msg['role'] === 'assistant') {
                $messages[] = array(
                    'role' => 'assistant', 
                    'content' => $msg['content']
                );
            }
        }
        
        return $messages;
    }
    
    /**
     * Get conversation history for a specific thread
     */
    private function get_thread_conversation_history($thread_id) {
        $cache_key = 'terpedia_thread_history_' . $thread_id;
        $history = wp_cache_get($cache_key);
        
        if ($history === false) {
            $history = get_option($cache_key, array());
            wp_cache_set($cache_key, $history, '', HOUR_IN_SECONDS);
        }
        
        return $history;
    }
    
    /**
     * Save conversation history for a specific thread
     */
    private function save_thread_conversation_history($thread_id, $history) {
        $cache_key = 'terpedia_thread_history_' . $thread_id;
        
        // Keep only last 50 messages to prevent database bloat
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        update_option($cache_key, $history);
        wp_cache_set($cache_key, $history, '', HOUR_IN_SECONDS);
    }
    
    /**
     * Load all conversation histories
     */
    private function load_conversation_histories() {
        global $wpdb;
        
        $histories = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'terpedia_thread_history_%'",
            ARRAY_A
        );
        
        foreach ($histories as $history) {
            $thread_id = str_replace('terpedia_thread_history_', '', $history['option_name']);
            $this->conversation_histories[$thread_id] = maybe_unserialize($history['option_value']);
        }
    }
    
    /**
     * Send message as agent
     */
    private function send_agent_message($thread_id, $agent_user_id, $message_content) {
        if (!function_exists('messages_new_message')) {
            return false;
        }
        
        // Get thread participants 
        $thread = new BP_Messages_Thread($thread_id);
        $recipients = array();
        
        foreach ($thread->recipients as $recipient) {
            if ($recipient->user_id != $agent_user_id) {
                $recipients[] = $recipient->user_id;
            }
        }
        
        if (empty($recipients)) {
            return false;
        }
        
        // Send message as agent
        $message_id = messages_new_message(array(
            'sender_id' => $agent_user_id,
            'thread_id' => $thread_id,
            'recipients' => $recipients,
            'subject' => '', // Will use existing thread subject
            'content' => $message_content
        ));
        
        if ($message_id) {
            // Mark as AI-generated message
            add_metadata('message', $message_id, 'terpedia_ai_generated', true);
            add_metadata('message', $message_id, 'terpedia_ai_model', 'openai/gpt-oss-120b:free');
            add_metadata('message', $message_id, 'terpedia_ai_provider', 'openrouter');
            add_metadata('message', $message_id, 'terpedia_ai_timestamp', current_time('mysql'));
        }
        
        return $message_id;
    }
    
    /**
     * AJAX: Send message to agent
     */
    public function ajax_send_agent_dm() {
        check_ajax_referer('terpedia_openrouter_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('error' => 'Must be logged in')));
        }
        
        $agent_username = sanitize_text_field($_POST['agent_username']);
        $message_content = sanitize_textarea_field($_POST['message']);
        $thread_id = intval($_POST['thread_id'] ?? 0);
        
        if (empty($agent_username) || empty($message_content)) {
            wp_die(json_encode(array('error' => 'Agent and message are required')));
        }
        
        $agent_user = get_user_by('login', $agent_username);
        if (!$agent_user) {
            wp_die(json_encode(array('error' => 'Agent not found')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Create or get existing thread
        if (empty($thread_id)) {
            $thread_id = messages_new_message(array(
                'sender_id' => $current_user_id,
                'recipients' => array($agent_user->ID),
                'subject' => 'Chat with ' . $agent_user->display_name,
                'content' => $message_content
            ));
        } else {
            // Add to existing thread
            messages_new_message(array(
                'sender_id' => $current_user_id,
                'thread_id' => $thread_id,
                'recipients' => array($agent_user->ID),
                'content' => $message_content
            ));
        }
        
        if ($thread_id) {
            // Generate agent response
            $this->generate_agent_dm_response($thread_id, $agent_user->ID, $current_user_id, $message_content);
            
            wp_die(json_encode(array(
                'success' => true,
                'thread_id' => $thread_id,
                'message' => 'Message sent and agent response generated'
            )));
        }
        
        wp_die(json_encode(array('error' => 'Failed to send message')));
    }
    
    /**
     * AJAX: Get thread conversation history
     */
    public function ajax_get_thread_history() {
        check_ajax_referer('terpedia_openrouter_nonce', 'nonce');
        
        $thread_id = intval($_POST['thread_id']);
        if (empty($thread_id)) {
            wp_die(json_encode(array('error' => 'Thread ID required')));
        }
        
        // Check if user has access to this thread
        if (!messages_check_thread_access($thread_id)) {
            wp_die(json_encode(array('error' => 'Access denied')));
        }
        
        $history = $this->get_thread_conversation_history($thread_id);
        
        wp_die(json_encode(array(
            'success' => true,
            'history' => $history,
            'thread_id' => $thread_id
        )));
    }
    
    /**
     * AJAX: Clear thread history
     */
    public function ajax_clear_thread_history() {
        check_ajax_referer('terpedia_openrouter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $thread_id = intval($_POST['thread_id']);
        if (empty($thread_id)) {
            wp_die(json_encode(array('error' => 'Thread ID required')));
        }
        
        $cache_key = 'terpedia_thread_history_' . $thread_id;
        delete_option($cache_key);
        wp_cache_delete($cache_key);
        
        wp_die(json_encode(array(
            'success' => true,
            'message' => 'Thread history cleared'
        )));
        }
    
    /**
     * Get conversation statistics
     */
    public function get_conversation_stats() {
        global $wpdb;
        
        $stats = array(
            'total_threads' => 0,
            'total_messages' => 0,
            'active_threads' => 0,
            'agents_with_conversations' => 0
        );
        
        // Count threads with AI conversation history
        $thread_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE 'terpedia_thread_history_%'"
        );
        
        $stats['total_threads'] = intval($thread_count);
        
        // Count total messages in histories
        $histories = $wpdb->get_results(
            "SELECT option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'terpedia_thread_history_%'",
            ARRAY_A
        );
        
        $total_messages = 0;
        $active_threads = 0;
        
        foreach ($histories as $history_data) {
            $history = maybe_unserialize($history_data['option_value']);
            if (is_array($history)) {
                $total_messages += count($history);
                
                // Consider active if messages in last 7 days
                $recent_messages = array_filter($history, function($msg) {
                    return isset($msg['timestamp']) && 
                           strtotime($msg['timestamp']) > (time() - (7 * 24 * 60 * 60));
                });
                
                if (!empty($recent_messages)) {
                    $active_threads++;
                }
            }
        }
        
        $stats['total_messages'] = $total_messages;
        $stats['active_threads'] = $active_threads;
        
        // Count agents with conversations
        $agents_with_conversations = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'terpedia_agent_type' 
             AND user_id IN (
                 SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key LIKE 'terpedia_thread_history_%'
             )"
        );
        
        $stats['agents_with_conversations'] = intval($agents_with_conversations);
        
        return $stats;
    }
}

// Initialize agent DM OpenRouter integration
new TerpediaAgentDMOpenRouter();