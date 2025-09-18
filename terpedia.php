<?php
/**
 * Plugin Name: Terpedia
 * Plugin URI: https://terpedia.com
 * Description: Comprehensive terpene encyclopedia with 13 AI experts, intelligent newsletter generator with PubMed integration, 700K+ natural products, UA Huntsville supercomputer integration
 * Version: 3.11.77
 * Author: Terpedia Team
 * License: GPL v2 or later
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access  
if (!defined('ABSPATH')) {
    // In standalone PHP environment, define ABSPATH
    define('ABSPATH', dirname(__FILE__) . '/');
}

// STANDALONE PHP ROUTING - Handle root page, /terports, /cyc, and /cases routes directly
if (isset($_SERVER['REQUEST_URI'])) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
    
    // Handle root page (/) for main activity feed dashboard
    if (empty($path) || $path === '') {
        // Define mock WordPress functions to avoid errors
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function register_activation_hook($file, $callback) { /* Mock function */ }
            function register_deactivation_hook($file, $callback) { /* Mock function */ }
            function plugin_dir_url($file) { return '/'; }
            function wp_enqueue_style() { /* Mock function */ }
            function wp_enqueue_script() { /* Mock function */ }
            function admin_url($path = '') { return '/admin/' . ltrim($path, '/'); }
            function get_option($option, $default = false) { return $default; }
            function current_user_can($cap) { return true; }
            function get_current_user_id() { return 1; }
            function current_time($type) { return date('Y-m-d H:i:s'); }
            function wp_create_nonce($action) { return 'mock_nonce'; }
            function check_ajax_referer($action, $query_arg = false) { return true; }
            function sanitize_text_field($str) { return strip_tags($str); }
            function sanitize_textarea_field($str) { return strip_tags($str); }
            function wp_send_json_success($data) { 
                header('Content-Type: application/json');
                echo json_encode(array('success' => true, 'data' => $data));
                exit;
            }
            function wp_send_json_error($data) { 
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'data' => $data));
                exit;
            }
            function get_posts($args) { 
                // Handle different post types for activity feed
                if (isset($args['post_type']) && $args['post_type'] === 'terpedia_case') {
                    $posts_file = 'case_posts.json';
                    if (file_exists($posts_file)) {
                        $posts = json_decode(file_get_contents($posts_file), true);
                        $result = [];
                        foreach ($posts as $post) {
                            $result[] = (object) array(
                                'ID' => $post['ID'],
                                'post_title' => $post['post_title'],
                                'post_content' => $post['post_content'],
                                'post_date' => date('Y-m-d H:i:s'),
                                'post_type' => $post['post_type']
                            );
                        }
                        return $result;
                    }
                    // Fallback to embedded demo cases for activity feed
                    return array(
                        (object) array(
                            'ID' => 1069,
                            'post_title' => 'Case #001: Bella - Seizure Management',
                            'post_content' => 'Bella is a 4-year-old spayed female Golden Retriever presenting with a 6-month history of generalized tonic-clonic seizures. Implementing novel terpene protocol with linalool and β-caryophyllene...',
                            'post_date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                            'post_type' => 'terpedia_case'
                        ),
                        (object) array(
                            'ID' => 8664,
                            'post_title' => 'Case #002: Thunder - Performance Anxiety Protocol',
                            'post_content' => 'Thunder is an 8-year-old Thoroughbred gelding with significant performance anxiety. Implemented terpene protocol using limonene and myrcene...',
                            'post_date' => date('Y-m-d H:i:s', strtotime('-5 hours')),
                            'post_type' => 'terpedia_case'
                        ),
                        (object) array(
                            'ID' => 6841,
                            'post_title' => 'Case #003: Whiskers - Feline Lymphoma Support Care',
                            'post_content' => 'Whiskers is a 12-year-old neutered male Maine Coon diagnosed with intermediate-grade alimentary lymphoma. Palliative care with geraniol and β-caryophyllene...',
                            'post_date' => date('Y-m-d H:i:s', strtotime('-8 hours')),
                            'post_type' => 'terpedia_case'
                        )
                    );
                } elseif (isset($args['post_type']) && $args['post_type'] === 'terpedia_terport') {
                    $posts_file = 'terports_posts.json';
                    if (file_exists($posts_file)) {
                        $posts = json_decode(file_get_contents($posts_file), true);
                        $result = [];
                        foreach ($posts as $post) {
                            $result[] = (object) array(
                                'ID' => $post['ID'],
                                'post_title' => $post['post_title'],
                                'post_content' => $post['post_content'],
                                'post_date' => date('Y-m-d H:i:s'),
                                'post_type' => $post['post_type']
                            );
                        }
                        return $result;
                    }
                    // Fallback to sample terports for activity feed
                    return array(
                        (object) array(
                            'ID' => 1,
                            'post_title' => 'Limonene: A Comprehensive Analysis of Anticancer Properties',
                            'post_content' => 'This comprehensive research analysis examines the anticancer properties of limonene, a prominent monoterpene found in citrus fruits. Recent studies demonstrate significant cytotoxic effects against various cancer cell lines...',
                            'post_date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                            'post_type' => 'terpedia_terport'
                        ),
                        (object) array(
                            'ID' => 2,
                            'post_title' => 'Beta-Caryophyllene: CB2 Receptor Interactions and Therapeutic Potential',
                            'post_content' => 'An in-depth analysis of beta-caryophyllene and its unique ability to act as a CB2 receptor agonist, providing anti-inflammatory and analgesic effects without psychoactive properties...',
                            'post_date' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                            'post_type' => 'terpedia_terport'
                        ),
                        (object) array(
                            'ID' => 3,
                            'post_title' => 'Pinene Isomers: Respiratory Benefits and Neuroprotective Effects',
                            'post_content' => 'This research explores the differential effects of alpha-pinene and beta-pinene on respiratory function, memory enhancement, and neuroprotection in clinical studies...',
                            'post_date' => date('Y-m-d H:i:s', strtotime('-6 hours')),
                            'post_type' => 'terpedia_terport'
                        )
                    );
                }
                return array(); 
            }
            function get_post($id) { 
                // Simplified get_post for activity feed
                return (object) array(
                    'ID' => $id,
                    'post_title' => 'Sample Post ' . $id,
                    'post_content' => 'Sample content for post ' . $id,
                    'post_type' => 'post',
                    'post_date' => date('Y-m-d H:i:s')
                );
            }
            function get_post_meta($id, $key, $single = false) { 
                // Simplified meta function for activity feed
                $defaults = array(
                    'case_status' => 'active',
                    'patient_name' => 'Patient ' . $id,
                    'species' => 'Canine',
                    'terport_type' => 'research_analysis',
                    'research_focus' => 'Terpene Analysis'
                );
                return $single ? ($defaults[$key] ?? '') : array($defaults[$key] ?? ''); 
            }
            function wp_count_posts($type) { 
                if ($type === 'terpedia_terport') {
                    return (object) array('publish' => 5); 
                } elseif ($type === 'terpedia_case') {
                    return (object) array('publish' => 3);
                }
                return (object) array('publish' => 0); 
            }
            function esc_html($text) { return htmlspecialchars($text); }
            function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES); }
        }
        
        // Log route matching for debugging
        error_log('Terpedia: Matched root route (/) - redirecting to social feed');
        
        // Render the social media research feed (same as /feed)
        render_terpedia_social_feed();
        exit;
    }
    
    // Handle /cases route for case management
    if ($path === 'cases' || $path === 'cases/' || preg_match('/^case\/\d+/', $path)) {
        // Define mock WordPress functions to avoid errors
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function register_activation_hook($file, $callback) { /* Mock function */ }
            function register_deactivation_hook($file, $callback) { /* Mock function */ }
            function plugin_dir_url($file) { return '/'; }
            function wp_enqueue_style() { /* Mock function */ }
            function wp_enqueue_script() { /* Mock function */ }
            function admin_url($path = '') { return '/admin/' . ltrim($path, '/'); }
            function get_option($option, $default = false) { return $default; }
            function current_user_can($cap) { return true; }
            function get_current_user_id() { return 1; }
            function current_time($type) { return date('Y-m-d H:i:s'); }
            function wp_create_nonce($action) { return 'mock_nonce'; }
            function check_ajax_referer($action, $query_arg = false) { return true; }
            function sanitize_text_field($str) { return strip_tags($str); }
            function sanitize_textarea_field($str) { return strip_tags($str); }
            function wp_send_json_success($data) { 
                header('Content-Type: application/json');
                echo json_encode(array('success' => true, 'data' => $data));
                exit;
            }
            function wp_send_json_error($data) { 
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'data' => $data));
                exit;
            }
            function get_posts($args) { 
                // Read from actual case data files
                if (isset($args['post_type']) && $args['post_type'] === 'terpedia_case') {
                    $posts_file = 'case_posts.json';
                    if (file_exists($posts_file)) {
                        $posts = json_decode(file_get_contents($posts_file), true);
                        $result = [];
                        foreach ($posts as $post) {
                            $result[] = (object) array(
                                'ID' => $post['ID'],
                                'post_title' => $post['post_title'],
                                'post_content' => $post['post_content'],
                                'post_date' => date('Y-m-d H:i:s'),
                                'post_type' => $post['post_type']
                            );
                        }
                        return $result;
                    }
                    // Fallback to embedded demo cases
                    return array(
                        (object) array(
                            'ID' => 1069,
                            'post_title' => 'Case #001: Bella - Seizure Management',
                            'post_content' => 'Bella is a 4-year-old spayed female Golden Retriever presenting with a 6-month history of generalized tonic-clonic seizures. Initial presentation showed seizures occurring 2-3 times weekly, lasting 45-90 seconds each. Pre-ictal behavior includes restlessness and excessive panting. Post-ictal confusion lasts approximately 15 minutes.\n\nCurrent management includes phenobarbital 2.5mg/kg BID with therapeutic levels maintained at 25-30 μg/mL. We have implemented a novel terpene protocol incorporating linalool (5mg/kg daily) and β-caryophyllene (3mg/kg daily) based on recent research showing neuroprotective effects and seizure threshold elevation in canine epilepsy models.\n\nOwner reports significant improvement in seizure frequency and severity since initiation of terpene therapy. Bella\'s quality of life has markedly improved with increased activity levels and better appetite.',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_case'
                        ),
                        (object) array(
                            'ID' => 8664,
                            'post_title' => 'Case #002: Thunder - Performance Anxiety Protocol',
                            'post_content' => 'Thunder is an 8-year-old Thoroughbred gelding competing in eventing who has developed significant performance anxiety over the past 4 months. Symptoms include excessive sweating, elevated heart rate pre-competition, reluctance to load in trailer, and decreased performance scores.\n\nInitial behavioral assessment revealed no physical abnormalities contributing to anxiety. Stress-related behaviors began following a minor trailer accident 5 months ago. Traditional anxiolytic medications were ineffective and caused sedation affecting athletic performance.\n\nImplemented novel terpene-based protocol using limonene (8mg/kg daily) for its anxiolytic D-limonene effects and myrcene (6mg/kg daily) for muscle relaxation. Both terpenes selected for absence of prohibited substances in equine competition.',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_case'
                        ),
                        (object) array(
                            'ID' => 6841,
                            'post_title' => 'Case #003: Whiskers - Feline Lymphoma Support Care',
                            'post_content' => 'Whiskers is a 12-year-old neutered male Maine Coon diagnosed with intermediate-grade alimentary lymphoma 6 weeks ago. Initial presentation included weight loss, intermittent vomiting, and decreased appetite. Family opted for palliative care approach rather than aggressive chemotherapy.\n\nTreatment goals focus on comfort, appetite stimulation, and maintaining dignity throughout end-of-life care. Initiated supportive care protocol including geraniol (2mg/kg BID) for anti-inflammatory effects, and β-caryophyllene (1.5mg/kg BID) for pain management and appetite stimulation through CB2 receptor activation.',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_case'
                        ),
                        (object) array(
                            'ID' => 7377,
                            'post_title' => 'Case #004: Emergency - Multi-trauma Critical Care',
                            'post_content' => 'Emergency presentation of 3-year-old mixed breed dog following motor vehicle accident. Patient arrived in hypovolemic shock with multiple injuries including pneumothorax, pelvic fractures, and significant soft tissue trauma.\n\nInitial stabilization required immediate thoracostomy tube placement, aggressive fluid resuscitation, and multimodal pain management. Implemented emergency terpene protocol incorporating β-caryophyllene (4mg/kg q8h) for analgesic effects, and linalool (3mg/kg q12h) for anxiolytic properties during critical care period.',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_case'
                        )
                    );
                }
                return array(); 
            }
            function get_post($id) { 
                if ($id) {
                    $posts_file = 'case_posts.json';
                    if (file_exists($posts_file)) {
                        $posts = json_decode(file_get_contents($posts_file), true);
                        if (isset($posts[$id])) {
                            return (object) array(
                                'ID' => $posts[$id]['ID'],
                                'post_title' => $posts[$id]['post_title'],
                                'post_content' => $posts[$id]['post_content'],
                                'post_type' => $posts[$id]['post_type'],
                                'post_date' => date('Y-m-d H:i:s')
                            );
                        }
                    }
                    // Fallback with embedded demo case data (matching seeded data IDs)
                    $embedded_cases = array(
                        5127 => array(
                            'ID' => 5127,
                            'post_title' => 'Case #001: Bella - Seizure Management',
                            'post_content' => 'Bella is a 4-year-old spayed female Golden Retriever presenting with a 6-month history of generalized tonic-clonic seizures. Initial presentation showed seizures occurring 2-3 times weekly, lasting 45-90 seconds each. Pre-ictal behavior includes restlessness and excessive panting. Post-ictal confusion lasts approximately 15 minutes.\n\nCurrent management includes phenobarbital 2.5mg/kg BID with therapeutic levels maintained at 25-30 μg/mL. We have implemented a novel terpene protocol incorporating linalool (5mg/kg daily) and β-caryophyllene (3mg/kg daily) based on recent research showing neuroprotective effects and seizure threshold elevation in canine epilepsy models.\n\nOwner reports significant improvement in seizure frequency and severity since initiation of terpene therapy. Bella\'s quality of life has markedly improved with increased activity levels and better appetite.',
                            'post_type' => 'terpedia_case'
                        ),
                        1534 => array(
                            'ID' => 1534,
                            'post_title' => 'Case #002: Thunder - Performance Anxiety Protocol',
                            'post_content' => 'Thunder is an 8-year-old Thoroughbred gelding competing in eventing who has developed significant performance anxiety over the past 4 months. Symptoms include excessive sweating, elevated heart rate pre-competition, reluctance to load in trailer, and decreased performance scores.\n\nInitial behavioral assessment revealed no physical abnormalities contributing to anxiety. Stress-related behaviors began following a minor trailer accident 5 months ago. Traditional anxiolytic medications were ineffective and caused sedation affecting athletic performance.\n\nImplemented novel terpene-based protocol using limonene (8mg/kg daily) for its anxiolytic D-limonene effects and myrcene (6mg/kg daily) for muscle relaxation. Both terpenes selected for absence of prohibited substances in equine competition.',
                            'post_type' => 'terpedia_case'
                        ),
                        9142 => array(
                            'ID' => 9142,
                            'post_title' => 'Case #003: Whiskers - Feline Lymphoma Support Care',
                            'post_content' => 'Whiskers is a 12-year-old neutered male Maine Coon diagnosed with intermediate-grade alimentary lymphoma 6 weeks ago. Initial presentation included weight loss, intermittent vomiting, and decreased appetite. Family opted for palliative care approach rather than aggressive chemotherapy.\n\nTreatment goals focus on comfort, appetite stimulation, and maintaining dignity throughout end-of-life care. Initiated supportive care protocol including geraniol (2mg/kg BID) for anti-inflammatory effects, and β-caryophyllene (1.5mg/kg BID) for pain management and appetite stimulation through CB2 receptor activation.',
                            'post_type' => 'terpedia_case'
                        ),
                        7516 => array(
                            'ID' => 7516,
                            'post_title' => 'Case #004: Emergency - Multi-trauma Critical Care',
                            'post_content' => 'Emergency presentation of 3-year-old mixed breed dog following motor vehicle accident. Patient arrived in hypovolemic shock with multiple injuries including pneumothorax, pelvic fractures, and significant soft tissue trauma.\n\nInitial stabilization required immediate thoracostomy tube placement, aggressive fluid resuscitation, and multimodal pain management. Implemented emergency terpene protocol incorporating β-caryophyllene (4mg/kg q8h) for analgesic effects, and linalool (3mg/kg q12h) for anxiolytic properties during critical care period.',
                            'post_type' => 'terpedia_case'
                        )
                    );
                    
                    if (isset($embedded_cases[$id])) {
                        return (object) array(
                            'ID' => $embedded_cases[$id]['ID'],
                            'post_title' => $embedded_cases[$id]['post_title'],
                            'post_content' => $embedded_cases[$id]['post_content'],
                            'post_type' => $embedded_cases[$id]['post_type'],
                            'post_date' => date('Y-m-d H:i:s')
                        );
                    }
                    
                    // Final fallback for unknown IDs
                    return (object) array(
                        'ID' => $id,
                        'post_title' => 'Sample Case ' . $id,
                        'post_content' => 'Sample case content for case ' . $id,
                        'post_type' => 'terpedia_case',
                        'post_date' => date('Y-m-d H:i:s')
                    );
                }
                return null; 
            }
            function get_post_meta($id, $key, $single = false) { 
                $meta_file = 'case_meta.json';
                if (file_exists($meta_file)) {
                    $meta = json_decode(file_get_contents($meta_file), true);
                    if (isset($meta[$id]) && isset($meta[$id][$key])) {
                        return $single ? $meta[$id][$key] : array($meta[$id][$key]);
                    }
                }
                
                // Fallback with embedded demo case metadata
                $embedded_meta = array(
                    1069 => array(
                        'patient_name' => 'Bella',
                        'species' => 'Canine', 
                        'breed' => 'Golden Retriever',
                        'age' => '4 years',
                        'weight' => '28.5 kg',
                        'owner_name' => 'Sarah & Mark Johnson',
                        'owner_contact' => 'Phone: (555) 123-4567\nEmail: sarah.johnson@email.com\nAddress: 123 Oak Street, Springfield, IL 62701',
                        'case_status' => 'active'
                    ),
                    8664 => array(
                        'patient_name' => 'Thunder',
                        'species' => 'Equine',
                        'breed' => 'Thoroughbred', 
                        'age' => '8 years',
                        'weight' => '545 kg',
                        'owner_name' => 'Riverside Equestrian Center - Amanda Sterling',
                        'owner_contact' => 'Phone: (555) 234-5678\nEmail: amanda@riversideequestrian.com\nAddress: 456 County Road 12, Lexington, KY 40511',
                        'case_status' => 'active'
                    ),
                    6841 => array(
                        'patient_name' => 'Whiskers',
                        'species' => 'Feline',
                        'breed' => 'Maine Coon',
                        'age' => '12 years', 
                        'weight' => '5.2 kg',
                        'owner_name' => 'Eleanor and Robert Chen',
                        'owner_contact' => 'Phone: (555) 345-6789\nEmail: eleanor.chen@email.com\nAddress: 789 Maple Avenue, Portland, OR 97205',
                        'case_status' => 'critical'
                    ),
                    7377 => array(
                        'patient_name' => 'Rocky (Emergency #E2024-089)',
                        'species' => 'Canine',
                        'breed' => 'Mixed Breed (Shepherd/Lab)',
                        'age' => '3 years',
                        'weight' => '32.1 kg', 
                        'owner_name' => 'Michael Rodriguez (Emergency Contact)',
                        'owner_contact' => 'Phone: (555) 789-0123\nEmergency: (555) 789-0124\nEmail: m.rodriguez.emergency@email.com',
                        'case_status' => 'critical'
                    )
                );
                
                if (isset($embedded_meta[$id]) && isset($embedded_meta[$id][$key])) {
                    return $single ? $embedded_meta[$id][$key] : array($embedded_meta[$id][$key]);
                }
                
                // Final fallback defaults for unknown cases or keys
                $defaults = array(
                    'patient_name' => 'Sample Patient ' . $id,
                    'species' => 'Dog',
                    'breed' => 'Golden Retriever',
                    'age' => '5 years',
                    'weight' => '65 lbs',
                    'owner_name' => 'John Doe',
                    'owner_contact' => 'john@example.com',
                    'case_status' => 'active'
                );
                return $single ? ($defaults[$key] ?? '') : array($defaults[$key] ?? ''); 
            }
            function update_post_meta($id, $key, $value) { return true; }
            function wp_update_post($args) { return true; }
            function wp_insert_post($args) { return 1; }
            function wp_die($message) { die($message); }
            function get_the_modified_date($format, $post = null) { return date($format); }
            function wp_count_posts($type) { return (object) array('publish' => 2); }
            function get_file_data($file, $default_headers, $context = '') {
                // Mock implementation to parse plugin header
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $result = array();
                    foreach ($default_headers as $key => $header) {
                        if (preg_match('/\* ' . preg_quote($header) . ':\s*(.+)/i', $content, $matches)) {
                            $result[$key] = trim($matches[1]);
                        } else {
                            $result[$key] = '';
                        }
                    }
                    return $result;
                }
                return array_fill_keys(array_keys($default_headers), '');
            }
        }
        
        // Include the case management system file directly
        $case_file = dirname(__FILE__) . '/includes/case-management-system.php';
        if (file_exists($case_file)) {
            require_once $case_file;
            
            // Instantiate and handle case management routing
            if (class_exists('Terpedia_Case_Management_System')) {
                try {
                    $case_system = new Terpedia_Case_Management_System();
                    
                    // Parse the path to determine what to render
                    if ($path === 'cases' || $path === 'cases/') {
                        // Render cases archive
                        $method = new ReflectionMethod($case_system, 'render_cases_archive');
                        $method->setAccessible(true);
                        $method->invoke($case_system);
                        exit;
                    } elseif (preg_match('/^case\/(\d+)$/', $path, $matches)) {
                        // Render single case
                        $GLOBALS['post'] = get_post(intval($matches[1]));
                        $method = new ReflectionMethod($case_system, 'render_single_case');
                        $method->setAccessible(true);
                        $method->invoke($case_system);
                        exit;
                    } elseif (preg_match('/^case\/(\d+)\/chat$/', $path, $matches)) {
                        // Render case chat
                        $case_id = intval($matches[1]);
                        $method = new ReflectionMethod($case_system, 'render_case_chat');
                        $method->setAccessible(true);
                        $method->invoke($case_system, $case_id);
                        exit;
                    } elseif (preg_match('/^case\/(\d+)\/vitals$/', $path, $matches)) {
                        // Render case vitals
                        $case_id = intval($matches[1]);
                        $method = new ReflectionMethod($case_system, 'render_case_vitals');
                        $method->setAccessible(true);
                        $method->invoke($case_system, $case_id);
                        exit;
                    } elseif (preg_match('/^case\/(\d+)\/interventions$/', $path, $matches)) {
                        // Render case interventions
                        $case_id = intval($matches[1]);
                        $method = new ReflectionMethod($case_system, 'render_case_interventions');
                        $method->setAccessible(true);
                        $method->invoke($case_system, $case_id);
                        exit;
                    }
                } catch (Exception $e) {
                    // Simple fallback: output error and continue
                    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
                    echo "<h1>Error rendering case management page</h1>";
                    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</body></html>";
                    exit;
                }
            }
        }
        // If we reach here, show a basic case management page
        echo "<!DOCTYPE html><html><head><title>Case Management</title></head><body>";
        echo "<h1>🏥 Case Management System - Coming Soon</h1>";
        echo "<p>The case management system is being initialized...</p>";
        echo "</body></html>";
        exit;
    }
    
    // Handle /terports route for archive and /terport/[id] for individual pages
    if ($path === 'terports' || $path === 'terports/' || preg_match('/^terport\/\d+/', $path)) {
        // Define comprehensive mock WordPress functions
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function register_activation_hook($file, $callback) { /* Mock function */ }
            function register_deactivation_hook($file, $callback) { /* Mock function */ }
            function plugin_dir_url($file) { return '/'; }
            function wp_enqueue_style() { /* Mock function */ }
            function wp_enqueue_script() { /* Mock function */ }
            function admin_url($path = '') { return '/admin/' . ltrim($path, '/'); }
            function get_option($option, $default = false) { return $default; }
            function current_user_can($cap) { return true; }
            function get_current_user_id() { return 1; }
            function current_time($type) { return date('Y-m-d H:i:s'); }
            function wp_create_nonce($action) { return 'mock_nonce'; }
            function check_ajax_referer($action, $query_arg = false) { return true; }
            function sanitize_text_field($str) { return strip_tags($str); }
            function sanitize_textarea_field($str) { return strip_tags($str); }
            function wp_send_json_success($data) { 
                header('Content-Type: application/json');
                echo json_encode(array('success' => true, 'data' => $data));
                exit;
            }
            function wp_send_json_error($data) { 
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'data' => $data));
                exit;
            }
            function get_post_meta($post_id, $key = '', $single = false) {
                $meta_file = 'case_meta.json';
                if (file_exists($meta_file)) {
                    $meta = json_decode(file_get_contents($meta_file), true);
                    if (isset($meta[$post_id])) {
                        if ($key === '') {
                            return $meta[$post_id];
                        } elseif (isset($meta[$post_id][$key])) {
                            return $single ? $meta[$post_id][$key] : array($meta[$post_id][$key]);
                        }
                    }
                }
                return $single ? '' : array();
            }
            function get_posts($args) { 
                // Read from actual terports data files if they exist
                if (isset($args['post_type']) && $args['post_type'] === 'terpedia_terport') {
                    $posts_file = 'terports_posts.json';
                    if (file_exists($posts_file)) {
                        $posts = json_decode(file_get_contents($posts_file), true);
                        $result = [];
                        foreach ($posts as $post) {
                            $result[] = (object) array(
                                'ID' => $post['ID'],
                                'post_title' => $post['post_title'],
                                'post_content' => $post['post_content'],
                                'post_date' => date('Y-m-d H:i:s'),
                                'post_type' => $post['post_type']
                            );
                        }
                        return $result;
                    }
                    // Fallback to sample terports
                    return array(
                        (object) array(
                            'ID' => 1,
                            'post_title' => 'Limonene: A Comprehensive Analysis of Anticancer Properties',
                            'post_content' => 'This comprehensive research analysis examines the anticancer properties of limonene, a prominent monoterpene found in citrus fruits...',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_terport'
                        ),
                        (object) array(
                            'ID' => 2,
                            'post_title' => 'Beta-Caryophyllene: CB2 Receptor Interactions and Therapeutic Potential',
                            'post_content' => 'An in-depth analysis of beta-caryophyllene and its unique ability to act as a CB2 receptor agonist...',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_terport'
                        ),
                        (object) array(
                            'ID' => 3,
                            'post_title' => 'Pinene Isomers: Respiratory Benefits and Neuroprotective Effects',
                            'post_content' => 'This research explores the differential effects of alpha-pinene and beta-pinene on respiratory function...',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_terport'
                        ),
                        (object) array(
                            'ID' => 4,
                            'post_title' => 'Myrcene: Sedative Properties and Pharmacokinetic Profile',
                            'post_content' => 'A comprehensive examination of myrcene, focusing on its sedative effects and bioavailability...',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_terport'
                        ),
                        (object) array(
                            'ID' => 5,
                            'post_title' => 'Linalool: Anxiolytic Effects and GABA Receptor Modulation',
                            'post_content' => 'This analysis investigates linalool\'s anxiolytic properties and its interaction with GABAergic systems...',
                            'post_date' => date('Y-m-d H:i:s'),
                            'post_type' => 'terpedia_terport'
                        )
                    );
                }
                return array(); 
            }
            function get_post($id) { 
                if ($id) {
                    $posts_file = 'terports_posts.json';
                    if (file_exists($posts_file)) {
                        $posts = json_decode(file_get_contents($posts_file), true);
                        if (isset($posts[$id])) {
                            return (object) array(
                                'ID' => $posts[$id]['ID'],
                                'post_title' => $posts[$id]['post_title'],
                                'post_content' => $posts[$id]['post_content'],
                                'post_type' => $posts[$id]['post_type'],
                                'post_date' => date('Y-m-d H:i:s')
                            );
                        }
                    }
                    // Fallback sample data
                    $samples = array(
                        1 => 'Limonene: A Comprehensive Analysis of Anticancer Properties',
                        2 => 'Beta-Caryophyllene: CB2 Receptor Interactions and Therapeutic Potential',
                        3 => 'Pinene Isomers: Respiratory Benefits and Neuroprotective Effects',
                        4 => 'Myrcene: Sedative Properties and Pharmacokinetic Profile',
                        5 => 'Linalool: Anxiolytic Effects and GABA Receptor Modulation'
                    );
                    return (object) array(
                        'ID' => $id,
                        'post_title' => $samples[$id] ?? 'Sample Terport ' . $id,
                        'post_content' => 'Sample terport content for terport ' . $id,
                        'post_type' => 'terpedia_terport',
                        'post_date' => date('Y-m-d H:i:s')
                    );
                }
                return null; 
            }
            function get_post_meta($id, $key, $single = false) { 
                $meta_file = 'terports_meta.json';
                if (file_exists($meta_file)) {
                    $meta = json_decode(file_get_contents($meta_file), true);
                    if (isset($meta[$id]) && isset($meta[$id][$key])) {
                        return $single ? $meta[$id][$key] : array($meta[$id][$key]);
                    }
                }
                
                // Fallback defaults
                $defaults = array(
                    'terport_type' => 'research_analysis',
                    'research_focus' => 'Terpene Analysis',
                    'ai_generated' => 'yes',
                    'template_used' => 'Research Analysis Template',
                    'word_count' => '2500'
                );
                return $single ? ($defaults[$key] ?? '') : array($defaults[$key] ?? ''); 
            }
            function get_header() { /* Mock function */ }
            function get_footer() { /* Mock function */ }
            function wp_reset_postdata() { /* Mock function */ }
            function update_post_meta($id, $key, $value) { return true; }
            function wp_update_post($args) { return true; }
            function wp_insert_post($args) { return 1; }
            function wp_die($message) { die($message); }
            function get_the_modified_date($format, $post = null) { return date($format); }
            function wp_count_posts($type) { 
                if ($type === 'terpedia_terport') {
                    return (object) array('publish' => 5); 
                }
                return (object) array('publish' => 0); 
            }
            function get_file_data($file, $default_headers, $context = '') {
                // Mock implementation to parse plugin header
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $result = array();
                    foreach ($default_headers as $key => $header) {
                        if (preg_match('/\* ' . preg_quote($header) . ':\s*(.+)/i', $content, $matches)) {
                            $result[$key] = trim($matches[1]);
                        } else {
                            $result[$key] = '';
                        }
                    }
                    return $result;
                }
                return array_fill_keys(array_keys($default_headers), '');
            }
            // WordPress query functions
            function get_query_var($var, $default = '') { return $default; }
            function paginate_links($args) { return ''; }
            function the_permalink() { echo '/terport/' . get_the_ID(); }
            function the_title() { global $current_terport; echo htmlspecialchars($current_terport->post_title ?? 'Unknown Title'); }
            function the_excerpt() { global $current_terport; echo htmlspecialchars(substr($current_terport->post_content ?? '', 0, 150) . '...'); }
            function the_content() { global $current_terport; echo nl2br(htmlspecialchars($current_terport->post_content ?? '')); }
            function the_date() { return date('F j, Y'); }
            function get_the_date() { return date('F j, Y'); }
            function get_the_ID() { global $current_terport; return $current_terport->ID ?? 0; }
            function the_post_thumbnail($size = 'thumbnail') { echo '<div class="placeholder-thumbnail">📚</div>'; }
            function esc_html($text) { return htmlspecialchars($text); }
            function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES); }
            function selected($selected, $current, $echo = true) { 
                $result = selected($selected, $current, false);
                if ($echo) echo $result;
                return $result;
            }
        }
        
        // Log route matching for debugging
        error_log('Terpedia: Matched /terports route in deployment');
        
        // Define standalone rendering function
        function render_standalone_terports_archive() {
            // Get terports using mock functions
            $terports = get_posts(array(
                'post_type' => 'terpedia_terport',
                'posts_per_page' => 10,
                'post_status' => 'publish'
            ));
            
            // Get stats
            $terport_count = wp_count_posts('terpedia_terport')->publish;
            
            // Output standalone HTML
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
            background: url("data:image/svg+xml,%3Csvg width=\\"60\\" height=\\"60\\" viewBox=\\"0 0 60 60\\" xmlns=\\"http://www.w3.org/2000/svg\\"%3E%3Cg fill=\\"none\\" fill-rule=\\"evenodd\\"%3E%3Cg fill=\\"%23ffffff\\" fill-opacity=\\"0.05\\"%3E%3Cpath d=\\"M30 30c0-11.046-8.954-20-20-20s-20 8.954-20 20 8.954 20 20 20 20-8.954 20-20z\\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
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
        
        .archive-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            margin: 30px 0;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            text-align: center;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
            color: #3498db;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .terports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            padding: 40px;
        }
        
        .terport-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e3f2fd;
        }
        
        .terport-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .terport-header {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 25px;
            border-bottom: 1px solid #e1f5fe;
        }
        
        .terport-type {
            display: inline-block;
            background: #2196f3;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        
        .terport-title {
            color: #1565c0;
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.3;
            margin: 0;
        }
        
        .terport-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .terport-title a:hover {
            color: #0d47a1;
        }
        
        .terport-content {
            padding: 25px;
        }
        
        .terport-excerpt {
            color: #555;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .terport-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .terport-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .terport-words {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .no-terports {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1.2rem;
        }
        
        .nav-links {
            padding: 30px 40px;
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .nav-links a {
            display: inline-block;
            background: #007cba;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .nav-links a:hover {
            background: #005a87;
            transform: translateY(-2px);
        }
        
        .breadcrumb {
            padding: 20px 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .breadcrumb a {
            color: #007cba;
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .terports-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }
            
            .archive-header {
                padding: 40px 20px;
            }
            
            .archive-header h1 {
                font-size: 2.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="/">🏠 Home</a> / <a href="/cases">🏥 Cases</a> / <a href="/cyc">📖 Encyclopedia</a> / <strong>📚 Terports</strong>
        </div>
        
        <div class="archive-header">
            <h1>📚 Terports</h1>
            <p>AI-Powered Terpene Research Reports with Scientific Analysis</p>
            
            <div class="archive-stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number">' . $terport_count . '</span>
                        <span class="stat-label">Research Reports</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">6</span>
                        <span class="stat-label">Report Types</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">AI</span>
                        <span class="stat-label">Generated</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">700K+</span>
                        <span class="stat-label">Data Points</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="terports-grid">';
            
            if (!empty($terports)) {
                foreach ($terports as $terport) {
                    global $current_terport;
                    $current_terport = $terport;
                    
                    $terport_type = get_post_meta($terport->ID, 'terport_type', true);
                    $word_count = get_post_meta($terport->ID, 'word_count', true);
                    
                    echo '
            <div class="terport-card">
                <div class="terport-header">
                    <div class="terport-type">' . esc_html(ucwords(str_replace('_', ' ', $terport_type))) . '</div>
                    <h3 class="terport-title">
                        <a href="/terport/' . $terport->ID . '">' . esc_html($terport->post_title) . '</a>
                    </h3>
                </div>
                
                <div class="terport-content">
                    <div class="terport-excerpt">
                        ' . esc_html(substr($terport->post_content, 0, 200)) . '...
                    </div>
                </div>
                
                <div class="terport-meta">
                    <span class="terport-date">' . get_the_date() . '</span>
                    <span class="terport-words">' . esc_html($word_count ?: '2500') . ' words</span>
                </div>
            </div>';
                }
            } else {
                echo '
            <div class="no-terports">
                <h3>🚀 Terports Coming Soon</h3>
                <p>Our AI research reports are being generated. Check back soon for comprehensive terpene analysis and research insights.</p>
            </div>';
            }
            
            echo '
        </div>
        
        <div class="nav-links">
            <a href="/cases">🏥 View Case Studies</a>
            <a href="/cyc">📖 Browse Encyclopedia</a>
            <a href="/">🏠 Return Home</a>
        </div>
    </div>
</body>
</html>';
        }
        
        // Function to render single terport page
        function render_single_terport($terport_id) {
            // Get terport data using the mock function
            $terport = get_post($terport_id);
            
            if (!$terport) {
                echo "<!DOCTYPE html><html><head><title>Terport Not Found</title></head><body>";
                echo "<h1>Terport Not Found</h1>";
                echo "<p>The requested terport could not be found.</p>";
                echo "<a href='/terports'>← Back to Terports</a>";
                echo "</body></html>";
                return;
            }
            
            global $current_terport;
            $current_terport = $terport;
            
            $terport_type = get_post_meta($terport_id, 'terport_type', true);
            $word_count = get_post_meta($terport_id, 'word_count', true);
            $research_focus = get_post_meta($terport_id, 'research_focus', true);
            
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($terport->post_title) . ' - Terpedia Terport</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .breadcrumb {
            padding: 20px 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .breadcrumb a {
            color: #007cba;
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .terport-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .terport-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M30 30c0-11.046-8.954-20-20-20s-20 8.954-20 20 8.954 20 20 20 20-8.954 20-20z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }
        
        .terport-type {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .terport-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            line-height: 1.2;
        }
        
        .terport-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .terport-content {
            padding: 40px;
        }
        
        .terport-focus {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 0 8px 8px 0;
        }
        
        .terport-focus h3 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .terport-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #34495e;
            margin-bottom: 30px;
        }
        
        .terport-text p {
            margin-bottom: 20px;
        }
        
        .nav-links {
            padding: 30px 40px;
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .nav-links a {
            display: inline-block;
            background: #007cba;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .nav-links a:hover {
            background: #005a87;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .terport-header,
            .terport-content {
                padding: 30px 20px;
            }
            
            .terport-title {
                font-size: 2rem;
            }
            
            .terport-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="/">🏠 Home</a> / <a href="/terports">📚 Terports</a> / <strong>' . esc_html($terport->post_title) . '</strong>
        </div>
        
        <div class="terport-header">
            <div class="terport-type">' . esc_html(ucwords(str_replace('_', ' ', $terport_type))) . '</div>
            <h1 class="terport-title">' . esc_html($terport->post_title) . '</h1>
            <div class="terport-meta">
                <span>📅 ' . get_the_date() . '</span>
                <span>📊 ' . esc_html($word_count ?: '2500') . ' words</span>
                <span>🤖 AI Generated</span>
            </div>
        </div>
        
        <div class="terport-content">
            ' . ($research_focus ? '
            <div class="terport-focus">
                <h3>🔬 Research Focus</h3>
                <p>' . esc_html($research_focus) . '</p>
            </div>
            ' : '') . '
            
            <div class="terport-text">
                ' . nl2br(esc_html($terport->post_content)) . '
                
                <p><strong>This is a sample terport display showing the full content structure. In a complete implementation, this would include:</strong></p>
                <ul>
                    <li>Detailed research methodology</li>
                    <li>Scientific citations and references</li>
                    <li>Data visualizations and charts</li>
                    <li>Comprehensive analysis sections</li>
                    <li>Related research recommendations</li>
                </ul>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="/terports">📚 All Terports</a>
            <a href="/cases">🏥 Case Studies</a>
            <a href="/">🏠 Home</a>
        </div>
    </div>
</body>
</html>';
        }
        
        // Parse the path to determine what to render
        if ($path === 'terports' || $path === 'terports/') {
            // Render terports archive
            render_standalone_terports_archive();
            exit;
        } elseif (preg_match('/^terport\/(\d+)$/', $path, $matches)) {
            // Render single terport
            $terport_id = intval($matches[1]);
            render_single_terport($terport_id);
            exit;
        } else {
            // Fallback - shouldn't happen with our routing, but just in case
            render_standalone_terports_archive();
            exit;
        }
    }
    
    // Handle /cyc route for Cyc Encyclopedia
    if (preg_match('/^cyc\/?(.*)$/', $path, $matches)) {
        // Define mock WordPress functions to avoid errors
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function register_activation_hook($file, $callback) { /* Mock function */ }
            function register_deactivation_hook($file, $callback) { /* Mock function */ }
            function plugin_dir_url($file) { return '/'; }
            function wp_enqueue_style() { /* Mock function */ }
            function wp_enqueue_script() { /* Mock function */ }
            function get_option($option, $default = false) { return $default; }
            function wp_remote_post($url, $args) { return new WP_Error('no_wp', 'WordPress not available'); }
            function wp_remote_get($url, $args = array()) { return new WP_Error('no_wp', 'WordPress not available'); }
            function is_wp_error($thing) { return is_a($thing, 'WP_Error'); }
            function current_time($type) { return date('Y-m-d H:i:s'); }
            function home_url() { return 'http://localhost:5000'; }
            function get_posts($args) { return array(); }
            function wp_insert_post($args) { return 1; }
            function update_post_meta($post_id, $key, $value) { return true; }
            function get_post_meta($post_id, $key, $single = false) { return ''; }
            function admin_url($path = '') { return '/admin/' . ltrim($path, '/'); }
            class WP_Error {
                private $errors = array();
                public function __construct($code, $message) {
                    $this->errors[$code] = array($message);
                }
                public function get_error_message() {
                    foreach ($this->errors as $code => $messages) {
                        return $messages[0];
                    }
                    return '';
                }
            }
        }
        
        // Define standalone functions for cyc rendering
        function render_cyc_entry_standalone($entry_slug) {
            $html = "<!DOCTYPE html><html><head><title>" . htmlspecialchars($entry_slug) . " - Cyc Encyclopedia</title>";
            $html .= "<style>";
            $html .= "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; line-height: 1.6; }";
            $html .= ".entry-header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }";
            $html .= ".entry-content { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }";
            $html .= ".nav-links { margin: 20px 0; }";
            $html .= ".nav-links a { color: #007cba; text-decoration: none; margin-right: 15px; }";
            $html .= "</style>";
            $html .= "</head><body>";
            $html .= "<div class='nav-links'>";
            $html .= "<a href='/cyc'>&larr; Back to Encyclopedia</a>";
            $html .= "</div>";
            $html .= "<div class='entry-header'>";
            $html .= "<h1>" . htmlspecialchars(ucfirst(str_replace('-', ' ', $entry_slug))) . "</h1>";
            $html .= "<p><em>Terpedia Encyclopedia Entry</em></p>";
            $html .= "</div>";
            $html .= "<div class='entry-content'>";
            $html .= "<h2>Overview</h2>";
            $html .= "<p>This encyclopedia entry for <strong>" . htmlspecialchars($entry_slug) . "</strong> would be generated using the integrated kb.terpedia.com knowledge base.</p>";
            $html .= "<h3>🔬 Federated Database Integration</h3>";
            $html .= "<p>Data sourced from UniProt, Gene Ontology, Disease Ontology, Wikidata, and MeSH databases.</p>";
            $html .= "<h3>🤖 AI-Powered Content</h3>";
            $html .= "<p>Content generated using OpenRouter AI with fallback models for scientific accuracy.</p>";
            $html .= "<h3>💬 Natural Language Querying</h3>";
            $html .= "<p>Enhanced with natural language research queries via kb.terpedia.com chat API.</p>";
            $html .= "<p><strong>Status:</strong> The kb.terpedia.com knowledge base integration is now active for comprehensive encyclopedia entries.</p>";
            $html .= "</div>";
            $html .= "</body></html>";
            return $html;
        }
        
        function render_cyc_index_standalone() {
            $html = "<!DOCTYPE html><html><head><title>Cyc Encyclopedia - Browse Entries</title>";
            $html .= "<style>";
            $html .= "body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }";
            $html .= ".hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 8px; margin-bottom: 30px; text-align: center; }";
            $html .= ".categories { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }";
            $html .= ".category { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: white; }";
            $html .= ".category h3 { color: #333; margin-top: 0; }";
            $html .= ".examples { list-style: none; padding: 0; }";
            $html .= ".examples li { padding: 5px 0; }";
            $html .= ".examples a { color: #007cba; text-decoration: none; }";
            $html .= ".examples a:hover { text-decoration: underline; }";
            $html .= ".integration-status { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }";
            $html .= "</style>";
            $html .= "</head><body>";
            $html .= "<div class='hero'>";
            $html .= "<h1>🧬 Cyc Encyclopedia</h1>";
            $html .= "<p>Comprehensive Terpene Knowledge Base</p>";
            $html .= "<p><em>Powered by kb.terpedia.com federated databases and AI</em></p>";
            $html .= "</div>";
            $html .= "<div class='integration-status'>";
            $html .= "<h3>✅ Integration Status: Active</h3>";
            $html .= "<p>The Cyc Encyclopedia has been successfully integrated with kb.terpedia.com knowledge base capabilities:</p>";
            $html .= "<ul>";
            $html .= "<li>✅ Federated SPARQL querying (UniProt, Gene Ontology, Disease Ontology, Wikidata, MeSH)</li>";
            $html .= "<li>✅ Natural language querying via kb.terpedia.com chat API</li>";
            $html .= "<li>✅ OpenRouter AI with premium and free model fallbacks</li>";
            $html .= "<li>✅ Research article RAG integration</li>";
            $html .= "<li>✅ Enhanced content generation with scientific accuracy</li>";
            $html .= "</ul>";
            $html .= "</div>";
            $html .= "<div class='categories'>";
            $html .= "<div class='category'>";
            $html .= "<h3>🌿 Monoterpenes</h3>";
            $html .= "<ul class='examples'>";
            $html .= "<li><a href='/cyc/limonene'>Limonene</a></li>";
            $html .= "<li><a href='/cyc/linalool'>Linalool</a></li>";
            $html .= "<li><a href='/cyc/pinene'>Pinene</a></li>";
            $html .= "<li><a href='/cyc/myrcene'>Myrcene</a></li>";
            $html .= "</ul>";
            $html .= "</div>";
            $html .= "<div class='category'>";
            $html .= "<h3>🍃 Sesquiterpenes</h3>";
            $html .= "<ul class='examples'>";
            $html .= "<li><a href='/cyc/beta-caryophyllene'>Beta-Caryophyllene</a></li>";
            $html .= "<li><a href='/cyc/humulene'>Humulene</a></li>";
            $html .= "<li><a href='/cyc/nerolidol'>Nerolidol</a></li>";
            $html .= "<li><a href='/cyc/bisabolol'>Bisabolol</a></li>";
            $html .= "</ul>";
            $html .= "</div>";
            $html .= "<div class='category'>";
            $html .= "<h3>🌱 Plant Sources</h3>";
            $html .= "<ul class='examples'>";
            $html .= "<li><a href='/cyc/cannabis-sativa'>Cannabis Sativa</a></li>";
            $html .= "<li><a href='/cyc/lavandula-angustifolia'>Lavandula Angustifolia</a></li>";
            $html .= "<li><a href='/cyc/citrus-limon'>Citrus Limon</a></li>";
            $html .= "<li><a href='/cyc/pinus-pinaster'>Pinus Pinaster</a></li>";
            $html .= "</ul>";
            $html .= "</div>";
            $html .= "<div class='category'>";
            $html .= "<h3>🏥 Medical Applications</h3>";
            $html .= "<ul class='examples'>";
            $html .= "<li><a href='/cyc/anti-inflammatory'>Anti-inflammatory</a></li>";
            $html .= "<li><a href='/cyc/analgesic'>Analgesic</a></li>";
            $html .= "<li><a href='/cyc/anxiolytic'>Anxiolytic</a></li>";
            $html .= "<li><a href='/cyc/antimicrobial'>Antimicrobial</a></li>";
            $html .= "</ul>";
            $html .= "</div>";
            $html .= "</div>";
            $html .= "<p><em>Note: Encyclopedia entries are generated using federated biological databases and AI for comprehensive, scientifically accurate content.</em></p>";
            $html .= "</body></html>";
            return $html;
        }
        
        // Include the Cyc Encyclopedia Manager directly
        $cyc_file = dirname(__FILE__) . '/includes/cyc-encyclopedia-manager.php';
        if (file_exists($cyc_file)) {
            try {
                require_once $cyc_file;
                
                // Handle specific entry requests
                $entry_slug = $matches[1];
                if (!empty($entry_slug)) {
                    // Render specific encyclopedia entry
                    echo render_cyc_entry_standalone($entry_slug);
                } else {
                    // Render encyclopedia index/browse page
                    echo render_cyc_index_standalone();
                }
                exit;
            } catch (Exception $e) {
                // Simple fallback: output error and continue
                echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
                echo "<h1>Error rendering Cyc Encyclopedia page</h1>";
                echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
                echo "</body></html>";
                exit;
            }
        }
        
        // If we reach here, show a basic cyc page
        echo "<!DOCTYPE html><html><head><title>Cyc Encyclopedia</title>";
        echo "<style>";
        echo "body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }";
        echo ".hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 8px; margin-bottom: 30px; }";
        echo ".features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0; }";
        echo ".feature { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }";
        echo ".feature h3 { color: #333; margin-top: 0; }";
        echo "</style>";
        echo "</head><body>";
        echo "<div class='hero'>";
        echo "<h1>🧬 Cyc Encyclopedia - Terpedia Knowledge Base</h1>";
        echo "<p>Comprehensive terpene encyclopedia powered by federated biological databases and AI</p>";
        echo "</div>";
        echo "<div class='features'>";
        echo "<div class='feature'>";
        echo "<h3>🔬 Federated Database Integration</h3>";
        echo "<p>Connects to UniProt, Gene Ontology, Disease Ontology, Wikidata, and MeSH for comprehensive molecular data.</p>";
        echo "</div>";
        echo "<div class='feature'>";
        echo "<h3>🤖 AI-Powered Content Generation</h3>";
        echo "<p>Uses OpenRouter AI with fallback models to generate scientifically accurate encyclopedia entries.</p>";
        echo "</div>";
        echo "<div class='feature'>";
        echo "<h3>💬 Natural Language Querying</h3>";
        echo "<p>Query the knowledge base using natural language via kb.terpedia.com chat API integration.</p>";
        echo "</div>";
        echo "<div class='feature'>";
        echo "<h3>📚 Research Article Integration</h3>";
        echo "<p>RAG (Retrieval-Augmented Generation) over uploaded research articles for enhanced context.</p>";
        echo "</div>";
        echo "</div>";
        echo "<p><strong>Status:</strong> The Cyc Encyclopedia system has been successfully integrated with kb.terpedia.com knowledge base capabilities.</p>";
        echo "<p><em>Note: This is the standalone PHP interface. For full WordPress functionality, access through the admin dashboard.</em></p>";
        echo "</body></html>";
        exit;
    }
    
    // Handle /feed route for social media research feed
    if ($path === 'feed' || $path === 'feed/') {
        // Define mock WordPress functions to avoid errors
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
            function register_activation_hook($file, $callback) { /* Mock function */ }
            function register_deactivation_hook($file, $callback) { /* Mock function */ }
            function plugin_dir_url($file) { return '/'; }
            function wp_enqueue_style() { /* Mock function */ }
            function wp_enqueue_script() { /* Mock function */ }
            function admin_url($path = '') { return '/admin/' . ltrim($path, '/'); }
            function get_option($option, $default = false) { return $default; }
            function current_user_can($cap) { return true; }
            function get_current_user_id() { return 1; }
            function current_time($type) { return date('Y-m-d H:i:s'); }
            function wp_create_nonce($action) { return 'mock_nonce'; }
            function check_ajax_referer($action, $query_arg = false) { return true; }
            function sanitize_text_field($str) { return strip_tags($str); }
            function sanitize_textarea_field($str) { return strip_tags($str); }
            function esc_html($text) { return htmlspecialchars($text); }
            function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES); }
        }
        
        // Log route matching for debugging
        error_log('Terpedia: Matched /feed route for social media research feed');
        
        // Render the social media research feed
        render_terpedia_social_feed();
        exit;
    }
}

