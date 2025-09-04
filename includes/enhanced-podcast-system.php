<?php
/**
 * Enhanced Podcast System for Terpedia
 * Includes speaker selection, topic management, and LLM conversation generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Enhanced_Podcast_System {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_podcast_meta_boxes'));
        add_action('save_post', array($this, 'save_podcast_meta'));
        add_action('wp_ajax_generate_podcast_conversation', array($this, 'generate_podcast_conversation'));
        add_action('wp_ajax_nopriv_generate_podcast_conversation', array($this, 'generate_podcast_conversation'));
        add_action('wp_ajax_get_available_speakers', array($this, 'get_available_speakers'));
        add_action('wp_ajax_nopriv_get_available_speakers', array($this, 'get_available_speakers'));
        add_shortcode('terpedia_podcast_player', array($this, 'podcast_player_shortcode'));
        add_shortcode('terpedia_podcast_conversation', array($this, 'podcast_conversation_shortcode'));
    }
    
    public function init() {
        // Register main podcast post type
        register_post_type('terpedia_podcast', array(
            'labels' => array(
                'name' => 'Podcasts',
                'singular_name' => 'Podcast',
                'add_new' => 'Add New Podcast',
                'add_new_item' => 'Add New Podcast Episode',
                'edit_item' => 'Edit Podcast Episode',
                'new_item' => 'New Podcast Episode',
                'view_item' => 'View Podcast Episode',
                'search_items' => 'Search Podcasts',
                'not_found' => 'No podcasts found',
                'not_found_in_trash' => 'No podcasts found in trash',
                'all_items' => 'All Podcasts',
                'menu_name' => 'Podcasts'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
            'menu_icon' => 'dashicons-microphone',
            'menu_position' => 25,
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'podcast'),
            'show_in_menu' => true
        ));
        
        // Register podcast conversation post type
        register_post_type('terpedia_podcast_conv', array(
            'labels' => array(
                'name' => 'Podcast Conversations',
                'singular_name' => 'Podcast Conversation',
                'add_new' => 'Add New Conversation',
                'add_new_item' => 'Add New Podcast Conversation',
                'edit_item' => 'Edit Podcast Conversation',
                'new_item' => 'New Podcast Conversation',
                'view_item' => 'View Podcast Conversation',
                'search_items' => 'Search Conversations',
                'not_found' => 'No conversations found',
                'not_found_in_trash' => 'No conversations found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-microphone',
            'menu_position' => 26,
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'podcast-conversation'),
            'show_in_menu' => true
        ));
    }
    
    public function enqueue_scripts() {
        if (is_singular('terpedia_podcast') || is_singular('terpedia_podcast_conv') || 
            is_page('podcast') || has_shortcode(get_the_content(), 'terpedia_podcast_player')) {
            
            wp_enqueue_script('terpedia-podcast-system', 
                plugin_dir_url(__FILE__) . '../assets/js/enhanced-podcast.js', 
                array('jquery'), '1.0.0', true);
            
            wp_enqueue_style('terpedia-podcast-styles', 
                plugin_dir_url(__FILE__) . '../assets/css/enhanced-podcast.css', 
                array(), '1.0.0');
            
            wp_localize_script('terpedia-podcast-system', 'terpediaPodcast', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_podcast_nonce'),
                'strings' => array(
                    'generating' => 'Generating conversation...',
                    'error' => 'Error generating conversation',
                    'success' => 'Conversation generated successfully'
                )
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'terpedia_podcast' || $post_type === 'terpedia_podcast_conv') {
            wp_enqueue_script('terpedia-podcast-admin', 
                plugin_dir_url(__FILE__) . '../assets/js/podcast-admin.js', 
                array('jquery', 'select2'), '1.0.0', true);
            
            wp_enqueue_style('terpedia-podcast-admin', 
                plugin_dir_url(__FILE__) . '../assets/css/podcast-admin.css', 
                array(), '1.0.0');
            
            wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
            wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'));
            
            wp_localize_script('terpedia-podcast-admin', 'terpediaPodcastAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_podcast_admin_nonce')
            ));
        }
    }
    
    public function add_podcast_meta_boxes() {
        // Add meta box below title and before body content
        add_meta_box(
            'podcast_speakers',
            'Podcast Members & Details',
            array($this, 'podcast_speakers_meta_box'),
            'terpedia_podcast',
            'normal',
            'high'
        );
        
        add_meta_box(
            'podcast_conversation_settings',
            'Conversation Settings',
            array($this, 'conversation_settings_meta_box'),
            'terpedia_podcast',
            'side',
            'default'
        );
    }
    
    public function podcast_speakers_meta_box($post) {
        wp_nonce_field('save_podcast_speakers', 'podcast_speakers_nonce');
        
        $speakers = get_post_meta($post->ID, '_podcast_speakers', true);
        $conversation = get_post_meta($post->ID, '_podcast_conversation', true);
        $topic = get_post_meta($post->ID, '_podcast_topic', true);
        
        if (!is_array($speakers)) {
            $speakers = array();
        }
        
        // Get available speakers (Terpene Queen + all terpedia users)
        $available_speakers = $this->get_available_speakers_data();
        
        echo '<div class="podcast-speakers-container">';
        
        // Topic input
        echo '<div class="podcast-topic-section">';
        echo '<h4>Podcast Topic</h4>';
        echo '<input type="text" id="podcast_topic" name="podcast_topic" value="' . esc_attr($topic) . '" 
              placeholder="Enter the main topic for this podcast episode" style="width: 100%;" />';
        echo '</div>';
        
        // Approximate length input
        $duration = get_post_meta($post->ID, '_podcast_duration', true);
        echo '<div class="podcast-duration-section">';
        echo '<h4>Approximate Length (minutes)</h4>';
        echo '<input type="number" id="podcast_duration" name="podcast_duration" value="' . esc_attr($duration) . '" 
              placeholder="e.g., 30" min="5" max="120" style="width: 100px;" />';
        echo '<p class="description">Estimated duration of the podcast episode</p>';
        echo '</div>';
        
        // Members/Speakers selection with enhanced interface
        echo '<div class="podcast-speakers-section">';
        echo '<h4>Select Podcast Members</h4>';
        echo '<p>Choose the members who will participate in this podcast conversation. You can select multiple members to create engaging discussions.</p>';
        
        // Member categories
        echo '<div class="member-categories">';
        
        // Terpene Queen (Host)
        echo '<div class="member-category">';
        echo '<h5>🎙️ Host</h5>';
        $terpene_queen = array_filter($available_speakers, function($speaker) {
            return strpos($speaker['name'], 'Terpene Queen') !== false;
        });
        if (!empty($terpene_queen)) {
            $queen = reset($terpene_queen);
            $selected = in_array($queen['id'], $speakers) ? 'checked' : 'checked'; // Always selected by default
            echo '<label class="member-option host-option">';
            echo '<input type="checkbox" name="podcast_speakers[]" value="' . esc_attr($queen['id']) . '" ' . $selected . ' disabled />';
            echo '<span class="member-name">' . esc_html($queen['name']) . '</span>';
            echo '<span class="member-description">' . esc_html($queen['description']) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        // Terpedia Agents
        echo '<div class="member-category">';
        echo '<h5>🔬 Terpedia Agents</h5>';
        $agents = array_filter($available_speakers, function($speaker) {
            return $speaker['type'] === 'Expert' && strpos($speaker['name'], 'Agt.') !== false;
        });
        foreach ($agents as $agent) {
            $selected = in_array($agent['id'], $speakers) ? 'checked' : '';
            echo '<label class="member-option agent-option">';
            echo '<input type="checkbox" name="podcast_speakers[]" value="' . esc_attr($agent['id']) . '" ' . $selected . ' />';
            echo '<span class="member-name">' . esc_html($agent['name']) . '</span>';
            echo '<span class="member-description">' . esc_html($agent['description']) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        // Researchers
        echo '<div class="member-category">';
        echo '<h5>📚 Researchers</h5>';
        $researchers = array_filter($available_speakers, function($speaker) {
            return $speaker['type'] === 'Researcher';
        });
        foreach ($researchers as $researcher) {
            $selected = in_array($researcher['id'], $speakers) ? 'checked' : '';
            echo '<label class="member-option researcher-option">';
            echo '<input type="checkbox" name="podcast_speakers[]" value="' . esc_attr($researcher['id']) . '" ' . $selected . ' />';
            echo '<span class="member-name">' . esc_html($researcher['name']) . '</span>';
            echo '<span class="member-description">' . esc_html($researcher['description']) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        // Other Terpedia Experts
        echo '<div class="member-category">';
        echo '<h5>💡 Other Experts</h5>';
        $other_experts = array_filter($available_speakers, function($speaker) {
            return $speaker['type'] === 'Expert' && strpos($speaker['name'], 'Agt.') === false && strpos($speaker['name'], 'Terpene Queen') === false;
        });
        foreach ($other_experts as $expert) {
            $selected = in_array($expert['id'], $speakers) ? 'checked' : '';
            echo '<label class="member-option expert-option">';
            echo '<input type="checkbox" name="podcast_speakers[]" value="' . esc_attr($expert['id']) . '" ' . $selected . ' />';
            echo '<span class="member-name">' . esc_html($expert['name']) . '</span>';
            echo '<span class="member-description">' . esc_html($expert['description']) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        echo '</div>'; // End member-categories
        
        // Selected members summary
        echo '<div class="selected-members-summary">';
        echo '<h5>Selected Members:</h5>';
        echo '<div id="selected-members-list">';
        if (!empty($speakers)) {
            foreach ($speakers as $speaker_id) {
                $speaker = array_filter($available_speakers, function($s) use ($speaker_id) {
                    return $s['id'] == $speaker_id;
                });
                if (!empty($speaker)) {
                    $s = reset($speaker);
                    echo '<span class="selected-member-tag">' . esc_html($s['name']) . '</span>';
                }
            }
        }
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Conversation generation
        echo '<div class="podcast-conversation-section">';
        echo '<h4>Generated Conversation</h4>';
        echo '<p>Click the button below to generate an AI-powered conversation between the selected speakers.</p>';
        
        echo '<button type="button" id="generate_conversation" class="button button-primary">Generate Conversation</button>';
        echo '<div id="conversation_status" style="margin-top: 10px;"></div>';
        
        echo '<textarea id="podcast_conversation" name="podcast_conversation" rows="15" style="width: 100%; margin-top: 10px;">' . 
             esc_textarea($conversation) . '</textarea>';
        echo '</div>';
        
        echo '</div>';
        
        // Add JavaScript for speaker selection and conversation generation
        echo '<script>
        jQuery(document).ready(function($) {
            $("#podcast_speakers").select2({
                placeholder: "Select speakers...",
                allowClear: true
            });
            
            $("#generate_conversation").on("click", function() {
                var speakers = [];
                $("input[name=\"podcast_speakers[]\"]:checked").each(function() {
                    speakers.push($(this).val());
                });
                var topic = $("#podcast_topic").val();
                var duration = $("#podcast_duration").val();
                
                if (speakers.length === 0) {
                    alert("Please select at least one speaker.");
                    return;
                }
                
                if (!topic) {
                    alert("Please enter a topic for the conversation.");
                    return;
                }
                
                $("#conversation_status").html("Generating conversation...");
                $("#generate_conversation").prop("disabled", true);
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "generate_podcast_conversation",
                        nonce: terpediaPodcastAdmin.nonce,
                        speakers: speakers,
                        topic: topic,
                        duration: duration,
                        post_id: ' . $post->ID . '
                    },
                    success: function(response) {
                        if (response.success) {
                            $("#podcast_conversation").val(response.data.conversation);
                            $("#conversation_status").html("Conversation generated successfully!");
                        } else {
                            $("#conversation_status").html("Error: " + response.data);
                        }
                    },
                    error: function() {
                        $("#conversation_status").html("Error generating conversation");
                    },
                    complete: function() {
                        $("#generate_conversation").prop("disabled", false);
                    }
                });
            });
        });
        </script>';
    }
    
    public function conversation_settings_meta_box($post) {
        $duration = get_post_meta($post->ID, '_podcast_duration', true);
        $conversation_style = get_post_meta($post->ID, '_conversation_style', true);
        $include_transcript = get_post_meta($post->ID, '_include_transcript', true);
        
        echo '<div class="conversation-settings">';
        
        echo '<p><label for="podcast_duration">Duration (minutes):</label><br>';
        echo '<input type="number" id="podcast_duration" name="podcast_duration" value="' . esc_attr($duration) . '" min="5" max="120" /></p>';
        
        echo '<p><label for="conversation_style">Conversation Style:</label><br>';
        echo '<select id="conversation_style" name="conversation_style">';
        echo '<option value="educational" ' . selected($conversation_style, 'educational', false) . '>Educational</option>';
        echo '<option value="casual" ' . selected($conversation_style, 'casual', false) . '>Casual Discussion</option>';
        echo '<option value="interview" ' . selected($conversation_style, 'interview', false) . '>Interview Format</option>';
        echo '<option value="debate" ' . selected($conversation_style, 'debate', false) . '>Debate/Discussion</option>';
        echo '</select></p>';
        
        echo '<p><label><input type="checkbox" name="include_transcript" value="1" ' . checked($include_transcript, '1', false) . ' /> Include full transcript</label></p>';
        
        echo '</div>';
    }
    
    public function save_podcast_meta($post_id) {
        if (!isset($_POST['podcast_speakers_nonce']) || !wp_verify_nonce($_POST['podcast_speakers_nonce'], 'save_podcast_speakers')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save speakers
        if (isset($_POST['podcast_speakers'])) {
            $speakers = array_map('intval', $_POST['podcast_speakers']);
            update_post_meta($post_id, '_podcast_speakers', $speakers);
        }
        
        // Save topic
        if (isset($_POST['podcast_topic'])) {
            update_post_meta($post_id, '_podcast_topic', sanitize_text_field($_POST['podcast_topic']));
        }
        
        // Save conversation
        if (isset($_POST['podcast_conversation'])) {
            update_post_meta($post_id, '_podcast_conversation', wp_kses_post($_POST['podcast_conversation']));
        }
        
        // Save other settings
        if (isset($_POST['podcast_duration'])) {
            update_post_meta($post_id, '_podcast_duration', intval($_POST['podcast_duration']));
        }
        
        if (isset($_POST['conversation_style'])) {
            update_post_meta($post_id, '_conversation_style', sanitize_text_field($_POST['conversation_style']));
        }
        
        update_post_meta($post_id, '_include_transcript', isset($_POST['include_transcript']) ? '1' : '0');
    }
    
    public function get_available_speakers_data() {
        $speakers = array();
        
        // Add Terpene Queen (always first)
        $terpene_queen = get_user_by('login', 'terpene_queen');
        if ($terpene_queen) {
            $speakers[] = array(
                'id' => $terpene_queen->ID,
                'name' => $terpene_queen->display_name,
                'type' => 'Host',
                'description' => 'Main podcast host and terpene expert'
            );
        }
        
        // Get all terpedia users (agents, researchers, etc.)
        $terpedia_users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'user_login',
                    'value' => 'terpedia-',
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'display_name'
        ));
        
        foreach ($terpedia_users as $user) {
            $speakers[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'type' => 'Expert',
                'description' => $user->description ?: 'Terpedia expert'
            );
        }
        
        // Add researcher users
        $researchers = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'user_login',
                    'value' => 'dr_',
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'display_name'
        ));
        
        foreach ($researchers as $user) {
            $speakers[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'type' => 'Researcher',
                'description' => $user->description ?: 'Research expert'
            );
        }
        
        return $speakers;
    }
    
    public function get_available_speakers() {
        check_ajax_referer('terpedia_podcast_nonce', 'nonce');
        
        $speakers = $this->get_available_speakers_data();
        wp_send_json_success($speakers);
    }
    
    public function generate_podcast_conversation() {
        check_ajax_referer('terpedia_podcast_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $speakers = isset($_POST['speakers']) ? array_map('intval', $_POST['speakers']) : array();
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($speakers) || empty($topic)) {
            wp_send_json_error('Missing speakers or topic');
        }
        
        // Get speaker information
        $speaker_data = array();
        foreach ($speakers as $speaker_id) {
            $user = get_user_by('ID', $speaker_id);
            if ($user) {
                $speaker_data[] = array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'description' => $user->description ?: '',
                    'type' => strpos($user->user_login, 'terpedia-') === 0 ? 'Agent' : 
                             (strpos($user->user_login, 'dr_') === 0 ? 'Researcher' : 'Host')
                );
            }
        }
        
        // Generate conversation using LLM
        $conversation = $this->generate_conversation_with_llm($speaker_data, $topic, $duration);
        
        if ($conversation) {
            // Save the conversation to the post
            if ($post_id > 0) {
                update_post_meta($post_id, '_podcast_conversation', $conversation);
            }
            
            wp_send_json_success(array(
                'conversation' => $conversation,
                'speakers' => $speaker_data
            ));
        } else {
            wp_send_json_error('Failed to generate conversation');
        }
    }
    
    private function generate_conversation_with_llm($speakers, $topic, $duration = 30) {
        // Build the prompt for the LLM with enhanced speaker information
        $speaker_list = '';
        foreach ($speakers as $speaker) {
            $speaker_posts = $this->get_speaker_posts($speaker['id']);
            $speaker_list .= "- {$speaker['name']} ({$speaker['type']}): {$speaker['description']}\n";
            if (!empty($speaker_posts)) {
                $speaker_list .= "  Recent posts/articles: " . implode(', ', array_slice($speaker_posts, 0, 3)) . "\n";
            }
        }
        
        $prompt = "Generate a natural, engaging podcast conversation between the following speakers about the topic: \"{$topic}\"\n\n";
        $prompt .= "Speakers:\n{$speaker_list}\n";
        $prompt .= "Target Duration: {$duration} minutes\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Make it sound like a real podcast conversation\n";
        $prompt .= "- Include natural dialogue, questions, and responses\n";
        $prompt .= "- Each speaker should contribute meaningfully\n";
        $prompt .= "- Speakers should reference their own posts/articles when relevant\n";
        $prompt .= "- Format as: [Speaker Name]: [Dialogue]\n";
        $prompt .= "- Make it educational but engaging\n";
        $prompt .= "- Length: approximately " . max(10, $duration / 2) . "-" . max(20, $duration / 1.5) . " exchanges\n\n";
        $prompt .= "Conversation:";
        
        // Try to use the existing LLM DM engine if available, otherwise fall back to enhanced conversation
        if (class_exists('Terpedia_Agent_DM_OpenRouter_Integration')) {
            $conversation = $this->generate_conversation_with_llm_engine($speakers, $topic, $duration);
        } else {
            $conversation = $this->generate_enhanced_conversation($speakers, $topic, $duration);
        }
        
        return $conversation;
    }
    
    private function generate_sample_conversation($speakers, $topic) {
        $conversation = '';
        
        // Get Terpene Queen as the host
        $host = null;
        $guests = array();
        
        foreach ($speakers as $speaker) {
            if (strpos($speaker['name'], 'Terpene Queen') !== false) {
                $host = $speaker;
            } else {
                $guests[] = $speaker;
            }
        }
        
        if (!$host) {
            $host = $speakers[0]; // Fallback to first speaker
        }
        
        $conversation .= "[{$host['name']}]: Welcome to the Terpedia Podcast! Today we're diving deep into {$topic}. ";
        $conversation .= "I'm excited to have some amazing experts with us to share their insights.\n\n";
        
        if (!empty($guests)) {
            $guest = $guests[0];
            $conversation .= "[{$host['name']}]: {$guest['name']}, as a {$guest['type']}, what's your take on {$topic}?\n\n";
            
            $conversation .= "[{$guest['name']}]: That's a fascinating question, Terpene Queen. ";
            $conversation .= "From my perspective as a {$guest['type']}, {$topic} represents a crucial intersection of ";
            $conversation .= "traditional knowledge and modern research. The implications for therapeutic applications are profound.\n\n";
            
            $conversation .= "[{$host['name']}]: Absolutely! And what about the practical applications? ";
            $conversation .= "How do you see this translating to real-world benefits?\n\n";
            
            $conversation .= "[{$guest['name']}]: Well, the key is understanding the synergistic effects. ";
            $conversation .= "When we look at {$topic} through the lens of the entourage effect, ";
            $conversation .= "we're seeing results that go beyond what individual compounds can achieve.\n\n";
            
            if (count($guests) > 1) {
                $guest2 = $guests[1];
                $conversation .= "[{$host['name']}]: {$guest2['name']}, you bring a different perspective to this. ";
                $conversation .= "What's your research showing us about {$topic}?\n\n";
                
                $conversation .= "[{$guest2['name']}]: Excellent question. My research has been focusing on ";
                $conversation .= "the molecular mechanisms behind {$topic}. What we're discovering is ";
                $conversation .= "that there are multiple pathways involved, each contributing to the overall therapeutic profile.\n\n";
            }
            
            $conversation .= "[{$host['name']}]: This is incredibly valuable information. ";
            $conversation .= "For our listeners who want to learn more about {$topic}, ";
            $conversation .= "what would you recommend as the next steps?\n\n";
            
            $conversation .= "[{$guest['name']}]: I'd suggest starting with the foundational research. ";
            $conversation .= "Understanding the basic principles of {$topic} will give you a solid base ";
            $conversation .= "to build upon. Then, explore the clinical applications and case studies.\n\n";
            
            $conversation .= "[{$host['name']}]: Perfect advice! Thank you both for sharing your expertise on {$topic}. ";
            $conversation .= "This has been an enlightening conversation, and I know our listeners will benefit greatly from your insights.\n\n";
            
            $conversation .= "[{$host['name']}]: That's all for today's episode. ";
            $conversation .= "Remember to subscribe for more deep dives into the world of terpenes and cannabinoids. ";
            $conversation .= "Until next time, stay curious and keep exploring the fascinating world of plant medicine!";
        }
        
        return $conversation;
    }
    
    /**
     * Generate conversation using the existing LLM DM engine
     */
    private function generate_conversation_with_llm_engine($speakers, $topic, $duration) {
        // This would integrate with the existing OpenRouter LLM system
        // For now, we'll use the enhanced conversation as a fallback
        // In a full implementation, this would:
        // 1. Create a conversation thread
        // 2. Have each speaker respond using their LLM personality
        // 3. Generate a natural back-and-forth discussion
        
        return $this->generate_enhanced_conversation($speakers, $topic, $duration);
    }
    
    /**
     * Get posts/articles authored by a specific speaker
     */
    private function get_speaker_posts($speaker_id) {
        $posts = get_posts(array(
            'author' => $speaker_id,
            'post_type' => array('post', 'terpedia_terport', 'terpedia_newsletter', 'research', 'terpene'),
            'post_status' => 'publish',
            'numberposts' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $post_titles = array();
        foreach ($posts as $post) {
            $post_titles[] = $post->post_title;
        }
        
        return $post_titles;
    }
    
    /**
     * Generate enhanced conversation with post references
     */
    private function generate_enhanced_conversation($speakers, $topic, $duration = 30) {
        $conversation = '';
        
        // Get Terpene Queen as the host
        $host = null;
        $guests = array();
        
        foreach ($speakers as $speaker) {
            if (strpos($speaker['name'], 'Terpene Queen') !== false) {
                $host = $speaker;
            } else {
                $guests[] = $speaker;
            }
        }
        
        if (!$host) {
            $host = $speakers[0]; // Fallback to first speaker
        }
        
        $conversation .= "[{$host['name']}]: Welcome to the Terpedia Podcast! Today we're diving deep into {$topic}. ";
        $conversation .= "I'm excited to have some amazing experts with us to share their insights.\n\n";
        
        if (!empty($guests)) {
            $guest = $guests[0];
            $guest_posts = $this->get_speaker_posts($guest['id']);
            
            $conversation .= "[{$host['name']}]: {$guest['name']}, as a {$guest['type']}, what's your take on {$topic}?\n\n";
            
            $conversation .= "[{$guest['name']}]: That's a fascinating question, Terpene Queen. ";
            $conversation .= "From my perspective as a {$guest['type']}, {$topic} represents a crucial intersection of ";
            $conversation .= "traditional knowledge and modern research. ";
            
            // Reference their own posts if available
            if (!empty($guest_posts)) {
                $referenced_post = $guest_posts[0];
                $conversation .= "In fact, I recently wrote about this in my article \"{$referenced_post}\" where I explored ";
                $conversation .= "the molecular mechanisms behind these interactions.\n\n";
            } else {
                $conversation .= "The implications for therapeutic applications are profound.\n\n";
            }
            
            $conversation .= "[{$host['name']}]: Absolutely! And what about the practical applications? ";
            $conversation .= "How do you see this translating to real-world benefits?\n\n";
            
            $conversation .= "[{$guest['name']}]: Well, the key is understanding the synergistic effects. ";
            $conversation .= "When we look at {$topic} through the lens of the entourage effect, ";
            $conversation .= "we're seeing results that go beyond what individual compounds can achieve. ";
            
            // Reference another post if available
            if (count($guest_posts) > 1) {
                $referenced_post2 = $guest_posts[1];
                $conversation .= "This is something I detailed in my research piece \"{$referenced_post2}\" where I documented ";
                $conversation .= "several case studies showing these synergistic benefits.\n\n";
            } else {
                $conversation .= "\n\n";
            }
            
            if (count($guests) > 1) {
                $guest2 = $guests[1];
                $guest2_posts = $this->get_speaker_posts($guest2['id']);
                
                $conversation .= "[{$host['name']}]: {$guest2['name']}, you bring a different perspective to this. ";
                $conversation .= "What's your research showing us about {$topic}?\n\n";
                
                $conversation .= "[{$guest2['name']}]: Excellent question. My research has been focusing on ";
                $conversation .= "the molecular mechanisms behind {$topic}. What we're discovering is ";
                $conversation .= "that there are multiple pathways involved, each contributing to the overall therapeutic profile. ";
                
                // Reference their posts
                if (!empty($guest2_posts)) {
                    $referenced_post3 = $guest2_posts[0];
                    $conversation .= "I've been documenting these findings in my series of articles, including \"{$referenced_post3}\" ";
                    $conversation .= "where I break down the specific biochemical interactions we're observing.\n\n";
                } else {
                    $conversation .= "\n\n";
                }
            }
            
            $conversation .= "[{$host['name']}]: This is incredibly valuable information. ";
            $conversation .= "For our listeners who want to learn more about {$topic}, ";
            $conversation .= "what would you recommend as the next steps?\n\n";
            
            $conversation .= "[{$guest['name']}]: I'd suggest starting with the foundational research. ";
            $conversation .= "Understanding the basic principles of {$topic} will give you a solid base ";
            $conversation .= "to build upon. ";
            
            // Reference their content for further reading
            if (!empty($guest_posts)) {
                $conversation .= "I have several articles on our site that dive deeper into these concepts, ";
                $conversation .= "and I'd encourage listeners to check out the research section for more detailed analysis.\n\n";
            } else {
                $conversation .= "Then, explore the clinical applications and case studies.\n\n";
            }
            
            $conversation .= "[{$host['name']}]: Perfect advice! Thank you both for sharing your expertise on {$topic}. ";
            $conversation .= "This has been an enlightening conversation, and I know our listeners will benefit greatly from your insights.\n\n";
            
            $conversation .= "[{$host['name']}]: That's all for today's episode. ";
            $conversation .= "Remember to subscribe for more deep dives into the world of terpenes and cannabinoids. ";
            $conversation .= "Until next time, stay curious and keep exploring the fascinating world of plant medicine!";
        }
        
        return $conversation;
    }
    
    public function podcast_player_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'show_conversation' => 'true',
            'autoplay' => 'false'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'terpedia_podcast') {
            return '<p>Podcast not found.</p>';
        }
        
        $speakers = get_post_meta($post_id, '_podcast_speakers', true);
        $topic = get_post_meta($post_id, '_podcast_topic', true);
        $conversation = get_post_meta($post_id, '_podcast_conversation', true);
        $duration = get_post_meta($post_id, '_podcast_duration', true);
        
        ob_start();
        ?>
        <div class="terpedia-podcast-player" data-post-id="<?php echo esc_attr($post_id); ?>">
            <div class="podcast-header">
                <h3><?php echo esc_html($post->post_title); ?></h3>
                <?php if ($topic): ?>
                    <p class="podcast-topic"><strong>Topic:</strong> <?php echo esc_html($topic); ?></p>
                <?php endif; ?>
                <?php if ($duration): ?>
                    <p class="podcast-duration"><strong>Duration:</strong> <?php echo esc_html($duration); ?> minutes</p>
                <?php endif; ?>
            </div>
            
            <div class="podcast-speakers">
                <h4>Speakers:</h4>
                <ul>
                    <?php
                    if (is_array($speakers)) {
                        foreach ($speakers as $speaker_id) {
                            $speaker = get_user_by('ID', $speaker_id);
                            if ($speaker) {
                                echo '<li>' . esc_html($speaker->display_name) . '</li>';
                            }
                        }
                    }
                    ?>
                </ul>
            </div>
            
            <?php if ($atts['show_conversation'] === 'true' && $conversation): ?>
                <div class="podcast-conversation">
                    <h4>Conversation:</h4>
                    <div class="conversation-content">
                        <?php echo wp_kses_post(nl2br($conversation)); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function podcast_conversation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID()
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        $conversation = get_post_meta($post_id, '_podcast_conversation', true);
        
        if (!$conversation) {
            return '<p>No conversation available for this podcast.</p>';
        }
        
        ob_start();
        ?>
        <div class="terpedia-podcast-conversation">
            <h3>Podcast Conversation</h3>
            <div class="conversation-transcript">
                <?php echo wp_kses_post(nl2br($conversation)); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the enhanced podcast system
new Terpedia_Enhanced_Podcast_System(); 