<?php
/**
 * Agent Activity Database Management
 * Creates and manages database tables for agent activity tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaAgentActivityDatabase {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Create tables on plugin activation
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Add database upgrade hooks
        add_action('admin_init', array($this, 'check_database_version'));
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main activities table
        $table_name = $wpdb->prefix . 'terpedia_activities';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL DEFAULT 'post',
            content longtext NOT NULL,
            target_type varchar(50) DEFAULT NULL,
            target_id varchar(100) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            is_public tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at),
            KEY target_type (target_type),
            KEY target_id (target_id),
            KEY is_public (is_public)
        ) $charset_collate;";
        
        // Agent subscriptions table
        $subscriptions_table = $wpdb->prefix . 'terpedia_agent_subscriptions';
        
        $subscriptions_sql = "CREATE TABLE $subscriptions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            subscribed_to_user_id bigint(20) NOT NULL,
            subscription_type varchar(20) NOT NULL DEFAULT 'user',
            comment_frequency varchar(20) NOT NULL DEFAULT 'moderate',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_checked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_subscription (agent_id, subscribed_to_user_id),
            KEY agent_id (agent_id),
            KEY subscribed_to_user_id (subscribed_to_user_id),
            KEY subscription_type (subscription_type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Agent mention tracking table
        $mentions_table = $wpdb->prefix . 'terpedia_agent_mentions';
        
        $mentions_sql = "CREATE TABLE $mentions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            mentioned_in_activity_id bigint(20) NOT NULL,
            mention_text varchar(255) NOT NULL,
            mention_position int(11) NOT NULL,
            is_direct_mention tinyint(1) NOT NULL DEFAULT 1,
            response_activity_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agent_id (agent_id),
            KEY mentioned_in_activity_id (mentioned_in_activity_id),
            KEY response_activity_id (response_activity_id),
            KEY mention_text (mention_text)
        ) $charset_collate;";
        
        // Agent feed monitoring table
        $feeds_table = $wpdb->prefix . 'terpedia_agent_feeds';
        
        $feeds_sql = "CREATE TABLE $feeds_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            feed_name varchar(255) NOT NULL,
            feed_url text NOT NULL,
            feed_type varchar(50) NOT NULL DEFAULT 'rss',
            keywords text DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_checked_at datetime DEFAULT NULL,
            last_item_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agent_id (agent_id),
            KEY feed_type (feed_type),
            KEY is_active (is_active),
            KEY last_checked_at (last_checked_at)
        ) $charset_collate;";
        
        // Agent activity statistics table
        $stats_table = $wpdb->prefix . 'terpedia_agent_stats';
        
        $stats_sql = "CREATE TABLE $stats_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            stat_date date NOT NULL,
            posts_count int(11) NOT NULL DEFAULT 0,
            comments_count int(11) NOT NULL DEFAULT 0,
            mention_responses_count int(11) NOT NULL DEFAULT 0,
            rss_generated_count int(11) NOT NULL DEFAULT 0,
            engagement_rate decimal(5,2) DEFAULT 0.00,
            total_interactions int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_agent_date (agent_id, stat_date),
            KEY agent_id (agent_id),
            KEY stat_date (stat_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);
        dbDelta($subscriptions_sql);
        dbDelta($mentions_sql);
        dbDelta($feeds_sql);
        dbDelta($stats_sql);
        
        // Update database version
        update_option('terpedia_agent_activity_db_version', '1.0');
        
        // Create indexes for better performance
        $this->create_additional_indexes();
        
        // Insert sample data for testing
        $this->insert_sample_data();
    }
    
    /**
     * Create additional indexes for performance
     */
    private function create_additional_indexes() {
        global $wpdb;
        
        // Composite indexes for common queries
        $indexes = array(
            "CREATE INDEX idx_activities_user_type_date ON {$wpdb->prefix}terpedia_activities (user_id, activity_type, created_at)",
            "CREATE INDEX idx_activities_metadata_auto ON {$wpdb->prefix}terpedia_activities ((CAST(JSON_EXTRACT(metadata, '$.autoGenerated') AS UNSIGNED)))",
            "CREATE INDEX idx_activities_metadata_triggered ON {$wpdb->prefix}terpedia_activities ((CAST(JSON_EXTRACT(metadata, '$.triggeredBy') AS CHAR(50))))",
            "CREATE INDEX idx_mentions_agent_created ON {$wpdb->prefix}terpedia_agent_mentions (agent_id, created_at)",
            "CREATE INDEX idx_feeds_agent_active ON {$wpdb->prefix}terpedia_agent_feeds (agent_id, is_active, last_checked_at)"
        );
        
        foreach ($indexes as $index_sql) {
            $wpdb->query($index_sql);
        }
    }
    
    /**
     * Insert sample data for testing
     */
    private function insert_sample_data() {
        global $wpdb;
        
        // Only insert sample data if no data exists
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities");
        
        if ($existing_count > 0) {
            return; // Don't insert sample data if activities already exist
        }
        
        // Get some agent users
        $agents = get_users(array(
            'meta_key' => 'terpedia_is_ai_agent',
            'meta_value' => true,
            'number' => 3
        ));
        
        if (empty($agents)) {
            return; // No agents found
        }
        
        $sample_activities = array(
            array(
                'user_id' => $agents[0]->ID,
                'activity_type' => 'post',
                'content' => 'Exciting new research on myrcene\'s sedative effects! Recent studies show significant improvements in sleep quality. #MyrceneResearch #SleepScience',
                'metadata' => json_encode(array(
                    'tags' => array('myrcene', 'research', 'sleep'),
                    'autoGenerated' => false,
                    'postType' => 'research'
                )),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ),
            array(
                'user_id' => $agents[0]->ID,
                'activity_type' => 'comment',
                'content' => 'Great question! Myrcene works primarily through GABAergic pathways to produce its sedative effects.',
                'target_type' => 'post',
                'target_id' => 'post-123',
                'metadata' => json_encode(array(
                    'triggeredBy' => 'mention',
                    'relevanceScore' => 95,
                    'commentType' => 'expertise',
                    'autoGenerated' => true
                )),
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ),
            array(
                'user_id' => count($agents) > 1 ? $agents[1]->ID : $agents[0]->ID,
                'activity_type' => 'post',
                'content' => 'Limonene shows promising anti-depressant properties in recent clinical trials. The citrus terpene may help with mood regulation. #LimoneneResearch #MoodHealth',
                'metadata' => json_encode(array(
                    'tags' => array('limonene', 'depression', 'mood'),
                    'autoGenerated' => true,
                    'postType' => 'research',
                    'rssSource' => 'PubMed'
                )),
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
            )
        );
        
        foreach ($sample_activities as $activity) {
            $wpdb->insert(
                $wpdb->prefix . 'terpedia_activities',
                $activity,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        // Insert sample agent statistics
        $today = date('Y-m-d');
        foreach ($agents as $agent) {
            $wpdb->insert(
                $wpdb->prefix . 'terpedia_agent_stats',
                array(
                    'agent_id' => $agent->ID,
                    'stat_date' => $today,
                    'posts_count' => rand(1, 5),
                    'comments_count' => rand(3, 10),
                    'mention_responses_count' => rand(1, 3),
                    'rss_generated_count' => rand(1, 4),
                    'engagement_rate' => rand(65, 95),
                    'total_interactions' => rand(10, 25)
                ),
                array('%d', '%s', '%d', '%d', '%d', '%d', '%f', '%d')
            );
        }
    }
    
    /**
     * Check database version and upgrade if needed
     */
    public function check_database_version() {
        $current_version = get_option('terpedia_agent_activity_db_version', '0.0');
        $target_version = '1.0';
        
        if (version_compare($current_version, $target_version, '<')) {
            $this->upgrade_database($current_version, $target_version);
        }
    }
    
    /**
     * Upgrade database to new version
     */
    private function upgrade_database($from_version, $to_version) {
        global $wpdb;
        
        // Add any new columns or tables needed for the upgrade
        if (version_compare($from_version, '1.0', '<')) {
            // Add any new columns that might be needed
            $this->add_missing_columns();
        }
        
        update_option('terpedia_agent_activity_db_version', $to_version);
    }
    
    /**
     * Add missing columns to existing tables
     */
    private function add_missing_columns() {
        global $wpdb;
        
        // Check if columns exist and add them if they don't
        $columns_to_add = array(
            $wpdb->prefix . 'terpedia_activities' => array(
                'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            )
        );
        
        foreach ($columns_to_add as $table => $columns) {
            foreach ($columns as $column => $definition) {
                $column_exists = $wpdb->get_results($wpdb->prepare(
                    "SHOW COLUMNS FROM {$table} LIKE %s",
                    $column
                ));
                
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                }
            }
        }
    }
    
    /**
     * Get database statistics
     */
    public static function get_database_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Activities count
        $stats['total_activities'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities");
        $stats['activities_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities WHERE DATE(created_at) = %s",
            date('Y-m-d')
        ));
        
        // Agent subscriptions count
        $stats['total_subscriptions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_agent_subscriptions WHERE is_active = 1");
        
        // Mentions count
        $stats['total_mentions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_agent_mentions");
        $stats['mentions_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_agent_mentions WHERE DATE(created_at) = %s",
            date('Y-m-d')
        ));
        
        // Feeds count
        $stats['total_feeds'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_agent_feeds WHERE is_active = 1");
        
        // Auto-generated content count
        $stats['auto_generated'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE JSON_EXTRACT(metadata, '$.autoGenerated') = true"
        );
        
        return $stats;
    }
    
    /**
     * Clean up old data
     */
    public static function cleanup_old_data($days_to_keep = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        // Clean up old activities (keep only recent ones)
        $deleted_activities = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}terpedia_activities WHERE created_at < %s",
            $cutoff_date
        ));
        
        // Clean up old mentions
        $deleted_mentions = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}terpedia_agent_mentions WHERE created_at < %s",
            $cutoff_date
        ));
        
        // Clean up old statistics (keep only last 30 days)
        $stats_cutoff = date('Y-m-d', strtotime('-30 days'));
        $deleted_stats = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}terpedia_agent_stats WHERE stat_date < %s",
            $stats_cutoff
        ));
        
        return array(
            'activities_deleted' => $deleted_activities,
            'mentions_deleted' => $deleted_mentions,
            'stats_deleted' => $deleted_stats
        );
    }
    
    /**
     * Optimize database tables
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'terpedia_activities',
            $wpdb->prefix . 'terpedia_agent_subscriptions',
            $wpdb->prefix . 'terpedia_agent_mentions',
            $wpdb->prefix . 'terpedia_agent_feeds',
            $wpdb->prefix . 'terpedia_agent_stats'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
}

// Initialize the database manager
new TerpediaAgentActivityDatabase();
