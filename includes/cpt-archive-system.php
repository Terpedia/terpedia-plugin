<?php
/**
 * Terpedia CPT Archive System
 * Creates unified frontend archive pages for all custom post types
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_CPT_Archive_System {
    
    public function __construct() {
        add_action('init', array($this, 'init'), 20); // Higher priority after CPTs are registered
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'archive_template'));
        add_action('wp_footer', array($this, 'add_archive_navigation'));
        
        // Add rewrite rules for archives
        add_action('init', array($this, 'add_archive_rewrite_rules'), 25);
        add_filter('query_vars', array($this, 'add_archive_query_vars'));
        
        // Flush rewrites on activation
        register_activation_hook(__FILE__, array($this, 'flush_rewrite_rules'));
        register_deactivation_hook(__FILE__, array($this, 'flush_rewrite_rules'));
    }
    
    public function init() {
        // Ensure all main CPTs have proper archive settings
        $this->ensure_cpt_archives();
        
        // Add CPT archive pages to main navigation
        add_filter('wp_nav_menu_items', array($this, 'add_cpt_nav_items'), 10, 2);
    }
    
    /**
     * Ensure CPTs have proper archive settings
     */
    private function ensure_cpt_archives() {
        global $wp_post_types;
        
        $cpt_configs = array(
            'terpedia_terproduct' => array(
                'has_archive' => true,
                'public' => true,
                'rewrite' => array('slug' => 'terproducts')
            ),
            'terpedia_podcast' => array(
                'has_archive' => true, 
                'public' => true,
                'rewrite' => array('slug' => 'podcasts')
            ),
            'terpedia_rx' => array(
                'has_archive' => true,
                'public' => true, 
                'rewrite' => array('slug' => 'rx-formulations')
            ),
            'terpedia_newsletter' => array(
                'has_archive' => true,
                'public' => true,
                'rewrite' => array('slug' => 'newsletters')
            ),
            'terpedia_terport' => array(
                'has_archive' => true,
                'public' => true,
                'rewrite' => array('slug' => 'terports')
            )
        );
        
        foreach ($cpt_configs as $post_type => $config) {
            if (isset($wp_post_types[$post_type])) {
                foreach ($config as $key => $value) {
                    $wp_post_types[$post_type]->$key = $value;
                }
            }
        }
    }
    
    /**
     * Add archive rewrite rules
     */
    public function add_archive_rewrite_rules() {
        add_rewrite_rule('^cpt-archives/?$', 'index.php?cpt_archive_hub=1', 'top');
        
        // Individual CPT routes with singular names
        add_rewrite_rule('^terproduct/([0-9]+)/?$', 'index.php?post_type=terpedia_terproduct&p=$matches[1]', 'top');
        add_rewrite_rule('^terproduct/?$', 'index.php?post_type=terpedia_terproduct', 'top');
        add_rewrite_rule('^podcast/([0-9]+)/?$', 'index.php?post_type=terpedia_podcast&p=$matches[1]', 'top');
        add_rewrite_rule('^podcast/?$', 'index.php?post_type=terpedia_podcast', 'top');
        add_rewrite_rule('^rx/([0-9]+)/?$', 'index.php?post_type=terpedia_rx&p=$matches[1]', 'top');
        add_rewrite_rule('^rx/?$', 'index.php?post_type=terpedia_rx', 'top');
        add_rewrite_rule('^newsletter/([0-9]+)/?$', 'index.php?post_type=terpedia_newsletter&p=$matches[1]', 'top');
        add_rewrite_rule('^newsletter/?$', 'index.php?post_type=terpedia_newsletter', 'top');
        
        // Terport routes (frontend only - no chat)
        add_rewrite_rule('^terport/([0-9]+)/?$', 'index.php?post_type=terpedia_terport&p=$matches[1]', 'top');
        add_rewrite_rule('^terports/?$', 'index.php?post_type=terpedia_terport', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_archive_query_vars($vars) {
        $vars[] = 'cpt_archive_hub';
        $vars[] = 'terproduct_category';
        $vars[] = 'podcast_category';
        $vars[] = 'rx_category';
        return $vars;
    }
    
    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
    
    /**
     * Enqueue archive styles and scripts
     */
    public function enqueue_scripts() {
        if (is_post_type_archive() || get_query_var('cpt_archive_hub')) {
            wp_enqueue_style(
                'terpedia-cpt-archives',
                plugin_dir_url(__FILE__) . '../assets/css/cpt-archives.css',
                array(),
                '3.9.4'
            );
            
            wp_enqueue_script(
                'terpedia-cpt-archives',
                plugin_dir_url(__FILE__) . '../assets/js/cpt-archives.js',
                array('jquery'),
                '3.9.4',
                true
            );
        }
    }
    
    /**
     * Handle archive and single templates
     */
    public function archive_template($template) {
        if (get_query_var('cpt_archive_hub')) {
            return $this->render_cpt_hub_page();
        }
        
        // Handle single posts
        if (is_singular()) {
            $post_type = get_post_type();
            
            switch ($post_type) {
                case 'terpedia_terproduct':
                    return $this->render_single_terproduct();
                case 'terpedia_podcast': 
                    return $this->render_single_podcast();
                case 'terpedia_rx':
                    return $this->render_single_rx();
                case 'terpedia_newsletter':
                    return $this->render_single_newsletter();
            }
        }
        
        // Handle archives
        if (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            
            switch ($post_type) {
                case 'terpedia_terproduct':
                    return $this->render_terproducts_archive();
                case 'terpedia_podcast': 
                    return $this->render_podcasts_archive();
                case 'terpedia_rx':
                    return $this->render_rx_archive();
                case 'terpedia_newsletter':
                    return $this->render_newsletters_archive();
                case 'terpedia_terport':
                    return $this->render_terports_archive();
            }
        }
        
        return $template;
    }
    
    /**
     * Render CPT hub page
     */
    private function render_cpt_hub_page() {
        get_header();
        ?>
        <div class="terpedia-cpt-hub">
            <div class="cpt-hub-header">
                <h1>🧬 Terpedia Content Library</h1>
                <p>Explore our comprehensive collection of terpene research, products, and formulations</p>
            </div>
            
            <div class="cpt-grid">
                <div class="cpt-card">
                    <div class="cpt-icon">📦</div>
                    <h3><a href="<?php echo get_post_type_archive_link('terpedia_terproduct'); ?>">Terproducts</a></h3>
                    <p>AI-analyzed product database with terpene profiles and ingredient insights</p>
                    <div class="cpt-stats">
                        <span><?php echo wp_count_posts('terpedia_terproduct')->publish; ?> products</span>
                    </div>
                </div>
                
                <div class="cpt-card">
                    <div class="cpt-icon">🎙️</div>
                    <h3><a href="<?php echo get_post_type_archive_link('terpedia_podcast'); ?>">Podcasts</a></h3>
                    <p>AI-generated conversations between terpene experts and researchers</p>
                    <div class="cpt-stats">
                        <span><?php echo wp_count_posts('terpedia_podcast')->publish; ?> episodes</span>
                    </div>
                </div>
                
                <div class="cpt-card">
                    <div class="cpt-icon">💊</div>
                    <h3><a href="<?php echo get_post_type_archive_link('terpedia_rx'); ?>">Rx Formulations</a></h3>
                    <p>Precision terpene recipes with calculated ratios and therapeutic profiles</p>
                    <div class="cpt-stats">
                        <span><?php echo wp_count_posts('terpedia_rx')->publish; ?> formulations</span>
                    </div>
                </div>
                
                <div class="cpt-card">
                    <div class="cpt-icon">📰</div>
                    <h3><a href="<?php echo get_post_type_archive_link('terpedia_newsletter'); ?>">Newsletters</a></h3>
                    <p>Automated research summaries and industry insights from PubMed integration</p>
                    <div class="cpt-stats">
                        <span><?php echo wp_count_posts('terpedia_newsletter')->publish; ?> issues</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        get_footer();
        exit;
    }
    
    /**
     * Render terproducts archive
     */
    private function render_terproducts_archive() {
        get_header();
        
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $category = get_query_var('terproduct_category');
        
        $args = array(
            'post_type' => 'terpedia_terproduct',
            'posts_per_page' => 12,
            'paged' => $paged,
            'post_status' => 'publish'
        );
        
        if ($category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'terproduct_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }
        
        $products = new WP_Query($args);
        ?>
        <div class="terpedia-terproducts-archive">
            <div class="archive-header">
                <h1>📦 Terproducts</h1>
                <p>AI-analyzed products with comprehensive terpene profiles</p>
                
                <div class="archive-filters">
                    <?php $this->render_terproduct_filters(); ?>
                </div>
            </div>
            
            <div class="products-grid">
                <?php if ($products->have_posts()): ?>
                    <?php while ($products->have_posts()): $products->the_post(); ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else: ?>
                                    <div class="placeholder-image">📦</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-content">
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <div class="product-meta">
                                    <?php
                                    $brand = get_post_meta(get_the_ID(), '_extracted_brand', true);
                                    $confidence = get_post_meta(get_the_ID(), '_ingredient_confidence', true);
                                    ?>
                                    <?php if ($brand): ?>
                                        <span class="brand">Brand: <?php echo esc_html($brand); ?></span>
                                    <?php endif; ?>
                                    <?php if ($confidence): ?>
                                        <span class="confidence">Analysis: <?php echo esc_html($confidence); ?>%</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <div class="terpenes-preview">
                                    <?php
                                    $terpenes = get_post_meta(get_the_ID(), '_detected_terpenes', true);
                                    if ($terpenes && is_array($terpenes)) {
                                        $displayed = array_slice($terpenes, 0, 3);
                                        foreach ($displayed as $terpene) {
                                            echo '<span class="terpene-tag">' . esc_html($terpene['name']) . '</span>';
                                        }
                                        if (count($terpenes) > 3) {
                                            echo '<span class="more-terpenes">+' . (count($terpenes) - 3) . ' more</span>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-products">No terproducts found. <a href="<?php echo admin_url('post-new.php?post_type=terpedia_terproduct'); ?>">Add the first one!</a></p>
                <?php endif; ?>
            </div>
            
            <?php if ($products->max_num_pages > 1): ?>
                <div class="archive-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $products->max_num_pages,
                        'current' => $paged
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        get_footer();
        exit;
    }
    
    /**
     * Render terproduct filters
     */
    private function render_terproduct_filters() {
        $categories = get_terms(array(
            'taxonomy' => 'terproduct_category',
            'hide_empty' => true
        ));
        
        if (!is_wp_error($categories) && !empty($categories)): ?>
            <div class="filter-section">
                <label>Filter by Category:</label>
                <select id="terproduct-category-filter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->slug); ?>" 
                                <?php selected(get_query_var('terproduct_category'), $category->slug); ?>>
                            <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif;
    }
    
    /**
     * Render podcasts archive  
     */
    private function render_podcasts_archive() {
        get_header();
        
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        $podcasts = new WP_Query(array(
            'post_type' => 'terpedia_podcast',
            'posts_per_page' => 10,
            'paged' => $paged,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        ?>
        <div class="terpedia-podcasts-archive">
            <div class="archive-header">
                <h1>🎙️ Podcasts</h1>
                <p>AI-generated conversations with terpene experts</p>
            </div>
            
            <div class="podcasts-list">
                <?php if ($podcasts->have_posts()): ?>
                    <?php while ($podcasts->have_posts()): $podcasts->the_post(); ?>
                        <div class="podcast-card">
                            <div class="podcast-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                            
                            <div class="podcast-content">
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <div class="podcast-meta">
                                    <span class="date"><?php echo get_the_date(); ?></span>
                                    <?php
                                    $duration = get_post_meta(get_the_ID(), '_episode_duration', true);
                                    if ($duration): ?>
                                        <span class="duration"><?php echo esc_html($duration); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="podcast-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-podcasts">No podcast episodes found.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($podcasts->max_num_pages > 1): ?>
                <div class="archive-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $podcasts->max_num_pages,
                        'current' => $paged
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        get_footer();
        exit;
    }
    
    /**
     * Render Rx archive
     */
    private function render_rx_archive() {
        get_header();
        
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        $rx_formulations = new WP_Query(array(
            'post_type' => 'terpedia_rx',
            'posts_per_page' => 12,
            'paged' => $paged,
            'post_status' => 'publish'
        ));
        ?>
        <div class="terpedia-rx-archive">
            <div class="archive-header">
                <h1>💊 Rx Formulations</h1>
                <p>Precision terpene recipes with calculated therapeutic profiles</p>
            </div>
            
            <div class="rx-grid">
                <?php if ($rx_formulations->have_posts()): ?>
                    <?php while ($rx_formulations->have_posts()): $rx_formulations->the_post(); ?>
                        <div class="rx-card">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <div class="rx-meta">
                                <?php
                                $total_volume = get_post_meta(get_the_ID(), '_total_volume', true);
                                $formulation_type = get_post_meta(get_the_ID(), '_formulation_type', true);
                                ?>
                                <?php if ($total_volume): ?>
                                    <span class="volume">Volume: <?php echo esc_html($total_volume); ?>ml</span>
                                <?php endif; ?>
                                <?php if ($formulation_type): ?>
                                    <span class="type">Type: <?php echo esc_html($formulation_type); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rx-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-rx">No Rx formulations found.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($rx_formulations->max_num_pages > 1): ?>
                <div class="archive-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $rx_formulations->max_num_pages,
                        'current' => $paged
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        get_footer();
        exit;
    }
    
    /**
     * Render newsletters archive
     */
    private function render_newsletters_archive() {
        get_header();
        
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        $newsletters = new WP_Query(array(
            'post_type' => 'terpedia_newsletter',
            'posts_per_page' => 10,
            'paged' => $paged,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        ?>
        <div class="terpedia-newsletters-archive">
            <div class="archive-header">
                <h1>📰 Newsletters</h1>
                <p>Automated research summaries and industry insights</p>
            </div>
            
            <div class="newsletters-list">
                <?php if ($newsletters->have_posts()): ?>
                    <?php while ($newsletters->have_posts()): $newsletters->the_post(); ?>
                        <div class="newsletter-card">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <div class="newsletter-meta">
                                <span class="date"><?php echo get_the_date(); ?></span>
                            </div>
                            <div class="newsletter-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-newsletters">No newsletters found.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($newsletters->max_num_pages > 1): ?>
                <div class="archive-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $newsletters->max_num_pages,
                        'current' => $paged
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        get_footer();
        exit;
    }
    
    /**
     * Render terports archive - Standalone version for non-WordPress environment
     */
    private function render_terports_archive() {
        // Get sample terport data
        $terports = $this->get_sample_terports();
        
        // Output HTML with embedded CSS
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Terports - Terpedia AI Research Reports</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .archive-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            text-align: center;
            padding: 60px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .archive-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M30 30c0-11.046-8.954-20-20-20s-20 8.954-20 20 8.954 20 20 20 20-8.954 20-20z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }
        
        .archive-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .archive-header p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .terports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            padding: 40px;
        }
        
        .terport-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }
        
        .terport-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .terport-image {
            height: 180px;
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .placeholder-image {
            font-size: 4rem;
            color: rgba(255,255,255,0.8);
        }
        
        .terport-content {
            padding: 25px;
        }
        
        .terport-content h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            line-height: 1.3;
        }
        
        .terport-content h3 a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s ease;
        }
        
        .terport-content h3 a:hover {
            color: #3498db;
        }
        
        .terport-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .terport-type {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .terport-excerpt {
            color: #5a6c7d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .terport-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .tag {
            background: #f8f9fa;
            color: #495057;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid #e9ecef;
        }
        
        .more-tags {
            background: #e9ecef;
            color: #6c757d;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .no-terports {
            text-align: center;
            padding: 80px 40px;
        }
        
        .empty-icon {
            font-size: 6rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .no-terports h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .no-terports p {
            font-size: 1.1rem;
            color: #7f8c8d;
            max-width: 500px;
            margin: 0 auto 30px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }
        
        @media (max-width: 768px) {
            .archive-header h1 { font-size: 2.5rem; }
            .archive-header p { font-size: 1.1rem; }
            .terports-grid { grid-template-columns: 1fr; padding: 20px; }
            .terport-card { margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="archive-header">
            <h1>📚 Terports</h1>
            <p>AI-generated reports and analyses on terpene research, compounds, and industry developments</p>
        </div>
        
        <div class="terports-grid">';
        
        if (!empty($terports)) {
            foreach ($terports as $terport) {
                echo '<div class="terport-card">
                    <div class="terport-image">
                        <div class="placeholder-image">📚</div>
                    </div>
                    
                    <div class="terport-content">
                        <h3><a href="/terport/' . $terport['id'] . '">' . htmlspecialchars($terport['title']) . '</a></h3>
                        <div class="terport-meta">
                            <span class="date">' . $terport['date'] . '</span>
                            <span class="terport-type">' . htmlspecialchars($terport['type']) . '</span>
                        </div>
                        
                        <div class="terport-excerpt">
                            ' . htmlspecialchars($terport['excerpt']) . '
                        </div>
                        
                        <div class="terport-tags">';
                
                foreach ($terport['tags'] as $tag) {
                    echo '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                }
                
                echo '</div>
                    </div>
                </div>';
            }
        } else {
            echo '<div class="no-terports">
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3>No Terports Yet</h3>
                    <p>No terport reports have been published yet. The AI system is generating comprehensive veterinary terpene research reports.</p>
                    <a href="/admin" class="cta-button">Generate Terports</a>
                </div>
            </div>';
        }
        
        echo '    </div>
    </div>
</body>
</html>';
        
        exit;
    }
    
    /**
     * Get sample terport data for standalone environment
     */
    private function get_sample_terports() {
        return array(
            array(
                'id' => 1,
                'title' => 'Veterinary Terpene Applications: A Comprehensive Research Analysis',
                'type' => 'Research Report',
                'date' => date('F j, Y'),
                'excerpt' => 'An in-depth analysis of terpene applications in veterinary medicine, covering therapeutic benefits, dosing protocols, and clinical applications for dogs, cats, and horses.',
                'tags' => array('Veterinary', 'Terpenes', 'Research', 'Clinical Studies')
            ),
            array(
                'id' => 2,
                'title' => 'Cannabinoid-Terpene Interactions in Animal Healthcare',
                'type' => 'Clinical Study',
                'date' => date('F j, Y', strtotime('-1 day')),
                'excerpt' => 'Exploring the entourage effect of cannabinoids and terpenes in veterinary applications, with focus on pain management and anxiety reduction in companion animals.',
                'tags' => array('Cannabinoids', 'Entourage Effect', 'Pain Management', 'Anxiety')
            ),
            array(
                'id' => 3,
                'title' => 'Myrcene and Limonene: Therapeutic Potential in Canine Medicine',
                'type' => 'Compound Analysis',
                'date' => date('F j, Y', strtotime('-2 days')),
                'excerpt' => 'Detailed analysis of myrcene and limonene therapeutic applications, including anti-inflammatory properties and stress-reduction benefits for canine patients.',
                'tags' => array('Myrcene', 'Limonene', 'Canine Health', 'Anti-inflammatory')
            ),
            array(
                'id' => 4,
                'title' => 'Terpene Safety Profiles for Veterinary Use',
                'type' => 'Safety Analysis',
                'date' => date('F j, Y', strtotime('-3 days')),
                'excerpt' => 'Comprehensive safety analysis of common terpenes used in veterinary medicine, including toxicity thresholds, contraindications, and best practices.',
                'tags' => array('Safety', 'Toxicology', 'Best Practices', 'Guidelines')
            ),
            array(
                'id' => 5,
                'title' => 'Aromatic Terpenes in Equine Therapy Applications',
                'type' => 'Species-Specific',
                'date' => date('F j, Y', strtotime('-4 days')),
                'excerpt' => 'Specialized report on terpene applications in equine medicine, focusing on respiratory health, muscle recovery, and stress management in horses.',
                'tags' => array('Equine', 'Respiratory Health', 'Muscle Recovery', 'Stress Management')
            ),
            array(
                'id' => 6,
                'title' => 'Beta-Caryophyllene: Anti-Inflammatory Properties in Small Animals',
                'type' => 'Compound Study',
                'date' => date('F j, Y', strtotime('-5 days')),
                'excerpt' => 'In-depth study of beta-caryophyllene anti-inflammatory mechanisms and therapeutic applications in small animal veterinary practice.',
                'tags' => array('Beta-Caryophyllene', 'Anti-inflammatory', 'Small Animals', 'Mechanism of Action')
            )
        );
    }
    
    /**
     * Add CPT navigation items
     */
    public function add_cpt_nav_items($items, $args) {
        // Only add to primary menu
        if ($args->theme_location == 'primary' || $args->menu == 'primary') {
            $cpt_menu = '<li><a href="' . home_url('/cpt-archives/') . '">Content Library</a></li>';
            $items .= $cpt_menu;
        }
        return $items;
    }
    
    /**
     * Add archive navigation footer
     */
    public function add_archive_navigation() {
        // Only show on CPT archive pages or hub
        if (is_post_type_archive() || get_query_var('cpt_archive_hub')) {
            ?>
            <div class="terpedia-archive-nav">
                <div class="nav-links">
                    <a href="<?php echo home_url('/cpt-archives/'); ?>" class="nav-hub">🏠 Content Hub</a>
                    <a href="<?php echo get_post_type_archive_link('terpedia_terproduct'); ?>">📦 Terproducts</a>
                    <a href="<?php echo get_post_type_archive_link('terpedia_podcast'); ?>">🎙️ Podcasts</a>
                    <a href="<?php echo get_post_type_archive_link('terpedia_rx'); ?>">💊 Rx</a>
                    <a href="<?php echo get_post_type_archive_link('terpedia_newsletter'); ?>">📰 Newsletters</a>
                    <a href="<?php echo home_url('/add-terproduct/'); ?>" class="add-product-btn">➕ Add Product</a>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Render single terproduct page
     */
    private function render_single_terproduct() {
        global $post;
        setup_postdata($post);
        
        get_header();
        ?>
        <div class="terpedia-single-terproduct">
            <div class="product-header">
                <div class="breadcrumbs">
                    <a href="/">Home</a> > <a href="/terproduct/">Terproducts</a> > <?php the_title(); ?>
                </div>
                <h1><?php the_title(); ?></h1>
                
                <?php
                $brand = get_post_meta(get_the_ID(), '_extracted_brand', true);
                $confidence = get_post_meta(get_the_ID(), '_ingredient_confidence', true);
                ?>
                
                <div class="product-meta">
                    <?php if ($brand): ?>
                        <span class="brand">Brand: <?php echo esc_html($brand); ?></span>
                    <?php endif; ?>
                    <?php if ($confidence): ?>
                        <span class="confidence">Analysis Confidence: <?php echo esc_html($confidence); ?>%</span>
                    <?php endif; ?>
                    <span class="date">Scanned: <?php echo get_the_date(); ?></span>
                </div>
            </div>
            
            <div class="product-content">
                <div class="product-main">
                    <div class="product-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('large'); ?>
                        <?php else: ?>
                            <div class="placeholder-image-large">📦 No Image</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-details">
                        <h3>🔬 AI Analysis Results</h3>
                        
                        <div class="analysis-section">
                            <h4>Detected Ingredients</h4>
                            <?php
                            $ingredients = get_post_meta(get_the_ID(), '_extracted_ingredients', true);
                            if ($ingredients): ?>
                                <div class="ingredients-list">
                                    <?php echo nl2br(esc_html($ingredients)); ?>
                                </div>
                            <?php else: ?>
                                <p>No ingredients detected</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="terpenes-section">
                            <h4>🌿 Terpene Profile</h4>
                            <?php
                            $terpenes = get_post_meta(get_the_ID(), '_detected_terpenes', true);
                            if ($terpenes && is_array($terpenes)): ?>
                                <div class="terpenes-grid">
                                    <?php foreach ($terpenes as $terpene): ?>
                                        <div class="terpene-item">
                                            <span class="terpene-name"><?php echo esc_html($terpene['name']); ?></span>
                                            <?php if (isset($terpene['percentage'])): ?>
                                                <span class="terpene-percentage"><?php echo esc_html($terpene['percentage']); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>No terpenes detected</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (get_the_content()): ?>
                            <div class="product-description">
                                <h4>Description</h4>
                                <?php the_content(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-actions">
                            <a href="/terproduct/" class="back-to-archive">← Back to All Terproducts</a>
                            <a href="/add-terproduct" class="add-new-product">📷 Scan New Product</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .terpedia-single-terproduct {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .breadcrumbs {
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        .breadcrumbs a {
            color: #0073aa;
            text-decoration: none;
        }
        .product-header h1 {
            margin: 0 0 15px 0;
            font-size: 2.5em;
        }
        .product-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .product-meta span {
            background: #f1f1f1;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .brand {
            background: #e3f2fd !important;
        }
        .confidence {
            background: #e8f5e8 !important;
        }
        .product-main {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .product-image img {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .placeholder-image-large {
            background: #f1f1f1;
            padding: 80px 20px;
            text-align: center;
            border-radius: 8px;
            font-size: 24px;
            color: #999;
        }
        .analysis-section, .terpenes-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .analysis-section h4, .terpenes-section h4 {
            margin-top: 0;
            color: #333;
        }
        .terpenes-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .terpene-item {
            background: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            min-width: 120px;
        }
        .terpene-name {
            font-weight: 600;
        }
        .terpene-percentage {
            color: #666;
            font-size: 12px;
        }
        .product-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .product-actions a {
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .back-to-archive {
            background: #f1f1f1;
            color: #333;
        }
        .add-new-product {
            background: #0073aa;
            color: white;
        }
        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .product-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
        </style>
        <?php
        get_footer();
        exit;
    }
    
    /**
     * Render single podcast page
     */
    private function render_single_podcast() {
        global $post;
        setup_postdata($post);
        
        get_header();
        ?>
        <div class="terpedia-single-podcast">
            <div class="podcast-header">
                <div class="breadcrumbs">
                    <a href="/">Home</a> > <a href="/podcast/">Podcasts</a> > <?php the_title(); ?>
                </div>
                <h1><?php the_title(); ?></h1>
                <div class="podcast-meta">
                    <span class="date">Published: <?php echo get_the_date(); ?></span>
                </div>
            </div>
            
            <div class="podcast-content">
                <?php the_content(); ?>
                
                <div class="podcast-actions">
                    <a href="/podcast/" class="back-to-archive">← Back to All Podcasts</a>
                </div>
            </div>
        </div>
        <?php
        get_footer();
        exit;
    }
    
    /**
     * Render single rx page
     */
    private function render_single_rx() {
        global $post;
        setup_postdata($post);
        
        get_header();
        ?>
        <div class="terpedia-single-rx">
            <div class="rx-header">
                <div class="breadcrumbs">
                    <a href="/">Home</a> > <a href="/rx/">Rx Formulations</a> > <?php the_title(); ?>
                </div>
                <h1><?php the_title(); ?></h1>
                <div class="rx-meta">
                    <span class="date">Created: <?php echo get_the_date(); ?></span>
                </div>
            </div>
            
            <div class="rx-content">
                <?php the_content(); ?>
                
                <div class="rx-actions">
                    <a href="/rx/" class="back-to-archive">← Back to All Rx Formulations</a>
                </div>
            </div>
        </div>
        <?php
        get_footer();
        exit;
    }
    
    /**
     * Render single newsletter page
     */
    private function render_single_newsletter() {
        global $post;
        setup_postdata($post);
        
        get_header();
        ?>
        <div class="terpedia-single-newsletter">
            <div class="newsletter-header">
                <div class="breadcrumbs">
                    <a href="/">Home</a> > <a href="/newsletter/">Newsletters</a> > <?php the_title(); ?>
                </div>
                <h1><?php the_title(); ?></h1>
                <div class="newsletter-meta">
                    <span class="date">Published: <?php echo get_the_date(); ?></span>
                </div>
            </div>
            
            <div class="newsletter-content">
                <?php the_content(); ?>
                
                <div class="newsletter-actions">
                    <a href="/newsletter/" class="back-to-archive">← Back to All Newsletters</a>
                </div>
            </div>
        </div>
        <?php
        get_footer();
        exit;
    }
}

// System will be initialized from terpedia.php main file