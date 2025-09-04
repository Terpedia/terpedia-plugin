<?php
/**
 * Google News RSS Feed Generator
 * Creates targeted RSS feeds for each terpene agent based on scientific research
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaGoogleNewsFeeds {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_generate_agent_rss_feeds', array($this, 'ajax_generate_feeds'));
        add_action('wp_ajax_backfill_agent_content', array($this, 'ajax_backfill_content'));
    }
    
    public function init() {
        // Auto-generate RSS feeds for agents that don't have them
        add_action('terpedia_setup_agent_feeds', array($this, 'setup_default_feeds_for_agents'));
    }
    
    /**
     * Generate Google News RSS feeds for all agents
     */
    public function setup_default_feeds_for_agents() {
        // Get all AI agents
        $agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_compare' => 'EXISTS'
        ));
        
        foreach ($agents as $agent) {
            $this->setup_agent_default_feeds($agent->ID);
        }
    }
    
    /**
     * Setup default RSS feeds for a specific agent
     */
    public function setup_agent_default_feeds($agent_id) {
        $agent_type = get_user_meta($agent_id, 'terpedia_agent_type', true);
        $terpene_name = get_user_meta($agent_id, 'terpedia_terpene_name', true);
        $username = get_userdata($agent_id)->user_login;
        
        $feeds = array();
        $keywords = '';
        
        if ($agent_type === 'terpene' && $terpene_name) {
            // Terpene-specific feeds
            $feeds = $this->get_terpene_rss_feeds($terpene_name);
            $keywords = $this->get_terpene_keywords($terpene_name);
        } elseif ($agent_type === 'expert') {
            // Expert agent feeds
            $expert_type = str_replace('terpedia-', '', $username);
            $feeds = $this->get_expert_rss_feeds($expert_type);
            $keywords = $this->get_expert_keywords($expert_type);
        }
        
        // Only update if agent doesn't have feeds configured
        $existing_feeds = get_user_meta($agent_id, 'terpedia_rss_feeds', true);
        if (empty($existing_feeds)) {
            update_user_meta($agent_id, 'terpedia_rss_feeds', $feeds);
            update_user_meta($agent_id, 'terpedia_rss_keywords', $keywords);
            update_user_meta($agent_id, 'terpedia_post_frequency', 'daily');
        }
    }
    
    /**
     * Get RSS feeds for terpene agents
     */
    private function get_terpene_rss_feeds($terpene_name) {
        $base_search = $terpene_name . ' terpene essential oils';
        $research_search = $terpene_name . ' research therapeutic effects';
        $industry_search = $terpene_name . ' aromatherapy cannabis hemp';
        
        return array(
            // Google News searches
            'https://news.google.com/rss/search?q=' . urlencode($base_search) . '&hl=en&gl=US&ceid=US:en',
            'https://news.google.com/rss/search?q=' . urlencode($research_search) . '&hl=en&gl=US&ceid=US:en',
            'https://news.google.com/rss/search?q=' . urlencode($industry_search) . '&hl=en&gl=US&ceid=US:en',
            
            // Specialized feeds
            'https://www.sciencedaily.com/rss/all.xml',
            'https://feeds.feedburner.com/acs/enfm', // ACS Publications
            'https://www.nature.com/subjects/plant-sciences.rss',
            'https://www.medicalnewstoday.com/rss'
        );
    }
    
    /**
     * Get keywords for terpene agents
     */
    private function get_terpene_keywords($terpene_name) {
        $specific_keywords = array(
            'myrcene' => 'myrcene, sedative effects, muscle relaxant, hops, mango, cannabis indica',
            'limonene' => 'limonene, citrus, mood enhancement, anxiety relief, orange peel, lemon',
            'pinene' => 'pinene, pine, focus, memory, respiratory, alpha-pinene, beta-pinene',
            'linalool' => 'linalool, lavender, calming, anti-anxiety, floral, sleep aid',
            'caryophyllene' => 'caryophyllene, spicy, anti-inflammatory, CB2 receptor, black pepper',
            'humulene' => 'humulene, earthy, appetite suppressant, anti-inflammatory, hops',
            'terpinolene' => 'terpinolene, floral, woody, antioxidant, tea tree, nutmeg'
        );
        
        $base_keywords = 'terpene, essential oils, aromatherapy, therapeutic effects, natural medicine, plant compounds';
        $specific = isset($specific_keywords[strtolower($terpene_name)]) ? $specific_keywords[strtolower($terpene_name)] : $terpene_name;
        
        return $specific . ', ' . $base_keywords;
    }
    
    /**
     * Get RSS feeds for expert agents
     */
    private function get_expert_rss_feeds($expert_type) {
        $feed_mappings = array(
            'molecule-maven' => array(
                'https://news.google.com/rss/search?q=' . urlencode('chemistry molecular structure research') . '&hl=en&gl=US&ceid=US:en',
                'https://feeds.feedburner.com/acs/jacs', // Journal of American Chemical Society
                'https://www.sciencedaily.com/rss/matter_energy/chemistry.xml'
            ),
            'pharmakin' => array(
                'https://news.google.com/rss/search?q=' . urlencode('pharmacology drug discovery medicine') . '&hl=en&gl=US&ceid=US:en',
                'https://www.pharmaceutical-technology.com/rss/',
                'https://www.medicalnewstoday.com/rss'
            ),
            'citeswell' => array(
                'https://news.google.com/rss/search?q=' . urlencode('scientific research publications studies') . '&hl=en&gl=US&ceid=US:en',
                'https://www.sciencedaily.com/rss/all.xml',
                'https://www.nature.com/nature.rss'
            ),
            'botanist' => array(
                'https://news.google.com/rss/search?q=' . urlencode('plant science botany agriculture') . '&hl=en&gl=US&ceid=US:en',
                'https://www.sciencedaily.com/rss/plants_animals/plants.xml',
                'https://www.nature.com/subjects/plant-sciences.rss'
            ),
            'aromatherapist' => array(
                'https://news.google.com/rss/search?q=' . urlencode('aromatherapy essential oils wellness') . '&hl=en&gl=US&ceid=US:en',
                'https://www.aromaweb.com/rss/',
                'https://www.medicalnewstoday.com/rss'
            )
        );
        
        return isset($feed_mappings[$expert_type]) ? 
               $feed_mappings[$expert_type] : 
               array('https://www.sciencedaily.com/rss/all.xml');
    }
    
    /**
     * Get keywords for expert agents
     */
    private function get_expert_keywords($expert_type) {
        $keyword_mappings = array(
            'molecule-maven' => 'chemistry, molecular structure, chemical compounds, organic chemistry, biochemistry',
            'pharmakin' => 'pharmacology, drug discovery, medicine, pharmaceutical, clinical trials',
            'citeswell' => 'scientific research, publications, studies, peer review, literature',
            'regulatory' => 'regulations, compliance, safety, FDA, drug approval',
            'veterinarian' => 'veterinary medicine, animal health, pet care, veterinary pharmaceuticals',
            'naturopath' => 'natural medicine, holistic health, herbal medicine, naturopathy',
            'botanist' => 'plant science, botany, plant biology, agriculture, horticulture',
            'aromatherapist' => 'aromatherapy, essential oils, wellness, natural healing, therapeutic oils',
            'formulator' => 'formulation, product development, cosmetics, perfume, blending',
            'patient' => 'patient experience, treatment outcomes, therapy, medical cannabis',
            'reporter' => 'medical news, health reporting, science journalism, research news',
            'protein' => 'protein structure, molecular biology, biochemistry, protein interactions',
            'prospector' => 'natural product discovery, drug discovery, novel compounds, bioprospecting'
        );
        
        $base_keywords = 'terpenes, essential oils, natural products, therapeutic compounds';
        $specific = isset($keyword_mappings[$expert_type]) ? $keyword_mappings[$expert_type] : $expert_type;
        
        return $specific . ', ' . $base_keywords;
    }
    
    /**
     * Backfill content for all agents
     */
    public function backfill_all_agents($days_back = 7) {
        $agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_compare' => 'EXISTS'
        ));
        
        $results = array();
        
        foreach ($agents as $agent) {
            // Setup default feeds if needed
            $this->setup_agent_default_feeds($agent->ID);
            
            // Generate content for past days
            for ($i = 1; $i <= $days_back; $i++) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $post_id = $this->generate_historical_content($agent->ID, $date);
                
                if ($post_id) {
                    $results[] = array(
                        'agent' => $agent->display_name,
                        'post_id' => $post_id,
                        'date' => $date
                    );
                }
                
                // Add delay to avoid rate limits
                sleep(2);
            }
        }
        
        return $results;
    }
    
    /**
     * Generate historical content for specific date
     */
    private function generate_historical_content($agent_id, $date) {
        // This would ideally fetch historical news for that date
        // For now, we'll generate content based on agent expertise
        
        $agent_data = $this->get_agent_data($agent_id);
        if (!$agent_data) {
            return false;
        }
        
        $terpene_name = get_user_meta($agent_id, 'terpedia_terpene_name', true);
        $historical_prompt = "Write an educational blog post about {$terpene_name} terpene from the perspective of a {$agent_data['specialty']} expert. Include recent research insights and practical applications. Make it engaging and informative for a general audience interested in natural compounds.";
        
        if (!$this->openrouter_handler) {
            return false;
        }
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => "You are " . $agent_data['name'] . ", " . $agent_data['description'] . ". Write in your characteristic style: " . $agent_data['voice_style'] . ". Use speech patterns: " . $agent_data['speech_pattern'] . "."
            ),
            array(
                'role' => 'user',
                'content' => $historical_prompt
            )
        );
        
        $response = $this->openrouter_handler->chat_completion($messages, array(
            'model' => 'openai/gpt-oss-120b:free',
            'max_tokens' => 1000,
            'temperature' => 0.8
        ));
        
        if (is_wp_error($response) || !isset($response['choices'][0]['message']['content'])) {
            return false;
        }
        
        $content = $response['choices'][0]['message']['content'];
        
        // Create post with historical date
        $post_data = array(
            'post_title' => $this->extract_title_from_content($content),
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => $agent_id,
            'post_date' => $date . ' 09:00:00',
            'post_date_gmt' => get_gmt_from_date($date . ' 09:00:00')
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            add_post_meta($post_id, 'terpedia_ai_generated', true);
            add_post_meta($post_id, 'terpedia_backfilled_content', true);
            add_post_meta($post_id, 'terpedia_generation_date', $date);
        }
        
        return $post_id;
    }
    
    /**
     * Extract title from content
     */
    private function extract_title_from_content($content) {
        $lines = explode("\n", $content);
        
        foreach (array_slice($lines, 0, 3) as $line) {
            $line = trim($line);
            if (!empty($line) && strlen($line) < 100) {
                $line = preg_replace('/^#+\s*/', '', $line);
                $line = preg_replace('/^\*+\s*/', '', $line);
                return $line;
            }
        }
        
        return 'Terpene Insights - ' . date('F j, Y');
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
        
        return array(
            'name' => get_userdata($agent_id)->display_name,
            'description' => 'Terpene Expert',
            'specialty' => 'Terpene Research',
            'voice_style' => 'Professional and educational',
            'speech_pattern' => 'Clear and informative'
        );
    }
    
    /**
     * AJAX: Generate RSS feeds for agent
     */
    public function ajax_generate_feeds() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $agent_id = intval($_POST['agent_id']);
        $this->setup_agent_default_feeds($agent_id);
        
        wp_die(json_encode(array(
            'success' => true,
            'message' => 'RSS feeds generated for agent'
        )));
    }
    
    /**
     * AJAX: Backfill content for agent
     */
    public function ajax_backfill_content() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $agent_id = intval($_POST['agent_id']);
        $days_back = intval($_POST['days_back'] ?? 7);
        
        // Initialize OpenRouter if needed
        if (!$this->openrouter_handler && class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_handler = new TerpediaOpenRouterHandler();
        }
        
        $results = array();
        
        for ($i = 1; $i <= $days_back; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $post_id = $this->generate_historical_content($agent_id, $date);
            
            if ($post_id) {
                $results[] = array(
                    'date' => $date,
                    'post_id' => $post_id,
                    'post_url' => get_permalink($post_id)
                );
            }
        }
        
        wp_die(json_encode(array(
            'success' => true,
            'posts_created' => count($results),
            'results' => $results
        )));
    }
}

// Initialize Google News RSS feeds
new TerpediaGoogleNewsFeeds();