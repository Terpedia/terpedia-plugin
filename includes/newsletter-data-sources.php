<?php
/**
 * Newsletter Data Sources for Terpedia
 * Provides data for various newsletter sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Newsletter_Data_Sources {
    
    public function __construct() {
        add_action('wp_ajax_get_newsletter_data', array($this, 'get_newsletter_data'));
        add_action('wp_ajax_nopriv_get_newsletter_data', array($this, 'get_newsletter_data'));
    }
    
    /**
     * Get data for newsletter sections
     */
    public function get_newsletter_data() {
        $data_source = sanitize_text_field($_POST['data_source']);
        $date_range = sanitize_text_field($_POST['date_range'] ?? 'last_week');
        $limit = intval($_POST['limit'] ?? 10);
        
        $data = $this->get_data_by_source($data_source, $date_range, $limit);
        
        wp_send_json_success($data);
    }
    
    /**
     * Get data based on source type
     */
    public function get_data_by_source($data_source, $date_range = 'last_week', $limit = 10) {
        switch ($data_source) {
            case 'pubmed_recent':
                return $this->get_recent_pubmed_articles($date_range, $limit);
            case 'pubmed_weekly':
                return $this->get_weekly_pubmed_articles($date_range, $limit);
            case 'industry_feeds':
                return $this->get_industry_news($date_range, $limit);
            case 'agent_activity':
                return $this->get_agent_activity($date_range, $limit);
            case 'recent_posts':
                return $this->get_recent_posts($date_range, $limit);
            case 'podcast_episodes':
                return $this->get_recent_podcast_episodes($date_range, $limit);
            case 'forum_activity':
                return $this->get_forum_activity($date_range, $limit);
            case 'terpene_database':
                return $this->get_terpene_facts($limit);
            case 'market_data':
                return $this->get_market_data($date_range);
            case 'research_highlights':
                return $this->get_research_highlights($date_range, $limit);
            default:
                return array();
        }
    }
    
    /**
     * Get recent PubMed articles
     */
    private function get_recent_pubmed_articles($date_range = 'last_week', $limit = 10) {
        // Calculate date range
        $date_query = $this->get_date_query($date_range);
        
        // Query for terpene-related articles
        $search_terms = array(
            'terpene',
            'terpenoid',
            'cannabinoid',
            'cannabis',
            'essential oil',
            'aroma therapy'
        );
        
        $articles = array();
        
        // For now, return mock data - in production, integrate with PubMed API
        $mock_articles = array(
            array(
                'pmid' => '12345678',
                'title' => 'Anti-inflammatory Effects of β-Caryophyllene in Murine Models',
                'authors' => 'Smith J, Johnson A, Williams B',
                'journal' => 'Journal of Natural Products',
                'date' => date('Y-m-d', strtotime('-3 days')),
                'abstract' => 'This study investigates the anti-inflammatory properties of β-caryophyllene, a sesquiterpene found in cannabis and black pepper...',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/12345678/',
                'keywords' => array('β-caryophyllene', 'anti-inflammatory', 'cannabis', 'terpene')
            ),
            array(
                'pmid' => '12345679',
                'title' => 'Limonene: A Review of Its Therapeutic Potential',
                'authors' => 'Brown C, Davis E, Miller F',
                'journal' => 'Phytotherapy Research',
                'date' => date('Y-m-d', strtotime('-5 days')),
                'abstract' => 'Limonene, a monoterpene found in citrus fruits, has shown promising results in various therapeutic applications...',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/12345679/',
                'keywords' => array('limonene', 'citrus', 'monoterpene', 'therapeutic')
            ),
            array(
                'pmid' => '12345680',
                'title' => 'The Entourage Effect: Terpene-Cannabinoid Interactions',
                'authors' => 'Wilson G, Taylor H, Anderson I',
                'journal' => 'Cannabis and Cannabinoid Research',
                'date' => date('Y-m-d', strtotime('-7 days')),
                'abstract' => 'This comprehensive review examines the synergistic effects between terpenes and cannabinoids...',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/12345680/',
                'keywords' => array('entourage effect', 'terpene', 'cannabinoid', 'synergy')
            )
        );
        
        return array_slice($mock_articles, 0, $limit);
    }
    
    /**
     * Get weekly PubMed articles
     */
    private function get_weekly_pubmed_articles($date_range = 'last_week', $limit = 10) {
        return $this->get_recent_pubmed_articles($date_range, $limit);
    }
    
    /**
     * Get industry news
     */
    private function get_industry_news($date_range = 'last_week', $limit = 10) {
        $date_query = $this->get_date_query($date_range);
        
        // Mock industry news data
        $industry_news = array(
            array(
                'title' => 'Major Cannabis Company Announces New Terpene Extraction Technology',
                'source' => 'Cannabis Business Times',
                'date' => date('Y-m-d', strtotime('-2 days')),
                'summary' => 'A leading cannabis company has unveiled a new extraction method that preserves terpene profiles more effectively...',
                'url' => '#',
                'category' => 'Technology',
                'impact' => 'High'
            ),
            array(
                'title' => 'FDA Approves First Terpene-Based Therapeutic',
                'source' => 'Pharmaceutical News',
                'date' => date('Y-m-d', strtotime('-4 days')),
                'summary' => 'The FDA has granted approval for a new therapeutic product based on specific terpene compounds...',
                'url' => '#',
                'category' => 'Regulatory',
                'impact' => 'High'
            ),
            array(
                'title' => 'Terpene Market Expected to Reach $2.5B by 2025',
                'source' => 'Market Research Report',
                'date' => date('Y-m-d', strtotime('-6 days')),
                'summary' => 'New market analysis predicts significant growth in the terpene industry over the next five years...',
                'url' => '#',
                'category' => 'Market',
                'impact' => 'Medium'
            )
        );
        
        return array_slice($industry_news, 0, $limit);
    }
    
    /**
     * Get agent activity
     */
    private function get_agent_activity($date_range = 'last_week', $limit = 10) {
        global $wpdb;
        
        // Query agent activity from database
        $date_query = $this->get_date_query($date_range);
        
        $activity = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_agent_activity 
             WHERE created_at >= %s 
             ORDER BY created_at DESC 
             LIMIT %d",
            $date_query['start'],
            $limit
        ));
        
        // If no real data, return mock data
        if (empty($activity)) {
            $activity = array(
                array(
                    'agent_name' => 'TerpeneQueen',
                    'activity_type' => 'forum_post',
                    'content' => 'Shared insights about the therapeutic benefits of linalool in anxiety management',
                    'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'engagement' => array('likes' => 15, 'replies' => 8)
                ),
                array(
                    'agent_name' => 'CannabisScientist',
                    'activity_type' => 'research_analysis',
                    'content' => 'Analyzed recent study on terpene-cannabinoid interactions in pain management',
                    'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                    'engagement' => array('likes' => 23, 'replies' => 12)
                ),
                array(
                    'agent_name' => 'IndustryInsider',
                    'activity_type' => 'market_update',
                    'content' => 'Provided market analysis on terpene pricing trends for Q4',
                    'date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'engagement' => array('likes' => 18, 'replies' => 6)
                )
            );
        }
        
        return array_slice($activity, 0, $limit);
    }
    
    /**
     * Get recent posts
     */
    private function get_recent_posts($date_range = 'last_week', $limit = 10) {
        $date_query = $this->get_date_query($date_range);
        
        $posts = get_posts(array(
            'numberposts' => $limit,
            'post_type' => 'post',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $date_query['start'],
                    'before' => $date_query['end']
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_terpene_related',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        $formatted_posts = array();
        foreach ($posts as $post) {
            $formatted_posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 20),
                'url' => get_permalink($post->ID),
                'date' => $post->post_date,
                'author' => get_the_author_meta('display_name', $post->post_author),
                'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
                'tags' => wp_get_post_tags($post->ID, array('fields' => 'names'))
            );
        }
        
        return $formatted_posts;
    }
    
    /**
     * Get recent podcast episodes
     */
    private function get_recent_podcast_episodes($date_range = 'last_week', $limit = 10) {
        $date_query = $this->get_date_query($date_range);
        
        // Query podcast episodes
        $episodes = get_posts(array(
            'numberposts' => $limit,
            'post_type' => 'terpedia_podcast',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $date_query['start'],
                    'before' => $date_query['end']
                )
            )
        ));
        
        $formatted_episodes = array();
        foreach ($episodes as $episode) {
            $formatted_episodes[] = array(
                'id' => $episode->ID,
                'title' => $episode->post_title,
                'description' => $episode->post_excerpt ?: wp_trim_words($episode->post_content, 30),
                'url' => get_permalink($episode->ID),
                'date' => $episode->post_date,
                'duration' => get_post_meta($episode->ID, '_episode_duration', true),
                'guests' => get_post_meta($episode->ID, '_episode_guests', true),
                'topics' => get_post_meta($episode->ID, '_episode_topics', true)
            );
        }
        
        return $formatted_episodes;
    }
    
    /**
     * Get forum activity
     */
    private function get_forum_activity($date_range = 'last_week', $limit = 10) {
        global $wpdb;
        
        $date_query = $this->get_date_query($date_range);
        
        // Query forum activity
        $activity = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_forum_activity 
             WHERE created_at >= %s 
             ORDER BY created_at DESC 
             LIMIT %d",
            $date_query['start'],
            $limit
        ));
        
        // If no real data, return mock data
        if (empty($activity)) {
            $activity = array(
                array(
                    'topic' => 'Best terpenes for sleep?',
                    'replies' => 15,
                    'views' => 234,
                    'last_activity' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'category' => 'Therapeutic Use',
                    'popularity' => 'High'
                ),
                array(
                    'topic' => 'Terpene extraction methods comparison',
                    'replies' => 8,
                    'views' => 156,
                    'last_activity' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'category' => 'Extraction',
                    'popularity' => 'Medium'
                ),
                array(
                    'topic' => 'New research on myrcene and pain relief',
                    'replies' => 12,
                    'views' => 189,
                    'last_activity' => date('Y-m-d H:i:s', strtotime('-3 days')),
                    'category' => 'Research',
                    'popularity' => 'High'
                )
            );
        }
        
        return array_slice($activity, 0, $limit);
    }
    
    /**
     * Get terpene facts
     */
    private function get_terpene_facts($limit = 10) {
        $facts = array(
            'Limonene is found in citrus fruits and has been shown to have anti-anxiety properties in multiple studies.',
            'Myrcene is the most common terpene in cannabis and has sedative effects that can enhance the effects of THC.',
            'Pinene can help improve memory and alertness, and may counteract some of the memory-impairing effects of THC.',
            'Linalool, found in lavender, has been used for centuries for its calming and anti-anxiety effects.',
            'β-Caryophyllene is the only terpene known to directly activate CB2 receptors in the endocannabinoid system.',
            'Humulene has appetite-suppressing effects and may help with weight management.',
            'Terpinolene has been shown to have antioxidant properties and may help protect against cellular damage.',
            'Ocimene is known for its uplifting effects and is commonly found in mint and parsley.',
            'Terpenes can make up 1-10% of the total weight of cannabis flower.',
            'The entourage effect suggests that terpenes work synergistically with cannabinoids to enhance therapeutic effects.'
        );
        
        return array_slice($facts, 0, $limit);
    }
    
    /**
     * Get market data
     */
    private function get_market_data($date_range = 'last_week') {
        return array(
            'terpene_market_size' => '$1.8B',
            'growth_rate' => '12.5%',
            'top_terpenes' => array('Limonene', 'Myrcene', 'Pinene', 'Linalool', 'β-Caryophyllene'),
            'price_trends' => array(
                'Limonene' => '+5.2%',
                'Myrcene' => '+3.8%',
                'Pinene' => '-1.2%',
                'Linalool' => '+7.1%',
                'β-Caryophyllene' => '+4.5%'
            ),
            'market_insights' => array(
                'Increased demand for natural terpenes in food and beverage industry',
                'Growing interest in terpene-based therapeutics',
                'Regulatory changes affecting terpene extraction methods'
            )
        );
    }
    
    /**
     * Get research highlights
     */
    private function get_research_highlights($date_range = 'last_week', $limit = 10) {
        $date_query = $this->get_date_query($date_range);
        
        $highlights = array(
            array(
                'title' => 'Breakthrough Study: Terpenes Show Promise in Treating Neurodegenerative Diseases',
                'journal' => 'Nature Neuroscience',
                'date' => date('Y-m-d', strtotime('-2 days')),
                'summary' => 'New research demonstrates the potential of specific terpene combinations in slowing neurodegenerative disease progression.',
                'impact' => 'High',
                'terpenes' => array('Linalool', 'Pinene', 'Myrcene')
            ),
            array(
                'title' => 'Clinical Trial: β-Caryophyllene Reduces Inflammation in Arthritis Patients',
                'journal' => 'Journal of Clinical Medicine',
                'date' => date('Y-m-d', strtotime('-4 days')),
                'summary' => 'Phase II clinical trial shows significant reduction in inflammation markers with β-caryophyllene treatment.',
                'impact' => 'High',
                'terpenes' => array('β-Caryophyllene')
            ),
            array(
                'title' => 'Terpene Synergy Study: Enhanced Effects When Combined',
                'journal' => 'Cannabis and Cannabinoid Research',
                'date' => date('Y-m-d', strtotime('-6 days')),
                'summary' => 'Research confirms that terpenes work better together than individually, supporting the entourage effect theory.',
                'impact' => 'Medium',
                'terpenes' => array('Limonene', 'Myrcene', 'Pinene')
            )
        );
        
        return array_slice($highlights, 0, $limit);
    }
    
    /**
     * Get date query based on range
     */
    private function get_date_query($date_range) {
        switch ($date_range) {
            case 'last_week':
                return array(
                    'start' => date('Y-m-d', strtotime('-1 week')),
                    'end' => date('Y-m-d')
                );
            case 'last_month':
                return array(
                    'start' => date('Y-m-d', strtotime('-1 month')),
                    'end' => date('Y-m-d')
                );
            case 'last_quarter':
                return array(
                    'start' => date('Y-m-d', strtotime('-3 months')),
                    'end' => date('Y-m-d')
                );
            case 'last_year':
                return array(
                    'start' => date('Y-m-d', strtotime('-1 year')),
                    'end' => date('Y-m-d')
                );
            default:
                return array(
                    'start' => date('Y-m-d', strtotime('-1 week')),
                    'end' => date('Y-m-d')
                );
        }
    }
    
    /**
     * Format data for newsletter sections
     */
    public function format_data_for_section($data, $section_type, $word_count = 200) {
        switch ($section_type) {
            case 'recent_science':
                return $this->format_research_data($data, $word_count);
            case 'industry_news':
                return $this->format_industry_data($data, $word_count);
            case 'recent_posts':
                return $this->format_posts_data($data, $word_count);
            case 'agent_spotlight':
                return $this->format_agent_data($data, $word_count);
            case 'podcast_highlights':
                return $this->format_podcast_data($data, $word_count);
            case 'community_corner':
                return $this->format_forum_data($data, $word_count);
            case 'quick_facts':
                return $this->format_facts_data($data, $word_count);
            case 'market_analysis':
                return $this->format_market_data($data, $word_count);
            case 'research_spotlight':
                return $this->format_research_highlights($data, $word_count);
            default:
                return $this->format_generic_data($data, $word_count);
        }
    }
    
    /**
     * Format research data for newsletter
     */
    private function format_research_data($data, $word_count) {
        $content = "Recent Research Highlights:\n\n";
        
        foreach (array_slice($data, 0, 3) as $article) {
            $content .= "• **{$article['title']}**\n";
            $content .= "  Published in {$article['journal']} on {$article['date']}\n";
            $content .= "  " . wp_trim_words($article['abstract'], 25) . "\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format industry data for newsletter
     */
    private function format_industry_data($data, $word_count) {
        $content = "Industry News Update:\n\n";
        
        foreach (array_slice($data, 0, 3) as $news) {
            $content .= "• **{$news['title']}**\n";
            $content .= "  Source: {$news['source']} | {$news['date']}\n";
            $content .= "  " . wp_trim_words($news['summary'], 20) . "\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format posts data for newsletter
     */
    private function format_posts_data($data, $word_count) {
        $content = "Featured Community Posts:\n\n";
        
        foreach (array_slice($data, 0, 3) as $post) {
            $content .= "• **{$post['title']}**\n";
            $content .= "  By {$post['author']} | {$post['date']}\n";
            $content .= "  " . wp_trim_words($post['excerpt'], 20) . "\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format agent data for newsletter
     */
    private function format_agent_data($data, $word_count) {
        $content = "AI Agent Spotlight:\n\n";
        
        if (!empty($data)) {
            $agent = $data[0];
            $content .= "**{$agent['agent_name']}** has been active this week:\n\n";
            $content .= "• {$agent['content']}\n";
            $content .= "• Engagement: {$agent['engagement']['likes']} likes, {$agent['engagement']['replies']} replies\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format podcast data for newsletter
     */
    private function format_podcast_data($data, $word_count) {
        $content = "Podcast Highlights:\n\n";
        
        foreach (array_slice($data, 0, 2) as $episode) {
            $content .= "• **{$episode['title']}**\n";
            $content .= "  Duration: {$episode['duration']} | {$episode['date']}\n";
            $content .= "  " . wp_trim_words($episode['description'], 20) . "\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format forum data for newsletter
     */
    private function format_forum_data($data, $word_count) {
        $content = "Community Corner:\n\n";
        
        foreach (array_slice($data, 0, 3) as $topic) {
            $content .= "• **{$topic['topic']}**\n";
            $content .= "  {$topic['replies']} replies | {$topic['views']} views\n";
            $content .= "  Category: {$topic['category']}\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format facts data for newsletter
     */
    private function format_facts_data($data, $word_count) {
        $content = "Quick Terpene Facts:\n\n";
        
        foreach (array_slice($data, 0, 5) as $fact) {
            $content .= "• {$fact}\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format market data for newsletter
     */
    private function format_market_data($data, $word_count) {
        $content = "Market Analysis:\n\n";
        $content .= "• Market Size: {$data['terpene_market_size']}\n";
        $content .= "• Growth Rate: {$data['growth_rate']}\n\n";
        
        $content .= "Price Trends:\n";
        foreach ($data['price_trends'] as $terpene => $trend) {
            $content .= "• {$terpene}: {$trend}\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format research highlights for newsletter
     */
    private function format_research_highlights($data, $word_count) {
        $content = "Research Spotlight:\n\n";
        
        foreach (array_slice($data, 0, 2) as $highlight) {
            $content .= "• **{$highlight['title']}**\n";
            $content .= "  {$highlight['journal']} | {$highlight['date']}\n";
            $content .= "  " . wp_trim_words($highlight['summary'], 25) . "\n\n";
        }
        
        return wp_trim_words($content, $word_count);
    }
    
    /**
     * Format generic data for newsletter
     */
    private function format_generic_data($data, $word_count) {
        $content = "Content Update:\n\n";
        
        foreach (array_slice($data, 0, 3) as $item) {
            if (is_array($item)) {
                $content .= "• " . (isset($item['title']) ? $item['title'] : json_encode($item)) . "\n";
            } else {
                $content .= "• {$item}\n";
            }
        }
        
        return wp_trim_words($content, $word_count);
    }
}

// Initialize the newsletter data sources
new Terpedia_Newsletter_Data_Sources();
