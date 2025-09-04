<?php
/**
 * RSS Feed Admin Interface
 * Central admin page for managing all agent RSS feeds and content generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaRSSAdminInterface {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_bulk_setup_agent_feeds', array($this, 'ajax_bulk_setup_feeds'));
        add_action('wp_ajax_bulk_backfill_content', array($this, 'ajax_bulk_backfill'));
        add_action('wp_ajax_rss_dashboard_stats', array($this, 'ajax_dashboard_stats'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-settings',
            'RSS & Content Generation',
            'RSS Feeds',
            'manage_options',
            'terpedia-rss-dashboard',
            array($this, 'admin_dashboard')
        );
    }
    
    /**
     * RSS management dashboard
     */
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1>📰 RSS Feed & Content Generation Dashboard</h1>
            
            <!-- Quick Actions -->
            <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
                <h3>🚀 Quick Actions</h3>
                <p>
                    <button type="button" id="bulk-setup-feeds" class="button button-primary">
                        🔧 Setup All Agent Feeds
                    </button>
                    <button type="button" id="bulk-backfill-content" class="button">
                        📚 Backfill 7 Days Content
                    </button>
                    <button type="button" id="refresh-dashboard" class="button">
                        🔄 Refresh Stats
                    </button>
                </p>
            </div>
            
            <!-- Dashboard Stats -->
            <div id="rss-dashboard-stats" style="margin: 20px 0;">
                <div class="stats-loading" style="padding: 40px; text-align: center; background: #fff; border: 1px solid #ccd0d4;">
                    <p>Loading dashboard statistics...</p>
                </div>
            </div>
            
            <!-- Agent Feed Overview -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                <h3>🤖 Agent Feed Configuration</h3>
                <?php $this->render_agent_feed_table(); ?>
            </div>
            
            <!-- System Information -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                <h3>⚙️ System Information</h3>
                <?php $this->render_system_info(); ?>
            </div>
            
            <div id="bulk-action-results"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Load dashboard stats on page load
            loadDashboardStats();
            
            function loadDashboardStats() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'rss_dashboard_stats',
                        nonce: '<?php echo wp_create_nonce("terpedia_admin_nonce"); ?>'
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $('#rss-dashboard-stats').html(data.html);
                        }
                    }
                });
            }
            
            // Bulk setup feeds
            $('#bulk-setup-feeds').on('click', function() {
                var $btn = $(this);
                var $result = $('#bulk-action-results');
                
                $btn.prop('disabled', true).text('Setting up feeds...');
                $result.html('<div style="background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 4px;">🔧 Setting up RSS feeds for all agents...</div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bulk_setup_agent_feeds',
                        nonce: '<?php echo wp_create_nonce("terpedia_admin_nonce"); ?>'
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $result.html('<div style="background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 4px;"><strong>✅ Setup Complete!</strong><br>Configured ' + data.agents_configured + ' agents with RSS feeds<br>Total feeds: ' + data.total_feeds + '</div>');
                            loadDashboardStats(); // Refresh stats
                        } else {
                            $result.html('<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;"><strong>❌ Setup Failed</strong><br>' + data.error + '</div>');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🔧 Setup All Agent Feeds');
                    }
                });
            });
            
            // Bulk backfill content
            $('#bulk-backfill-content').on('click', function() {
                var $btn = $(this);
                var $result = $('#bulk-action-results');
                
                if (!confirm('This will generate 7 days of content for all 20 agents (up to 140 posts). Continue?')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Generating content...');
                $result.html('<div style="background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 4px;">📚 Generating historical content for all agents... This may take several minutes.</div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bulk_backfill_content',
                        days: 7,
                        nonce: '<?php echo wp_create_nonce("terpedia_admin_nonce"); ?>'
                    },
                    timeout: 300000, // 5 minutes
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            $result.html('<div style="background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 4px;"><strong>✅ Backfill Complete!</strong><br>Generated ' + data.posts_created + ' posts across ' + data.agents_processed + ' agents</div>');
                            loadDashboardStats();
                        } else {
                            $result.html('<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;"><strong>❌ Backfill Failed</strong><br>' + data.error + '</div>');
                        }
                    },
                    error: function() {
                        $result.html('<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;"><strong>❌ Request Failed</strong><br>Content generation timed out or failed.</div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('📚 Backfill 7 Days Content');
                    }
                });
            });
            
            // Refresh dashboard
            $('#refresh-dashboard').on('click', function() {
                loadDashboardStats();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render agent feed configuration table
     */
    private function render_agent_feed_table() {
        $agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_compare' => 'EXISTS'
        ));
        
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Type</th>
                    <th>Feeds</th>
                    <th>Keywords</th>
                    <th>Frequency</th>
                    <th>Last Check</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): 
                    $agent_type = get_user_meta($agent->ID, 'terpedia_agent_type', true);
                    $terpene_name = get_user_meta($agent->ID, 'terpedia_terpene_name', true);
                    $feeds = get_user_meta($agent->ID, 'terpedia_rss_feeds', true) ?: array();
                    $keywords = get_user_meta($agent->ID, 'terpedia_rss_keywords', true);
                    $frequency = get_user_meta($agent->ID, 'terpedia_post_frequency', true) ?: 'daily';
                    $last_check = get_user_meta($agent->ID, 'terpedia_last_rss_check', true);
                    $posts_count = get_user_meta($agent->ID, 'terpedia_posts_created_count', true) ?: 0;
                ?>
                <tr>
                    <td><strong><?php echo esc_html($agent->display_name); ?></strong></td>
                    <td><?php echo esc_html($agent_type === 'terpene' ? $terpene_name : $agent_type); ?></td>
                    <td><?php echo count($feeds); ?> feeds</td>
                    <td><?php echo esc_html(substr($keywords, 0, 50)) . (strlen($keywords) > 50 ? '...' : ''); ?></td>
                    <td><?php echo esc_html($frequency); ?></td>
                    <td><?php echo $last_check ? human_time_diff(strtotime($last_check)) . ' ago' : 'Never'; ?></td>
                    <td><?php echo intval($posts_count); ?></td>
                    <td>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $agent->ID . '#terpedia-rss-settings'); ?>" class="button button-small">
                            ⚙️ Configure
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render system information
     */
    private function render_system_info() {
        $cron_scheduled = wp_next_scheduled('terpedia_daily_rss_check');
        ?>
        <table class="form-table">
            <tr>
                <th>Daily Cron Status</th>
                <td>
                    <?php if ($cron_scheduled): ?>
                        <span style="color: green;">✅ Scheduled</span> 
                        - Next run: <?php echo human_time_diff($cron_scheduled) . ' from now'; ?>
                    <?php else: ?>
                        <span style="color: red;">❌ Not Scheduled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>OpenRouter Status</th>
                <td>
                    <?php 
                    $api_key = get_option('terpedia_openrouter_api_key', '') ?: ($_ENV['OPENROUTER_API_KEY'] ?? '');
                    echo !empty($api_key) ? '<span style="color: green;">✅ Configured</span>' : '<span style="color: red;">❌ API Key Missing</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <th>Model</th>
                <td><?php echo get_option('terpedia_openrouter_model', 'openai/gpt-oss-120b:free'); ?></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * AJAX: Bulk setup agent feeds
     */
    public function ajax_bulk_setup_feeds() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        if (class_exists('TerpediaGoogleNewsFeeds')) {
            $google_feeds = new TerpediaGoogleNewsFeeds();
            $google_feeds->setup_default_feeds_for_agents();
            
            // Count configured agents
            $agents = get_users(array(
                'meta_key' => 'terpedia_agent_type',
                'meta_compare' => 'EXISTS'
            ));
            
            $total_feeds = 0;
            foreach ($agents as $agent) {
                $feeds = get_user_meta($agent->ID, 'terpedia_rss_feeds', true) ?: array();
                $total_feeds += count($feeds);
            }
            
            wp_die(json_encode(array(
                'success' => true,
                'agents_configured' => count($agents),
                'total_feeds' => $total_feeds
            )));
        }
        
        wp_die(json_encode(array('error' => 'Google News Feeds class not available')));
    }
    
    /**
     * AJAX: Bulk backfill content
     */
    public function ajax_bulk_backfill() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $days = intval($_POST['days'] ?? 7);
        
        if (class_exists('TerpediaGoogleNewsFeeds')) {
            $google_feeds = new TerpediaGoogleNewsFeeds();
            $results = $google_feeds->backfill_all_agents($days);
            
            wp_die(json_encode(array(
                'success' => true,
                'posts_created' => count($results),
                'agents_processed' => count(array_unique(array_column($results, 'agent'))),
                'results' => $results
            )));
        }
        
        wp_die(json_encode(array('error' => 'Google News Feeds class not available')));
    }
    
    /**
     * AJAX: Dashboard statistics
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        $stats = $this->get_dashboard_stats();
        $html = $this->render_dashboard_stats($stats);
        
        wp_die(json_encode(array(
            'success' => true,
            'html' => $html,
            'stats' => $stats
        )));
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        $agents = get_users(array(
            'meta_key' => 'terpedia_agent_type',
            'meta_compare' => 'EXISTS'
        ));
        
        $stats = array(
            'total_agents' => count($agents),
            'agents_with_feeds' => 0,
            'total_feeds' => 0,
            'total_posts_generated' => 0,
            'posts_today' => 0,
            'posts_this_week' => 0,
            'active_agents' => 0
        );
        
        foreach ($agents as $agent) {
            $feeds = get_user_meta($agent->ID, 'terpedia_rss_feeds', true) ?: array();
            $posts_count = get_user_meta($agent->ID, 'terpedia_posts_created_count', true) ?: 0;
            $last_check = get_user_meta($agent->ID, 'terpedia_last_rss_check', true);
            
            if (!empty($feeds)) {
                $stats['agents_with_feeds']++;
                $stats['total_feeds'] += count($feeds);
            }
            
            $stats['total_posts_generated'] += intval($posts_count);
            
            // Count posts from today
            $today_posts = get_posts(array(
                'author' => $agent->ID,
                'date_query' => array(
                    array(
                        'after' => 'today'
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => 'terpedia_ai_generated',
                        'value' => true
                    )
                )
            ));
            $stats['posts_today'] += count($today_posts);
            
            // Count posts from this week
            $week_posts = get_posts(array(
                'author' => $agent->ID,
                'date_query' => array(
                    array(
                        'after' => '1 week ago'
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => 'terpedia_ai_generated',
                        'value' => true
                    )
                )
            ));
            $stats['posts_this_week'] += count($week_posts);
            
            // Check if agent is active (checked feeds in last 24 hours)
            if ($last_check && strtotime($last_check) > (time() - 24 * 60 * 60)) {
                $stats['active_agents']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Render dashboard statistics HTML
     */
    private function render_dashboard_stats($stats) {
        ob_start();
        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            
            <div style="background: #fff; padding: 20px; border: 1px solid #0073aa; border-radius: 4px; text-align: center;">
                <h3 style="margin: 0; color: #0073aa;">🤖 Total Agents</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold;"><?php echo $stats['total_agents']; ?></p>
                <p style="margin: 0; color: #666;">AI personalities active</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #00a32a; border-radius: 4px; text-align: center;">
                <h3 style="margin: 0; color: #00a32a;">📡 RSS Feeds</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold;"><?php echo $stats['total_feeds']; ?></p>
                <p style="margin: 0; color: #666;"><?php echo $stats['agents_with_feeds']; ?> agents configured</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #d63638; border-radius: 4px; text-align: center;">
                <h3 style="margin: 0; color: #d63638;">📝 Posts Generated</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold;"><?php echo $stats['total_posts_generated']; ?></p>
                <p style="margin: 0; color: #666;">Total content created</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #f56e28; border-radius: 4px; text-align: center;">
                <h3 style="margin: 0; color: #f56e28;">📅 Today</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold;"><?php echo $stats['posts_today']; ?></p>
                <p style="margin: 0; color: #666;">Posts created today</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #7c3aed; border-radius: 4px; text-align: center;">
                <h3 style="margin: 0; color: #7c3aed;">📊 This Week</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold;"><?php echo $stats['posts_this_week']; ?></p>
                <p style="margin: 0; color: #666;">Posts this week</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #059669; border-radius: 4px; text-align: center;">
                <h3 style="margin: 0; color: #059669;">⚡ Active Agents</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold;"><?php echo $stats['active_agents']; ?></p>
                <p style="margin: 0; color: #666;">Active in 24h</p>
            </div>
            
        </div>
        
        <!-- Recent Activity -->
        <div style="margin: 20px 0;">
            <h4>📈 Recent Activity</h4>
            <?php
            $recent_posts = get_posts(array(
                'numberposts' => 10,
                'meta_key' => 'terpedia_ai_generated',
                'meta_value' => true,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if (!empty($recent_posts)): ?>
                <ul style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                    <?php foreach ($recent_posts as $post): 
                        $author = get_userdata($post->post_author);
                        $model = get_post_meta($post->ID, 'terpedia_ai_model', true);
                    ?>
                        <li style="margin: 5px 0;">
                            <strong><?php echo esc_html($author->display_name); ?></strong> - 
                            <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                            <em style="color: #666; font-size: 0.9em;">
                                (<?php echo human_time_diff(strtotime($post->post_date)) . ' ago'; ?>, 
                                <?php echo $model ?: 'gpt-oss-120b'; ?>)
                            </em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #666;">No AI-generated posts found yet.</p>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
}

// Initialize RSS admin interface
new TerpediaRSSAdminInterface();