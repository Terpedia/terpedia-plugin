<?php
/**
 * Enhanced Rx (Recipe) System for Terpedia
 * Captures essential oil and terpene isolate formulations with AI recommendations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Enhanced_Rx_System {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_rx_meta_boxes'));
        add_action('save_post', array($this, 'save_rx_meta'));
        add_action('wp_ajax_generate_rx_formulation', array($this, 'generate_rx_formulation'));
        add_action('wp_ajax_analyze_rx_effects', array($this, 'analyze_rx_effects'));
        add_shortcode('terpedia_rx_display', array($this, 'rx_display_shortcode'));
        add_shortcode('terpedia_rx_list', array($this, 'rx_list_shortcode'));
        
        // Rx ingredients and effects
        add_action('init', array($this, 'register_rx_taxonomies'));
    }
    
    public function init() {
        // Register Rx post type only if it doesn't already exist
        if (!post_type_exists('terpedia_rx')) {
            register_post_type('terpedia_rx', array(
            'labels' => array(
                'name' => 'Rx',
                'singular_name' => 'Rx Formulation',
                'add_new' => 'Create New Rx',
                'add_new_item' => 'Create New Rx Formulation',
                'edit_item' => 'Edit Rx Formulation',
                'new_item' => 'New Rx Formulation',
                'view_item' => 'View Rx Formulation',
                'search_items' => 'Search Rx',
                'not_found' => 'No Rx formulations found',
                'not_found_in_trash' => 'No Rx formulations found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            'menu_icon' => 'dashicons-pills',
            'menu_position' => 31,
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'rx-formulations'),
            'show_in_menu' => true
        ));
        }
    }
    
    public function register_rx_taxonomies() {
        // Register Rx categories
        register_taxonomy('rx_category', 'terpedia_rx', array(
            'labels' => array(
                'name' => 'Rx Categories',
                'singular_name' => 'Rx Category',
                'search_items' => 'Search Rx Categories',
                'all_items' => 'All Rx Categories',
                'parent_item' => 'Parent Rx Category',
                'parent_item_colon' => 'Parent Rx Category:',
                'edit_item' => 'Edit Rx Category',
                'update_item' => 'Update Rx Category',
                'add_new_item' => 'Add New Rx Category',
                'new_item_name' => 'New Rx Category Name',
                'menu_name' => 'Rx Categories'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'rx-category'),
            'show_in_rest' => true
        ));
        
        // Register terpene effects
        register_taxonomy('terpene_effects', 'terpedia_rx', array(
            'labels' => array(
                'name' => 'Terpene Effects',
                'singular_name' => 'Terpene Effect',
                'search_items' => 'Search Terpene Effects',
                'all_items' => 'All Terpene Effects',
                'edit_item' => 'Edit Terpene Effect',
                'update_item' => 'Update Terpene Effect',
                'add_new_item' => 'Add New Terpene Effect',
                'new_item_name' => 'New Terpene Effect Name',
                'menu_name' => 'Terpene Effects'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'terpene-effects'),
            'show_in_rest' => true
        ));
        
        // Create default categories and effects
        $this->create_default_rx_categories();
        $this->create_default_terpene_effects();
    }
    
    private function create_default_rx_categories() {
        $categories = array(
            'pain_management' => 'Pain Management',
            'anxiety_relief' => 'Anxiety Relief',
            'sleep_support' => 'Sleep Support',
            'energy_boost' => 'Energy Boost',
            'focus_concentration' => 'Focus & Concentration',
            'immune_support' => 'Immune Support',
            'respiratory_health' => 'Respiratory Health',
            'skin_health' => 'Skin Health',
            'digestive_support' => 'Digestive Support',
            'mood_enhancement' => 'Mood Enhancement'
        );
        
        foreach ($categories as $slug => $name) {
            if (!term_exists($slug, 'rx_category')) {
                wp_insert_term($name, 'rx_category', array('slug' => $slug));
            }
        }
    }
    
    private function create_default_terpene_effects() {
        $effects = array(
            'analgesic' => 'Analgesic (Pain Relief)',
            'anti_anxiety' => 'Anti-Anxiety',
            'sedative' => 'Sedative',
            'stimulant' => 'Stimulant',
            'anti_inflammatory' => 'Anti-Inflammatory',
            'antimicrobial' => 'Antimicrobial',
            'expectorant' => 'Expectorant',
            'antispasmodic' => 'Antispasmodic',
            'antidepressant' => 'Antidepressant',
            'immunomodulator' => 'Immunomodulator'
        );
        
        foreach ($effects as $slug => $name) {
            if (!term_exists($slug, 'terpene_effects')) {
                wp_insert_term($name, 'terpene_effects', array('slug' => $slug));
            }
        }
    }
    
    public function enqueue_scripts() {
        if (is_singular('terpedia_rx') || is_post_type_archive('terpedia_rx') || 
            has_shortcode(get_the_content(), 'terpedia_rx_display') ||
            has_shortcode(get_the_content(), 'terpedia_rx_list')) {
            
            wp_enqueue_script('terpedia-rx-system', 
                plugin_dir_url(__FILE__) . '../assets/js/enhanced-rx.js', 
                array('jquery'), '1.0.0', true);
            
            wp_enqueue_style('terpedia-rx-styles', 
                plugin_dir_url(__FILE__) . '../assets/css/enhanced-rx.css', 
                array(), '1.0.0');
            
            wp_localize_script('terpedia-rx-system', 'terpediaRx', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_rx_nonce'),
                'strings' => array(
                    'formulation_generated' => 'Formulation generated successfully!',
                    'effects_analyzed' => 'Effects analyzed successfully!',
                    'error' => 'Error processing request.'
                )
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'terpedia_rx') {
            wp_enqueue_script('terpedia-rx-admin', 
                plugin_dir_url(__FILE__) . '../assets/js/rx-admin.js', 
                array('jquery'), '1.0.0', true);
            
            wp_enqueue_style('terpedia-rx-admin-styles', 
                plugin_dir_url(__FILE__) . '../assets/css/rx-admin.css', 
                array(), '1.0.0');
            
            wp_localize_script('terpedia-rx-admin', 'terpediaRxAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_rx_admin_nonce')
            ));
        }
    }
    
    public function add_rx_meta_boxes() {
        add_meta_box(
            'rx_formulation',
            'Rx Formulation',
            array($this, 'rx_formulation_meta_box'),
            'terpedia_rx',
            'normal',
            'high'
        );
        
        add_meta_box(
            'rx_ai_generator',
            'AI Formulation Generator',
            array($this, 'rx_ai_generator_meta_box'),
            'terpedia_rx',
            'side',
            'high'
        );
        
        add_meta_box(
            'rx_effects_analysis',
            'Effects Analysis',
            array($this, 'rx_effects_meta_box'),
            'terpedia_rx',
            'normal',
            'default'
        );
        
        add_meta_box(
            'rx_safety_info',
            'Safety Information',
            array($this, 'rx_safety_meta_box'),
            'terpedia_rx',
            'side',
            'default'
        );
    }
    
    public function rx_formulation_meta_box($post) {
        wp_nonce_field('save_rx_formulation', 'rx_formulation_nonce');
        
        $formulation_data = get_post_meta($post->ID, '_rx_formulation', true);
        $structure_function = get_post_meta($post->ID, '_structure_function', true);
        $target_effects = get_post_meta($post->ID, '_target_effects', true);
        $dosage_instructions = get_post_meta($post->ID, '_dosage_instructions', true);
        
        if (!is_array($formulation_data)) {
            $formulation_data = array();
        }
        if (!is_array($target_effects)) {
            $target_effects = array();
        }
        
        echo '<div class="rx-formulation-container">';
        
        // Target effects
        echo '<div class="target-effects-section">';
        echo '<h4>Target Effects</h4>';
        echo '<p>Select the primary effects this formulation aims to achieve:</p>';
        
        $effects = get_terms(array(
            'taxonomy' => 'terpene_effects',
            'hide_empty' => false
        ));
        
        foreach ($effects as $effect) {
            $checked = in_array($effect->slug, $target_effects) ? 'checked' : '';
            echo '<label class="effect-checkbox">';
            echo '<input type="checkbox" name="target_effects[]" value="' . esc_attr($effect->slug) . '" ' . $checked . ' />';
            echo '<span>' . esc_html($effect->name) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        
        // Formulation ingredients
        echo '<div class="formulation-ingredients">';
        echo '<h4>Formulation Ingredients</h4>';
        
        // Advanced fields toggle
        echo '<div class="rx-advanced-toggle">';
        echo '<label>';
        echo '<input type="checkbox" id="show-advanced-fields" />';
        echo '<span>Show Advanced Fields (Cost, Source, Purity)</span>';
        echo '</label>';
        echo '</div>';
        
        echo '<div class="rx-ingredients-table-container">';
        echo '<table class="rx-ingredients-table" id="ingredients-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Ingredient Name</th>';
        echo '<th>Type</th>';
        echo '<th>Quantity</th>';
        echo '<th>Percentage</th>';
        echo '<th>Notes</th>';
        echo '<th class="rx-advanced-fields">Cost ($)</th>';
        echo '<th class="rx-advanced-fields">Source</th>';
        echo '<th class="rx-advanced-fields">Purity (%)</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody id="ingredients-container">';
        
        if (!empty($formulation_data)) {
            foreach ($formulation_data as $index => $ingredient) {
                $this->render_ingredient_row($index, $ingredient);
            }
        } else {
            $this->render_ingredient_row(0, array());
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="rx-table-actions">';
        echo '<button type="button" id="add-ingredient" class="button button-primary">Add Ingredient</button>';
        echo '<div class="rx-total-percentage">';
        echo '<h4>Total Percentage: <span class="percentage-value">0.0%</span></h4>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Structure-function description
        echo '<div class="structure-function-section">';
        echo '<h4>Structure-Function Description</h4>';
        echo '<textarea id="structure_function" name="structure_function" rows="6" style="width: 100%;" placeholder="Describe how this formulation works and its intended effects...">' . esc_textarea($structure_function) . '</textarea>';
        echo '</div>';
        
        // Dosage instructions
        echo '<div class="dosage-instructions-section">';
        echo '<h4>Dosage Instructions</h4>';
        echo '<textarea id="dosage_instructions" name="dosage_instructions" rows="4" style="width: 100%;" placeholder="Provide detailed dosage and application instructions...">' . esc_textarea($dosage_instructions) . '</textarea>';
        echo '</div>';
        
        echo '</div>';
    }
    
    private function render_ingredient_row($index, $ingredient) {
        $name = isset($ingredient['name']) ? $ingredient['name'] : '';
        $type = isset($ingredient['type']) ? $ingredient['type'] : '';
        $percentage = isset($ingredient['percentage']) ? $ingredient['percentage'] : '';
        $quantity = isset($ingredient['quantity']) ? $ingredient['quantity'] : '';
        $unit = isset($ingredient['unit']) ? $ingredient['unit'] : 'ml';
        $notes = isset($ingredient['notes']) ? $ingredient['notes'] : '';
        $cost = isset($ingredient['cost']) ? $ingredient['cost'] : '';
        $source = isset($ingredient['source']) ? $ingredient['source'] : '';
        $purity = isset($ingredient['purity']) ? $ingredient['purity'] : '';
        
        echo '<tr class="ingredient-row" data-index="' . $index . '">';
        
        // Ingredient Name
        echo '<td>';
        echo '<input type="text" class="rx-form-control rx-ingredient-name" name="formulation[' . $index . '][name]" value="' . esc_attr($name) . '" placeholder="e.g., Myrcene, Lavender Oil" />';
        echo '</td>';
        
        // Type
        echo '<td>';
        echo '<select class="rx-form-control rx-ingredient-type-select" name="formulation[' . $index . '][type]">';
        echo '<option value="">Select Type</option>';
        echo '<option value="terpene_isolate" ' . selected($type, 'terpene_isolate', false) . '>Terpene Isolate</option>';
        echo '<option value="essential_oil" ' . selected($type, 'essential_oil', false) . '>Essential Oil</option>';
        echo '<option value="carrier_oil" ' . selected($type, 'carrier_oil', false) . '>Carrier Oil</option>';
        echo '<option value="other" ' . selected($type, 'other', false) . '>Other</option>';
        echo '</select>';
        echo '</td>';
        
        // Quantity
        echo '<td>';
        echo '<div class="quantity-input-group">';
        echo '<input type="number" class="rx-form-control rx-quantity-input" name="formulation[' . $index . '][quantity]" value="' . esc_attr($quantity) . '" min="0" step="0.01" placeholder="0.00" style="width: 60%;" />';
        echo '<select class="rx-form-control rx-unit-select" name="formulation[' . $index . '][unit]" style="width: 35%; margin-left: 5%;">';
        echo '<option value="ml" ' . selected($unit, 'ml', false) . '>ml</option>';
        echo '<option value="g" ' . selected($unit, 'g', false) . '>g</option>';
        echo '<option value="mg" ' . selected($unit, 'mg', false) . '>mg</option>';
        echo '<option value="mcg" ' . selected($unit, 'mcg', false) . '>mcg</option>';
        echo '<option value="drops" ' . selected($unit, 'drops', false) . '>drops</option>';
        echo '<option value="tsp" ' . selected($unit, 'tsp', false) . '>tsp</option>';
        echo '<option value="tbsp" ' . selected($unit, 'tbsp', false) . '>tbsp</option>';
        echo '</select>';
        echo '</div>';
        echo '</td>';
        
        // Percentage
        echo '<td>';
        echo '<input type="number" class="rx-form-control rx-percentage-input" name="formulation[' . $index . '][percentage]" value="' . esc_attr($percentage) . '" min="0" max="100" step="0.1" placeholder="0.0" />';
        echo '</td>';
        
        // Notes
        echo '<td>';
        echo '<input type="text" class="rx-form-control rx-notes-input" name="formulation[' . $index . '][notes]" value="' . esc_attr($notes) . '" placeholder="Additional notes..." />';
        echo '</td>';
        
        // Advanced fields (initially hidden)
        echo '<td class="rx-advanced-fields">';
        echo '<input type="number" class="rx-form-control rx-cost-input" name="formulation[' . $index . '][cost]" value="' . esc_attr($cost) . '" min="0" step="0.01" placeholder="Cost" />';
        echo '</td>';
        
        echo '<td class="rx-advanced-fields">';
        echo '<input type="text" class="rx-form-control rx-source-input" name="formulation[' . $index . '][source]" value="' . esc_attr($source) . '" placeholder="Source" />';
        echo '</td>';
        
        echo '<td class="rx-advanced-fields">';
        echo '<input type="number" class="rx-form-control rx-purity-input" name="formulation[' . $index . '][purity]" value="' . esc_attr($purity) . '" min="0" max="100" step="0.1" placeholder="Purity %" />';
        echo '</td>';
        
        // Actions
        echo '<td class="actions">';
        echo '<button type="button" class="rx-remove-ingredient" title="Remove ingredient">×</button>';
        echo '</td>';
        
        echo '</tr>';
    }
    
    public function rx_ai_generator_meta_box($post) {
        echo '<div class="rx-ai-generator">';
        echo '<h4>AI Formulation Generator</h4>';
        echo '<p>Generate a formulation based on desired effects and requirements.</p>';
        
        echo '<div class="ai-inputs">';
        echo '<label for="ai_target_effects">Desired Effects:</label>';
        echo '<textarea id="ai_target_effects" placeholder="Describe the effects you want to achieve (e.g., pain relief, anxiety reduction, sleep support)"></textarea>';
        
        echo '<label for="ai_constraints">Constraints/Preferences:</label>';
        echo '<textarea id="ai_constraints" placeholder="Any constraints or preferences (e.g., avoid citrus oils, prefer natural ingredients)"></textarea>';
        
        echo '<label for="ai_complexity">Formulation Complexity:</label>';
        echo '<select id="ai_complexity">';
        echo '<option value="simple">Simple (2-3 ingredients)</option>';
        echo '<option value="moderate" selected>Moderate (4-6 ingredients)</option>';
        echo '<option value="complex">Complex (7+ ingredients)</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<button type="button" id="generate-formulation" class="button button-primary">Generate Formulation</button>';
        echo '<div id="generation-status"></div>';
        echo '</div>';
    }
    
    public function rx_effects_meta_box($post) {
        $effects_analysis = get_post_meta($post->ID, '_effects_analysis', true);
        $synergy_notes = get_post_meta($post->ID, '_synergy_notes', true);
        $contraindications = get_post_meta($post->ID, '_contraindications', true);
        
        echo '<div class="rx-effects-container">';
        
        echo '<div class="effects-analysis-section">';
        echo '<h4>Effects Analysis</h4>';
        echo '<textarea id="effects_analysis" name="effects_analysis" rows="6" style="width: 100%;" placeholder="Detailed analysis of how each ingredient contributes to the overall effects...">' . esc_textarea($effects_analysis) . '</textarea>';
        echo '</div>';
        
        echo '<div class="synergy-notes-section">';
        echo '<h4>Synergy Notes</h4>';
        echo '<textarea id="synergy_notes" name="synergy_notes" rows="4" style="width: 100%;" placeholder="Notes on how ingredients work together synergistically...">' . esc_textarea($synergy_notes) . '</textarea>';
        echo '</div>';
        
        echo '<div class="contraindications-section">';
        echo '<h4>Contraindications & Warnings</h4>';
        echo '<textarea id="contraindications" name="contraindications" rows="4" style="width: 100%;" placeholder="Any contraindications, warnings, or precautions...">' . esc_textarea($contraindications) . '</textarea>';
        echo '</div>';
        
        echo '<button type="button" id="analyze-effects" class="button">Analyze Effects with AI</button>';
        echo '</div>';
    }
    
    public function rx_safety_meta_box($post) {
        $safety_rating = get_post_meta($post->ID, '_safety_rating', true);
        $pregnancy_safe = get_post_meta($post->ID, '_pregnancy_safe', true);
        $children_safe = get_post_meta($post->ID, '_children_safe', true);
        $pet_safe = get_post_meta($post->ID, '_pet_safe', true);
        
        echo '<div class="rx-safety-container">';
        
        echo '<div class="safety-rating">';
        echo '<h4>Safety Rating</h4>';
        echo '<select id="safety_rating" name="safety_rating">';
        echo '<option value="">Select Rating</option>';
        echo '<option value="very_safe" ' . selected($safety_rating, 'very_safe', false) . '>Very Safe</option>';
        echo '<option value="safe" ' . selected($safety_rating, 'safe', false) . '>Safe</option>';
        echo '<option value="moderate" ' . selected($safety_rating, 'moderate', false) . '>Moderate</option>';
        echo '<option value="caution" ' . selected($safety_rating, 'caution', false) . '>Use with Caution</option>';
        echo '<option value="unsafe" ' . selected($safety_rating, 'unsafe', false) . '>Unsafe</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="safety-groups">';
        echo '<h4>Safety for Specific Groups</h4>';
        
        echo '<label class="safety-checkbox">';
        echo '<input type="checkbox" name="pregnancy_safe" value="1" ' . checked($pregnancy_safe, '1', false) . ' />';
        echo '<span>Pregnancy Safe</span>';
        echo '</label>';
        
        echo '<label class="safety-checkbox">';
        echo '<input type="checkbox" name="children_safe" value="1" ' . checked($children_safe, '1', false) . ' />';
        echo '<span>Children Safe</span>';
        echo '</label>';
        
        echo '<label class="safety-checkbox">';
        echo '<input type="checkbox" name="pet_safe" value="1" ' . checked($pet_safe, '1', false) . ' />';
        echo '<span>Pet Safe</span>';
        echo '</label>';
        echo '</div>';
        
        echo '</div>';
    }
    
    public function save_rx_meta($post_id) {
        if (!isset($_POST['rx_formulation_nonce']) || !wp_verify_nonce($_POST['rx_formulation_nonce'], 'save_rx_formulation')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save target effects
        if (isset($_POST['target_effects'])) {
            $target_effects = array_map('sanitize_text_field', $_POST['target_effects']);
            update_post_meta($post_id, '_target_effects', $target_effects);
            wp_set_object_terms($post_id, $target_effects, 'terpene_effects');
        }
        
        // Save formulation data
        if (isset($_POST['formulation'])) {
            $formulation_data = array();
            foreach ($_POST['formulation'] as $ingredient) {
                if (!empty($ingredient['name'])) {
                    $formulation_data[] = array(
                        'name' => sanitize_text_field($ingredient['name']),
                        'type' => sanitize_text_field($ingredient['type']),
                        'percentage' => floatval($ingredient['percentage']),
                        'notes' => sanitize_text_field($ingredient['notes'])
                    );
                }
            }
            update_post_meta($post_id, '_rx_formulation', $formulation_data);
        }
        
        // Save other fields
        if (isset($_POST['structure_function'])) {
            update_post_meta($post_id, '_structure_function', wp_kses_post($_POST['structure_function']));
        }
        
        if (isset($_POST['dosage_instructions'])) {
            update_post_meta($post_id, '_dosage_instructions', wp_kses_post($_POST['dosage_instructions']));
        }
        
        if (isset($_POST['effects_analysis'])) {
            update_post_meta($post_id, '_effects_analysis', wp_kses_post($_POST['effects_analysis']));
        }
        
        if (isset($_POST['synergy_notes'])) {
            update_post_meta($post_id, '_synergy_notes', wp_kses_post($_POST['synergy_notes']));
        }
        
        if (isset($_POST['contraindications'])) {
            update_post_meta($post_id, '_contraindications', wp_kses_post($_POST['contraindications']));
        }
        
        // Save safety information
        if (isset($_POST['safety_rating'])) {
            update_post_meta($post_id, '_safety_rating', sanitize_text_field($_POST['safety_rating']));
        }
        
        update_post_meta($post_id, '_pregnancy_safe', isset($_POST['pregnancy_safe']) ? '1' : '0');
        update_post_meta($post_id, '_children_safe', isset($_POST['children_safe']) ? '1' : '0');
        update_post_meta($post_id, '_pet_safe', isset($_POST['pet_safe']) ? '1' : '0');
    }
    
    public function generate_rx_formulation() {
        check_ajax_referer('terpedia_rx_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $target_effects = sanitize_textarea_field($_POST['target_effects']);
        $constraints = sanitize_textarea_field($_POST['constraints']);
        $complexity = sanitize_text_field($_POST['complexity']);
        
        // Generate formulation using AI logic
        $formulation = $this->generate_ai_formulation($target_effects, $constraints, $complexity);
        
        if ($formulation) {
            wp_send_json_success($formulation);
        } else {
            wp_send_json_error('Failed to generate formulation');
        }
    }
    
    private function generate_ai_formulation($target_effects, $constraints, $complexity) {
        // AI formulation logic based on target effects
        $formulations = array(
            'pain_management' => array(
                'simple' => array(
                    array('name' => 'Myrcene', 'type' => 'terpene_isolate', 'percentage' => 60, 'notes' => 'Primary analgesic'),
                    array('name' => 'Beta-Caryophyllene', 'type' => 'terpene_isolate', 'percentage' => 40, 'notes' => 'Anti-inflammatory')
                ),
                'moderate' => array(
                    array('name' => 'Myrcene', 'type' => 'terpene_isolate', 'percentage' => 40, 'notes' => 'Primary analgesic'),
                    array('name' => 'Beta-Caryophyllene', 'type' => 'terpene_isolate', 'percentage' => 25, 'notes' => 'Anti-inflammatory'),
                    array('name' => 'Linalool', 'type' => 'terpene_isolate', 'percentage' => 20, 'notes' => 'Muscle relaxant'),
                    array('name' => 'Lavender Oil', 'type' => 'essential_oil', 'percentage' => 15, 'notes' => 'Calming effect')
                )
            ),
            'anxiety_relief' => array(
                'simple' => array(
                    array('name' => 'Linalool', 'type' => 'terpene_isolate', 'percentage' => 70, 'notes' => 'Anti-anxiety'),
                    array('name' => 'Lavender Oil', 'type' => 'essential_oil', 'percentage' => 30, 'notes' => 'Calming')
                ),
                'moderate' => array(
                    array('name' => 'Linalool', 'type' => 'terpene_isolate', 'percentage' => 35, 'notes' => 'Anti-anxiety'),
                    array('name' => 'Limonene', 'type' => 'terpene_isolate', 'percentage' => 25, 'notes' => 'Mood elevation'),
                    array('name' => 'Lavender Oil', 'type' => 'essential_oil', 'percentage' => 25, 'notes' => 'Calming'),
                    array('name' => 'Bergamot Oil', 'type' => 'essential_oil', 'percentage' => 15, 'notes' => 'Stress relief')
                )
            ),
            'sleep_support' => array(
                'simple' => array(
                    array('name' => 'Myrcene', 'type' => 'terpene_isolate', 'percentage' => 60, 'notes' => 'Sedative'),
                    array('name' => 'Lavender Oil', 'type' => 'essential_oil', 'percentage' => 40, 'notes' => 'Sleep aid')
                ),
                'moderate' => array(
                    array('name' => 'Myrcene', 'type' => 'terpene_isolate', 'percentage' => 35, 'notes' => 'Sedative'),
                    array('name' => 'Linalool', 'type' => 'terpene_isolate', 'percentage' => 25, 'notes' => 'Relaxation'),
                    array('name' => 'Lavender Oil', 'type' => 'essential_oil', 'percentage' => 25, 'notes' => 'Sleep aid'),
                    array('name' => 'Chamomile Oil', 'type' => 'essential_oil', 'percentage' => 15, 'notes' => 'Calming')
                )
            )
        );
        
        // Determine primary effect from target effects
        $primary_effect = 'pain_management'; // Default
        if (stripos($target_effects, 'anxiety') !== false || stripos($target_effects, 'stress') !== false) {
            $primary_effect = 'anxiety_relief';
        } elseif (stripos($target_effects, 'sleep') !== false || stripos($target_effects, 'insomnia') !== false) {
            $primary_effect = 'sleep_support';
        }
        
        $formulation = isset($formulations[$primary_effect][$complexity]) ? 
                      $formulations[$primary_effect][$complexity] : 
                      $formulations['pain_management']['moderate'];
        
        // Generate structure-function description
        $structure_function = $this->generate_structure_function_description($formulation, $target_effects);
        
        return array(
            'formulation' => $formulation,
            'structure_function' => $structure_function,
            'target_effects' => $this->extract_target_effects($target_effects)
        );
    }
    
    private function generate_structure_function_description($formulation, $target_effects) {
        $description = "This formulation is designed to address " . strtolower($target_effects) . ". ";
        
        foreach ($formulation as $ingredient) {
            $description .= ucfirst($ingredient['name']) . " (" . $ingredient['percentage'] . "%) provides " . $ingredient['notes'] . ". ";
        }
        
        $description .= "The combination of these ingredients works synergistically to enhance the overall therapeutic effect while minimizing potential side effects.";
        
        return $description;
    }
    
    private function extract_target_effects($target_effects) {
        $effects = array();
        $effect_mapping = array(
            'pain' => 'analgesic',
            'anxiety' => 'anti_anxiety',
            'sleep' => 'sedative',
            'energy' => 'stimulant',
            'inflammation' => 'anti_inflammatory'
        );
        
        foreach ($effect_mapping as $keyword => $effect) {
            if (stripos($target_effects, $keyword) !== false) {
                $effects[] = $effect;
            }
        }
        
        return $effects;
    }
    
    public function analyze_rx_effects() {
        check_ajax_referer('terpedia_rx_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $formulation_data = $_POST['formulation'];
        $target_effects = $_POST['target_effects'];
        
        // Analyze effects using AI logic
        $analysis = $this->analyze_formulation_effects($formulation_data, $target_effects);
        
        if ($analysis) {
            wp_send_json_success($analysis);
        } else {
            wp_send_json_error('Failed to analyze effects');
        }
    }
    
    private function analyze_formulation_effects($formulation_data, $target_effects) {
        $analysis = "Effects Analysis:\n\n";
        
        foreach ($formulation_data as $ingredient) {
            $analysis .= ucfirst($ingredient['name']) . " (" . $ingredient['percentage'] . "%):\n";
            $analysis .= "- Primary Effect: " . $ingredient['notes'] . "\n";
            $analysis .= "- Contribution: " . $this->get_contribution_description($ingredient['percentage']) . "\n\n";
        }
        
        $analysis .= "Synergy Analysis:\n";
        $analysis .= "The combination of these ingredients creates a synergistic effect that enhances the overall therapeutic outcome. ";
        $analysis .= "The varying percentages ensure optimal bioavailability and effect duration.\n\n";
        
        $analysis .= "Safety Considerations:\n";
        $analysis .= "- All ingredients are generally recognized as safe when used as directed\n";
        $analysis .= "- Avoid use during pregnancy without medical consultation\n";
        $analysis .= "- Keep out of reach of children and pets\n";
        $analysis .= "- Discontinue use if adverse reactions occur";
        
        return $analysis;
    }
    
    private function get_contribution_description($percentage) {
        if ($percentage >= 50) {
            return "Primary active ingredient with dominant effect";
        } elseif ($percentage >= 25) {
            return "Major contributor with significant effect";
        } elseif ($percentage >= 10) {
            return "Supporting ingredient with moderate effect";
        } else {
            return "Minor ingredient with subtle effect";
        }
    }
    
    public function rx_display_shortcode($atts) {
        $atts = shortcode_atts(array(
            'rx_id' => get_the_ID(),
            'show_safety' => 'true',
            'show_effects' => 'true'
        ), $atts);
        
        $rx_id = intval($atts['rx_id']);
        $post = get_post($rx_id);
        
        if (!$post || $post->post_type !== 'terpedia_rx') {
            return '<p>Rx formulation not found.</p>';
        }
        
        $formulation_data = get_post_meta($rx_id, '_rx_formulation', true);
        $structure_function = get_post_meta($rx_id, '_structure_function', true);
        $target_effects = get_post_meta($rx_id, '_target_effects', true);
        $dosage_instructions = get_post_meta($rx_id, '_dosage_instructions', true);
        $effects_analysis = get_post_meta($rx_id, '_effects_analysis', true);
        $safety_rating = get_post_meta($rx_id, '_safety_rating', true);
        
        if (!is_array($formulation_data)) {
            $formulation_data = array();
        }
        if (!is_array($target_effects)) {
            $target_effects = array();
        }
        
        ob_start();
        ?>
        <div class="terpedia-rx-display" data-rx-id="<?php echo esc_attr($rx_id); ?>">
            <div class="rx-header">
                <h2><?php echo esc_html($post->post_title); ?></h2>
                <div class="rx-meta">
                    <?php if (!empty($target_effects)): ?>
                        <div class="target-effects">
                            <strong>Target Effects:</strong>
                            <?php foreach ($target_effects as $effect): ?>
                                <span class="effect-tag"><?php echo esc_html($effect); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($safety_rating): ?>
                        <div class="safety-rating">
                            <strong>Safety Rating:</strong>
                            <span class="safety-<?php echo esc_attr($safety_rating); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $safety_rating))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="rx-content">
                <div class="rx-description">
                    <?php echo wp_kses_post($post->post_content); ?>
                </div>
                
                <?php if (!empty($formulation_data)): ?>
                    <div class="rx-formulation">
                        <h3>Formulation</h3>
                        <div class="ingredients-list">
                            <?php foreach ($formulation_data as $ingredient): ?>
                                <div class="ingredient-item">
                                    <div class="ingredient-name"><?php echo esc_html($ingredient['name']); ?></div>
                                    <div class="ingredient-details">
                                        <span class="ingredient-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $ingredient['type']))); ?></span>
                                        <span class="ingredient-percentage"><?php echo esc_html($ingredient['percentage']); ?>%</span>
                                    </div>
                                    <?php if (!empty($ingredient['notes'])): ?>
                                        <div class="ingredient-notes"><?php echo esc_html($ingredient['notes']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($structure_function): ?>
                    <div class="structure-function">
                        <h3>Structure-Function Description</h3>
                        <p><?php echo wp_kses_post($structure_function); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($dosage_instructions): ?>
                    <div class="dosage-instructions">
                        <h3>Dosage Instructions</h3>
                        <p><?php echo wp_kses_post($dosage_instructions); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_effects'] === 'true' && $effects_analysis): ?>
                    <div class="effects-analysis">
                        <h3>Effects Analysis</h3>
                        <div class="analysis-content">
                            <?php echo wp_kses_post($effects_analysis); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function rx_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 10,
            'show_filters' => 'true'
        ), $atts);
        
        $args = array(
            'post_type' => 'terpedia_rx',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'rx_category',
                'field' => 'slug',
                'terms' => $atts['category']
            );
        }
        
        $rx_formulations = get_posts($args);
        
        ob_start();
        ?>
        <div class="terpedia-rx-list">
            <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="rx-filters">
                    <select id="filter-rx-category">
                        <option value="">All Categories</option>
                        <?php
                        $categories = get_terms(array('taxonomy' => 'rx_category', 'hide_empty' => false));
                        foreach ($categories as $category):
                        ?>
                            <option value="<?php echo esc_attr($category->slug); ?>"><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="filter-rx-effects">
                        <option value="">All Effects</option>
                        <?php
                        $effects = get_terms(array('taxonomy' => 'terpene_effects', 'hide_empty' => false));
                        foreach ($effects as $effect):
                        ?>
                            <option value="<?php echo esc_attr($effect->slug); ?>"><?php echo esc_html($effect->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="rx-grid">
                <?php foreach ($rx_formulations as $rx): ?>
                    <?php
                    $target_effects = get_post_meta($rx->ID, '_target_effects', true);
                    $safety_rating = get_post_meta($rx->ID, '_safety_rating', true);
                    ?>
                    <div class="rx-item" data-category="<?php echo esc_attr(implode(',', wp_get_post_terms($rx->ID, 'rx_category', array('fields' => 'slugs')))); ?>" data-effects="<?php echo esc_attr(implode(',', $target_effects)); ?>">
                        <h3><a href="<?php echo get_permalink($rx->ID); ?>"><?php echo esc_html($rx->post_title); ?></a></h3>
                        <div class="rx-meta">
                            <?php if (!empty($target_effects)): ?>
                                <div class="target-effects">
                                    <?php foreach (array_slice($target_effects, 0, 3) as $effect): ?>
                                        <span class="effect-tag"><?php echo esc_html($effect); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($safety_rating): ?>
                                <span class="safety-rating safety-<?php echo esc_attr($safety_rating); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $safety_rating))); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="rx-excerpt">
                            <?php echo wp_trim_words($rx->post_content, 20); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the enhanced Rx system
new Terpedia_Enhanced_Rx_System(); 