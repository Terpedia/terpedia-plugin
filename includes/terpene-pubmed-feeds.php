<?php
/**
 * Terpene-Specific PubMed RSS Feed Generator
 * Creates personalized PubMed RSS feeds for each terpene agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpenePubMedFeeds {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_generate_terpene_pubmed_feeds', array($this, 'ajax_generate_feeds'));
        add_action('wp_ajax_test_terpene_pubmed_feed', array($this, 'ajax_test_feed'));
    }
    
    public function init() {
        // Auto-setup PubMed feeds for terpene agents
        add_action('terpedia_setup_terpene_feeds', array($this, 'setup_terpene_pubmed_feeds'));
    }
    
    /**
     * Setup PubMed RSS feeds for all terpene agents
     */
    public function setup_terpene_pubmed_feeds() {
        $terpene_agents = $this->get_terpene_agents();
        
        foreach ($terpene_agents as $agent) {
            $this->setup_agent_pubmed_feeds($agent->ID);
        }
        
        echo "PubMed feeds setup completed for " . count($terpene_agents) . " terpene agents.";
    }
    
    /**
     * Get all terpene agents (tersonas)
     */
    private function get_terpene_agents() {
        return get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_value' => 'tersona',
            'meta_compare' => '='
        ));
    }
    
    /**
     * Setup PubMed feeds for a specific terpene agent
     */
    public function setup_agent_pubmed_feeds($agent_id) {
        $terpene_type = get_user_meta($agent_id, 'terpene_type', true);
        
        if (!$terpene_type) {
            return false;
        }
        
        $pubmed_feeds = $this->generate_terpene_pubmed_feeds($terpene_type);
        $keywords = $this->get_terpene_keywords($terpene_type);
        
        // Save feeds to agent profile
        update_user_meta($agent_id, 'terpedia_pubmed_feeds', $pubmed_feeds);
        update_user_meta($agent_id, 'terpedia_pubmed_keywords', $keywords);
        update_user_meta($agent_id, 'terpedia_pubmed_enabled', true);
        
        return true;
    }
    
    /**
     * Generate PubMed RSS feed URLs for a specific terpene
     */
    private function generate_terpene_pubmed_feeds($terpene_type) {
        $terpene_lower = strtolower($terpene_type);
        $terpene_capitalized = ucfirst($terpene_lower);
        
        $feeds = array();
        
        // Primary terpene research feed
        $primary_search = urlencode($terpene_capitalized . ' AND (therapeutic OR clinical OR research)');
        $feeds[] = array(
            'name' => $terpene_capitalized . ' Research',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/rss/search/' . $this->generate_pubmed_hash($primary_search) . '/?limit=20&utm_campaign=pubmed-2',
            'keywords' => $terpene_lower . ', research, therapeutic, clinical'
        );
        
        // Mechanism of action feed
        $mechanism_search = urlencode($terpene_capitalized . ' AND (mechanism OR pathway OR receptor)');
        $feeds[] = array(
            'name' => $terpene_capitalized . ' Mechanisms',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/rss/search/' . $this->generate_pubmed_hash($mechanism_search) . '/?limit=15&utm_campaign=pubmed-2',
            'keywords' => $terpene_lower . ', mechanism, pathway, receptor, molecular'
        );
        
        // Therapeutic applications feed
        $therapeutic_search = urlencode($terpene_capitalized . ' AND (pain OR anxiety OR sleep OR inflammation OR mood)');
        $feeds[] = array(
            'name' => $terpene_capitalized . ' Therapeutics',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/rss/search/' . $this->generate_pubmed_hash($therapeutic_search) . '/?limit=15&utm_campaign=pubmed-2',
            'keywords' => $terpene_lower . ', therapeutic, pain, anxiety, sleep, inflammation'
        );
        
        // Safety and pharmacokinetics feed
        $safety_search = urlencode($terpene_capitalized . ' AND (safety OR pharmacokinetic OR toxicity OR bioavailability)');
        $feeds[] = array(
            'name' => $terpene_capitalized . ' Safety & PK',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/rss/search/' . $this->generate_pubmed_hash($safety_search) . '/?limit=10&utm_campaign=pubmed-2',
            'keywords' => $terpene_lower . ', safety, pharmacokinetic, toxicity, bioavailability'
        );
        
        return $feeds;
    }
    
    /**
     * Generate a PubMed hash for RSS feed URL
     * This simulates the hash that PubMed generates for search queries
     */
    private function generate_pubmed_hash($search_query) {
        // PubMed uses a specific hash format - this is a simplified version
        // In practice, you'd need to make actual PubMed API calls to get real hashes
        return substr(md5($search_query . time()), 0, 20);
    }
    
    /**
     * Get terpene-specific keywords
     */
    private function get_terpene_keywords($terpene_type) {
        $terpene_keywords = array(
            'myrcene' => array(
                'myrcene', 'β-myrcene', 'beta-myrcene', 'sedative', 'sleep', 'relaxation', 
                'GABA', 'muscle relaxant', 'analgesic', 'pain relief', 'cannabis', 'hops'
            ),
            'limonene' => array(
                'limonene', 'd-limonene', 'l-limonene', 'citrus', 'mood', 'antidepressant', 
                'anxiety', 'stress relief', 'serotonin', 'uplifting', 'energizing', 'focus'
            ),
            'pinene' => array(
                'pinene', 'α-pinene', 'alpha-pinene', 'β-pinene', 'beta-pinene', 'bronchodilator', 
                'respiratory', 'memory', 'alertness', 'anti-inflammatory', 'antimicrobial', 'pine'
            ),
            'caryophyllene' => array(
                'caryophyllene', 'β-caryophyllene', 'beta-caryophyllene', 'CB2', 'cannabinoid', 
                'anti-inflammatory', 'pain', 'anxiety', 'depression', 'spice', 'pepper', 'clove'
            ),
            'linalool' => array(
                'linalool', 'lavender', 'calming', 'anxiety', 'sleep', 'sedative', 'anticonvulsant', 
                'anti-inflammatory', 'analgesic', 'aromatherapy', 'relaxation', 'stress'
            ),
            'humulene' => array(
                'humulene', 'α-humulene', 'alpha-humulene', 'anti-inflammatory', 'appetite suppressant', 
                'weight loss', 'hops', 'beer', 'analgesic', 'antibacterial', 'antimicrobial'
            )
        );
        
        $terpene_lower = strtolower($terpene_type);
        return isset($terpene_keywords[$terpene_lower]) ? 
               implode(', ', $terpene_keywords[$terpene_lower]) : 
               $terpene_lower . ', therapeutic, research, clinical';
    }
    
    /**
     * Get all terpene PubMed feed configurations
     */
    public function get_all_terpene_pubmed_feeds() {
        $terpene_configs = array();
        $terpene_agents = $this->get_terpene_agents();
        
        foreach ($terpene_agents as $agent) {
            $terpene_type = get_user_meta($agent->ID, 'terpene_type', true);
            if ($terpene_type) {
                $terpene_configs[$terpene_type] = array(
                    'agent_id' => $agent->ID,
                    'agent_name' => $agent->display_name,
                    'feeds' => $this->generate_terpene_pubmed_feeds($terpene_type),
                    'keywords' => $this->get_terpene_keywords($terpene_type)
                );
            }
        }
        
        return $terpene_configs;
    }
    
    /**
     * AJAX: Generate PubMed feeds for all terpenes
     */
    public function ajax_generate_feeds() {
        check_ajax_referer('terpedia_pubmed_feeds', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $this->setup_terpene_pubmed_feeds();
            wp_send_json_success('PubMed feeds generated successfully for all terpene agents');
        } catch (Exception $e) {
            wp_send_json_error('Error generating feeds: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Test a specific terpene PubMed feed
     */
    public function ajax_test_feed() {
        check_ajax_referer('terpedia_pubmed_feeds', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $terpene_type = sanitize_text_field($_POST['terpene_type']);
        $feed_url = sanitize_url($_POST['feed_url']);
        
        try {
            $result = $this->test_pubmed_feed($feed_url);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error('Error testing feed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test a PubMed RSS feed
     */
    private function test_pubmed_feed($feed_url) {
        $response = wp_remote_get($feed_url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Terpedia RSS Tester/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'status' => 'error',
                'message' => 'Failed to fetch feed: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return array(
                'status' => 'error',
                'message' => 'HTTP ' . $status_code . ' - Feed not accessible'
            );
        }
        
        // Parse RSS to check if it's valid
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            return array(
                'status' => 'error',
                'message' => 'Invalid RSS format'
            );
        }
        
        $item_count = count($xml->channel->item ?? []);
        
        return array(
            'status' => 'success',
            'message' => 'Feed is working correctly',
            'item_count' => $item_count,
            'feed_title' => (string)$xml->channel->title,
            'last_build' => (string)$xml->channel->lastBuildDate
        );
    }
    
    /**
     * Get PubMed feed statistics for an agent
     */
    public function get_agent_pubmed_stats($agent_id) {
        $feeds = get_user_meta($agent_id, 'terpedia_pubmed_feeds', true);
        $enabled = get_user_meta($agent_id, 'terpedia_pubmed_enabled', true);
        
        if (!$feeds || !$enabled) {
            return array(
                'enabled' => false,
                'feed_count' => 0,
                'last_checked' => null
            );
        }
        
        return array(
            'enabled' => true,
            'feed_count' => count($feeds),
            'feeds' => $feeds,
            'last_checked' => get_user_meta($agent_id, 'terpedia_pubmed_last_checked', true)
        );
    }
    
    /**
     * Update agent's PubMed feed last checked time
     */
    public function update_agent_last_checked($agent_id) {
        update_user_meta($agent_id, 'terpedia_pubmed_last_checked', current_time('mysql'));
    }
    
    /**
     * Get terpene-specific PubMed search suggestions
     */
    public function get_terpene_search_suggestions($terpene_type) {
        $suggestions = array(
            'myrcene' => array(
                'Basic: myrcene',
                'Therapeutic: myrcene AND (pain OR sleep OR anxiety)',
                'Mechanism: myrcene AND (GABA OR sedative OR muscle relaxant)',
                'Clinical: myrcene AND (clinical trial OR human study)',
                'Safety: myrcene AND (safety OR toxicity OR pharmacokinetic)'
            ),
            'limonene' => array(
                'Basic: limonene',
                'Mood: limonene AND (mood OR depression OR anxiety)',
                'Mechanism: limonene AND (serotonin OR antidepressant)',
                'Clinical: limonene AND (clinical trial OR human study)',
                'Safety: limonene AND (safety OR toxicity OR pharmacokinetic)'
            ),
            'pinene' => array(
                'Basic: pinene',
                'Respiratory: pinene AND (bronchodilator OR respiratory OR asthma)',
                'Cognitive: pinene AND (memory OR alertness OR cognitive)',
                'Clinical: pinene AND (clinical trial OR human study)',
                'Safety: pinene AND (safety OR toxicity OR pharmacokinetic)'
            ),
            'caryophyllene' => array(
                'Basic: caryophyllene',
                'CB2: caryophyllene AND (CB2 OR cannabinoid receptor)',
                'Inflammation: caryophyllene AND (anti-inflammatory OR inflammation)',
                'Clinical: caryophyllene AND (clinical trial OR human study)',
                'Safety: caryophyllene AND (safety OR toxicity OR pharmacokinetic)'
            ),
            'linalool' => array(
                'Basic: linalool',
                'Anxiety: linalool AND (anxiety OR calming OR stress)',
                'Sleep: linalool AND (sleep OR sedative OR insomnia)',
                'Clinical: linalool AND (clinical trial OR human study)',
                'Safety: linalool AND (safety OR toxicity OR pharmacokinetic)'
            ),
            'humulene' => array(
                'Basic: humulene',
                'Appetite: humulene AND (appetite OR weight loss OR obesity)',
                'Inflammation: humulene AND (anti-inflammatory OR inflammation)',
                'Clinical: humulene AND (clinical trial OR human study)',
                'Safety: humulene AND (safety OR toxicity OR pharmacokinetic)'
            )
        );
        
        $terpene_lower = strtolower($terpene_type);
        return isset($suggestions[$terpene_lower]) ? $suggestions[$terpene_lower] : array();
    }
}

// Initialize the terpene PubMed feeds system
new TerpenePubMedFeeds();

// Hook to setup feeds when plugin is activated
register_activation_hook(__FILE__, function() {
    do_action('terpedia_setup_terpene_feeds');
});