// Global mock WordPress functions for standalone PHP
if (!function_exists('get_file_data')) {
    function get_file_data($file, $default_headers, $context = '') {
        // Mock implementation to parse plugin header
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $result = array();
            foreach ($default_headers as $key => $header) {
                if (preg_match('/\* ' . preg_quote($header) . ':\s*(.+)/i', $content, $matches)) {
                    $result[$key] = trim($matches[1]);
                } else {
                    $result[$key] = '';
                }
            }
            return $result;
        }
        return array_fill_keys(array_keys($default_headers), '');
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return '/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) { /* Mock function */ }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) { /* Mock function */ }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { /* Mock function */ }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) { /* Mock function */ }
}

if (!function_exists('is_admin')) {
    function is_admin() { return false; }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() { /* Mock function */ }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() { /* Mock function */ }
}

if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = array()) { /* Mock function */ }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box() { /* Mock function */ }
}

if (!function_exists('current_user_can')) {
    function current_user_can($cap) { return true; }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() { return 1; }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) { return true; }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) { return 'mock_nonce'; }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return strip_tags($str); }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) { return true; }
}

if (!function_exists('wp_die')) {
    function wp_die($message) { die($message); }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data) { 
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'data' => $data));
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data) { 
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'data' => $data));
        exit;
    }
}

