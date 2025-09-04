<?php
/**
 * Podcast Manager Class
 * Handles podcast episode generation and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Podcast_Manager {
    
    private $podcast_post_type = 'terpedia_podcast';
    
    public function __construct() {
        add_action('init', array($this, 'register_podcast_post_type'));
        add_action('wp_ajax_generate_podcast', array($this, 'ajax_generate_podcast'));
        add_action('wp_ajax_nopriv_generate_podcast', array($this, 'ajax_generate_podcast'));
        add_shortcode('terpedia_podcast_player', array($this, 'podcast_player_shortcode'));
        add_shortcode('terpedia_podcast_generator', array($this, 'podcast_generator_shortcode'));
    }
    
    /**
     * Register podcast custom post type
     */
    public function register_podcast_post_type() {
        $args = array(
            'label' => 'Terpedia Podcasts',
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'podcast'),
            'query_var' => true,
            'menu_icon' => 'dashicons-microphone',
            'supports' => array(
                'title',
                'editor',
                'excerpt',
                'trackbacks',
                'custom-fields',
                'comments',
                'revisions',
                'thumbnail',
                'author',
                'page-attributes'
            ),
            'labels' => array(
                'name' => 'Podcasts',
                'singular_name' => 'Podcast',
                'menu_name' => 'Terpedia Podcasts',
                'add_new' => 'Add New Episode',
                'add_new_item' => 'Add New Podcast Episode',
                'edit' => 'Edit',
                'edit_item' => 'Edit Podcast Episode',
                'new_item' => 'New Episode',
                'view' => 'View Episode',
                'view_item' => 'View Podcast Episode',
                'search_items' => 'Search Episodes',
                'not_found' => 'No Episodes Found',
                'not_found_in_trash' => 'No Episodes Found in Trash',
                'parent' => 'Parent Episode'
            )
        );
        register_post_type($this->podcast_post_type, $args);
    }
    
    /**
     * AJAX handler for podcast generation
     */
    public function ajax_generate_podcast() {
        check_ajax_referer('terpedia_podcast_nonce', 'nonce');
        
        $terpene_name = sanitize_text_field($_POST['terpene_name']);
        $research_topic = sanitize_text_field($_POST['research_topic']);
        
        if (empty($terpene_name)) {
            wp_send_json_error('Terpene name is required');
            return;
        }
        
        try {
            $result = $this->generate_podcast_episode($terpene_name, $research_topic);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error('Failed to generate podcast: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate podcast episode using Node.js service
     */
    private function generate_podcast_episode($terpene_name, $research_topic = '') {
        // Call Node.js service to generate podcast
        $node_service_url = 'http://localhost:3000/api/podcast/generate';
        
        $data = array(
            'terpeneName' => $terpene_name,
            'researchTopic' => $research_topic
        );
        
        $response = wp_remote_post($node_service_url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to connect to podcast generation service');
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (!$result || isset($result['error'])) {
            throw new Exception($result['error'] ?? 'Unknown error occurred');
        }
        
        // Create WordPress post for the podcast episode
        $post_id = wp_insert_post(array(
            'post_title' => $result['episode']['title'],
            'post_content' => $result['episode']['script'],
            'post_excerpt' => $result['episode']['description'],
            'post_status' => 'publish',
            'post_type' => $this->podcast_post_type,
            'meta_input' => array(
                'terpedia_audio_file' => $result['audioFile'],
                'terpedia_terpene_name' => $terpene_name,
                'terpedia_research_topic' => $research_topic,
                'terpedia_episode_duration' => $result['episode']['duration'] ?? 0
            )
        ));
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create podcast post');
        }
        
        return array(
            'post_id' => $post_id,
            'episode' => $result['episode'],
            'audio_file' => $result['audioFile'],
            'permalink' => get_permalink($post_id)
        );
    }
    
    /**
     * Podcast player shortcode
     */
    public function podcast_player_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'audio_file' => '',
            'title' => '',
            'description' => ''
        ), $atts);
        
        $audio_file = $atts['audio_file'];
        if (empty($audio_file) && $atts['id']) {
            $audio_file = get_post_meta($atts['id'], 'terpedia_audio_file', true);
            $atts['title'] = get_the_title($atts['id']);
            $atts['description'] = get_post_field('post_excerpt', $atts['id']);
        }
        
        if (empty($audio_file)) {
            return '<p>No audio file available.</p>';
        }
        
        $audio_url = wp_upload_dir()['baseurl'] . '/' . $audio_file;
        
        ob_start();
        ?>
        <div class="terpedia-podcast-player">
            <div class="podcast-info">
                <?php if (!empty($atts['title'])): ?>
                    <h3 class="podcast-title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($atts['description'])): ?>
                    <p class="podcast-description"><?php echo esc_html($atts['description']); ?></p>
                <?php endif; ?>
            </div>
            <audio controls preload="metadata" style="width: 100%; margin: 16px 0;">
                <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
            <div class="podcast-meta">
                <span class="terpene-queen-voice">🎙️ Voiced by Terpene Queen</span>
                <span class="elevenlabs-powered">Powered by ElevenLabs</span>
            </div>
        </div>
        
        <style>
        .terpedia-podcast-player {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        .podcast-title {
            color: #1a202c;
            margin: 0 0 8px 0;
            font-weight: 600;
        }
        .podcast-description {
            color: #64748b;
            margin: 0 0 16px 0;
            line-height: 1.5;
        }
        .podcast-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 12px;
        }
        .terpene-queen-voice {
            color: #10b981;
            font-weight: 500;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Podcast generator shortcode
     */
    public function podcast_generator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_form' => 'true'
        ), $atts);
        
        if ($atts['show_form'] !== 'true') {
            return '';
        }
        
        ob_start();
        ?>
        <div class="terpedia-podcast-generator">
            <h3>Generate Terpene Podcast Episode</h3>
            <form id="podcast-generator-form">
                <?php wp_nonce_field('terpedia_podcast_nonce', 'nonce'); ?>
                <div class="form-group">
                    <label for="terpene_name">Terpene Name:</label>
                    <input type="text" id="terpene_name" name="terpene_name" required 
                           placeholder="e.g., Linalool, Humulene, Limonene">
                </div>
                <div class="form-group">
                    <label for="research_topic">Research Focus (Optional):</label>
                    <input type="text" id="research_topic" name="research_topic" 
                           placeholder="e.g., anxiety relief, appetite suppression">
                </div>
                <button type="submit" id="generate-btn">Generate Podcast Episode</button>
            </form>
            <div id="generation-status" style="display: none;"></div>
            <div id="podcast-result" style="display: none;"></div>
        </div>
        
        <style>
        .terpedia-podcast-generator {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: #1a202c;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 14px;
        }
        #generate-btn {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        #generate-btn:hover {
            background: #059669;
        }
        #generate-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#podcast-generator-form').on('submit', function(e) {
                e.preventDefault();
                
                const btn = $('#generate-btn');
                const status = $('#generation-status');
                const result = $('#podcast-result');
                
                btn.prop('disabled', true).text('Generating...');
                status.show().html('<p>Generating podcast episode with Terpene Queen voice...</p>');
                result.hide();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'generate_podcast',
                        terpene_name: $('#terpene_name').val(),
                        research_topic: $('#research_topic').val(),
                        nonce: $('input[name="nonce"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            status.hide();
                            result.show().html(`
                                <h4>Episode Generated Successfully!</h4>
                                <p><strong>Title:</strong> ${response.data.episode.title}</p>
                                <p><strong>Description:</strong> ${response.data.episode.description}</p>
                                <p><a href="${response.data.permalink}" target="_blank">View Episode</a></p>
                            `);
                        } else {
                            status.html(`<p style="color: red;">Error: ${response.data}</p>`);
                        }
                    },
                    error: function() {
                        status.html('<p style="color: red;">Failed to generate podcast episode.</p>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Generate Podcast Episode');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize the podcast manager
new Terpedia_Podcast_Manager();