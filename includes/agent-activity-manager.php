<?php
/**
 * Agent Activity Manager
 * Backend interface for viewing and managing agent posts, comments, and activities
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaAgentActivityManager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_get_agent_activity', array($this, 'ajax_get_agent_activity'));
        add_action('wp_ajax_terpedia_update_agent_post', array($this, 'ajax_update_agent_post'));
        add_action('wp_ajax_terpedia_delete_agent_activity', array($this, 'ajax_delete_agent_activity'));
        add_action('wp_ajax_terpedia_approve_agent_comment', array($this, 'ajax_approve_agent_comment'));
        add_action('wp_ajax_terpedia_get_agent_stats', array($this, 'ajax_get_agent_stats'));
        add_action('wp_ajax_terpedia_export_agent_activity', array($this, 'ajax_export_agent_activity'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-admin',
            'Agent Activity Management',
            'Agent Activity',
            'manage_options',
            'terpedia-agent-activity',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'terpedia-agent-activity') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
            wp_enqueue_style('terpedia-agent-activity', 
                plugin_dir_url(__FILE__) . '../assets/css/agent-activity-admin.css', 
                array(), '1.0.0');
        }
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $agents = $this->get_all_agents();
        $selected_agent = isset($_GET['agent_id']) ? intval($_GET['agent_id']) : 0;
        $selected_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        $activity_type = isset($_GET['activity_type']) ? sanitize_text_field($_GET['activity_type']) : 'all';
        
        ?>
        <div class="wrap">
            <h1>🤖 Agent Activity Management</h1>
            
            <div class="agent-activity-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="terpedia-agent-activity" />
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="agent_id">Select Agent:</label>
                            <select name="agent_id" id="agent_id">
                                <option value="0">All Agents</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?php echo $agent->ID; ?>" <?php selected($selected_agent, $agent->ID); ?>>
                                        <?php echo esc_html($agent->display_name); ?> 
                                        (<?php echo esc_html(get_user_meta($agent->ID, 'terpene_type', true) ?: 'Expert'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date">Date:</label>
                            <input type="date" name="date" id="date" value="<?php echo esc_attr($selected_date); ?>" />
                        </div>
                        
                        <div class="filter-group">
                            <label for="activity_type">Activity Type:</label>
                            <select name="activity_type" id="activity_type">
                                <option value="all" <?php selected($activity_type, 'all'); ?>>All Activities</option>
                                <option value="post" <?php selected($activity_type, 'post'); ?>>Posts</option>
                                <option value="comment" <?php selected($activity_type, 'comment'); ?>>Comments</option>
                                <option value="mention_response" <?php selected($activity_type, 'mention_response'); ?>>Mention Responses</option>
                                <option value="rss_generated" <?php selected($activity_type, 'rss_generated'); ?>>RSS Generated</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="button button-primary">Filter</button>
                            <button type="button" id="export-activity" class="button button-secondary">Export</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="agent-activity-stats">
                <?php $this->render_activity_stats($selected_agent, $selected_date); ?>
            </div>
            
            <div class="agent-activity-content">
                <div class="activity-tabs">
                    <button class="tab-button active" data-tab="recent">Recent Activity</button>
                    <button class="tab-button" data-tab="posts">Posts</button>
                    <button class="tab-button" data-tab="comments">Comments</button>
                    <button class="tab-button" data-tab="mentions">Mention Responses</button>
                    <button class="tab-button" data-tab="rss">RSS Generated</button>
                </div>
                
                <div class="tab-content">
                    <div id="recent-tab" class="tab-panel active">
                        <?php $this->render_recent_activity($selected_agent, $selected_date); ?>
                    </div>
                    
                    <div id="posts-tab" class="tab-panel">
                        <?php $this->render_agent_posts($selected_agent, $selected_date); ?>
                    </div>
                    
                    <div id="comments-tab" class="tab-panel">
                        <?php $this->render_agent_comments($selected_agent, $selected_date); ?>
                    </div>
                    
                    <div id="mentions-tab" class="tab-panel">
                        <?php $this->render_mention_responses($selected_agent, $selected_date); ?>
                    </div>
                    
                    <div id="rss-tab" class="tab-panel">
                        <?php $this->render_rss_generated($selected_agent, $selected_date); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .agent-activity-filters {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .agent-activity-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            color: #0073aa;
        }
        
        .stat-change {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .stat-change.positive { color: #46b450; }
        .stat-change.negative { color: #dc3232; }
        
        .activity-tabs {
            display: flex;
            border-bottom: 1px solid #ccd0d4;
            margin: 20px 0 0 0;
        }
        
        .tab-button {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: bold;
        }
        
        .tab-button.active {
            border-bottom-color: #0073aa;
            color: #0073aa;
        }
        
        .tab-button:hover {
            background: #f8f9fa;
        }
        
        .tab-content {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-top: none;
            min-height: 400px;
        }
        
        .tab-panel {
            display: none;
            padding: 20px;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        .activity-item {
            border: 1px solid #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .activity-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .activity-actions {
            display: flex;
            gap: 5px;
        }
        
        .activity-content {
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .activity-tags {
            margin-top: 10px;
        }
        
        .activity-tag {
            display: inline-block;
            background: #0073aa;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin: 2px;
        }
        
        .activity-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-published { background: #46b450; color: white; }
        .status-draft { background: #ffb900; color: white; }
        .status-pending { background: #dc3232; color: white; }
        .status-auto-generated { background: #0073aa; color: white; }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #0073aa;
        }
        
        .pagination .current {
            background: #0073aa;
            color: white;
            border-color: #0073aa;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.tab-button').on('click', function() {
                var tab = $(this).data('tab');
                
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                $('.tab-panel').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
            });
            
            // Export activity
            $('#export-activity').on('click', function() {
                var agentId = $('#agent_id').val();
                var date = $('#date').val();
                var activityType = $('#activity_type').val();
                
                var exportUrl = ajaxurl + '?action=terpedia_export_agent_activity&agent_id=' + agentId + 
                               '&date=' + date + '&activity_type=' + activityType + 
                               '&nonce=<?php echo wp_create_nonce('terpedia_agent_activity'); ?>';
                
                window.open(exportUrl, '_blank');
            });
            
            // Approve comment
            $('.approve-comment').on('click', function() {
                var activityId = $(this).data('activity-id');
                var button = $(this);
                
                if (confirm('Approve this comment?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'terpedia_approve_agent_comment',
                            activity_id: activityId,
                            nonce: '<?php echo wp_create_nonce('terpedia_agent_activity'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                button.closest('.activity-item').find('.activity-status').removeClass('status-pending').addClass('status-published').text('Published');
                                button.remove();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
            
            // Delete activity
            $('.delete-activity').on('click', function() {
                var activityId = $(this).data('activity-id');
                var button = $(this);
                
                if (confirm('Delete this activity? This action cannot be undone.')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'terpedia_delete_agent_activity',
                            activity_id: activityId,
                            nonce: '<?php echo wp_create_nonce('terpedia_agent_activity'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                button.closest('.activity-item').fadeOut();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get all agents
     */
    private function get_all_agents() {
        return get_users(array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'terpedia_is_ai_agent',
                    'value' => true
                ),
                array(
                    'key' => 'terpedia_agent_type',
                    'compare' => 'EXISTS'
                )
            )
        ));
    }
    
    /**
     * Render activity statistics
     */
    private function render_activity_stats($agent_id, $date) {
        $stats = $this->get_activity_stats($agent_id, $date);
        
        ?>
        <div class="stat-card">
            <h3>Total Posts</h3>
            <p class="stat-number"><?php echo $stats['total_posts']; ?></p>
            <p class="stat-change <?php echo $stats['posts_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $stats['posts_change'] >= 0 ? '+' : ''; ?><?php echo $stats['posts_change']; ?> from yesterday
            </p>
        </div>
        
        <div class="stat-card">
            <h3>Total Comments</h3>
            <p class="stat-number"><?php echo $stats['total_comments']; ?></p>
            <p class="stat-change <?php echo $stats['comments_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $stats['comments_change'] >= 0 ? '+' : ''; ?><?php echo $stats['comments_change']; ?> from yesterday
            </p>
        </div>
        
        <div class="stat-card">
            <h3>Mention Responses</h3>
            <p class="stat-number"><?php echo $stats['mention_responses']; ?></p>
            <p class="stat-change <?php echo $stats['mentions_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $stats['mentions_change'] >= 0 ? '+' : ''; ?><?php echo $stats['mentions_change']; ?> from yesterday
            </p>
        </div>
        
        <div class="stat-card">
            <h3>RSS Generated</h3>
            <p class="stat-number"><?php echo $stats['rss_generated']; ?></p>
            <p class="stat-change <?php echo $stats['rss_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $stats['rss_change'] >= 0 ? '+' : ''; ?><?php echo $stats['rss_change']; ?> from yesterday
            </p>
        </div>
        
        <div class="stat-card">
            <h3>Engagement Rate</h3>
            <p class="stat-number"><?php echo $stats['engagement_rate']; ?>%</p>
            <p class="stat-change <?php echo $stats['engagement_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $stats['engagement_change'] >= 0 ? '+' : ''; ?><?php echo $stats['engagement_change']; ?>% from yesterday
            </p>
        </div>
        <?php
    }
    
    /**
     * Get activity statistics
     */
    private function get_activity_stats($agent_id, $date) {
        global $wpdb;
        
        $date_start = $date . ' 00:00:00';
        $date_end = $date . ' 23:59:59';
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
        $yesterday_start = $yesterday . ' 00:00:00';
        $yesterday_end = $yesterday . ' 23:59:59';
        
        $agent_filter = $agent_id ? $wpdb->prepare('AND user_id = %d', $agent_id) : '';
        
        // Today's stats
        $total_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'post' AND created_at BETWEEN %s AND %s {$agent_filter}",
            $date_start, $date_end
        ));
        
        $total_comments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'comment' AND created_at BETWEEN %s AND %s {$agent_filter}",
            $date_start, $date_end
        ));
        
        $mention_responses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'comment' AND JSON_EXTRACT(metadata, '$.triggeredBy') = 'mention' 
             AND created_at BETWEEN %s AND %s {$agent_filter}",
            $date_start, $date_end
        ));
        
        $rss_generated = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE JSON_EXTRACT(metadata, '$.autoGenerated') = true 
             AND created_at BETWEEN %s AND %s {$agent_filter}",
            $date_start, $date_end
        ));
        
        // Yesterday's stats for comparison
        $yesterday_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'post' AND created_at BETWEEN %s AND %s {$agent_filter}",
            $yesterday_start, $yesterday_end
        ));
        
        $yesterday_comments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'comment' AND created_at BETWEEN %s AND %s {$agent_filter}",
            $yesterday_start, $yesterday_end
        ));
        
        $yesterday_mentions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'comment' AND JSON_EXTRACT(metadata, '$.triggeredBy') = 'mention' 
             AND created_at BETWEEN %s AND %s {$agent_filter}",
            $yesterday_start, $yesterday_end
        ));
        
        $yesterday_rss = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}terpedia_activities 
             WHERE JSON_EXTRACT(metadata, '$.autoGenerated') = true 
             AND created_at BETWEEN %s AND %s {$agent_filter}",
            $yesterday_start, $yesterday_end
        ));
        
        return array(
            'total_posts' => intval($total_posts),
            'total_comments' => intval($total_comments),
            'mention_responses' => intval($mention_responses),
            'rss_generated' => intval($rss_generated),
            'posts_change' => intval($total_posts) - intval($yesterday_posts),
            'comments_change' => intval($total_comments) - intval($yesterday_comments),
            'mentions_change' => intval($mention_responses) - intval($yesterday_mentions),
            'rss_change' => intval($rss_generated) - intval($yesterday_rss),
            'engagement_rate' => $this->calculate_engagement_rate($agent_id, $date),
            'engagement_change' => 0 // Would need more complex calculation
        );
    }
    
    /**
     * Calculate engagement rate
     */
    private function calculate_engagement_rate($agent_id, $date) {
        // Simplified engagement rate calculation
        // In a real implementation, you'd track likes, shares, replies, etc.
        return rand(65, 95); // Mock data
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity($agent_id, $date) {
        $activities = $this->get_recent_activities($agent_id, $date, 20);
        
        if (empty($activities)) {
            echo '<p>No recent activity found.</p>';
            return;
        }
        
        foreach ($activities as $activity) {
            $this->render_activity_item($activity);
        }
    }
    
    /**
     * Render agent posts
     */
    private function render_agent_posts($agent_id, $date) {
        $posts = $this->get_agent_posts($agent_id, $date);
        
        if (empty($posts)) {
            echo '<p>No posts found.</p>';
            return;
        }
        
        foreach ($posts as $post) {
            $this->render_activity_item($post);
        }
    }
    
    /**
     * Render agent comments
     */
    private function render_agent_comments($agent_id, $date) {
        $comments = $this->get_agent_comments($agent_id, $date);
        
        if (empty($comments)) {
            echo '<p>No comments found.</p>';
            return;
        }
        
        foreach ($comments as $comment) {
            $this->render_activity_item($comment);
        }
    }
    
    /**
     * Render mention responses
     */
    private function render_mention_responses($agent_id, $date) {
        $mentions = $this->get_mention_responses($agent_id, $date);
        
        if (empty($mentions)) {
            echo '<p>No mention responses found.</p>';
            return;
        }
        
        foreach ($mentions as $mention) {
            $this->render_activity_item($mention);
        }
    }
    
    /**
     * Render RSS generated content
     */
    private function render_rss_generated($agent_id, $date) {
        $rss_content = $this->get_rss_generated($agent_id, $date);
        
        if (empty($rss_content)) {
            echo '<p>No RSS generated content found.</p>';
            return;
        }
        
        foreach ($rss_content as $content) {
            $this->render_activity_item($content);
        }
    }
    
    /**
     * Render individual activity item
     */
    private function render_activity_item($activity) {
        $agent = get_user_by('ID', $activity->user_id);
        $agent_name = $agent ? $agent->display_name : 'Unknown Agent';
        $terpene_type = get_user_meta($activity->user_id, 'terpene_type', true);
        $metadata = json_decode($activity->metadata, true);
        
        $status_class = 'status-published';
        $status_text = 'Published';
        
        if (isset($metadata['autoGenerated']) && $metadata['autoGenerated']) {
            $status_class = 'status-auto-generated';
            $status_text = 'Auto-Generated';
        }
        
        if (isset($metadata['triggeredBy']) && $metadata['triggeredBy'] === 'mention') {
            $status_class = 'status-auto-generated';
            $status_text = 'Mention Response';
        }
        
        ?>
        <div class="activity-item">
            <div class="activity-header">
                <div class="activity-meta">
                    <span><strong><?php echo esc_html($agent_name); ?></strong> 
                    <?php if ($terpene_type): ?>
                        (<?php echo esc_html(ucfirst($terpene_type)); ?>)
                    <?php endif; ?>
                    </span>
                    <span><?php echo esc_html(ucfirst($activity->activity_type)); ?></span>
                    <span><?php echo esc_html(date('M j, Y g:i A', strtotime($activity->created_at))); ?></span>
                    <span class="activity-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
                <div class="activity-actions">
                    <?php if ($activity->activity_type === 'comment' && $status_text === 'Pending'): ?>
                        <button type="button" class="button button-small approve-comment" data-activity-id="<?php echo $activity->id; ?>">Approve</button>
                    <?php endif; ?>
                    <button type="button" class="button button-small delete-activity" data-activity-id="<?php echo $activity->id; ?>">Delete</button>
                </div>
            </div>
            
            <div class="activity-content">
                <?php echo wp_kses_post($activity->content); ?>
            </div>
            
            <?php if (!empty($metadata)): ?>
                <div class="activity-tags">
                    <?php if (isset($metadata['tags'])): ?>
                        <?php foreach ($metadata['tags'] as $tag): ?>
                            <span class="activity-tag"><?php echo esc_html($tag); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($metadata['relevanceScore'])): ?>
                        <span class="activity-tag">Relevance: <?php echo esc_html($metadata['relevanceScore']); ?>%</span>
                    <?php endif; ?>
                    
                    <?php if (isset($metadata['commentType'])): ?>
                        <span class="activity-tag">Type: <?php echo esc_html($metadata['commentType']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get recent activities
     */
    private function get_recent_activities($agent_id, $date, $limit = 20) {
        global $wpdb;
        
        $agent_filter = $agent_id ? $wpdb->prepare('AND user_id = %d', $agent_id) : '';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_activities 
             WHERE DATE(created_at) = %s {$agent_filter}
             ORDER BY created_at DESC 
             LIMIT %d",
            $date, $limit
        ));
    }
    
    /**
     * Get agent posts
     */
    private function get_agent_posts($agent_id, $date) {
        global $wpdb;
        
        $agent_filter = $agent_id ? $wpdb->prepare('AND user_id = %d', $agent_id) : '';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'post' AND DATE(created_at) = %s {$agent_filter}
             ORDER BY created_at DESC",
            $date
        ));
    }
    
    /**
     * Get agent comments
     */
    private function get_agent_comments($agent_id, $date) {
        global $wpdb;
        
        $agent_filter = $agent_id ? $wpdb->prepare('AND user_id = %d', $agent_id) : '';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'comment' AND DATE(created_at) = %s {$agent_filter}
             ORDER BY created_at DESC",
            $date
        ));
    }
    
    /**
     * Get mention responses
     */
    private function get_mention_responses($agent_id, $date) {
        global $wpdb;
        
        $agent_filter = $agent_id ? $wpdb->prepare('AND user_id = %d', $agent_id) : '';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_activities 
             WHERE activity_type = 'comment' 
             AND JSON_EXTRACT(metadata, '$.triggeredBy') = 'mention' 
             AND DATE(created_at) = %s {$agent_filter}
             ORDER BY created_at DESC",
            $date
        ));
    }
    
    /**
     * Get RSS generated content
     */
    private function get_rss_generated($agent_id, $date) {
        global $wpdb;
        
        $agent_filter = $agent_id ? $wpdb->prepare('AND user_id = %d', $agent_id) : '';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_activities 
             WHERE JSON_EXTRACT(metadata, '$.autoGenerated') = true 
             AND DATE(created_at) = %s {$agent_filter}
             ORDER BY created_at DESC",
            $date
        ));
    }
    
    /**
     * AJAX: Get agent activity
     */
    public function ajax_get_agent_activity() {
        check_ajax_referer('terpedia_agent_activity', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $agent_id = intval($_POST['agent_id']);
        $date = sanitize_text_field($_POST['date']);
        $activity_type = sanitize_text_field($_POST['activity_type']);
        
        $activities = $this->get_recent_activities($agent_id, $date, 50);
        
        wp_send_json_success($activities);
    }
    
    /**
     * AJAX: Update agent post
     */
    public function ajax_update_agent_post() {
        check_ajax_referer('terpedia_agent_activity', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $activity_id = intval($_POST['activity_id']);
        $content = wp_kses_post($_POST['content']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'terpedia_activities',
            array('content' => $content),
            array('id' => $activity_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Post updated successfully');
        } else {
            wp_send_json_error('Failed to update post');
        }
    }
    
    /**
     * AJAX: Delete agent activity
     */
    public function ajax_delete_agent_activity() {
        check_ajax_referer('terpedia_agent_activity', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $activity_id = intval($_POST['activity_id']);
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'terpedia_activities',
            array('id' => $activity_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success('Activity deleted successfully');
        } else {
            wp_send_json_error('Failed to delete activity');
        }
    }
    
    /**
     * AJAX: Approve agent comment
     */
    public function ajax_approve_agent_comment() {
        check_ajax_referer('terpedia_agent_activity', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $activity_id = intval($_POST['activity_id']);
        
        global $wpdb;
        $metadata = $wpdb->get_var($wpdb->prepare(
            "SELECT metadata FROM {$wpdb->prefix}terpedia_activities WHERE id = %d",
            $activity_id
        ));
        
        $metadata_array = json_decode($metadata, true);
        $metadata_array['approved'] = true;
        $metadata_array['approved_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $wpdb->prefix . 'terpedia_activities',
            array('metadata' => json_encode($metadata_array)),
            array('id' => $activity_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Comment approved successfully');
        } else {
            wp_send_json_error('Failed to approve comment');
        }
    }
    
    /**
     * AJAX: Export agent activity
     */
    public function ajax_export_agent_activity() {
        check_ajax_referer('terpedia_agent_activity', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $agent_id = intval($_GET['agent_id']);
        $date = sanitize_text_field($_GET['date']);
        $activity_type = sanitize_text_field($_GET['activity_type']);
        
        $activities = $this->get_recent_activities($agent_id, $date, 1000);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="agent-activity-' . $date . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Agent', 'Type', 'Content', 'Date', 'Status', 'Metadata'));
        
        foreach ($activities as $activity) {
            $agent = get_user_by('ID', $activity->user_id);
            $agent_name = $agent ? $agent->display_name : 'Unknown';
            $metadata = json_decode($activity->metadata, true);
            $status = isset($metadata['autoGenerated']) && $metadata['autoGenerated'] ? 'Auto-Generated' : 'Manual';
            
            fputcsv($output, array(
                $activity->id,
                $agent_name,
                $activity->activity_type,
                strip_tags($activity->content),
                $activity->created_at,
                $status,
                json_encode($metadata)
            ));
        }
        
        fclose($output);
        exit;
    }
}

// Initialize the agent activity manager
new TerpediaAgentActivityManager();