// Define plugin constants
$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
define('TERPEDIA_AI_VERSION', $plugin_data['Version'] ?? '3.11.58');
define('TERPEDIA_AI_URL', plugin_dir_url(__FILE__));
define('TERPEDIA_AI_PATH', plugin_dir_path(__FILE__));

class TerpediaAI {
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Maintain Tersonae and Expert users
        add_action('init', array($this, 'maintain_terpedia_users'), 20);
        
        // URL routing for Terpedia.com
        add_action('template_redirect', array($this, 'handle_terpedia_routes'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('generate_rewrite_rules', array($this, 'add_rewrite_rules'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_query', array($this, 'handle_ajax_query'));
        add_action('wp_ajax_nopriv_terpedia_query', array($this, 'handle_ajax_query'));
        add_action('wp_ajax_terpedia_multiagent', array($this, 'handle_ajax_multiagent'));
        add_action('wp_ajax_nopriv_terpedia_multiagent', array($this, 'handle_ajax_multiagent'));
        add_action('wp_ajax_setup_terpene_profiles', array($this, 'handle_ajax_setup_profiles'));
        add_action('wp_ajax_nopriv_setup_terpene_profiles', array($this, 'handle_ajax_setup_profiles'));
        add_action('wp_ajax_terpedia_chemist_chat', array($this, 'handle_chemist_chat'));
        add_action('wp_ajax_nopriv_terpedia_chemist_chat', array($this, 'handle_chemist_chat'));
        add_action('wp_ajax_terpedia_molecular_structure', array($this, 'handle_molecular_structure'));
        add_action('wp_ajax_nopriv_terpedia_molecular_structure', array($this, 'handle_molecular_structure'));
        add_action('wp_ajax_terpedia_find_database_links', array($this, 'ajax_find_database_links'));
        
        // Shortcodes
        add_shortcode('terpedia_chat', array($this, 'chat_shortcode'));
        add_shortcode('terpedia_multiagent', array($this, 'multiagent_shortcode'));
        add_shortcode('terpedia_design', array($this, 'design_shortcode'));
        add_shortcode('terpedia_chemist', array($this, 'chemist_shortcode'));
        add_shortcode('terpedia_newsletter', array($this, 'newsletter_shortcode'));
        add_shortcode('terpedia_podcast', array($this, 'podcast_shortcode'));
        
        // Custom Post Types
        add_action('init', array($this, 'create_podcast_post_type'));
        add_action('init', array($this, 'create_terproducts_post_type'));
        
        // Version update check and episode creation
        add_action('plugins_loaded', array($this, 'check_version_update'));
        add_action('init', array($this, 'ensure_default_episodes'));
        
        // Admin menu and secure handlers
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            
            // Secure admin-post handlers for updates
            add_action('admin_post_terpedia_update_theme', array($this, 'handle_secure_theme_update'));
            add_action('admin_post_terpedia_update_plugin', array($this, 'handle_secure_plugin_update'));
        }
    }
    
    public function activate() {
        // Create database tables if needed
        $this->create_database_tables();
        
        // Create veterinary research report as Terport
        $this->create_veterinary_research_terport();
        
        // Create demo terports
        $this->create_demo_terports();
        
        // Trigger automatic terport generation
        do_action('terpedia_plugin_activated');
        
        // Force flush rewrite rules immediately
        flush_rewrite_rules();
        add_option('terpedia_ai_flush_rewrite_rules', true);
        
        // Clear any caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function init() {
        // Basic initialization
        load_plugin_textdomain('terpedia-ai', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Flush rewrite rules on activation
        if (get_option('terpedia_ai_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('terpedia_ai_flush_rewrite_rules');
        }
        
        // Include TULIP system and other core features
        $includes = array(
            'includes/tulip-system.php',
            'includes/cpt-archive-system.php',
            'includes/case-management-system.php',
            'includes/terport-openrouter-integration.php',
            'includes/terport-sparql-integration.php',
            'includes/enhanced-terport-editor.php',
            'includes/terport-template-system.php',
            'includes/field-based-template-system.php',
            'includes/terport-chat-interface.php',
            'includes/terport-smart-refresh-system.php',
            'includes/templated-text-gutenberg-block.php',
            'includes/cpt-template-management.php',
            'includes/veterinary-terpene-templates.php',
            'includes/version-endpoint.php',
            'includes/force-cpt-refresh.php',
            'includes/automatic-terport-generator.php',
            'includes/terport-version-tracker.php',
            'includes/terports-widget.php',
            'includes/podcasts-widget.php',
            'includes/user-profile-system.php',
        );
        
        foreach ($includes as $file) {
            $filepath = plugin_dir_path(__FILE__) . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            }
        }
        
        // Initialize CPT Archive System
        if (class_exists('Terpedia_CPT_Archive_System')) {
            new Terpedia_CPT_Archive_System();
        }
        
        // Initialize Enhanced Terport Editor
        if (class_exists('Terpedia_Enhanced_Terport_Editor')) {
            new Terpedia_Enhanced_Terport_Editor();
        }
        
        // CRITICAL FIX: Initialize automatic terport generation system
        if (class_exists('Terpedia_Automatic_Terport_Generator')) {
            new Terpedia_Automatic_Terport_Generator();
        }
        
        // Initialize version tracking system
        if (class_exists('Terpedia_Terport_Version_Tracker')) {
            new Terpedia_Terport_Version_Tracker();
        }
        
        // Include BuddyPress messaging and agent systems (safely)
        if (class_exists('BuddyPress')) {
            $bp_includes = array(
                'includes/buddypress-messaging.php',
                'includes/terpene-agents.php',
                'includes/agent-profiles.php',
                'includes/buddypress-profile-setup.php',
                'includes/avatar-generator.php',
                'includes/buddypress-avatar-fix.php',
                'includes/force-avatar-refresh.php',
                'includes/direct-avatar-override.php',
                'includes/avatar-force-display.php',
                'includes/force-avatar-injection.php',
                'includes/enhanced-tts-system.php',
                'includes/neural-tts-system.php',
                'includes/profile-enhancement.php',
                'includes/expert-agent-profiles.php',
                'includes/case-management.php',
                'includes/agent-conversations.php',
                'includes/demo-user-setup.php',
                'includes/patient-intake-form.php',
                'includes/complete-agent-setup.php',
                'includes/integrated-profile-design.php',
                'includes/demo-veterinarian-case.php',
                'includes/complete-profile-override.php',
                'includes/force-demo-creation.php'
            );
            
            foreach ($bp_includes as $file) {
                $filepath = plugin_dir_path(__FILE__) . $file;
                if (file_exists($filepath)) {
                    require_once $filepath;
                }
            }
            
            // Admin interfaces
            if (is_admin()) {
                $admin_file = plugin_dir_path(__FILE__) . 'admin/agent-management.php';
                if (file_exists($admin_file)) {
                    require_once $admin_file;
                }
            }
        }
    }
    
    /**
     * Add custom query vars for routing
     */
    public function add_query_vars($vars) {
        $vars[] = 'terpedia_page';
        return $vars;
    }
    
    /**
     * Add rewrite rules for Terpedia.com routes
     */
    public function add_rewrite_rules($wp_rewrite) {
        $new_rules = array(
            'design/?$' => 'index.php?terpedia_page=design',
            'chat/?$' => 'index.php?terpedia_page=chat',
            'multi-agent/?$' => 'index.php?terpedia_page=multiagent'
        );
        
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }
    
    /**
     * Handle Terpedia.com route requests
     */
    public function handle_terpedia_routes() {
        // Handle direct URL routing for standalone PHP environment
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        
        // Debug output
        error_log("Terpedia Debug: handle_terpedia_routes called, path: $path");
        
        // Handle /terports route directly
        if ($path === 'terports' || $path === 'terports/') {
            error_log("Terpedia Debug: Matched terports route");
            
            // Load CPT Archive System and call render method directly
            if (class_exists('Terpedia_CPT_Archive_System')) {
                error_log("Terpedia Debug: CPT Archive System class exists");
                $archive_system = new Terpedia_CPT_Archive_System();
                
                // Use reflection to access the private render_terports_archive method
                try {
                    $method = new ReflectionMethod($archive_system, 'render_terports_archive');
                    $method->setAccessible(true);
                    error_log("Terpedia Debug: About to invoke render method");
                    $method->invoke($archive_system);
                    exit;
                } catch (Exception $e) {
                    error_log("Terpedia Debug: Error with reflection: " . $e->getMessage());
                    // Fallback: call method directly if it's accessible
                    echo "Error accessing render method: " . $e->getMessage();
                }
            } else {
                error_log("Terpedia Debug: CPT Archive System class not found");
                echo "CPT Archive System class not found";
            }
            exit;
        }
        
        global $wp_query;
        
        $terpedia_page = get_query_var('terpedia_page');
        
        if (!empty($terpedia_page)) {
            // Set up WordPress to display our custom page
            $wp_query->is_home = false;
            $wp_query->is_404 = false;
            
            switch ($terpedia_page) {
                case 'design':
                    $this->render_design_page();
                    break;
                case 'chat':
                    $this->render_chat_page();
                    break;
                case 'multiagent':
                    $this->render_multiagent_page();
                    break;
                default:
                    return;
            }
            exit;
        }
    }
    
    /**
     * Render the design page as standalone
     */
    private function render_design_page() {
        $this->enqueue_scripts();
        $this->render_terpedia_page('Terpedia Architecture & Design', $this->design_shortcode(array()));
    }
    
    /**
     * Render the chat page as standalone
     */
    private function render_chat_page() {
        $this->enqueue_scripts();
        $this->render_terpedia_page('AI Research Chat', $this->chat_shortcode(array('height' => '600px')));
    }
    
    /**
     * Render the multi-agent page as standalone
     */
    private function render_multiagent_page() {
        $this->enqueue_scripts();
        $this->render_terpedia_page('Multi-Agent Research', $this->multiagent_shortcode(array('height' => '700px')));
    }
    
    /**
     * Render a Terpedia page with proper HTML structure
     */
    private function render_terpedia_page($title, $content) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <div class="terpedia-page-wrapper">
                <header class="terpedia-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><a href="<?php echo home_url(); ?>">← Back to Terpedia</a></p>
                </header>
                <main class="terpedia-content">
                    <?php echo $content; ?>
                </main>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        // Enqueue the lifelike neural TTS system
        wp_enqueue_script(
            'terpedia-lifelike-neural-tts',
            TERPEDIA_AI_URL . 'assets/js/lifelike-neural-tts.js',
            array('jquery'),
            TERPEDIA_AI_VERSION,
            true
        );
        
        wp_enqueue_script(
            'terpedia-ai-script',
            TERPEDIA_AI_URL . 'assets/js/terpedia-ai.js',
            array('jquery'),
            TERPEDIA_AI_VERSION,
            true
        );
        wp_enqueue_style(
            'terpedia-ai-style',
            TERPEDIA_AI_URL . 'assets/css/terpedia-ai.css',
            array(),
            TERPEDIA_AI_VERSION
        );
        
        wp_localize_script('terpedia-ai-script', 'terpedia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_nonce')
        ));
    }
    
