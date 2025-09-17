<?php
/**
 * User Profile System
 * 
 * Handles user profile pages and displays user's posts
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_User_Profile_System {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('template_redirect', array($this, 'handle_profile_routes'));
    }
    
    public function init() {
        // Add rewrite rules for user profiles
        add_rewrite_rule('^user/([^/]+)/?$', 'index.php?terpedia_user_profile=1&terpedia_username=$matches[1]', 'top');
        add_rewrite_rule('^profile/([^/]+)/?$', 'index.php?terpedia_user_profile=1&terpedia_username=$matches[1]', 'top');
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'terpedia_user_profile';
        $vars[] = 'terpedia_username';
        return $vars;
    }
    
    public function handle_profile_routes() {
        if (get_query_var('terpedia_user_profile')) {
            $username = get_query_var('terpedia_username');
            $this->render_user_profile($username);
            exit;
        }
    }
    
    public function render_user_profile($username) {
        $user = get_user_by('login', $username);
        
        if (!$user) {
            $user = get_user_by('slug', $username);
        }
        
        if (!$user) {
            $this->render_user_not_found($username);
            return;
        }
        
        // Get user's posts
        $user_posts = $this->get_user_posts($user->ID);
        $user_stats = $this->get_user_stats($user->ID);
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($user->display_name); ?> - Terpedia Profile</title>
            <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../assets/css/user-profile.css'; ?>">
            <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                margin: 0;
                padding: 0;
                background: #f8f9fa;
            }
            
            .profile-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .profile-header {
                background: white;
                border-radius: 12px;
                padding: 30px;
                margin-bottom: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 30px;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 48px;
                color: white;
                font-weight: bold;
            }
            
            .profile-info h1 {
                margin: 0 0 10px 0;
                color: #2c3e50;
                font-size: 32px;
            }
            
            .profile-username {
                color: #6c757d;
                font-size: 18px;
                margin-bottom: 15px;
            }
            
            .profile-bio {
                color: #495057;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            
            .profile-stats {
                display: flex;
                gap: 30px;
            }
            
            .stat-item {
                text-align: center;
            }
            
            .stat-number {
                display: block;
                font-size: 24px;
                font-weight: bold;
                color: #007cba;
            }
            
            .stat-label {
                font-size: 14px;
                color: #6c757d;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .profile-content {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 20px;
            }
            
            .posts-section {
                background: white;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .posts-section h2 {
                margin: 0 0 20px 0;
                color: #2c3e50;
                font-size: 24px;
            }
            
            .post-item {
                border-bottom: 1px solid #e1e5e9;
                padding: 20px 0;
            }
            
            .post-item:last-child {
                border-bottom: none;
            }
            
            .post-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 10px;
            }
            
            .post-type-icon {
                font-size: 20px;
            }
            
            .post-title {
                color: #2c3e50;
                text-decoration: none;
                font-size: 18px;
                font-weight: 600;
            }
            
            .post-title:hover {
                color: #007cba;
            }
            
            .post-meta {
                display: flex;
                gap: 15px;
                font-size: 14px;
                color: #6c757d;
                margin-bottom: 10px;
            }
            
            .post-excerpt {
                color: #495057;
                line-height: 1.6;
            }
            
            .sidebar {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .sidebar-widget {
                background: white;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .sidebar-widget h3 {
                margin: 0 0 15px 0;
                color: #2c3e50;
                font-size: 18px;
            }
            
            .terpene-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .terpene-list li {
                padding: 8px 0;
                border-bottom: 1px solid #f1f3f4;
            }
            
            .terpene-list li:last-child {
                border-bottom: none;
            }
            
            .terpene-list a {
                color: #007cba;
                text-decoration: none;
                font-weight: 500;
            }
            
            .terpene-list a:hover {
                text-decoration: underline;
            }
            
            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: #6c757d;
                text-decoration: none;
                margin-bottom: 20px;
                font-weight: 500;
            }
            
            .back-link:hover {
                color: #007cba;
            }
            
            @media (max-width: 768px) {
                .profile-content {
                    grid-template-columns: 1fr;
                }
                
                .profile-header {
                    flex-direction: column;
                    text-align: center;
                }
                
                .profile-stats {
                    justify-content: center;
                }
            }
            </style>
        </head>
        <body>
            <div class="profile-container">
                <a href="<?php echo home_url('/feed/'); ?>" class="back-link">
                    ← Back to Feed
                </a>
                
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user->display_name, 0, 2)); ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo esc_html($user->display_name); ?></h1>
                        <div class="profile-username">@<?php echo esc_html($user->user_login); ?></div>
                        <div class="profile-bio">
                            <?php echo esc_html($user->description ?: 'No bio available.'); ?>
                        </div>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['total_posts']; ?></span>
                                <span class="stat-label">Posts</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['terports']; ?></span>
                                <span class="stat-label">Terports</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['podcasts']; ?></span>
                                <span class="stat-label">Podcasts</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['newsletters']; ?></span>
                                <span class="stat-label">Newsletters</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-content">
                    <div class="posts-section">
                        <h2>📚 Recent Posts</h2>
                        <?php if (!empty($user_posts)): ?>
                            <?php foreach ($user_posts as $post): ?>
                                <div class="post-item">
                                    <div class="post-header">
                                        <span class="post-type-icon"><?php echo $this->get_post_type_icon($post->post_type); ?></span>
                                        <a href="<?php echo get_permalink($post->ID); ?>" class="post-title">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </div>
                                    <div class="post-meta">
                                        <span>📅 <?php echo get_the_date('M j, Y', $post->ID); ?></span>
                                        <span>🏷️ <?php echo esc_html(ucwords(str_replace('_', ' ', $post->post_type))); ?></span>
                                    </div>
                                    <div class="post-excerpt">
                                        <?php echo esc_html(wp_trim_words($post->post_content, 30)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No posts found for this user.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sidebar">
                        <div class="sidebar-widget">
                            <h3>🧬 Research Focus</h3>
                            <ul class="terpene-list">
                                <?php
                                $user_terpenes = get_user_meta($user->ID, 'terpedia_research_terpenes', true);
                                if ($user_terpenes && is_array($user_terpenes)) {
                                    foreach (array_slice($user_terpenes, 0, 5) as $terpene) {
                                        echo '<li><a href="' . home_url('/terpene/' . $terpene . '/') . '">' . esc_html(ucwords($terpene)) . '</a></li>';
                                    }
                                } else {
                                    echo '<li>No specific research focus listed</li>';
                                }
                                ?>
                            </ul>
                        </div>
                        
                        <div class="sidebar-widget">
                            <h3>📊 Activity</h3>
                            <p>Member since: <?php echo date('M Y', strtotime($user->user_registered)); ?></p>
                            <p>Last active: <?php echo date('M j, Y', strtotime($user->user_registered)); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    public function render_user_not_found($username) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>User Not Found - Terpedia</title>
        </head>
        <body>
            <div style="text-align: center; padding: 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <h1>User Not Found</h1>
                <p>The user "@<?php echo esc_html($username); ?>" could not be found.</p>
                <a href="<?php echo home_url('/feed/'); ?>">← Back to Feed</a>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function get_user_posts($user_id) {
        return get_posts(array(
            'author' => $user_id,
            'post_type' => array('terpedia_terport', 'terpedia_podcast', 'terpedia_newsletter', 'post'),
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }
    
    private function get_user_stats($user_id) {
        $terports = wp_count_posts('terpedia_terport');
        $podcasts = wp_count_posts('terpedia_podcast');
        $newsletters = wp_count_posts('terpedia_newsletter');
        $posts = wp_count_posts('post');
        
        return array(
            'total_posts' => ($terports->publish ?? 0) + ($podcasts->publish ?? 0) + ($newsletters->publish ?? 0) + ($posts->publish ?? 0),
            'terports' => $terports->publish ?? 0,
            'podcasts' => $podcasts->publish ?? 0,
            'newsletters' => $newsletters->publish ?? 0
        );
    }
    
    private function get_post_type_icon($post_type) {
        $icons = array(
            'terpedia_terport' => '🧬',
            'terpedia_podcast' => '🎙️',
            'terpedia_newsletter' => '📰',
            'post' => '📝'
        );
        
        return isset($icons[$post_type]) ? $icons[$post_type] : '📄';
    }
    
    public static function get_user_profile_url($user_id) {
        $user = get_user_by('ID', $user_id);
        if ($user) {
            return home_url('/user/' . $user->user_login . '/');
        }
        return '';
    }
}

// Initialize the user profile system
new Terpedia_User_Profile_System();

