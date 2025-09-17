<?php
/**
 * Podcasts Sidebar Widget
 * 
 * Displays recent Podcasts and provides quick access to Podcast functionality
 * in WordPress sidebars and widget areas.
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Podcasts_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'terpedia_podcasts_widget',
            'Terpedia Podcasts',
            array(
                'description' => 'Display recent Podcasts and provide quick access to Podcast functionality',
                'classname' => 'terpedia-podcasts-widget'
            )
        );
    }
    
    /**
     * Output the widget content
     */
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $number_of_posts = !empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 5;
        $show_create_button = !empty($instance['show_create_button']) ? true : false;
        $show_podcast_stats = !empty($instance['show_podcast_stats']) ? true : false;
        $show_featured_podcast = !empty($instance['show_featured_podcast']) ? true : false;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        ?>
        <div class="terpedia-podcasts-widget-content">
            <?php if ($show_create_button && is_user_logged_in()): ?>
                <div class="podcast-create-section">
                    <a href="<?php echo admin_url('post-new.php?post_type=terpedia_podcast'); ?>" class="podcast-create-btn">
                        <span class="dashicons dashicons-microphone"></span>
                        Create New Podcast
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($show_podcast_stats): ?>
                <div class="podcast-stats-section">
                    <h4>📊 Podcast Stats</h4>
                    <div class="stats-grid">
                        <?php
                        $total_podcasts = wp_count_posts('terpedia_podcast');
                        $published_podcasts = $total_podcasts->publish ?? 0;
                        $draft_podcasts = $total_podcasts->draft ?? 0;
                        $this_month = get_posts(array(
                            'post_type' => 'terpedia_podcast',
                            'post_status' => 'publish',
                            'date_query' => array(
                                array(
                                    'after' => '1 month ago'
                                )
                            ),
                            'numberposts' => -1
                        ));
                        $this_month_count = count($this_month);
                        ?>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $published_podcasts; ?></span>
                            <span class="stat-label">Published</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $draft_podcasts; ?></span>
                            <span class="stat-label">Drafts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $this_month_count; ?></span>
                            <span class="stat-label">This Month</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($show_featured_podcast): ?>
                <div class="featured-podcast-section">
                    <h4>⭐ Featured Podcast</h4>
                    <?php
                    $featured_podcast = get_posts(array(
                        'post_type' => 'terpedia_podcast',
                        'post_status' => 'publish',
                        'numberposts' => 1,
                        'meta_query' => array(
                            array(
                                'key' => '_featured_podcast',
                                'value' => '1',
                                'compare' => '='
                            )
                        )
                    ));
                    
                    if (empty($featured_podcast)) {
                        // Fallback to most recent podcast
                        $featured_podcast = get_posts(array(
                            'post_type' => 'terpedia_podcast',
                            'post_status' => 'publish',
                            'numberposts' => 1,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ));
                    }
                    
                    if (!empty($featured_podcast)):
                        $podcast = $featured_podcast[0];
                        $podcast_topic = get_post_meta($podcast->ID, '_podcast_topic', true);
                        $podcast_duration = get_post_meta($podcast->ID, '_podcast_duration', true);
                        $podcast_speakers = get_post_meta($podcast->ID, '_podcast_speakers', true);
                    ?>
                        <div class="featured-podcast-item">
                            <div class="podcast-thumbnail">
                                <?php if (has_post_thumbnail($podcast->ID)): ?>
                                    <?php echo get_the_post_thumbnail($podcast->ID, 'thumbnail'); ?>
                                <?php else: ?>
                                    <div class="default-thumbnail">🎙️</div>
                                <?php endif; ?>
                            </div>
                            <div class="podcast-info">
                                <h5 class="podcast-title">
                                    <a href="<?php echo get_permalink($podcast->ID); ?>">
                                        <?php echo esc_html($podcast->post_title); ?>
                                    </a>
                                </h5>
                                <?php if ($podcast_topic): ?>
                                    <p class="podcast-topic"><?php echo esc_html($podcast_topic); ?></p>
                                <?php endif; ?>
                                <div class="podcast-meta">
                                    <?php if ($podcast_duration): ?>
                                        <span class="podcast-duration">⏱️ <?php echo esc_html($podcast_duration); ?> min</span>
                                    <?php endif; ?>
                                    <span class="podcast-date">📅 <?php echo get_the_date('M j, Y', $podcast->ID); ?></span>
                                </div>
                                <?php if ($podcast_speakers && is_array($podcast_speakers)): ?>
                                    <div class="podcast-speakers">
                                        <span class="speakers-label">Speakers:</span>
                                        <?php
                                        $speaker_names = array();
                                        foreach ($podcast_speakers as $speaker_id) {
                                            $speaker = get_user_by('ID', $speaker_id);
                                            if ($speaker) {
                                                $speaker_names[] = $speaker->display_name;
                                            }
                                        }
                                        echo esc_html(implode(', ', array_slice($speaker_names, 0, 3)));
                                        if (count($speaker_names) > 3) {
                                            echo ' +' . (count($speaker_names) - 3) . ' more';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="no-featured-podcast">No featured podcast available.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="recent-podcasts-section">
                <h4>🎧 Recent Podcasts</h4>
                <?php
                $recent_podcasts = get_posts(array(
                    'post_type' => 'terpedia_podcast',
                    'numberposts' => $number_of_posts,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));
                
                if ($recent_podcasts): ?>
                    <ul class="recent-podcasts-list">
                        <?php foreach ($recent_podcasts as $podcast): 
                            $podcast_topic = get_post_meta($podcast->ID, '_podcast_topic', true);
                            $podcast_duration = get_post_meta($podcast->ID, '_podcast_duration', true);
                            $podcast_speakers = get_post_meta($podcast->ID, '_podcast_speakers', true);
                        ?>
                            <li class="podcast-item">
                                <div class="podcast-header">
                                    <span class="podcast-icon">🎙️</span>
                                    <a href="<?php echo get_permalink($podcast->ID); ?>" class="podcast-title">
                                        <?php echo esc_html($podcast->post_title); ?>
                                    </a>
                                </div>
                                <?php if ($podcast_topic): ?>
                                    <div class="podcast-topic">
                                        <?php echo esc_html(wp_trim_words($podcast_topic, 12)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="podcast-meta">
                                    <span class="podcast-date"><?php echo get_the_date('M j, Y', $podcast->ID); ?></span>
                                    <?php if ($podcast_duration): ?>
                                        <span class="podcast-duration"><?php echo esc_html($podcast_duration); ?> min</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($podcast_speakers && is_array($podcast_speakers)): ?>
                                    <div class="podcast-speakers">
                                        <?php
                                        $speaker_names = array();
                                        foreach (array_slice($podcast_speakers, 0, 2) as $speaker_id) {
                                            $speaker = get_user_by('ID', $speaker_id);
                                            if ($speaker) {
                                                $speaker_names[] = $speaker->display_name;
                                            }
                                        }
                                        echo esc_html(implode(', ', $speaker_names));
                                        if (count($podcast_speakers) > 2) {
                                            echo ' +' . (count($podcast_speakers) - 2) . ' more';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-podcasts">No podcasts found. <a href="<?php echo admin_url('post-new.php?post_type=terpedia_podcast'); ?>">Create your first one!</a></p>
                <?php endif; ?>
            </div>
            
            <div class="podcast-actions">
                <a href="<?php echo home_url('/podcasts/'); ?>" class="view-all-podcasts">
                    View All Podcasts
                </a>
                <a href="<?php echo home_url('/podcast-conversations/'); ?>" class="view-conversations">
                    View Conversations
                </a>
            </div>
        </div>
        
        <style>
        .terpedia-podcasts-widget-content {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .podcast-create-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .podcast-create-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .podcast-create-btn:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
            color: white;
        }
        
        .podcast-stats-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .podcast-stats-section h4 {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .stat-item {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .stat-number {
            display: block;
            font-size: 18px;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .featured-podcast-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .featured-podcast-section h4 {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .featured-podcast-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #fff;
            border: 2px solid #e74c3c;
            border-radius: 8px;
        }
        
        .podcast-thumbnail {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .podcast-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .default-thumbnail {
            font-size: 24px;
        }
        
        .podcast-info {
            flex: 1;
        }
        
        .podcast-title {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .podcast-title a {
            color: #2c3e50;
            text-decoration: none;
        }
        
        .podcast-title a:hover {
            color: #e74c3c;
        }
        
        .podcast-topic {
            margin: 0 0 8px 0;
            color: #6c757d;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .podcast-meta {
            display: flex;
            gap: 12px;
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 6px;
        }
        
        .podcast-speakers {
            font-size: 11px;
            color: #6c757d;
        }
        
        .speakers-label {
            font-weight: 600;
        }
        
        .no-featured-podcast {
            color: #6c757d;
            font-size: 14px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .recent-podcasts-section h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .recent-podcasts-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .podcast-item {
            margin-bottom: 15px;
            padding: 12px;
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .podcast-item:hover {
            border-color: #e74c3c;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.1);
        }
        
        .podcast-header {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .podcast-icon {
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .podcast-title {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .podcast-title:hover {
            color: #e74c3c;
        }
        
        .podcast-topic {
            color: #6c757d;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }
        
        .podcast-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 6px;
        }
        
        .podcast-duration {
            background: #e7f3ff;
            color: #0066cc;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 500;
        }
        
        .podcast-speakers {
            font-size: 11px;
            color: #6c757d;
        }
        
        .no-podcasts {
            color: #6c757d;
            font-size: 14px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .no-podcasts a {
            color: #e74c3c;
            text-decoration: none;
        }
        
        .no-podcasts a:hover {
            text-decoration: underline;
        }
        
        .podcast-actions {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e1e5e9;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .view-all-podcasts,
        .view-conversations {
            display: inline-block;
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .view-all-podcasts:hover,
        .view-conversations:hover {
            background: #e9ecef;
            color: #e74c3c;
        }
        </style>
        <?php
        
        echo $args['after_widget'];
    }
    
    /**
     * Output the widget settings form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Podcasts';
        $number_of_posts = !empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 5;
        $show_create_button = !empty($instance['show_create_button']) ? true : false;
        $show_podcast_stats = !empty($instance['show_podcast_stats']) ? true : false;
        $show_featured_podcast = !empty($instance['show_featured_podcast']) ? true : false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('number_of_posts'); ?>">Number of recent Podcasts to show:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('number_of_posts'); ?>" name="<?php echo $this->get_field_name('number_of_posts'); ?>" type="number" step="1" min="1" max="20" value="<?php echo esc_attr($number_of_posts); ?>">
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_create_button); ?> id="<?php echo $this->get_field_id('show_create_button'); ?>" name="<?php echo $this->get_field_name('show_create_button'); ?>" />
            <label for="<?php echo $this->get_field_id('show_create_button'); ?>">Show "Create New Podcast" button</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_podcast_stats); ?> id="<?php echo $this->get_field_id('show_podcast_stats'); ?>" name="<?php echo $this->get_field_name('show_podcast_stats'); ?>" />
            <label for="<?php echo $this->get_field_id('show_podcast_stats'); ?>">Show podcast statistics</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_featured_podcast); ?> id="<?php echo $this->get_field_id('show_featured_podcast'); ?>" name="<?php echo $this->get_field_name('show_featured_podcast'); ?>" />
            <label for="<?php echo $this->get_field_id('show_featured_podcast'); ?>">Show featured podcast</label>
        </p>
        <?php
    }
    
    /**
     * Update widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['number_of_posts'] = (!empty($new_instance['number_of_posts'])) ? absint($new_instance['number_of_posts']) : 5;
        $instance['show_create_button'] = !empty($new_instance['show_create_button']) ? true : false;
        $instance['show_podcast_stats'] = !empty($new_instance['show_podcast_stats']) ? true : false;
        $instance['show_featured_podcast'] = !empty($new_instance['show_featured_podcast']) ? true : false;
        
        return $instance;
    }
}

// Register the widget
function register_terpedia_podcasts_widget() {
    register_widget('Terpedia_Podcasts_Widget');
}
add_action('widgets_init', 'register_terpedia_podcasts_widget');