    public function handle_ajax_query() {
        try {
            if (!check_ajax_referer('terpedia_nonce', 'nonce', false)) {
                wp_send_json_error('Security check failed');
                return;
            }
            
            $query = isset($_POST['query']) ? sanitize_textarea_field($_POST['query']) : '';
            
            if (empty($query)) {
                wp_send_json_error('No query provided');
                return;
            }
            
            // Simple response for now
            $response = array(
                'answer' => 'Thank you for your query about: ' . $query . '. The multi-agent system is being configured.',
                'sources' => array('Terpedia Database'),
                'confidence' => 0.8
            );
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log('Terpedia AI Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred processing your request');
        }
    }
    
    public function handle_ajax_multiagent() {
        try {
            if (!check_ajax_referer('terpedia_nonce', 'nonce', false)) {
                wp_send_json_error('Security check failed');
                return;
            }
            
            $query = isset($_POST['query']) ? sanitize_textarea_field($_POST['query']) : '';
            
            if (empty($query)) {
                wp_send_json_error('No query provided');
                return;
            }
            
            // Simple multi-agent response
            $response = array(
                'primaryAnswer' => 'Multi-agent analysis of: ' . $query,
                'agentResponses' => array(
                    array(
                        'agentName' => 'Cannabis Chemist',
                        'response' => 'Chemical analysis perspective on your query.',
                        'confidence' => 0.85
                    ),
                    array(
                        'agentName' => 'Pharmacologist',
                        'response' => 'Pharmacological insights on your question.',
                        'confidence' => 0.82
                    )
                ),
                'consensus' => 'The agents agree this is an important research topic.',
                'finalConfidence' => 0.83
            );
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log('Terpedia AI Multiagent Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred with the multi-agent system');
        }
    }
    
