<?php
/**
 * Database management for Terpedia Replit Bridge
 */

class Terpedia_Database {
    
    /**
     * Create custom database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // AI Conversations table
        $conversations_table = $wpdb->prefix . 'terpedia_conversations';
        $conversations_sql = "CREATE TABLE $conversations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            agent_id bigint(20) UNSIGNED NOT NULL,
            conversation_data longtext NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY agent_id (agent_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Agent Analytics table
        $analytics_table = $wpdb->prefix . 'terpedia_agent_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            interaction_type varchar(50) NOT NULL,
            query_text text,
            response_time decimal(10,3),
            satisfaction_rating tinyint(1),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agent_id (agent_id),
            KEY user_id (user_id),
            KEY interaction_type (interaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Terpene Knowledge Base table
        $knowledge_table = $wpdb->prefix . 'terpedia_knowledge_base';
        $knowledge_sql = "CREATE TABLE $knowledge_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            terpene_name varchar(255) NOT NULL,
            chemical_formula varchar(100),
            molecular_weight decimal(10,3),
            boiling_point decimal(10,3),
            density decimal(10,4),
            iupac_name text,
            cas_number varchar(50),
            smiles_notation text,
            inchi_key varchar(255),
            therapeutic_effects longtext,
            natural_sources longtext,
            biosynthetic_pathway text,
            pharmacological_data longtext,
            research_citations longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY terpene_name (terpene_name),
            KEY cas_number (cas_number),
            KEY molecular_weight (molecular_weight)
        ) $charset_collate;";
        
        // Podcast Episodes table
        $podcast_table = $wpdb->prefix . 'terpedia_podcast_episodes';
        $podcast_sql = "CREATE TABLE $podcast_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            episode_number int(11) NOT NULL,
            title varchar(255) NOT NULL,
            description longtext,
            host_persona_id bigint(20) UNSIGNED,
            guest_agent_id bigint(20) UNSIGNED,
            audio_file_url varchar(500),
            transcript_text longtext,
            duration_seconds int(11),
            published_date datetime,
            download_count int(11) DEFAULT 0,
            listen_count int(11) DEFAULT 0,
            rating decimal(3,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'draft',
            featured_terpenes longtext,
            episode_tags varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY episode_number (episode_number),
            KEY host_persona_id (host_persona_id),
            KEY guest_agent_id (guest_agent_id),
            KEY published_date (published_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Newsletter Subscribers table
        $newsletter_table = $wpdb->prefix . 'terpedia_newsletter_subscribers';
        $newsletter_sql = "CREATE TABLE $newsletter_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            first_name varchar(100),
            last_name varchar(100),
            subscription_status varchar(20) DEFAULT 'active',
            interests longtext,
            subscription_source varchar(100),
            confirmation_token varchar(255),
            confirmed_at datetime,
            unsubscribed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY subscription_status (subscription_status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Newsletter Issues table
        $newsletter_issues_table = $wpdb->prefix . 'terpedia_newsletter_issues';
        $newsletter_issues_sql = "CREATE TABLE $newsletter_issues_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            issue_number int(11) NOT NULL,
            title varchar(255) NOT NULL,
            subject_line varchar(255),
            content longtext NOT NULL,
            featured_terpene varchar(255),
            sent_date datetime,
            sent_count int(11) DEFAULT 0,
            open_count int(11) DEFAULT 0,
            click_count int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'draft',
            template_used varchar(100),
            created_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY issue_number (issue_number),
            KEY sent_date (sent_date),
            KEY status (status),
            KEY featured_terpene (featured_terpene)
        ) $charset_collate;";
        
        // User Preferences table
        $preferences_table = $wpdb->prefix . 'terpedia_user_preferences';
        $preferences_sql = "CREATE TABLE $preferences_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            preferred_agents longtext,
            favorite_terpenes longtext,
            consultation_history longtext,
            notification_settings longtext,
            personalization_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        // Research Documents table (for terports)
        $terports_table = $wpdb->prefix . 'terpedia_terports';
        $terports_sql = "CREATE TABLE $terports_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            research_topic varchar(255) NOT NULL,
            executive_summary longtext,
            content longtext NOT NULL,
            author_agent_id bigint(20) UNSIGNED,
            requested_by_user_id bigint(20) UNSIGNED,
            google_doc_id varchar(255),
            google_doc_url varchar(500),
            pdf_file_url varchar(500),
            status varchar(20) DEFAULT 'draft',
            visibility varchar(20) DEFAULT 'private',
            download_count int(11) DEFAULT 0,
            view_count int(11) DEFAULT 0,
            citation_count int(11) DEFAULT 0,
            research_categories longtext,
            keywords varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY author_agent_id (author_agent_id),
            KEY requested_by_user_id (requested_by_user_id),
            KEY status (status),
            KEY visibility (visibility),
            KEY research_topic (research_topic)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($conversations_sql);
        dbDelta($analytics_sql);
        dbDelta($knowledge_sql);
        dbDelta($podcast_sql);
        dbDelta($newsletter_sql);
        dbDelta($newsletter_issues_sql);
        dbDelta($preferences_sql);
        dbDelta($terports_sql);
        
        // Create indexes for better performance
        self::create_indexes();
        
        // Insert sample data
        self::insert_sample_data();
    }
    
    /**
     * Create additional indexes for performance
     */
    private static function create_indexes() {
        global $wpdb;
        
        // Add composite indexes for better query performance
        $wpdb->query("ALTER TABLE {$wpdb->prefix}terpedia_conversations 
                     ADD INDEX user_agent_status (user_id, agent_id, status)");
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}terpedia_agent_analytics 
                     ADD INDEX agent_date (agent_id, created_at)");
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}terpedia_podcast_episodes 
                     ADD INDEX status_date (status, published_date)");
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}terpedia_newsletter_issues 
                     ADD INDEX status_sent (status, sent_date)");
    }
    
    /**
     * Insert sample data for demonstration
     */
    private static function insert_sample_data() {
        global $wpdb;
        
        // Sample terpene data
        $sample_terpenes = array(
            array(
                'terpene_name' => 'Limonene',
                'chemical_formula' => 'C10H16',
                'molecular_weight' => 136.234,
                'boiling_point' => 176.0,
                'density' => 0.8411,
                'iupac_name' => '1-Methyl-4-(prop-1-en-2-yl)cyclohexene',
                'cas_number' => '5989-27-5',
                'smiles_notation' => 'CC1=CCC(CC1)C(=C)C',
                'inchi_key' => 'XMGQYMWWDOXHJM-UHFFFAOYSA-N',
                'therapeutic_effects' => json_encode(array('mood enhancement', 'stress relief', 'antibacterial', 'antioxidant')),
                'natural_sources' => json_encode(array('citrus peels', 'pine needles', 'peppermint', 'juniper')),
                'biosynthetic_pathway' => 'Monoterpenoid biosynthesis pathway',
                'pharmacological_data' => json_encode(array('absorption' => 'rapid', 'bioavailability' => 'high', 'half_life' => '2-3 hours')),
                'research_citations' => json_encode(array('PubMed:12345678', 'DOI:10.1000/xyz123'))
            ),
            array(
                'terpene_name' => 'Myrcene',
                'chemical_formula' => 'C10H16',
                'molecular_weight' => 136.234,
                'boiling_point' => 166.7,
                'density' => 0.7969,
                'iupac_name' => '7-Methyl-3-methyleneocta-1,6-diene',
                'cas_number' => '123-35-3',
                'smiles_notation' => 'CC(=CCCC(=C)C=C)C',
                'inchi_key' => 'YWLMHOPFVNHXOD-UHFFFAOYSA-N',
                'therapeutic_effects' => json_encode(array('sedative', 'muscle relaxant', 'analgesic', 'anti-inflammatory')),
                'natural_sources' => json_encode(array('hops', 'mangoes', 'basil', 'thyme')),
                'biosynthetic_pathway' => 'Monoterpenoid biosynthesis pathway',
                'pharmacological_data' => json_encode(array('absorption' => 'moderate', 'bioavailability' => 'medium', 'half_life' => '1-2 hours')),
                'research_citations' => json_encode(array('PubMed:98765432', 'DOI:10.1000/abc789'))
            )
        );
        
        foreach ($sample_terpenes as $terpene) {
            $wpdb->insert(
                $wpdb->prefix . 'terpedia_knowledge_base',
                $terpene
            );
        }
        
        // Sample podcast episodes
        $sample_episodes = array(
            array(
                'episode_number' => 1,
                'title' => 'Introduction to Terpenes with Dr. Molecule Maven',
                'description' => 'A comprehensive introduction to the world of terpenes, their molecular structures, and therapeutic potential.',
                'duration_seconds' => 2400,
                'status' => 'published',
                'published_date' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'featured_terpenes' => json_encode(array('limonene', 'myrcene', 'pinene')),
                'episode_tags' => 'introduction,education,molecular science'
            ),
            array(
                'episode_number' => 2,
                'title' => 'Taxol: The Cancer-Fighting Terpene with Agt. Taxol',
                'description' => 'Deep dive into taxol, its discovery, mechanism of action, and impact on cancer treatment.',
                'duration_seconds' => 3600,
                'status' => 'published',
                'published_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'featured_terpenes' => json_encode(array('taxol', 'paclitaxel')),
                'episode_tags' => 'cancer,research,medical'
            )
        );
        
        foreach ($sample_episodes as $episode) {
            $wpdb->insert(
                $wpdb->prefix . 'terpedia_podcast_episodes',
                $episode
            );
        }
        
        // Sample newsletter issue
        $sample_newsletter = array(
            'issue_number' => 1,
            'title' => 'Terpene Times - Welcome Edition',
            'subject_line' => 'Welcome to the Terpene Times Newsletter!',
            'content' => '<h2>Welcome to Terpene Times!</h2><p>Your weekly source for the latest in terpene research, applications, and discoveries.</p><h3>Featured This Week: Limonene</h3><p>Discover the mood-enhancing properties of limonene and its applications in aromatherapy.</p>',
            'featured_terpene' => 'Limonene',
            'status' => 'sent',
            'sent_date' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'sent_count' => 1250,
            'open_count' => 875,
            'click_count' => 234,
            'template_used' => 'morning_brew'
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'terpedia_newsletter_issues',
            $sample_newsletter
        );
    }
    
    /**
     * Get conversation history
     */
    public static function get_conversations($user_id, $agent_id = null, $limit = 50) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$wpdb->prefix}terpedia_conversations WHERE user_id = %d";
        $params = array($user_id);
        
        if ($agent_id) {
            $sql .= " AND agent_id = %d";
            $params[] = $agent_id;
        }
        
        $sql .= " ORDER BY updated_at DESC LIMIT %d";
        $params[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Save conversation
     */
    public static function save_conversation($user_id, $agent_id, $conversation_data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'terpedia_conversations',
            array(
                'user_id' => $user_id,
                'agent_id' => $agent_id,
                'conversation_data' => json_encode($conversation_data),
                'status' => 'active'
            ),
            array('%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Log agent interaction
     */
    public static function log_interaction($agent_id, $user_id, $interaction_type, $query_text = '', $response_time = 0, $rating = null) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'terpedia_agent_analytics',
            array(
                'agent_id' => $agent_id,
                'user_id' => $user_id,
                'interaction_type' => $interaction_type,
                'query_text' => $query_text,
                'response_time' => $response_time,
                'satisfaction_rating' => $rating
            ),
            array('%d', '%d', '%s', '%s', '%f', '%d')
        );
    }
    
    /**
     * Get terpene information
     */
    public static function get_terpene($terpene_name) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_knowledge_base WHERE terpene_name = %s",
            $terpene_name
        ));
    }
    
    /**
     * Search terpenes
     */
    public static function search_terpenes($query, $limit = 20) {
        global $wpdb;
        
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_knowledge_base 
             WHERE terpene_name LIKE %s 
                OR iupac_name LIKE %s 
                OR therapeutic_effects LIKE %s 
                OR natural_sources LIKE %s
             ORDER BY terpene_name ASC 
             LIMIT %d",
            $search_term, $search_term, $search_term, $search_term, $limit
        ));
    }
    
    /**
     * Get podcast episodes
     */
    public static function get_podcast_episodes($status = 'published', $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_podcast_episodes 
             WHERE status = %s 
             ORDER BY published_date DESC 
             LIMIT %d",
            $status, $limit
        ));
    }
    
    /**
     * Get newsletter subscribers
     */
    public static function get_newsletter_stats() {
        global $wpdb;
        
        $total_subscribers = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_newsletter_subscribers WHERE subscription_status = 'active'"
        );
        
        $recent_subscribers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_newsletter_subscribers 
             WHERE subscription_status = 'active' AND created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        return array(
            'total_subscribers' => $total_subscribers,
            'recent_subscribers' => $recent_subscribers
        );
    }
}