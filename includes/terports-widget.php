<?php
/**
 * Terports Sidebar Widget
 * 
 * Displays recent Terports and provides quick access to Terport functionality
 * in WordPress sidebars and widget areas.
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Terports_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'terpedia_terports_widget',
            'Terpedia Terports',
            array(
                'description' => 'Display recent Terports and provide quick access to Terport functionality',
                'classname' => 'terpedia-terports-widget'
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
        $show_terport_types = !empty($instance['show_terport_types']) ? true : false;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        ?>
        <div class="terpedia-terports-widget-content">
            <?php if ($show_create_button && is_user_logged_in()): ?>
                <div class="terport-create-section">
                    <a href="<?php echo admin_url('post-new.php?post_type=terpedia_terport'); ?>" class="terport-create-btn">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Create New Terport
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($show_terport_types): ?>
                <div class="terport-types-section">
                    <h4>🧬 Terport Types</h4>
                    <ul class="terport-types-list">
                        <li><a href="<?php echo home_url('/?terport_type=research_analysis'); ?>">🔬 Research Analysis</a></li>
                        <li><a href="<?php echo home_url('/?terport_type=compound_profile'); ?>">🧪 Compound Profile</a></li>
                        <li><a href="<?php echo home_url('/?terport_type=clinical_study'); ?>">🏥 Clinical Study</a></li>
                        <li><a href="<?php echo home_url('/?terport_type=market_analysis'); ?>">📊 Market Analysis</a></li>
                        <li><a href="<?php echo home_url('/?terport_type=regulatory_update'); ?>">📋 Regulatory Update</a></li>
                        <li><a href="<?php echo home_url('/?terport_type=industry_news'); ?>">📰 Industry News</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="recent-terports-section">
                <h4>📚 Recent Terports</h4>
                <?php
                $recent_terports = get_posts(array(
                    'post_type' => 'terpedia_terport',
                    'numberposts' => $number_of_posts,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));
                
                if ($recent_terports): ?>
                    <ul class="recent-terports-list">
                        <?php foreach ($recent_terports as $terport): 
                            $terport_type = get_post_meta($terport->ID, '_terpedia_terport_type', true);
                            $terport_description = get_post_meta($terport->ID, '_terpedia_terport_description', true);
                            $type_icon = $this->get_terport_type_icon($terport_type);
                        ?>
                            <li class="terport-item">
                                <div class="terport-header">
                                    <span class="terport-type-icon"><?php echo $type_icon; ?></span>
                                    <a href="<?php echo get_permalink($terport->ID); ?>" class="terport-title">
                                        <?php echo esc_html($terport->post_title); ?>
                                    </a>
                                </div>
                                <?php if ($terport_description): ?>
                                    <div class="terport-description">
                                        <?php echo esc_html(wp_trim_words($terport_description, 15)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="terport-meta">
                                    <span class="terport-date"><?php echo get_the_date('M j, Y', $terport->ID); ?></span>
                                    <?php if ($terport_type): ?>
                                        <span class="terport-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $terport_type))); ?></span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-terports">No Terports found. <a href="<?php echo admin_url('post-new.php?post_type=terpedia_terport'); ?>">Create your first one!</a></p>
                <?php endif; ?>
            </div>
            
            <div class="terport-actions">
                <a href="<?php echo home_url('/terports/'); ?>" class="view-all-terports">
                    View All Terports
                </a>
            </div>
        </div>
        
        <style>
        .terpedia-terports-widget-content {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .terport-create-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .terport-create-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .terport-create-btn:hover {
            background: linear-gradient(135deg, #005a87 0%, #003d5c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 124, 186, 0.3);
            color: white;
        }
        
        .terport-types-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .terport-types-section h4 {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .terport-types-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .terport-types-list li {
            margin-bottom: 8px;
        }
        
        .terport-types-list a {
            display: block;
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        
        .terport-types-list a:hover {
            background: #e9ecef;
            border-color: #007cba;
            color: #007cba;
        }
        
        .recent-terports-section h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .recent-terports-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .terport-item {
            margin-bottom: 15px;
            padding: 12px;
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .terport-item:hover {
            border-color: #007cba;
            box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
        }
        
        .terport-header {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .terport-type-icon {
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .terport-title {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .terport-title:hover {
            color: #007cba;
        }
        
        .terport-description {
            color: #6c757d;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }
        
        .terport-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #6c757d;
        }
        
        .terport-type {
            background: #e7f3ff;
            color: #0066cc;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 500;
        }
        
        .no-terports {
            color: #6c757d;
            font-size: 14px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .no-terports a {
            color: #007cba;
            text-decoration: none;
        }
        
        .no-terports a:hover {
            text-decoration: underline;
        }
        
        .terport-actions {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e1e5e9;
            text-align: center;
        }
        
        .view-all-terports {
            display: inline-block;
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .view-all-terports:hover {
            background: #e9ecef;
            color: #007cba;
        }
        </style>
        <?php
        
        echo $args['after_widget'];
    }
    
    /**
     * Output the widget settings form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Terports';
        $number_of_posts = !empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 5;
        $show_create_button = !empty($instance['show_create_button']) ? true : false;
        $show_terport_types = !empty($instance['show_terport_types']) ? true : false;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('number_of_posts'); ?>">Number of recent Terports to show:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('number_of_posts'); ?>" name="<?php echo $this->get_field_name('number_of_posts'); ?>" type="number" step="1" min="1" max="20" value="<?php echo esc_attr($number_of_posts); ?>">
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_create_button); ?> id="<?php echo $this->get_field_id('show_create_button'); ?>" name="<?php echo $this->get_field_name('show_create_button'); ?>" />
            <label for="<?php echo $this->get_field_id('show_create_button'); ?>">Show "Create New Terport" button</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_terport_types); ?> id="<?php echo $this->get_field_id('show_terport_types'); ?>" name="<?php echo $this->get_field_name('show_terport_types'); ?>" />
            <label for="<?php echo $this->get_field_id('show_terport_types'); ?>">Show Terport types list</label>
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
        $instance['show_terport_types'] = !empty($new_instance['show_terport_types']) ? true : false;
        
        return $instance;
    }
    
    /**
     * Get icon for Terport type
     */
    private function get_terport_type_icon($type) {
        $icons = array(
            'research_analysis' => '🔬',
            'compound_profile' => '🧪',
            'clinical_study' => '🏥',
            'market_analysis' => '📊',
            'regulatory_update' => '📋',
            'industry_news' => '📰'
        );
        
        return isset($icons[$type]) ? $icons[$type] : '📄';
    }
}

// Register the widget
function register_terpedia_terports_widget() {
    register_widget('Terpedia_Terports_Widget');
}
add_action('widgets_init', 'register_terpedia_terports_widget');