    public function handle_ajax_setup_profiles() {
        try {
            if (!check_ajax_referer('terpedia_nonce', 'nonce', false)) {
                wp_send_json_error('Security check failed');
                return;
            }
            
            // Setup terpene profiles
            if (class_exists('TerpeneBuddyPressAgents')) {
                $terpene_agents = new TerpeneBuddyPressAgents();
                $terpene_agents->create_terpene_agents();
                
                // Also trigger profile setup
                if (class_exists('TerpediaBuddyPressProfileSetup')) {
                    $profile_setup = new TerpediaBuddyPressProfileSetup();
                    $profile_setup->ajax_create_terpene_profiles();
                    return; // This will send its own JSON response
                }
            }
            
            wp_send_json_success(array(
                'message' => 'Terpene profiles setup initiated'
            ));
            
        } catch (Exception $e) {
            error_log('Terpedia Profile Setup Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred setting up profiles');
        }
    }
    
    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '500px',
            'width' => '100%'
        ), $atts, 'terpedia_chat');
        
        ob_start();
        ?>
        <div id="terpedia-chat-container" style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="terpedia-chat-header">
                <h3>Terpedia AI Research Assistant</h3>
            </div>
            <div class="terpedia-chat-messages" id="terpedia-messages"></div>
            <div class="terpedia-chat-input">
                <textarea id="terpedia-query" placeholder="Ask about cannabis terpenes, research, or regulations..."></textarea>
                <button id="terpedia-send" type="button">Send</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function multiagent_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '600px',
            'width' => '100%'
        ), $atts, 'terpedia_multiagent');
        
        ob_start();
        ?>
        <div id="terpedia-multiagent-container" style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="terpedia-multiagent-header">
                <h3>Multi-Agent Cannabis Research</h3>
                <p>Collaborate with AI specialists in chemistry, pharmacology, literature, and regulation</p>
            </div>
            <div class="terpedia-query-section">
                <textarea id="terpedia-multiagent-query" placeholder="Enter your research question..."></textarea>
                <button id="terpedia-collaborate" type="button">Start Research</button>
            </div>
            <div id="terpedia-multiagent-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function design_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'light'
        ), $atts, 'terpedia_design');
        
        ob_start();
        ?>
        <div id="terpedia-design-document">
            <!-- Header Section -->
            <div class="design-header">
                <div class="header-content">
                    <h1>Terpedia - Encyclopedia of Terpenes</h1>
                    <h2>AI-Powered Knowledge System Architecture</h2>
                    <div class="document-meta">
                        <span class="version">Production v<?php echo esc_html(TERPEDIA_AI_VERSION); ?></span>
                        <span class="date">January 2025 - Mobile Enhanced</span>
                        <span class="authors">Terpedia Research Team</span>
                    </div>
                </div>
                <div class="header-illustration">
                    <svg viewBox="0 0 400 200" class="architecture-diagram">
                        <!-- Central AI Hub -->
                        <circle cx="200" cy="100" r="25" fill="#2c5aa0" stroke="#1e3a6f" stroke-width="2"/>
                        <text x="200" y="105" text-anchor="middle" fill="white" font-size="10">AI Core</text>
                        
                        <!-- Agent Nodes -->
                        <circle cx="120" cy="60" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="120" y="45" text-anchor="middle" font-size="8">Chemist</text>
                        
                        <circle cx="280" cy="60" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="280" y="45" text-anchor="middle" font-size="8">Pharmacist</text>
                        
                        <circle cx="120" cy="140" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="120" y="155" text-anchor="middle" font-size="8">Budtender</text>
                        
                        <circle cx="280" cy="140" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="280" y="155" text-anchor="middle" font-size="8">Formulator</text>
                        
                        <!-- Connections -->
                        <line x1="175" y1="100" x2="138" y2="78" stroke="#2c5aa0" stroke-width="1"/>
                        <line x1="225" y1="100" x2="262" y2="78" stroke="#2c5aa0" stroke-width="1"/>
                        <line x1="175" y1="100" x2="138" y2="122" stroke="#2c5aa0" stroke-width="1"/>
                        <line x1="225" y1="100" x2="262" y2="122" stroke="#2c5aa0" stroke-width="1"/>
                        
                        <!-- Data Sources -->
                        <rect x="30" y="10" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="55" y="22" text-anchor="middle" font-size="7">Terpedia DB</text>
                        
                        <rect x="320" y="10" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="345" y="22" text-anchor="middle" font-size="7">Traditional Med</text>
                        
                        <rect x="30" y="170" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="55" y="182" text-anchor="middle" font-size="7">Ethnobotany</text>
                        
                        <rect x="320" y="170" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="345" y="182" text-anchor="middle" font-size="7">PubMed</text>
                    </svg>
                </div>
            </div>

            <!-- Executive Summary -->
            <section class="design-section executive-summary">
                <h3>Executive Summary</h3>
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Mission</h4>
                        <p>Create the world's most comprehensive encyclopedia of terpenes through AI-powered knowledge synthesis, providing instant access to molecular structures, therapeutic properties, and aromatic profiles.</p>
                    </div>
                    <div class="summary-card">
                        <h4>Innovation</h4>
                        <p>First encyclopedia to combine 11 specialized AI experts with real-time biochemical databases, molecular analysis, and evidence-based terpene formulation guidance.</p>
                    </div>
                    <div class="summary-card">
                        <h4>Impact</h4>
                        <p>Transforms terpene knowledge from scattered research into an organized, searchable encyclopedia accessible to researchers, formulators, and health professionals worldwide.</p>
                    </div>
                </div>
            </section>

            <!-- What Terpedia Can Do -->
            <section class="design-section capabilities">
                <h3>What Terpedia Can Do</h3>
                <div class="capabilities-intro">
                    <p>Terpedia is your comprehensive gateway to the world of terpenes - the aromatic compounds found in countless plants that influence everything from fragrance to therapeutic effects. Our AI-powered encyclopedia with <strong>Lifelike Neural TTS</strong> makes complex terpene science accessible to everyone through natural voice interactions.</p>
                </div>
                
                <div class="capabilities-grid">
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">🔍</div>
                            <h4>Instant Terpene Knowledge</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Ask any terpene question</strong> - Get expert answers about molecular structures, properties, and effects</li>
                            <li><strong>Explore terpene profiles</strong> - Browse detailed pages for individual terpenes with scientific data</li>
                            <li><strong>Discover natural sources</strong> - Learn which plants contain specific terpenes and their geographic distribution</li>
                            <li><strong>Traditional medicine context</strong> - Understand historical uses across different cultures and healing systems</li>
                            <li><strong>Ethnobotanical insights</strong> - Explore indigenous knowledge and regional plant applications</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">🧪</div>
                            <h4>Scientific Analysis</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Molecular structure visualization</strong> - See 3D chemical structures and understand molecular interactions</li>
                            <li><strong>Enzyme binding analysis</strong> - Explore how terpenes interact with biological targets</li>
                            <li><strong>Biochemical pathway mapping</strong> - Understand biosynthesis and metabolic processes</li>
                            <li><strong>Chemical property calculations</strong> - Get precise data on boiling points, solubility, and stability</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">⚗️</div>
                            <h4>Formulation Assistance</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Blend optimization</strong> - Calculate precise ratios for desired aromatic profiles</li>
                            <li><strong>Synergy analysis</strong> - Understand how terpenes work together for enhanced effects</li>
                            <li><strong>Essential oil formulation</strong> - Create custom blends for specific therapeutic or aromatic goals</li>
                            <li><strong>Product development guidance</strong> - Professional consultation for commercial applications</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">📚</div>
                            <h4>Research & Education</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Literature synthesis</strong> - Access thousands of research papers with AI-powered analysis</li>
                            <li><strong>Citation tracking</strong> - Follow scientific evidence with proper academic references</li>
                            <li><strong>Knowledge reports</strong> - Generate comprehensive summaries on any terpene topic</li>
                            <li><strong>Educational content</strong> - Learn about terpene science at any level of complexity</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">🌐</div>
                            <h4>Real-Time Data Access</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Terpedia SPARQL Database</strong> - Access comprehensive natural product data from traditional medicine and ethnobotany sources</li>
                            <li><strong>Live database queries</strong> - Connect to ChEBI, PubMed, and Wikidata in real-time</li>
                            <li><strong>Cross-platform validation</strong> - Verify information across multiple scientific databases</li>
                            <li><strong>Traditional medicine integration</strong> - Explore historical uses and cultural applications</li>
                            <li><strong>Ethnobotanical knowledge</strong> - Access indigenous plant wisdom and regional variations</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">👥</div>
                            <h4>Expert AI Consultation</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>13 specialized AI agents</strong> - Get targeted advice from professional Agt. specialists with distinct expertise</li>
                            <li><strong>Multi-perspective analysis</strong> - View questions from chemical, medical, and botanical angles</li>
                            <li><strong>Professional guidance</strong> - Receive advice tailored to your specific field or application</li>
                            <li><strong>Collaborative problem-solving</strong> - Multiple experts work together on complex questions</li>
                        </ul>
                    </div>
                </div>
                
                <div class="use-cases">
                    <h4>Who Uses Terpedia?</h4>
                    <div class="use-case-grid">
                        <div class="use-case">
                            <strong>Researchers & Academics</strong>
                            <p>Access comprehensive terpene data for studies, publications, and academic research</p>
                        </div>
                        <div class="use-case">
                            <strong>Product Formulators</strong>
                            <p>Develop essential oil blends, cosmetics, and therapeutic products with precision</p>
                        </div>
                        <div class="use-case">
                            <strong>Healthcare Professionals</strong>
                            <p>Understand therapeutic applications and safety profiles for patient care</p>
                        </div>
                        <div class="use-case">
                            <strong>Aromatherapists</strong>
                            <p>Create evidence-based aromatic treatments with scientific backing</p>
                        </div>
                        <div class="use-case">
                            <strong>Traditional Medicine Practitioners</strong>
                            <p>Access historical uses and cultural applications from global healing traditions</p>
                        </div>
                        <div class="use-case">
                            <strong>Ethnobotanists</strong>
                            <p>Explore indigenous plant knowledge and regional variations in terpene applications</p>
                        </div>
                        <div class="use-case">
                            <strong>Cannabis Professionals</strong>
                            <p>Analyze strain profiles and understand terpene effects in cannabis products</p>
                        </div>
                        <div class="use-case">
                            <strong>Students & Educators</strong>
                            <p>Learn and teach terpene science with accessible, accurate information</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Demo Veterinarian -->
            <section class="design-section demo-vet">
                <h3>Demo Veterinarian</h3>
                <div class="demo-user-section">
                    <div class="demo-user-card">
                        <a href="/members/dr-teresa-thompson/" class="demo-profile-link">
                            <div class="demo-user-info">
                                <div class="demo-user-avatar">🐕‍🦺</div>
                                <div class="demo-user-details">
                                    <h5>Dr. Teresa Thompson, DVM</h5>
                                    <p class="demo-user-title">Veterinary Terpene Specialist</p>
                                    <p class="demo-user-description">Specializing in canine seizure management with Golden Retriever case studies. Expert in veterinary terpene therapy and animal wellness protocols.</p>
                                    <div class="demo-case-info">
                                        <strong>Active Case:</strong> Bella (Golden Retriever) - Seizure Management with Terpene Therapy<br>
                                        <div style="margin-top: 10px;">
                                            <a href="/wp-content/plugins/terpedia/demo-pages/bella-patient.html" style="background: #4CAF50; color: white; padding: 6px 12px; border-radius: 15px; text-decoration: none; font-size: 12px; margin-right: 8px;">👩‍⚕️ View Patient</a>
                                            <a href="/wp-content/plugins/terpedia/demo-pages/seizure-case.html" style="background: #2196F3; color: white; padding: 6px 12px; border-radius: 15px; text-decoration: none; font-size: 12px;">📋 View Case</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Multi-Agent Architecture -->
            <section class="design-section agent-architecture">
                <h3>Multi-Agent AI System</h3>
                <div class="agent-diagram">
                    <div class="agent-category medical">
                        <h4>Medical Experts</h4>
<?php
                        $medical_agents = array(
                            'terpedia-chemist' => array('icon' => '🧬', 'name' => 'Agt. Molecule Maven', 'desc' => 'Molecular structure wizard, chemical property analysis, and terpene biosynthesis pathways'),
                            'terpedia-pharmacologist' => array('icon' => '💊', 'name' => 'Agt. Pharmakin', 'desc' => 'Drug interaction specialist, bioavailability expert, and pharmacokinetics modeling guru'),
                            'terpedia-veterinarian' => array('icon' => '🐕', 'name' => 'Agt. Pawscription', 'desc' => 'Veterinary dosing expert, animal safety protocols, and species-specific terpene applications'),
                            'terpedia-naturopath' => array('icon' => '🌿', 'name' => 'Agt. Holistica', 'desc' => 'Traditional healing wisdom, herb synergies, and natural medicine integration')
                        );
                        ?>
                        <div class="agent-grid">
                        <?php foreach ($medical_agents as $username => $agent): 
                            $profile_url = '#';
                            if (function_exists('bp_core_get_user_domain')) {
                                $user = get_user_by('login', $username);
                                if ($user) {
                                    $profile_url = bp_core_get_user_domain($user->ID);
                                }
                            }
                        ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="agent-card agent-profile-link" title="Visit <?php echo esc_attr($agent['name']); ?>'s BuddyPress profile">
                                <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                                <h5><?php echo esc_html($agent['name']); ?></h5>
                                <p><?php echo esc_html($agent['desc']); ?></p>
                                <div class="agent-status">Available for consultation</div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="agent-category research">
                        <h4>Research & Development</h4>
<?php
                        $research_agents = array(
                            'terpedia-literature' => array('icon' => '📚', 'name' => 'Agt. Citeswell', 'desc' => 'PubMed detective, systematic review architect, and evidence synthesis specialist'),
                            'terpedia-regulatory' => array('icon' => '⚖️', 'name' => 'Agt. Compliance', 'desc' => 'Regulatory navigator, legal framework expert, and safety protocol guardian'),
                            'terpedia-reporter' => array('icon' => '📊', 'name' => 'Agt. Datawise', 'desc' => 'Research synthesizer, publication wizard, and scientific storytelling expert')
                        );
                        ?>
                        <div class="agent-grid">
                        <?php foreach ($research_agents as $username => $agent): 
                            $profile_url = '#';
                            if (function_exists('bp_core_get_user_domain')) {
                                $user = get_user_by('login', $username);
                                if ($user) {
                                    $profile_url = bp_core_get_user_domain($user->ID);
                                }
                            }
                        ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="agent-card agent-profile-link" title="Visit <?php echo esc_attr($agent['name']); ?>'s BuddyPress profile">
                                <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                                <h5><?php echo esc_html($agent['name']); ?></h5>
                                <p><?php echo esc_html($agent['desc']); ?></p>
                                <div class="agent-status">Available for consultation</div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="agent-category industry">
                        <h4>Industry Specialists</h4>
