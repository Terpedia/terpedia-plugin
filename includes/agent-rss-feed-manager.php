<?php
/**
 * Agent RSS Feed Manager
 * Manages RSS feed subscriptions and content generation for each AI agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaAgentRSSManager {
    
    private $openrouter_handler;
    
    public function __construct() {
        add_action('init', array($this, 'init'), 30);
        
        // Admin interface hooks
        add_action('show_user_profile', array($this, 'add_agent_rss_fields'));
        add_action('edit_user_profile', array($this, 'add_agent_rss_fields'));
        add_action('personal_options_update', array($this, 'save_agent_rss_fields'));
        add_action('edit_user_profile_update', array($this, 'save_agent_rss_fields'));
        
        // Cron hooks for daily processing
        add_action('terpedia_daily_rss_check', array($this, 'process_all_agent_feeds'));
        add_action('terpedia_agent_feed_process', array($this, 'process_single_agent_feed'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_test_agent_rss_feed', array($this, 'ajax_test_rss_feed'));
        add_action('wp_ajax_trigger_agent_feed_update', array($this, 'ajax_trigger_feed_update'));
        add_action('wp_ajax_get_agent_feed_stats', array($this, 'ajax_get_feed_stats'));
        
        // Schedule daily cron if not already scheduled
        if (!wp_next_scheduled('terpedia_daily_rss_check')) {
            wp_schedule_event(time(), 'daily', 'terpedia_daily_rss_check');
        }
    }
    
    public function init() {
        if (class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_handler = new TerpediaOpenRouterHandler();
        }
    }
    
    /**
     * Add RSS feed management fields to agent profiles
     */
    public function add_agent_rss_fields($user) {
        $agent_type = get_user_meta($user->ID, 'terpedia_agent_type', true);
        
        // Only show for AI agents
        if (empty($agent_type)) {
            return;
        }
        
        $rss_feeds = get_user_meta($user->ID, 'terpedia_rss_feeds', true) ?: array();
        $keywords = get_user_meta($user->ID, 'terpedia_rss_keywords', true) ?: '';
        $post_frequency = get_user_meta($user->ID, 'terpedia_post_frequency', true) ?: 'daily';
        $last_check = get_user_meta($user->ID, 'terpedia_last_rss_check', true);
        $posts_created = get_user_meta($user->ID, 'terpedia_posts_created_count', true) ?: 0;
        
        ?>
        <div id="terpedia-rss-settings" style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h3>🔗 RSS Feed Management</h3>
            <p>Configure RSS feeds and keywords for automatic content generation.</p>
            
            <!-- Feed Statistics -->
            <div style="background: white; padding: 15px; margin: 15px 0; border-radius: 3px;">
                <h4>📊 Feed Statistics</h4>
                <p><strong>Posts Created:</strong> <?php echo esc_html($posts_created); ?></p>
                <p><strong>Last Check:</strong> <?php echo $last_check ? esc_html(human_time_diff(strtotime($last_check))) . ' ago' : 'Never'; ?></p>
                <p><strong>Next Check:</strong> <?php echo wp_next_scheduled('terpedia_agent_feed_process', array($user->ID)) ? human_time_diff(wp_next_scheduled('terpedia_agent_feed_process', array($user->ID))) : 'Not scheduled'; ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="terpedia_rss_keywords">Search Keywords</label></th>
                    <td>
                        <input type="text" id="terpedia_rss_keywords" name="terpedia_rss_keywords" 
                               value="<?php echo esc_attr($keywords); ?>" class="regular-text" />
                        <p class="description">
                            Keywords to search for in news feeds (comma-separated). 
                            Example: "limonene, citrus essential oils, aromatherapy"
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="terpedia_rss_feeds">RSS Feed URLs</label></th>
                    <td>
                        <textarea id="terpedia_rss_feeds" name="terpedia_rss_feeds" 
                                  rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $rss_feeds)); ?></textarea>
                        <p class="description">
                            One RSS feed URL per line. Examples:<br>
                            • https://feeds.feedburner.com/science-news<br>
                            • https://www.essentialoilhaven.com/feed/<br>
                            • https://news.google.com/rss/search?q=essential+oils&hl=en&gl=US&ceid=US:en
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="terpedia_post_frequency">Post Frequency</label></th>
                    <td>
                        <select id="terpedia_post_frequency" name="terpedia_post_frequency">
                            <option value="daily" <?php selected($post_frequency, 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected($post_frequency, 'weekly'); ?>>Weekly</option>
                            <option value="monthly" <?php selected($post_frequency, 'monthly'); ?>>Monthly</option>
                            <option value="manual" <?php selected($post_frequency, 'manual'); ?>>Manual Only</option>
                        </select>
                        <p class="description">How often this agent should check feeds and create posts</p>
                    </td>
                </tr>
            </table>
            
            <!-- Test and Control Buttons -->
            <div style="margin: 20px 0;">
                <button type="button" id="test-rss-feed-<?php echo $user->ID; ?>" class="button">
                    🧪 Test Feed Connection
                </button>
                <button type="button" id="trigger-feed-update-<?php echo $user->ID; ?>" class="button button-primary">
                    🚀 Generate Post Now
                </button>
                <button type="button" id="get-feed-stats-<?php echo $user->ID; ?>" class="button">
                    📊 View Feed Stats
                </button>
            </div>
            
            <div id="rss-test-result-<?php echo $user->ID; ?>"></div>
        </div>
        
        <!-- Suggested Feeds for Different Agent Types -->
        <div style="background: #e7f3ff; padding: 15px; margin: 15px 0; border-radius: 3px;">
            <h4>💡 Suggested Feeds for <?php echo esc_html($user->display_name); ?></h4>
            <?php
            $terpene_name = get_user_meta($user->ID, 'terpedia_terpene_name', true);
            $suggested_feeds = $this->get_suggested_feeds($agent_type, $terpene_name);
            ?>
            <ul>
                <?php foreach ($suggested_feeds as $feed): ?>
                    <li><strong><?php echo esc_html($feed['name']); ?>:</strong> 
                        <code><?php echo esc_html($feed['url']); ?></code>
                        <br><em><?php echo esc_html($feed['description']); ?></em>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test RSS feed
            $('#test-rss-feed-<?php echo $user->ID; ?>').on('click', function() {
                var $btn = $(this);
                var $result = $('#rss-test-result-<?php echo $user->ID; ?>');
                
                $btn.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_agent_rss_feed',
                        user_id: <?php echo $user->ID; ?>,
                        nonce: '<?php echo wp_create_nonce("terpedia_rss_nonce"); ?>'
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $result.html('<div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;"><strong>✅ Feed Test Successful!</strong><br>Found ' + data.items_count + ' recent items<br>Keywords matched: ' + data.keyword_matches + '</div>');
                        } else {
                            $result.html('<div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;"><strong>❌ Feed Test Failed</strong><br>' + data.error + '</div>');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🧪 Test Feed Connection');
                    }
                });
            });
            
            // Trigger feed update
            $('#trigger-feed-update-<?php echo $user->ID; ?>').on('click', function() {
                var $btn = $(this);
                var $result = $('#rss-test-result-<?php echo $user->ID; ?>');
                
                $btn.prop('disabled', true).text('Generating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'trigger_agent_feed_update',
                        user_id: <?php echo $user->ID; ?>,
                        nonce: '<?php echo wp_create_nonce("terpedia_rss_nonce"); ?>'
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $result.html('<div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;"><strong>✅ Post Generated!</strong><br>Created: <a href="' + data.post_url + '" target="_blank">' + data.post_title + '</a><br>Model: ' + data.model + '</div>');
                        } else {
                            $result.html('<div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;"><strong>❌ Generation Failed</strong><br>' + data.error + '</div>');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🚀 Generate Post Now');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save RSS feed settings
     */
    public function save_agent_rss_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        $agent_type = get_user_meta($user_id, 'terpedia_agent_type', true);
        if (empty($agent_type)) {
            return false;
        }
        
        // Save RSS feeds
        $rss_feeds = array();
        if (isset($_POST['terpedia_rss_feeds'])) {
            $feeds_input = sanitize_textarea_field($_POST['terpedia_rss_feeds']);
            $rss_feeds = array_filter(array_map('trim', explode("\n", $feeds_input)));
        }
        update_user_meta($user_id, 'terpedia_rss_feeds', $rss_feeds);
        
        // Save keywords
        if (isset($_POST['terpedia_rss_keywords'])) {
            $keywords = sanitize_text_field($_POST['terpedia_rss_keywords']);
            update_user_meta($user_id, 'terpedia_rss_keywords', $keywords);
        }
        
        // Save post frequency
        if (isset($_POST['terpedia_post_frequency'])) {
            $frequency = sanitize_text_field($_POST['terpedia_post_frequency']);
            update_user_meta($user_id, 'terpedia_post_frequency', $frequency);
        }
    }
    
    /**
     * Get suggested feeds based on agent type
     */
    private function get_suggested_feeds($agent_type, $terpene_name = '') {
        $feeds = array();
        
        if ($agent_type === 'terpene') {
            $feeds = array(
                array(
                    'name' => 'Google News - ' . ucfirst($terpene_name),
                    'url' => 'https://news.google.com/rss/search?q=' . urlencode($terpene_name . ' essential oils terpene') . '&hl=en&gl=US&ceid=US:en',
                    'description' => 'Latest news about ' . $terpene_name . ' and essential oils'
                ),
                array(
                    'name' => 'PubMed Research',
                    'url' => 'https://pubmed.ncbi.nlm.nih.gov/rss/search/1MxwEqUqVCqRlI8Ug0pDTSRFvfkH0QfvZsYKjI/?limit=20&utm_campaign=pubmed-2&fc=20210506031353',
                    'description' => 'Latest research publications'
                ),
                array(
                    'name' => 'Essential Oils Research',
                    'url' => 'https://news.google.com/rss/search?q=' . urlencode('essential oils research ' . $terpene_name) . '&hl=en&gl=US&ceid=US:en',
                    'description' => 'Research and studies on essential oils'
                )
            );
        } elseif ($agent_type === 'expert') {
            $feeds = array(
                array(
                    'name' => 'Science Daily',
                    'url' => 'https://www.sciencedaily.com/rss/all.xml',
                    'description' => 'Latest science news and research'
                ),
                array(
                    'name' => 'Medical News Today',
                    'url' => 'https://www.medicalnewstoday.com/rss',
                    'description' => 'Medical and health news'
                ),
                array(
                    'name' => 'Nature News',
                    'url' => 'https://www.nature.com/subjects/biological-sciences.rss',
                    'description' => 'Biological sciences research updates'
                )
            );
        }
        
        return $feeds;
    }
    
    /**
     * Process all agent RSS feeds daily
     */
    public function process_all_agent_feeds() {
        // Get all AI agents
        $agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_compare' => 'EXISTS'
        ));
        
        foreach ($agents as $agent) {
            $frequency = get_user_meta($agent->ID, 'terpedia_post_frequency', true) ?: 'daily';
            
            if ($frequency === 'daily') {
                // Schedule individual agent processing with 5-minute delays to spread load
                wp_schedule_single_event(
                    time() + (rand(1, 60) * 60), // Random delay 1-60 minutes
                    'terpedia_agent_feed_process', 
                    array($agent->ID, 'daily')
                );
            }
        }
    }
    
    /**
     * Process RSS feeds for a single agent
     */
    public function process_single_agent_feed($agent_id, $trigger_source = 'manual') {
        if (!$this->openrouter_handler) {
            error_log('OpenRouter handler not available for agent ' . $agent_id);
            return false;
        }
        
        $rss_feeds = get_user_meta($agent_id, 'terpedia_rss_feeds', true) ?: array();
        $keywords = get_user_meta($agent_id, 'terpedia_rss_keywords', true) ?: '';
        
        if (empty($rss_feeds) || empty($keywords)) {
            return false;
        }
        
        $keywords_array = array_map('trim', explode(',', $keywords));
        $relevant_items = array();
        
        // Check each RSS feed
        foreach ($rss_feeds as $feed_url) {
            $feed_items = $this->fetch_rss_feed($feed_url);
            
            if (!empty($feed_items)) {
                // Filter items by keywords and date
                foreach ($feed_items as $item) {
                    if ($this->item_matches_keywords($item, $keywords_array) && 
                        $this->is_recent_item($item)) {
                        $relevant_items[] = array(
                            'title' => $item['title'],
                            'link' => $item['link'],
                            'description' => $item['description'],
                            'date' => $item['date'],
                            'feed_url' => $feed_url
                        );
                    }
                }
            }
        }
        
        // Generate post if relevant items found
        if (!empty($relevant_items)) {
            // Limit to 3 most relevant items to avoid token limits
            $relevant_items = array_slice($relevant_items, 0, 3);
            $post_id = $this->generate_agent_post($agent_id, $relevant_items);
            
            if ($post_id) {
                // Update agent stats
                $posts_count = get_user_meta($agent_id, 'terpedia_posts_created_count', true) ?: 0;
                update_user_meta($agent_id, 'terpedia_posts_created_count', $posts_count + 1);
                update_user_meta($agent_id, 'terpedia_last_rss_check', current_time('mysql'));
                
                return $post_id;
            }
        }
        
        // Update last check time even if no posts created
        update_user_meta($agent_id, 'terpedia_last_rss_check', current_time('mysql'));
        
        return false;
    }
    
    /**
     * Fetch RSS feed items
     */
    private function fetch_rss_feed($feed_url) {
        $response = wp_remote_get($feed_url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            error_log('RSS Feed Error: ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string($body);
        
        if (!$xml) {
            error_log('Failed to parse RSS feed: ' . $feed_url);
            return array();
        }
        
        $items = array();
        
        // Handle different RSS formats
        if (isset($xml->channel->item)) {
            // RSS 2.0
            foreach ($xml->channel->item as $item) {
                $items[] = array(
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'description' => (string) $item->description,
                    'date' => (string) $item->pubDate
                );
            }
        } elseif (isset($xml->entry)) {
            // Atom
            foreach ($xml->entry as $entry) {
                $items[] = array(
                    'title' => (string) $entry->title,
                    'link' => (string) $entry->link['href'],
                    'description' => (string) $entry->summary,
                    'date' => (string) $entry->published
                );
            }
        }
        
        return $items;
    }
    
    /**
     * Check if item matches agent keywords
     */
    private function item_matches_keywords($item, $keywords_array) {
        $content = strtolower($item['title'] . ' ' . $item['description']);
        
        foreach ($keywords_array as $keyword) {
            if (stripos($content, trim($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if item is recent (within last 7 days)
     */
    private function is_recent_item($item) {
        $item_date = strtotime($item['date']);
        if (!$item_date) {
            return false;
        }
        
        $seven_days_ago = time() - (7 * 24 * 60 * 60);
        return $item_date > $seven_days_ago;
    }
    
    /**
     * Generate blog post from RSS items using OpenRouter
     */
    private function generate_agent_post($agent_id, $relevant_items) {
        $agent_data = $this->get_agent_data($agent_id);
        if (!$agent_data) {
            return false;
        }
        
        // Build content summary from RSS items
        $content_summary = "Recent news items:\n\n";
        foreach ($relevant_items as $item) {
            $content_summary .= "• " . $item['title'] . "\n";
            $content_summary .= "  Link: " . $item['link'] . "\n";
            $content_summary .= "  Summary: " . substr($item['description'], 0, 200) . "...\n\n";
        }
        
        // Create system prompt for content generation
        $system_prompt = "You are " . $agent_data['name'] . ", " . $agent_data['description'] . "\n\n";
        $system_prompt .= "Create an engaging blog post based on recent news items. Use your unique personality and expertise.\n\n";
        $system_prompt .= "WRITING GUIDELINES:\n";
        $system_prompt .= "- Write in your characteristic voice style: " . $agent_data['voice_style'] . "\n";
        $system_prompt .= "- Use speech patterns: " . $agent_data['speech_pattern'] . "\n";
        $system_prompt .= "- Focus on your specialty: " . $agent_data['specialty'] . "\n";
        $system_prompt .= "- Create catchy, engaging headlines\n";
        $system_prompt .= "- Summarize and personalize the news items\n";
        $system_prompt .= "- Include relevant scientific insights\n";
        $system_prompt .= "- Keep post between 300-600 words\n";
        $system_prompt .= "- End with a call-to-action or discussion prompt\n\n";
        
        $user_prompt = "Based on these recent news items, create an engaging blog post:\n\n" . $content_summary;
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        );
        
        $options = array(
            'model' => 'openai/gpt-oss-120b:free',
            'max_tokens' => 1200,
            'temperature' => 0.8 // Slightly more creative for blog posts
        );
        
        $response = $this->openrouter_handler->chat_completion($messages, $options);
        
        if (is_wp_error($response)) {
            error_log('OpenRouter Error for RSS post: ' . $response->get_error_message());
            return false;
        }
        
        $ai_content = $response['choices'][0]['message']['content'] ?? '';
        
        if (empty($ai_content)) {
            return false;
        }
        
        // Create WordPress post
        $post_title = $this->extract_title_from_content($ai_content);
        
        $post_data = array(
            'post_title' => $post_title,
            'post_content' => $ai_content,
            'post_status' => 'publish',
            'post_author' => $agent_id,
            'post_type' => 'post',
            'post_category' => array(get_option('default_category'))
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            // Add post meta
            add_post_meta($post_id, 'terpedia_ai_generated', true);
            add_post_meta($post_id, 'terpedia_ai_model', $response['model'] ?? 'openai/gpt-oss-120b:free');
            add_post_meta($post_id, 'terpedia_ai_provider', 'openrouter');
            add_post_meta($post_id, 'terpedia_source_feeds', $relevant_items);
            add_post_meta($post_id, 'terpedia_generation_date', current_time('mysql'));
            
            // Add terpene category if applicable
            $terpene_name = get_user_meta($agent_id, 'terpedia_terpene_name', true);
            if ($terpene_name) {
                $terpene_category = get_category_by_slug($terpene_name);
                if ($terpene_category) {
                    wp_set_post_categories($post_id, array($terpene_category->term_id), true);
                }
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Extract title from AI-generated content
     */
    private function extract_title_from_content($content) {
        $lines = explode("\n", $content);
        $title = '';
        
        // Look for title in first few lines
        foreach (array_slice($lines, 0, 3) as $line) {
            $line = trim($line);
            if (!empty($line) && strlen($line) < 100) {
                // Remove common markdown heading markers
                $line = preg_replace('/^#+\s*/', '', $line);
                $line = preg_replace('/^\*+\s*/', '', $line);
                $title = $line;
                break;
            }
        }
        
        if (empty($title)) {
            $title = 'Latest Terpene Insights from ' . get_userdata($agent_id)->display_name;
        }
        
        return $title;
    }
    
    /**
     * Get agent data
     */
    private function get_agent_data($agent_id) {
        $agent_type = get_user_meta($agent_id, 'terpedia_agent_type', true);
        $terpene_name = get_user_meta($agent_id, 'terpedia_terpene_name', true);
        
        if ($agent_type === 'terpene' && $terpene_name) {
            if (class_exists('TerpeneBuddyPressAgents')) {
                $terpene_agents = new TerpeneBuddyPressAgents();
                $agents = $terpene_agents->get_terpene_agents();
                return isset($agents[$terpene_name]) ? $agents[$terpene_name] : null;
            }
        }
        
        if ($agent_type === 'expert') {
            $username = get_userdata($agent_id)->user_login;
            $agent_key = str_replace('terpedia-', '', $username);
            
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
     * AJAX: Test RSS feed
     */
    public function ajax_test_rss_feed() {
        check_ajax_referer('terpedia_rss_nonce', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $user_id = intval($_POST['user_id']);
        $rss_feeds = get_user_meta($user_id, 'terpedia_rss_feeds', true) ?: array();
        $keywords = get_user_meta($user_id, 'terpedia_rss_keywords', true) ?: '';
        
        if (empty($rss_feeds)) {
            wp_die(json_encode(array('error' => 'No RSS feeds configured')));
        }
        
        $keywords_array = array_map('trim', explode(',', $keywords));
        $total_items = 0;
        $keyword_matches = 0;
        
        foreach ($rss_feeds as $feed_url) {
            $items = $this->fetch_rss_feed($feed_url);
            $total_items += count($items);
            
            foreach ($items as $item) {
                if ($this->item_matches_keywords($item, $keywords_array)) {
                    $keyword_matches++;
                }
            }
        }
        
        wp_die(json_encode(array(
            'success' => true,
            'items_count' => $total_items,
            'keyword_matches' => $keyword_matches
        )));
    }
    
    /**
     * AJAX: Trigger feed update
     */
    public function ajax_trigger_feed_update() {
        check_ajax_referer('terpedia_rss_nonce', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $user_id = intval($_POST['user_id']);
        $post_id = $this->process_single_agent_feed($user_id, 'manual');
        
        if ($post_id) {
            $post = get_post($post_id);
            wp_die(json_encode(array(
                'success' => true,
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'post_url' => get_permalink($post_id),
                'model' => 'openai/gpt-oss-120b:free'
            )));
        }
        
        wp_die(json_encode(array('error' => 'No relevant content found or generation failed')));
    }
    
    /**
     * AJAX: Get feed statistics
     */
    public function ajax_get_feed_stats() {
        check_ajax_referer('terpedia_rss_nonce', 'nonce');
        
        $user_id = intval($_POST['user_id']);
        $stats = $this->get_agent_feed_stats($user_id);
        
        wp_die(json_encode(array(
            'success' => true,
            'stats' => $stats
        )));
    }
    
    /**
     * Get feed statistics for an agent
     */
    private function get_agent_feed_stats($agent_id) {
        return array(
            'posts_created' => get_user_meta($agent_id, 'terpedia_posts_created_count', true) ?: 0,
            'last_check' => get_user_meta($agent_id, 'terpedia_last_rss_check', true),
            'feeds_count' => count(get_user_meta($agent_id, 'terpedia_rss_feeds', true) ?: array()),
            'keywords' => get_user_meta($agent_id, 'terpedia_rss_keywords', true),
            'frequency' => get_user_meta($agent_id, 'terpedia_post_frequency', true) ?: 'daily'
        );
    }
}

// Initialize RSS feed manager
new TerpediaAgentRSSManager();