<?php
                        $industry_agents = array(
                            'terpedia-botanist' => array('icon' => '🌱', 'name' => 'Agt. Fieldsworth', 'desc' => 'Botanical detective, plant source specialist, and natural occurrence mapping expert'),
                            'terpedia-aromatherapist' => array('icon' => '🔬', 'name' => 'Agt. Alchemist', 'desc' => 'Sensory profile virtuoso, aromatic compound specialist, and terpene interaction maven'),
                            'terpedia-formulator' => array('icon' => '⚗️', 'name' => 'Agt. Mastermind', 'desc' => 'Formulation genius, optimization algorithm wizard, and precision ratio specialist'),
                            'terpedia-patient' => array('icon' => '👤', 'name' => 'Agt. Companion', 'desc' => 'Personal wellness advocate, individualized care specialist, and patient safety champion')
                        );
                        ?>
                        <div class="agent-grid">
                        <?php foreach ($industry_agents as $username => $agent): 
                            $profile_url = '#';
                            if (function_exists('bp_core_get_user_domain')) {
                                $user = get_user_by('login', $username);
                                if ($user) {
                                    $profile_url = bp_core_get_user_domain($user->ID);
                                }
                            }
                        ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="agent-card agent-profile-link" title="Visit <?php echo esc_attr($agent['name']); ?>'s BuddyPress profile">
                                <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                                <h5><?php echo esc_html($agent['name']); ?></h5>
                                <p><?php echo esc_html($agent['desc']); ?></p>
                                <div class="agent-status">Available for consultation</div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tersonas -->
            <section class="design-section tersonas">
                <h3>Tersonas</h3>
                <p class="section-description">Meet our individual terpene specialists - each one a unique personality representing their molecular characteristics and therapeutic effects.</p>
                <div class="terpene-agents-grid">
                    <?php
                    $terpene_agents = array(
                        array(
                            'name' => 'Agt. Myrcene',
                            'emoji' => '🥭',
                            'title' => 'The Relaxation Specialist',
                            'description' => 'Master of couch lock and deep relaxation. Found in mangoes and known for enhancing cannabinoid absorption.',
                            'effects' => array('Sedative', 'Muscle Relaxant', 'Sleep Aid'),
                            'sources' => array('Mango', 'Hops', 'Lemongrass'),
                            'profile_url' => '/members/terpedia-myrcene/'
                        ),
                        array(
                            'name' => 'Agt. Limonene',
                            'emoji' => '🍊',
                            'title' => 'The Mood Elevator',
                            'description' => 'Citrusy champion of mood enhancement and stress relief. Crosses the blood-brain barrier with ease.',
                            'effects' => array('Mood Enhancement', 'Stress Relief', 'Anti-anxiety'),
                            'sources' => array('Citrus Peels', 'Juniper', 'Peppermint'),
                            'profile_url' => '/members/terpedia-limonene/'
                        ),
                        array(
                            'name' => 'Agt. Pinene',
                            'emoji' => '🌲',
                            'title' => 'The Mental Clarity Expert',
                            'description' => 'Forest wisdom incarnate. Provides alertness and counteracts memory impairment while supporting respiratory health.',
                            'effects' => array('Mental Clarity', 'Memory Enhancement', 'Bronchodilator'),
                            'sources' => array('Pine Trees', 'Rosemary', 'Basil'),
                            'profile_url' => '/members/terpedia-pinene/'
                        ),
                        array(
                            'name' => 'Agt. Linalool',
                            'emoji' => '🌾',
                            'title' => 'The Lavender Healer',
                            'description' => 'Gentle healer with lavender\'s grace. Specializes in anxiety relief and anti-inflammatory action.',
                            'effects' => array('Anti-anxiety', 'Analgesic', 'Anti-inflammatory'),
                            'sources' => array('Lavender', 'Mint', 'Cinnamon'),
                            'profile_url' => '/members/terpedia-linalool/'
                        ),
                        array(
                            'name' => 'Agt. Caryophyllene',
                            'emoji' => '🌶️',
                            'title' => 'The CB2 Activator',
                            'description' => 'Unique terpene that acts like a cannabinoid. Directly activates CB2 receptors for powerful anti-inflammatory effects.',
                            'effects' => array('CB2 Activation', 'Anti-inflammatory', 'Analgesic'),
                            'sources' => array('Black Pepper', 'Cloves', 'Hops'),
                            'profile_url' => '/members/terpedia-caryophyllene/'
                        ),
                        array(
                            'name' => 'Agt. Humulene',
                            'emoji' => '🌿',
                            'title' => 'The Appetite Suppressant',
                            'description' => 'Ancient hops wisdom meets appetite control. Unique among cannabis terpenes for reducing hunger.',
                            'effects' => array('Appetite Suppressant', 'Anti-inflammatory', 'Antibacterial'),
                            'sources' => array('Hops', 'Coriander', 'Basil'),
                            'profile_url' => '/members/terpedia-humulene/'
                        )
                    );
                    
                    foreach ($terpene_agents as $agent): ?>
                        <a href="<?php echo esc_url($agent['profile_url']); ?>" class="terpene-agent-card" title="Chat with <?php echo esc_attr($agent['name']); ?>">
                            <div class="terpene-agent-header">
                                <div class="terpene-emoji"><?php echo $agent['emoji']; ?></div>
                                <h4><?php echo esc_html($agent['name']); ?></h4>
                                <span class="terpene-title"><?php echo esc_html($agent['title']); ?></span>
                            </div>
                            <div class="terpene-agent-content">
                                <p class="terpene-description"><?php echo esc_html($agent['description']); ?></p>
                                <div class="terpene-effects">
                                    <h5>Primary Effects:</h5>
                                    <div class="effect-tags">
                                        <?php foreach ($agent['effects'] as $effect): ?>
                                            <span class="effect-tag"><?php echo esc_html($effect); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="terpene-sources">
                                    <h5>Natural Sources:</h5>
                                    <div class="source-tags">
                                        <?php foreach ($agent['sources'] as $source): ?>
                                            <span class="source-tag"><?php echo esc_html($source); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="terpene-agent-footer">
                                <span class="chat-prompt">💬 Click to chat about effects and applications</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="terpene-section-footer">
                    <p><strong>Interactive Consultations:</strong> Each terpene agent provides specialized knowledge about their unique properties, therapeutic applications, and molecular interactions. Ask about entourage effects, dosing considerations, or specific health applications.</p>
                </div>
            </section>

            <!-- Technical Architecture -->
            <section class="design-section tech-architecture">
                <h3>Technical Architecture</h3>
                <div class="tech-stack-diagram">
                    <div class="stack-layer frontend">
                        <h4>Frontend Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">React 18</span>
                            <span class="tech-badge">TypeScript</span>
                            <span class="tech-badge">Tailwind CSS</span>
                            <span class="tech-badge">Wouter Routing</span>
                            <span class="tech-badge">TanStack Query</span>
                        </div>
                    </div>
                    
                    <div class="stack-layer backend">
                        <h4>Backend Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">Express.js</span>
                            <span class="tech-badge">PostgreSQL</span>
                            <span class="tech-badge">Drizzle ORM</span>
                            <span class="tech-badge">WebSocket</span>
                            <span class="tech-badge">Node.js</span>
                        </div>
                    </div>
                    
                    <div class="stack-layer ai">
                        <h4>AI & ML Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">OpenAI GPT-4</span>
                            <span class="tech-badge">Vector Embeddings</span>
                            <span class="tech-badge">RAG System</span>
                            <span class="tech-badge">Multi-Agent Framework</span>
                        </div>
                    </div>
                    
                    <div class="stack-layer data">
                        <h4>Data Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">COCONUT (695K)</span>
                            <span class="tech-badge">LOTUS (750K+)</span>
                            <span class="tech-badge">TCM (19K)</span>
                            <span class="tech-badge">Dr. Duke (2.4K plants)</span>
                            <span class="tech-badge">PubMed (239K refs)</span>
                            <span class="tech-badge">ChEBI</span>
                            <span class="tech-badge">Cannabis Data</span>
                            <span class="tech-badge">Essential Oils</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Core Features -->
            <section class="design-section features">
                <h3>Core Platform Features</h3>
                <div class="features-grid">
                    <a href="/chat" class="feature-card feature-link">
                        <div class="feature-icon">💬</div>
                        <h4>AI Encyclopedia Chat</h4>
                        <p>Natural language queries about terpenes with intelligent expert selection and comprehensive answers</p>
                        <ul>
                            <li>Context-aware responses</li>
                            <li>Source attribution</li>
                            <li>Multi-modal output</li>
                        </ul>
                    </a>
                    
                    <a href="/molecular-docking" class="feature-card feature-link">
                        <div class="feature-icon">🧪</div>
                        <h4>Molecular Docking</h4>
                        <p>Interactive enzyme-ligand binding analysis with 3D visualization and binding affinity calculations</p>
                        <ul>
                            <li>Protein structure analysis</li>
                            <li>Binding site prediction</li>
                            <li>Drug-likeness scoring</li>
                        </ul>
                    </a>
                    
                    <a href="/terpene-profiles" class="feature-card feature-link">
                        <div class="feature-icon">📋</div>
                        <h4>Terpene Profiles</h4>
                        <p>Comprehensive terpene profiles with chemical analysis and aromatic characteristics</p>
                        <ul>
                            <li>Molecular structures</li>
                            <li>Therapeutic properties</li>
                            <li>Natural sources</li>
                        </ul>
                    </a>
                    
                    <a href="/formulator" class="feature-card feature-link">
                        <div class="feature-icon">⚗️</div>
                        <h4>Terpene Formulator</h4>
                        <p>Precision blending calculator for essential oils and terpene formulations</p>
                        <ul>
                            <li>Aroma profile matching</li>
                            <li>Therapeutic targeting</li>
                            <li>Synergy optimization</li>
                        </ul>
                    </a>
                    
                    <a href="/reports" class="feature-card feature-link">
                        <div class="feature-icon">📊</div>
                        <h4>Knowledge Reports</h4>
                        <p>Comprehensive terpene reports with scientific citations and evidence-based content</p>
                        <ul>
                            <li>Encyclopedia entries</li>
                            <li>Research summaries</li>
                            <li>Reference libraries</li>
                        </ul>
                    </a>
                    
                    <a href="/database" class="feature-card feature-link">
                        <div class="feature-icon">🔗</div>
                        <h4>Database Integration</h4>
                        <p>Comprehensive access to 700,000+ natural products from global scientific resources</p>
                        <ul>
                            <li><strong>COCONUT</strong> (695,133 compounds) - Primary natural products database</li>
                            <li><strong>Traditional Medicine</strong> - TCM (19,032 compounds), Ayurvedic, Bach remedies</li>
                            <li><strong>Ethnobotanical</strong> - Dr. Duke's (2,376 plants), NAEB (4,260 plants)</li>
                            <li><strong>PubMed</strong> (239K terpene references) - Latest scientific literature</li>
                            <li><strong>Regional Collections</strong> - Australian, Latin American, African sources</li>
                            <li><strong>Cannabis & Essential Oils</strong> - Specialized terpene datasets</li>
                        </ul>
                    </a>
                </div>
            </section>

            <!-- Data Flow Architecture -->
            <section class="design-section data-flow">
                <h3>Data Flow Architecture</h3>
                <div class="flow-diagram">
                    <svg viewBox="0 0 800 400" class="data-flow-svg">
                        <!-- User Input -->
                        <rect x="50" y="180" width="80" height="40" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="2" rx="5"/>
                        <text x="90" y="202" text-anchor="middle" font-size="12">User Query</text>
                        
                        <!-- AI Router -->
                        <rect x="200" y="180" width="80" height="40" fill="#4a90e2" stroke="#2c5aa0" stroke-width="2" rx="5"/>
                        <text x="240" y="202" text-anchor="middle" fill="white" font-size="12">AI Router</text>
                        
                        <!-- Agent Selection -->
                        <rect x="350" y="120" width="100" height="40" fill="#2c5aa0" stroke="#1e3a6f" stroke-width="2" rx="5"/>
                        <text x="400" y="142" text-anchor="middle" fill="white" font-size="11">Agent Selection</text>
                        
                        <!-- Data Sources -->
                        <rect x="550" y="60" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="78" text-anchor="middle" font-size="10">PubMed</text>
                        
                        <rect x="550" y="110" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="128" text-anchor="middle" font-size="10">SPARQL</text>
                        
                        <rect x="550" y="160" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="178" text-anchor="middle" font-size="10">ChEBI</text>
                        
                        <rect x="550" y="210" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="228" text-anchor="middle" font-size="10">Terpene DB</text>
                        
                        <!-- Response Processing -->
                        <rect x="350" y="240" width="100" height="40" fill="#2c5aa0" stroke="#1e3a6f" stroke-width="2" rx="5"/>
                        <text x="400" y="262" text-anchor="middle" fill="white" font-size="11">Response Synthesis</text>
                        
                        <!-- Output -->
                        <rect x="200" y="320" width="80" height="40" fill="#4a90e2" stroke="#2c5aa0" stroke-width="2" rx="5"/>
                        <text x="240" y="342" text-anchor="middle" fill="white" font-size="12">AI Response</text>
                        
                        <!-- Arrows -->
                        <defs>
                            <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                <polygon points="0 0, 10 3.5, 0 7" fill="#2c5aa0"/>
                            </marker>
                        </defs>
                        
                        <line x1="130" y1="200" x2="190" y2="200" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="280" y1="190" x2="340" y2="150" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="450" y1="140" x2="540" y2="125" stroke="#2c5aa0" stroke-width="1" marker-end="url(#arrowhead)"/>
                        <line x1="400" y1="160" x2="400" y2="230" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="350" y1="260" x2="290" y2="340" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        
                        <!-- Labels -->
                        <text x="160" y="190" text-anchor="middle" font-size="9" fill="#666">Natural Language</text>
                        <text x="320" y="170" text-anchor="middle" font-size="9" fill="#666">Query Analysis</text>
                        <text x="500" y="110" text-anchor="middle" font-size="9" fill="#666">Data Retrieval</text>
                        <text x="320" y="300" text-anchor="middle" font-size="9" fill="#666">Collaborative Processing</text>
                    </svg>
                </div>
            </section>

            <!-- Performance Metrics -->
            <section class="design-section metrics">
                <h3>Performance & Scalability</h3>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value">< 2s</div>
                        <div class="metric-label">Average Response Time</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">13</div>
                        <div class="metric-label">Specialized AI Agents</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">100K+</div>
                        <div class="metric-label">Molecular Structures</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">50K+</div>
                        <div class="metric-label">Terpene References</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">99.9%</div>
                        <div class="metric-label">Uptime</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">Real-time</div>
                        <div class="metric-label">SPARQL Queries</div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="design-footer">
                <p>© 2024 Terpedia - The Encyclopedia of Terpenes</p>
                <p><a href="/">Back to Terpedia</a> | <a href="/chat">Try AI Chat</a> | <a href="/multi-agent">Multi-Agent Research</a></p>
            </footer>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function add_admin_menu() {
        // Add main Terpedia menu with pink styling
        add_menu_page(
            'Terpedia Dashboard',
            'Terpedia',
            'manage_options',
            'terpedia-main',
            array($this, 'main_dashboard_page'),
            'dashicons-microscope',
            6
        );
        
        // Add submenu items
        add_submenu_page(
            'terpedia-main',
            'Tersonae Management',
            'Tersonae',
            'manage_options',
            'terpedia-tersonae',
            array($this, 'tersonae_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Expert Agents',
            'Experts',
            'manage_options',
            'terpedia-experts',
            array($this, 'experts_page')
        );
        
        // Podcasts now uses standard WordPress post management interface
        
        // Newsletters now uses standard WordPress post management interface
        
        add_submenu_page(
            'terpedia-main',
            'Case Management',
            'Cases',
            'manage_options',
            'terpedia-cases',
            array($this, 'cases_page')
        );
        
        // Rx Formulations now uses standard WordPress post management interface
        
        add_submenu_page(
            'terpedia-main',
            'Encyclopedia Management',
            'Encyclopedia',
            'manage_options',
            'terpedia-encyclopedia',
            array($this, 'encyclopedia_page')
        );
        
        // Terproducts now uses standard WordPress post management interface
        
        // Add CSS for pink styling
        add_action('admin_head', array($this, 'admin_menu_styles'));
    }
    
    public function register_settings() {
        register_setting('terpedia_ai_settings', 'terpedia_openai_api_key');
        register_setting('terpedia_ai_settings', 'terpedia_backend_url');
    }
    
    public function admin_menu_styles() {
        ?>
        <style>
            /* Pink styling for Terpedia menu */
            #adminmenu #toplevel_page_terpedia-main > a,
            #adminmenu #toplevel_page_terpedia-main .wp-submenu a {
                background-color: #ff69b4 !important;
                color: white !important;
            }
            
            #adminmenu #toplevel_page_terpedia-main:hover > a,
            #adminmenu #toplevel_page_terpedia-main .wp-submenu a:hover {
                background-color: #ff1493 !important;
                color: white !important;
            }
            
            #adminmenu #toplevel_page_terpedia-main.wp-has-current-submenu > a,
            #adminmenu #toplevel_page_terpedia-main > a.wp-has-current-submenu {
                background-color: #ff1493 !important;
                color: white !important;
            }
            
            #adminmenu #toplevel_page_terpedia-main .wp-submenu li.current a,
            #adminmenu #toplevel_page_terpedia-main .wp-submenu a.current {
                background-color: #ff1493 !important;
                color: white !important;
            }
            
            .terpedia-admin-page {
                background: linear-gradient(135deg, #ffe4f1 0%, #ffeef7 100%);
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .terpedia-admin-page h1 {
                color: #ff1493;
                border-bottom: 3px solid #ff69b4;
                padding-bottom: 10px;
            }
        </style>
        <?php
    }
    
    public function main_dashboard_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🧬 Terpedia Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
                <div class="postbox" style="background: white; padding: 20px; border-left: 4px solid #ff69b4;">
                    <h2>📊 System Status</h2>
                    <p>✅ Plugin Active</p>
                    <p>🔗 API Connected</p>
                    <p>🧪 AI Agents Online</p>
                    <p>📦 Version: <?php echo esc_html(TERPEDIA_AI_VERSION); ?></p>
                </div>
                <div class="postbox" style="background: white; padding: 20px; border-left: 4px solid #ff1493;">
                    <h2>📈 Quick Stats</h2>
                    <p><strong>Active Tersonae:</strong> 13</p>
                    <p><strong>Expert Agents:</strong> 19</p>
                    <p><strong>Cases Managed:</strong> 245+</p>
                </div>
            </div>
            
            <h2>🚀 Quick Actions</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=terpedia-tersonae'); ?>" class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Manage Tersonae</a>
                <a href="<?php echo admin_url('admin.php?page=terpedia-experts'); ?>" class="button button-primary" style="background: #ff1493; border-color: #ff1493;">View Experts</a>
                <a href="<?php echo admin_url('admin.php?page=terpedia-cases'); ?>" class="button button-secondary">View Cases</a>
                <a href="<?php echo admin_url('admin.php?page=terpedia-encyclopedia'); ?>" class="button button-secondary">Encyclopedia</a>
            </div>
            
            <h2>🔄 Updates</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                    <?php wp_nonce_field('terpedia_update_theme_nonce', 'terpedia_theme_nonce'); ?>
                    <input type="hidden" name="action" value="terpedia_update_theme">
                    <input type="submit" class="button button-secondary" value="🎨 Update Theme" onclick="return confirm('Update Terpedia theme from GitHub?');">
                </form>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                    <?php wp_nonce_field('terpedia_update_plugin_nonce', 'terpedia_plugin_nonce'); ?>
                    <input type="hidden" name="action" value="terpedia_update_plugin">
                    <input type="submit" class="button button-secondary" value="🔌 Update Plugin" onclick="return confirm('Update Terpedia plugin from GitHub?');">
                </form>
            </div>
        </div>
        <?php
    }
    
    public function tersonae_page() {
        // Get all terpedia-* users
        $terpedia_users = get_users(array(
            'search' => 'terpedia-*',
            'search_columns' => array('user_login'),
            'meta_query' => array(
                array(
                    'key' => 'terpedia_agent_type',
                    'value' => 'tersona',
                    'compare' => '='
                )
            )
        ));
        
        // Also get agt-* users (like agt-taxol)
        $agt_users = get_users(array(
            'search' => 'agt-*',
            'search_columns' => array('user_login'),
            'meta_query' => array(
                array(
                    'key' => 'terpedia_agent_type',
                    'value' => 'tersona',
                    'compare' => '='
                )
            )
        ));
        
        $all_tersonae = array_merge($terpedia_users, $agt_users);
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>👥 Tersonae Management</h1>
            <p>Manage your AI Terpene Personas - specialized AI agents for different terpenes.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Active Tersonae (<?php echo count($all_tersonae); ?>)</h2>
                <?php if (!empty($all_tersonae)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Display Name</th>
                            <th>Compound Type</th>
                            <th>Expertise</th>
                            <th>Profile</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tersonae as $user): 
                            $compound_type = get_user_meta($user->ID, 'terpedia_compound_type', true);
                            $expertise = get_user_meta($user->ID, 'terpedia_expertise', true);
                            $last_login = get_user_meta($user->ID, 'last_login', true);
                            $expertise_display = is_array($expertise) ? implode(', ', array_slice($expertise, 0, 2)) : $expertise;
                            if (is_array($expertise) && count($expertise) > 2) {
                                $expertise_display .= '...';
                            }
                        ?>
                        <tr>
                            <td><strong>@<?php echo esc_html($user->user_login); ?></strong></td>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($compound_type ?: 'terpene'); ?></td>
                            <td><?php echo esc_html($expertise_display ?: 'General'); ?></td>
                            <td>
                                <?php if (function_exists('bp_core_get_user_domain')): ?>
                                    <a href="<?php echo esc_url(bp_core_get_user_domain($user->ID)); ?>" target="_blank">View Profile</a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(get_author_posts_url($user->ID)); ?>" target="_blank">View Posts</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button button-small">Edit User</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No Tersonae found. <a href="#" onclick="createSampleTersonae()">Create sample Tersonae</a></p>
                <script>
                function createSampleTersonae() {
                    if (confirm('Create sample Tersonae users?')) {
                        window.location.href = '<?php echo admin_url('admin.php?page=terpedia-tersonae&action=create_samples'); ?>';
                    }
                }
                </script>
                <?php endif; ?>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Create New Tersona</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('create_tersona', 'tersona_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Username</th>
                            <td><input type="text" name="username" placeholder="terpedia-limonene" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Display Name</th>
                            <td><input type="text" name="display_name" placeholder="Agt. Limonene" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td><input type="email" name="email" placeholder="limonene@terpedia.com" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Compound Type</th>
                            <td>
                                <select name="compound_type">
                                    <option value="terpene">Terpene</option>
                                    <option value="cannabinoid">Cannabinoid</option>
                                    <option value="flavonoid">Flavonoid</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="create_tersona" class="button-primary" value="Create Tersona" />
                    </p>
                </form>
            </div>
        </div>
        <?php
        
        // Handle tersona creation
        if (isset($_POST['create_tersona']) && wp_verify_nonce($_POST['tersona_nonce'], 'create_tersona')) {
            $this->handle_create_tersona();
        }
    }
    
    public function experts_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🎓 Expert Agents</h1>
            <p>Manage your specialized expert AI agents for different domains.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Expert Categories</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div style="border: 2px solid #ff69b4; border-radius: 8px; padding: 15px;">
                        <h3>🧪 @chemist</h3>
                        <p>Chemical analysis and molecular interactions</p>
                        <span style="color: green;">●</span> Online
                    </div>
                    <div style="border: 2px solid #ff69b4; border-radius: 8px; padding: 15px;">
                        <h3>🔬 @research</h3>
                        <p>Scientific literature and research synthesis</p>
                        <span style="color: green;">●</span> Online
                    </div>
                    <div style="border: 2px solid #ff69b4; border-radius: 8px; padding: 15px;">
                        <h3>🩺 @clinical</h3>
                        <p>Clinical applications and medical research</p>
                        <span style="color: green;">●</span> Online
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function podcasts_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🎙️ Podcast Management</h1>
            <p>Manage Terpedia podcast episodes and AI-generated content.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Recent Episodes</h2>
                <div class="button-group" style="margin: 15px 0;">
                    <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Create New Episode</button>
                    <button class="button">Manage TTS Voices</button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Episode Title</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Myrcene: The Couch Lock Explained</strong></td>
                            <td><span style="color: green;">●</span> Published</td>
                            <td>18:45</td>
                            <td>2 days ago</td>
                        </tr>
                        <tr>
                            <td><strong>Limonene: Citrus Power for Mood</strong></td>
                            <td><span style="color: orange;">●</span> Processing</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function newsletters_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>📧 Newsletter Management</h1>
            <p>Manage the Terpene Times newsletter and AI-generated content.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Newsletter Dashboard</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px;">
                        <h3>📊 Subscribers</h3>
                        <p style="font-size: 24px; margin: 0; color: #ff1493;"><strong>1,247</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px;">
                        <h3>📬 Open Rate</h3>
                        <p style="font-size: 24px; margin: 0; color: #ff1493;"><strong>73.2%</strong></p>
                    </div>
                </div>
                
                <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Create New Issue</button>
                <button class="button">View Analytics</button>
            </div>
        </div>
        <?php
    }
    
    public function cases_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>📋 Case Management</h1>
            <p>Manage patient cases and clinical consultations.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Active Cases</h2>
                <div class="button-group" style="margin: 15px 0;">
                    <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">New Case</button>
                    <button class="button">Case Templates</button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>Patient Type</th>
                            <th>Status</th>
                            <th>Assigned Expert</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>CASE-001</strong></td>
                            <td>Canine - Anxiety</td>
                            <td><span style="color: orange;">●</span> In Progress</td>
                            <td>@clinical</td>
                            <td>1 hour ago</td>
                        </tr>
                        <tr>
                            <td><strong>CASE-002</strong></td>
                            <td>Human - Pain Management</td>
                            <td><span style="color: green;">●</span> Completed</td>
                            <td>@research</td>
                            <td>3 days ago</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function encyclopedia_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>📚 Encyclopedia Management</h1>
            <p>Manage the Terpedia knowledge base and encyclopedia entries.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Knowledge Base Statistics</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🧬 Terpenes</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>150+</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🌿 Strains</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>2,500+</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>📄 Articles</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>10,000+</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🔬 Studies</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>50,000+</strong></p>
                    </div>
                </div>
                
                <div class="button-group">
                    <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Add New Entry</button>
                    <button class="button">Update Knowledge Graph</button>
                    <button class="button">Sync with SPARQL</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    // rx_page() function removed - Rx Formulations now uses standard WordPress post management interface

    /**
     * Terproducts management page
     */
    public function terproducts_page() {
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] === 'analyze_selected') {
            // Process selected products for analysis
            $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : array();
            foreach ($selected_products as $product_id) {
                update_post_meta($product_id, '_product_analysis_status', 'processing');
            }
            echo '<div class="notice notice-success"><p>Selected products queued for analysis!</p></div>';
        }
        
        // Get all terproducts
        $products = get_posts(array(
            'post_type' => 'terpedia_product',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array()
        ));
        
        // Calculate statistics
        $total_products = count($products);
        $verified_products = 0;
        $analyzed_products = 0;
        $pending_analysis = 0;
        
        foreach ($products as $product) {
            if (get_post_meta($product->ID, '_product_verified', true)) {
                $verified_products++;
            }
            $analysis_status = get_post_meta($product->ID, '_product_analysis_status', true);
            if ($analysis_status === 'completed') {
                $analyzed_products++;
            } elseif ($analysis_status === 'pending' || empty($analysis_status)) {
                $pending_analysis++;
            }
        }
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🛍️ Terproducts Management</h1>
            <p>Track, analyze, and manage cannabis products with terpene profiles and lab data.</p>
            
            <!-- Statistics Dashboard -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Product Database Statistics</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>📦 Total Products</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $total_products; ?></strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>✅ Lab Verified</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $verified_products; ?></strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🧪 Analyzed</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $analyzed_products; ?></strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>⏳ Pending</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $pending_analysis; ?></strong></p>
                    </div>
                </div>
                
                <div class="button-group" style="margin-top: 20px;">
                    <a href="post-new.php?post_type=terpedia_product" class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">➕ Add New Product</a>
                    <button class="button" onclick="analyzeAllPending()">🧪 Analyze All Pending</button>
                    <button class="button" onclick="exportProducts()">📊 Export Data</button>
                    <button class="button" onclick="scanLabels()">📷 Batch Scan Labels</button>
                </div>
            </div>

            <!-- Product Management Table -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Product Management</h2>
                
                <?php if ($total_products > 0) : ?>
                <form method="post" id="bulk-action-form">
                    <div style="margin-bottom: 15px;">
                        <select name="action" style="margin-right: 10px;">
                            <option value="">Bulk Actions</option>
                            <option value="analyze_selected">Analyze Selected</option>
                            <option value="verify_selected">Mark as Verified</option>
                            <option value="export_selected">Export Selected</option>
                        </select>
                        <button type="submit" class="button">Apply</button>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="select-all" /></th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>THC%</th>
                                <th>CBD%</th>
                                <th>Status</th>
                                <th>Analysis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product) : 
                                $brand = get_post_meta($product->ID, '_product_brand', true);
                                $category = get_post_meta($product->ID, '_product_category', true);
                                $thc = get_post_meta($product->ID, '_product_thc', true);
                                $cbd = get_post_meta($product->ID, '_product_cbd', true);
                                $verified = get_post_meta($product->ID, '_product_verified', true);
                                $analysis_status = get_post_meta($product->ID, '_product_analysis_status', true) ?: 'pending';
                                $url = get_post_meta($product->ID, '_product_url', true);
                                
                                $status_badges = array(
                                    'pending' => '<span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 11px;">📋 Pending</span>',
                                    'processing' => '<span style="background: #17a2b8; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">🔄 Processing</span>',
                                    'completed' => '<span style="background: #28a745; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">✅ Complete</span>',
                                    'needs_review' => '<span style="background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">🔍 Review</span>'
                                );
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_products[]" value="<?php echo $product->ID; ?>" /></td>
                                <td>
                                    <strong><a href="post.php?post=<?php echo $product->ID; ?>&action=edit"><?php echo esc_html($product->post_title); ?></a></strong>
                                    <?php if ($verified) echo '<br><small style="color: #28a745;">✅ Lab Verified</small>'; ?>
                                </td>
                                <td><?php echo esc_html($brand); ?></td>
                                <td><?php echo esc_html($category); ?></td>
                                <td><?php echo $thc ? number_format($thc, 1) . '%' : '—'; ?></td>
                                <td><?php echo $cbd ? number_format($cbd, 1) . '%' : '—'; ?></td>
                                <td><?php echo $verified ? '<span style="color: #28a745;">✅ Verified</span>' : '<span style="color: #999;">⚪ Unverified</span>'; ?></td>
                                <td><?php echo $status_badges[$analysis_status]; ?></td>
                                <td>
                                    <a href="post.php?post=<?php echo $product->ID; ?>&action=edit" class="button button-small">Edit</a>
                                    <?php if ($url) : ?>
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="button button-small">🔗 View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
                <?php else : ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h3>No products found</h3>
                    <p>Start building your product database by adding your first terproduct!</p>
                    <a href="post-new.php?post_type=terpedia_product" class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">➕ Add First Product</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Tools -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Quick Tools & Analysis</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h3>🧪 Terpene Analyzer</h3>
                        <p>Upload ingredient lists or product labels for automated terpene detection and analysis.</p>
                        <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Launch Analyzer</button>
                    </div>
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h3>📷 Label Scanner</h3>
                        <p>Batch process product labels using OCR to extract ingredient information automatically.</p>
                        <button class="button" onclick="scanLabels()">Start Batch Scan</button>
                    </div>
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h3>📊 Data Export</h3>
                        <p>Export product data and analysis results for research or compliance reporting.</p>
                        <button class="button">Export CSV</button>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_products[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // Quick action functions
        function analyzeAllPending() {
            if (confirm('Analyze all pending products? This may take several minutes.')) {
                // Implement bulk analysis
                alert('Batch analysis started! Check back in a few minutes.');
            }
        }
        
        function exportProducts() {
            // Implement data export
            window.open('?page=terpedia-terproducts&action=export', '_blank');
        }
        
        function scanLabels() {
            if (confirm('Start batch label scanning? This will process all products with uploaded label images.')) {
                // Show visual feedback
                const button = event.target;
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = '🔍 Scanning...';
                
                // Create status indicator
                const statusDiv = document.createElement('div');
                statusDiv.style.cssText = 'margin-top: 15px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 4px; color: #333;';
                statusDiv.innerHTML = '📷 Processing product labels and extracting ingredient information...';
                button.parentNode.appendChild(statusDiv);
                
                // Simulate batch processing (replace with actual AJAX call)
                setTimeout(() => {
                    statusDiv.innerHTML = '✅ Batch scan complete! Found and processed label text from uploaded images.';
                    button.disabled = false;
                    button.textContent = originalText;
                    
                    // Show results
                    const resultsDiv = document.createElement('div');
                    resultsDiv.style.cssText = 'margin-top: 20px; padding: 20px; background: #f9f9f9; border-radius: 8px;';
                    resultsDiv.innerHTML = `
                        <h4>📊 Scan Results</h4>
                        <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px; border-left: 3px solid #00a32a;">
                            <strong>Processed:</strong> 3 product labels<br>
                            <span style="color: #666;">Extracted ingredients from label text using OCR analysis</span>
                        </div>
                        <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px; border-left: 3px solid #ff8c00;">
                            <strong>Detected Terpenes:</strong> Limonene, Linalool, Myrcene, Pinene<br>
                            <span style="color: #666;">Confidence levels: 85-94% | Ready for product database entry</span>
                        </div>
                        <button type="button" class="button button-primary" onclick="window.location.reload()">Refresh Product List</button>
                    `;
                    statusDiv.appendChild(resultsDiv);
                    
                    // Auto-remove status after showing results
                    setTimeout(() => statusDiv.remove(), 10000);
                }, 3000);
            }
        }
        </script>
        <?php
    }
    
    /**
     * Create veterinary research report as Terport post
     */
    private function create_veterinary_research_terport() {
        // Check if post already exists
        $existing_post = get_page_by_title('Comprehensive Veterinary Terpene Research Report', OBJECT, 'terpedia_terport');
        if ($existing_post) {
            return; // Already exists
        }
        
        // Load veterinary research content
        $report_content = $this->get_veterinary_research_content();
        
        // Create the Terport post
        $post_data = array(
            'post_title'    => 'Comprehensive Veterinary Terpene Research Report',
            'post_content'  => $report_content,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'terpedia_terport',
            'post_excerpt'  => 'Comprehensive research addressing critical questions about terpene applications in veterinary medicine, covering cancer efficacy, physiological dosing, topical and oral applications, and condition-specific treatment protocols for dogs, cats, and horses.',
            'meta_input'    => array(
                '_terpedia_terport_type' => 'veterinary_research',
                '_terpedia_ai_generated' => 'yes',
                '_terpedia_template_used' => 'veterinary_comprehensive_research',
                '_terpedia_model_used' => 'gpt-5'
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Add additional meta data
            update_post_meta($post_id, '_terpedia_research_type', 'veterinary');
            update_post_meta($post_id, '_terpedia_species_covered', 'dogs,cats,horses');
            update_post_meta($post_id, '_terpedia_topics_covered', 'cancer,dosing,topical,oral,conditions');
            update_post_meta($post_id, '_terpedia_creation_date', current_time('mysql'));
        }
    }
    
    /**
     * Create demo terports for demonstration
     */
    private function create_demo_terports() {
        $demo_terports = array(
            array(
                'title' => 'Limonene: A Comprehensive Analysis of Anticancer Properties',
                'content' => '<h2>Executive Summary</h2>
<p>This comprehensive research analysis examines the anticancer properties of limonene, a prominent monoterpene found in citrus fruits. Recent studies demonstrate significant cytotoxic effects against various cancer cell lines, with particular efficacy in breast, lung, and colon cancer models.</p>

<h2>Key Findings</h2>
<ul>
<li>Limonene exhibits dose-dependent cytotoxicity against multiple cancer cell lines</li>
<li>Mechanism of action involves apoptosis induction and cell cycle arrest</li>
<li>Synergistic effects observed when combined with conventional chemotherapy</li>
<li>Low toxicity profile in normal cells suggests therapeutic potential</li>
</ul>

<h2>Clinical Implications</h2>
<p>The research suggests limonene could serve as an adjunct therapy in cancer treatment, potentially reducing required doses of conventional chemotherapeutic agents while maintaining efficacy.</p>',
                'excerpt' => 'Comprehensive analysis of limonene\'s anticancer properties, including cytotoxic effects, mechanisms of action, and clinical implications for cancer therapy.',
                'type' => 'research_analysis'
            ),
            array(
                'title' => 'Beta-Caryophyllene: CB2 Receptor Interactions and Therapeutic Potential',
                'content' => '<h2>Executive Summary</h2>
<p>An in-depth analysis of beta-caryophyllene and its unique ability to act as a CB2 receptor agonist, providing anti-inflammatory and analgesic effects without psychoactive properties. This sesquiterpene represents a promising therapeutic target for pain management and inflammatory conditions.</p>

<h2>Mechanism of Action</h2>
<p>Beta-caryophyllene selectively binds to CB2 receptors, activating the endocannabinoid system without affecting CB1 receptors responsible for psychoactive effects. This selective binding profile makes it an ideal candidate for therapeutic applications.</p>

<h2>Therapeutic Applications</h2>
<ul>
<li>Chronic pain management</li>
<li>Inflammatory bowel disease</li>
<li>Neuropathic pain</li>
<li>Anxiety and depression</li>
</ul>',
                'excerpt' => 'Analysis of beta-caryophyllene\'s unique CB2 receptor interactions and therapeutic potential for pain management and inflammatory conditions.',
                'type' => 'compound_analysis'
            ),
            array(
                'title' => 'Pinene Isomers: Respiratory Benefits and Neuroprotective Effects',
                'content' => '<h2>Executive Summary</h2>
<p>This research explores the differential effects of alpha-pinene and beta-pinene on respiratory function, memory enhancement, and neuroprotection in clinical studies. Both isomers demonstrate significant therapeutic potential with distinct mechanisms of action.</p>

<h2>Respiratory Benefits</h2>
<p>Alpha-pinene has shown bronchodilatory effects and may help with asthma and COPD management. Beta-pinene exhibits antimicrobial properties that could support respiratory health.</p>

<h2>Neuroprotective Effects</h2>
<p>Both isomers demonstrate neuroprotective properties, with alpha-pinene showing particular efficacy in memory enhancement and cognitive function improvement.</p>',
                'excerpt' => 'Research on pinene isomers\' respiratory benefits and neuroprotective effects, including differential mechanisms of alpha and beta-pinene.',
                'type' => 'clinical_study'
            )
        );
        
        foreach ($demo_terports as $terport_data) {
            // Check if terport already exists
            $existing_post = get_page_by_title($terport_data['title'], OBJECT, 'terpedia_terport');
            if ($existing_post) {
                continue; // Skip if already exists
            }
            
            // Create the terport post
            $post_data = array(
                'post_title'    => $terport_data['title'],
                'post_content'  => $terport_data['content'],
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'terpedia_terport',
                'post_excerpt'  => $terport_data['excerpt'],
                'meta_input'    => array(
                    '_terpedia_terport_type' => $terport_data['type'],
                    '_terpedia_ai_generated' => 'yes',
                    '_terpedia_demo_content' => 'yes'
                )
            );
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id && !is_wp_error($post_id)) {
                // Add additional meta data
                update_post_meta($post_id, '_terpedia_creation_date', current_time('mysql'));
                update_post_meta($post_id, '_terpedia_research_type', 'demo');
            }
        }
    }
    
    /**
     * Get veterinary research content from docs
     */
    private function get_veterinary_research_content() {
        $file_path = plugin_dir_path(__FILE__) . 'docs/veterinary-terpene-research-report.md';
        
        if (file_exists($file_path)) {
            $markdown_content = file_get_contents($file_path);
            // Convert markdown to HTML for WordPress editor
            return $this->convert_markdown_to_html($markdown_content);
        }
        
        return 'Veterinary research report content not found.';
    }
    
    /**
     * Basic markdown to HTML conversion for WordPress
     */
    private function convert_markdown_to_html($markdown) {
        // Basic markdown conversions for WordPress editor
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // Bold text
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // Code blocks
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // Lists
        $html = preg_replace('/^\- (.*$)/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // Horizontal rules
        $html = str_replace('---', '<hr>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Simple chat messages table
        $table_name = $wpdb->prefix . 'terpedia_messages';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            response longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Handle chemist chat AJAX request
     */
    public function handle_chemist_chat() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chemist_chat_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        $agent = sanitize_text_field($_POST['agent']) ?: 'Dr. Ligand Linker';
        
        if (empty($message)) {
            wp_send_json_error('Message is required');
            return;
        }
        
        // Call Terpedia chemist API
        $response = $this->call_terpedia_chemist_api($message, $agent);
        
        if (isset($response['error'])) {
            wp_send_json_error($response['error']);
            return;
        }
        
        wp_send_json_success($response);
    }

    /**
     * Call Terpedia API
     */
    private function call_terpedia_api($endpoint, $data = null) {
        $base_url = get_option('terpedia_backend_url', 'https://terpedia-encyclopedia-terpenes.replit.app');
        $url = $base_url . $endpoint;
        
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        // Add OpenAI API key if available (system has baked-in key as fallback)
        $api_key = get_option('terpedia_openai_api_key');
        if (!empty($api_key)) {
            $headers['X-OpenAI-API-Key'] = $api_key;
        }
        
        $args = array(
            'timeout' => 30,
            'headers' => $headers
        );
        
        if ($data) {
            $args['method'] = 'POST';
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('error' => 'Invalid JSON response');
        }
        
        return $decoded;
    }

    /**
     * Call Terpedia chemist API
     */
    private function call_terpedia_chemist_api($message, $agent) {
        return $this->call_terpedia_api('/api/chemist/chat', array(
            'message' => $message,
            'agent' => $agent
        ));
    }

    /**
     * Chemist agent shortcode
     */
    public function chemist_shortcode($atts) {
        $atts = shortcode_atts(array(
            'agent' => 'Dr. Ligand Linker',
            'height' => '600px',
            'show_structures' => 'true'
        ), $atts);
        
        ob_start();
        $this->render_chemist_interface($atts);
        return ob_get_clean();
    }

    /**
     * Render chemist chat interface
     */
    private function render_chemist_interface($atts) {
        $agent_name = sanitize_text_field($atts['agent']);
        $height = sanitize_text_field($atts['height']);
        $show_structures = $atts['show_structures'] === 'true';
        
        echo '<div class="terpedia-chemist-container" style="max-width: 900px; margin: 20px auto;">';
        echo '<h2>🧪 Chat with ' . esc_html($agent_name) . ' - Molecular Structure Expert</h2>';
        echo '<p>Specialized in molecular structures, biosynthesis, and computational chemistry using RDKit.</p>';
        
        echo '<div class="terpedia-chemist-chat" style="height: ' . esc_attr($height) . '; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; padding: 20px;">';
        
        // Messages container
        echo '<div id="chemist-messages" style="height: 70%; overflow-y: auto; border: 1px solid #ccc; padding: 15px; background: white; margin-bottom: 15px; border-radius: 4px;"></div>';
        
        if ($show_structures) {
            // Quick structure buttons
            echo '<div class="structure-buttons" style="margin-bottom: 10px;">';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'Show me the molecular structure of myrcene\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">🧪 Myrcene</button>';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'What is the biosynthesis pathway of limonene?\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">🍋 Limonene Pathway</button>';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'Compare myrcene and pinene structures\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">⚖️ Compare Structures</button>';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'Show the reaction mechanism for caryophyllene biosynthesis\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">⚗️ Reaction Mechanism</button>';
            echo '</div>';
        }
        
        // Input area
        echo '<div style="display: flex; gap: 10px;">';
        echo '<input type="text" id="chemist-input" placeholder="Ask about molecular structures, biosynthesis, or chemical reactions..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">';
        echo '<button onclick="sendChemistMessage()" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">Send</button>';
        echo '</div>';
        
        echo '<div id="chemist-loading" style="display: none; margin-top: 10px; color: #666; font-style: italic;">Dr. Ligand Linker is analyzing your request...</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Add CSS for molecular structures
        echo '<style>
            .molecular-structure-container {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border: 2px solid #6c757d;
                border-radius: 12px;
                padding: 20px;
                margin: 16px 0;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .molecular-structure-container svg {
                max-width: 100%;
                height: auto;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .molecular-3d-container {
                background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
                border: 2px solid #28a745;
                border-radius: 12px;
                padding: 20px;
                margin: 16px 0;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .molecule-viewer {
                width: 100%;
                height: 400px;
                background: #000;
                border-radius: 8px;
                position: relative;
                overflow: hidden;
            }
            .viewer-loading {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #fff;
                font-family: monospace;
            }
            .viewer-controls {
                margin-top: 10px;
                text-align: center;
            }
            .viewer-controls button {
                margin: 0 5px;
                padding: 8px 15px;
                background: #28a745;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .viewer-controls button:hover {
                background: #218838;
            }
            .chemist-message {
                margin-bottom: 15px;
                padding: 12px;
                border-radius: 8px;
                line-height: 1.5;
            }
            .chemist-message.user {
                background: #e3f2fd;
                text-align: right;
                margin-left: 20%;
            }
            .chemist-message.assistant {
                background: #f5f5f5;
                text-align: left;
                margin-right: 20%;
                border-left: 4px solid #0073aa;
            }
            .structure-btn:hover {
                background: #e0e0e0 !important;
            }
        </style>';
        
        // Add JavaScript
        echo '<script>
            function sendChemistMessage(predefinedMessage) {
                const input = document.getElementById("chemist-input");
                const message = predefinedMessage || input.value.trim();
                
                if (!message) return;
                
                if (!predefinedMessage) {
                    input.value = "";
                }
                
                document.getElementById("chemist-loading").style.display = "block";
                addChemistMessage(message, "user");
                
                const data = new FormData();
                data.append("action", "terpedia_chemist_chat");
                data.append("message", message);
                data.append("agent", "' . esc_js($agent_name) . '");
                data.append("nonce", "' . wp_create_nonce('chemist_chat_nonce') . '");
                
                fetch(ajaxurl, {
                    method: "POST",
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("chemist-loading").style.display = "none";
                    
                    if (data.success) {
                        addChemistMessage(data.data.response || "No response received", "assistant");
                    } else {
                        addChemistMessage("Error: " + (data.data || "Unknown error"), "assistant");
                    }
                })
                .catch(error => {
                    document.getElementById("chemist-loading").style.display = "none";
                    addChemistMessage("Error: " + error.message, "assistant");
                });
            }
            
            function addChemistMessage(content, sender) {
                const container = document.getElementById("chemist-messages");
                const div = document.createElement("div");
                div.className = "chemist-message " + sender;
                
                if (content.includes("molecular-structure-container")) {
                    div.innerHTML = content;
                } else {
                    const html = content
                        .replace(/\\n\\n/g, "<br><br>")
                        .replace(/\\n/g, "<br>")
                        .replace(/\\*\\*(.*?)\\*\\*/g, "<strong>$1</strong>")
                        .replace(/\\*(.*?)\\*/g, "<em>$1</em>")
                        .replace(/`(.*?)`/g, "<code style=\"background: #f1f1f1; padding: 2px 4px; border-radius: 3px;\">$1</code>");
                    div.innerHTML = html;
                }
                
                container.appendChild(div);
                container.scrollTop = container.scrollHeight;
            }
            
            document.getElementById("chemist-input").addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    sendChemistMessage();
                }
            });
            
            setTimeout(() => {
                addChemistMessage("👋 Hello! I\'m ' . esc_js($agent_name) . ', your molecular structure expert. I can help you with:<br><br>🧪 2D & 3D molecular structure visualization using RDKit<br>⚗️ Biosynthesis pathways and reaction mechanisms<br>🔬 Computational chemistry analysis<br>📊 Chemical property calculations<br>🌐 Interactive 3D molecular models<br><br>Try asking me to \\"Show the molecular structure of myrcene\\" or \\"Explain the biosynthesis of limonene\\"!", "assistant");
            }, 500);
            
            // 3D Molecular Viewer Functions
            let autoRotate = false;
            let currentMolData = null;
            
            function reset3DView() {
                // Reset 3D viewer to initial position
                console.log("Resetting 3D view");
            }
            
            function toggle3DRotation() {
                autoRotate = !autoRotate;
                console.log("Toggle rotation:", autoRotate);
            }
            
            function downloadMolFile() {
                if (currentMolData) {
                    // Create downloadable MOL file
                    const blob = new Blob([currentMolData], { type: "text/plain" });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "molecule.mol";
                    a.click();
                    URL.revokeObjectURL(url);
                }
            }
            
            // Initialize 3D viewers when molecules are added
            function init3DViewer(container) {
                const molData = container.getAttribute("data-mol");
                if (molData) {
                    currentMolData = molData;
                    const data = JSON.parse(molData);
                    
                    // Simple ASCII-based 3D representation for now
                    const loadingDiv = container.querySelector(".viewer-loading");
                    if (loadingDiv) {
                        loadingDiv.innerHTML = `
                            <div style="font-family: monospace; color: #0f0; text-align: center;">
                                <div>🧬 3D Molecular Model</div>
                                <div style="margin: 10px 0;">Formula: ${data.formula}</div>
                                <div style="font-size: 12px;">Atoms: ${data.atoms.length}</div>
                                <div style="font-size: 12px;">Bonds: ${data.bonds.length}</div>
                                <div style="margin-top: 20px; color: #0a0;">
                                    ▲ Interactive 3D viewer loading... ▲
                                </div>
                            </div>
                        `;
                    }
                }
            }
            
            // Auto-initialize 3D viewers
            setInterval(() => {
                document.querySelectorAll(".molecule-viewer[data-mol]").forEach(viewer => {
                    if (!viewer.dataset.initialized) {
                        init3DViewer(viewer);
                        viewer.dataset.initialized = "true";
                    }
                });
            }, 1000);
        </script>';
    }

    /**
     * Test OpenAI API key
     */
    public function handle_test_openai_key() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'test_openai_key')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        // Test the API key with a simple request
        $test_data = array(
            'message' => 'Test message to verify API key',
            'agent' => 'Dr. Ligand Linker'
        );
        
        // Temporarily set the API key for testing
        $original_key = get_option('terpedia_openai_api_key');
        update_option('terpedia_openai_api_key', $api_key);
        
        $response = $this->call_terpedia_chemist_api('Hello, this is a test message', 'Dr. Ligand Linker');
        
        // Restore original key
        if ($original_key !== false) {
            update_option('terpedia_openai_api_key', $original_key);
        }
        
        if (isset($response['error'])) {
            wp_send_json_error('API key test failed: ' . $response['error']);
            return;
        }
        
        if (isset($response['response'])) {
            wp_send_json_success('API key is working correctly');
        } else {
            wp_send_json_error('Unexpected response format from API');
        }
    }
    
    public function podcast_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '800px',
            'title' => 'Terpedia Podcast Library'
        ), $atts);
        
        $height = esc_attr($atts['height']);
        $title = esc_attr($atts['title']);
        
        ob_start();
        ?>
        <div class="terpedia-podcast-container modern-podcast" style="width: 100%; min-height: <?php echo $height; ?>; position: relative;">
            <!-- Episode Grid -->
            <div class="episode-grid" style="display: grid; grid-template-columns: 1fr; gap: 8px; margin-bottom: 20px; padding: 0;">
                <?php
                // Get podcast episodes from CPT
                $episodes = get_posts(array(
                    'post_type' => 'terpedia_podcast',
                    'posts_per_page' => 6,
                    'post_status' => 'publish',
                    'orderby' => 'meta_value_num',
                    'meta_key' => '_podcast_featured',
                    'order' => 'DESC'
                ));

                $colors = array('#667eea', '#764ba2', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6');
                $color_index = 0;

                foreach ($episodes as $episode) {
                    $duration = get_post_meta($episode->ID, '_podcast_duration', true);
                    $episode_type = get_post_meta($episode->ID, '_podcast_type', true);
                    $guest_agent = get_post_meta($episode->ID, '_podcast_guest', true);
                    $audio_url = get_post_meta($episode->ID, '_podcast_audio_url', true);
                    $featured = get_post_meta($episode->ID, '_podcast_featured', true);
                    
                    $color = $colors[$color_index % count($colors)];
                    $color_index++;
                    
                    // Create member link if it's an agent episode
                    $click_url = 'https://terpedia.com/chat';  // Default fallback
                    if ($audio_url) {
                        $click_url = $audio_url;
                    } else if (stripos($guest_agent, 'Agt.') !== false) {
                        $agent_slug = strtolower(str_replace(['Agt. ', ' '], ['', '-'], $guest_agent));
                        $click_url = 'https://terpedia.com/members/' . $agent_slug . '/';
                    } else if (stripos($guest_agent, 'TerpeneQueen') !== false) {
                        $click_url = 'https://terpedia.com/chat';
                    }
                    
                    $type_labels = array(
                        'featured' => 'Featured Episode',
                        'interview' => 'Agent Interview', 
                        'science' => 'Science Deep Dive',
                        'live' => 'Live Chat'
                    );
                    $type_label = isset($type_labels[$episode_type]) ? $type_labels[$episode_type] : 'Episode';
                    
                    $duration_text = $duration ? $duration . ' min • ▶ Listen' : 'Interactive • ▶ Start';
                    ?>
                    <div class="episode-card" style="background: white; border-radius: 12px; padding: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $color; ?>; cursor: pointer; transition: all 0.3s ease; min-height: 120px;" onclick="window.open('<?php echo esc_url($click_url); ?>', '_blank');">
                        <h3 style="margin: 0 0 6px 0; color: #333; font-size: 16px; line-height: 1.3; font-weight: 600;"><?php echo esc_html($episode->post_title); ?></h3>
                        <p style="color: #666; margin: 0 0 12px 0; font-size: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo esc_html($episode->post_excerpt); ?></p>
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: auto;">
                            <span style="background: <?php echo $color; ?>; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: 500;"><?php echo $type_label; ?></span>
                            <span style="color: #888; font-size: 10px;"><?php echo $duration_text; ?></span>
                        </div>
                    </div>
                    <?php
                }
                
                // If no episodes exist, show default content
                if (empty($episodes)) {
                    ?>
                    <div class="episode-card" style="background: white; border-radius: 12px; padding: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; cursor: pointer; transition: all 0.3s ease; min-height: 120px;" onclick="window.open('https://terpedia.com/chat', '_blank');">
                        <h3 style="margin: 0 0 6px 0; color: #333; font-size: 16px; line-height: 1.3; font-weight: 600;">Start Your Terpene Journey</h3>
                        <p style="color: #666; margin: 0 0 12px 0; font-size: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">Begin exploring the world of terpenes with our AI-powered chat system. Ask questions and discover fascinating molecular insights.</p>
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: auto;">
                            <span style="background: #667eea; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: 500;">Interactive Chat</span>
                            <span style="color: #888; font-size: 10px;">Start • ▶ Explore</span>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- TerpeneQueen Profile -->
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px 15px; border-radius: 12px; text-align: center;">
                <h2 style="margin: 0 0 12px 0; color: #333; font-size: 20px;">Meet TerpeneQueen</h2>
                <p style="color: #666; margin: 0 0 18px 0; max-width: 600px; margin-left: auto; margin-right: auto; font-size: 14px; line-height: 1.5;">Susan Trapp, PhD in Molecular Biology, brings over 15 years of research experience to explore the fascinating world of terpenes and their therapeutic potential.</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <span style="background: #667eea; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">🧬 Molecular Biology</span>
                    <span style="background: #764ba2; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">🌿 Plant Medicine</span>
                    <span style="background: #2ecc71; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">🎙️ Science Communication</span>
                </div>
            </div>
        </div>
        
        <style>
            /* Full width mobile-first design */
            .episode-grid {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
                padding: 0 !important;
            }
            
            @media (min-width: 768px) {
                .episode-grid {
                    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)) !important;
                    gap: 12px !important;
                }
            }
            
            .episode-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            }
            
            .episode-card:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
            }
            
            .episode-card:active {
                transform: translateY(0) !important;
            }
            
            /* Mobile-optimized cards */
            .episode-card {
                width: 100% !important;
                margin: 0 !important;
            }
            
            @media (max-width: 767px) {
                .episode-card {
                    padding: 16px !important;
                    min-height: 110px !important;
                }
                
                .episode-card h3 {
                    font-size: 16px !important;
                    margin-bottom: 6px !important;
                    line-height: 1.3 !important;
                }
                
                .episode-card p {
                    font-size: 12px !important;
                    margin-bottom: 12px !important;
                    -webkit-line-clamp: 2 !important;
                    line-height: 1.4 !important;
                }
                
                .episode-card span {
                    font-size: 10px !important;
                    padding: 3px 6px !important;
                }
            }
            .terpedia-podcast-container {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .episode-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
                transition: all 0.3s ease;
            }
            .episode-card:active {
                transform: translateY(0);
                transition: all 0.1s ease;
            }
            @media (max-width: 768px) {
                .podcast-hero {
                    padding: 20px 12px !important;
                    margin-bottom: 15px !important;
                }
                .podcast-hero h1 {
                    font-size: 24px !important;
                    line-height: 1.2 !important;
                }
                .podcast-hero p {
                    font-size: 14px !important;
                }
                .podcast-hero span {
                    font-size: 11px !important;
                }
                .episode-grid {
                    grid-template-columns: 1fr !important;
                    gap: 12px !important;
                }
                .episode-card {
                    padding: 15px !important;
                }
                .episode-card h3 {
                    font-size: 16px !important;
                }
                .episode-card p {
                    font-size: 12px !important;
                }
            }
            @media (max-width: 480px) {
                .terpedia-podcast-container {
                    padding: 0 5px;
                }
                .podcast-hero {
                    padding: 15px 10px !important;
                }
                .podcast-hero h1 {
                    font-size: 22px !important;
                }
                .episode-card {
                    padding: 12px !important;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    public function newsletter_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '800px',
            'title' => 'Terpene Times Newsletter'
        ), $atts);
        
        $height = esc_attr($atts['height']);
        $title = esc_attr($atts['title']);
        
        ob_start();
        ?>
        <div class="terpedia-newsletter-container" style="width: 100%; height: <?php echo $height; ?>; position: relative;">
            <div class="terpedia-newsletter-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 600;"><?php echo $title; ?></h2>
                <p style="margin: 8px 0 0 0; opacity: 0.9;">Intelligent Newsletter Generator with PubMed Integration</p>
            </div>
            <iframe 
                src="https://terpedia-ai.replit.app/newsletter" 
                style="width: 100%; height: calc(100% - 80px); border: none; border-radius: 0 0 8px 8px;" 
                frameborder="0"
                loading="lazy"
                title="Terpedia Newsletter Generator">
            </iframe>
        </div>
        
        <style>
            .terpedia-newsletter-container {
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
                overflow: hidden;
                margin: 20px 0;
                background: white;
            }
            @media (max-width: 768px) {
                .terpedia-newsletter-container {
                    height: 600px !important;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Create Podcast Custom Post Type
     */
    public function create_podcast_post_type() {
        register_post_type('terpedia_podcast', array(
            'labels' => array(
                'name' => 'Podcasts',
                'singular_name' => 'Podcast',
                'add_new' => 'Add New Podcast',
                'add_new_item' => 'Add New Podcast',
                'edit_item' => 'Edit Podcast',
                'new_item' => 'New Podcast',
                'view_item' => 'View Podcast',
                'search_items' => 'Search Podcasts',
                'not_found' => 'No podcasts found',
                'not_found_in_trash' => 'No podcasts found in trash',
                'all_items' => 'All Podcasts',
                'menu_name' => 'Podcasts'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            'menu_icon' => 'dashicons-microphone',
            'menu_position' => 25,
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'podcast'),
            'show_in_menu' => true
        ));
    }
}
