<?php
/**
 * Test script to verify automatic terport generation system
 */

// Load WordPress
if (file_exists('wp-config.php')) {
    require_once 'wp-config.php';
} else {
    // Try to find WordPress in parent directories
    $wp_load_file = null;
    $current_dir = __DIR__;
    
    for ($i = 0; $i < 5; $i++) {
        $path = $current_dir . '/wp-config.php';
        if (file_exists($path)) {
            $wp_load_file = $path;
            break;
        }
        $current_dir = dirname($current_dir);
    }
    
    if ($wp_load_file) {
        require_once $wp_load_file;
    } else {
        die("WordPress not found\n");
    }
}

// Test 1: Check if classes are loaded
echo "=== Terpedia Automatic Terport Generation System Test ===\n\n";

echo "1. Checking if classes are loaded...\n";
$classes_to_check = array(
    'Terpedia_Automatic_Terport_Generator',
    'Terpedia_Terport_Version_Tracker',
    'Terpedia_Terport_SPARQL_Integration',
    'Terpedia_Enhanced_Terport_Editor'
);

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "   ✓ $class: LOADED\n";
    } else {
        echo "   ✗ $class: NOT LOADED\n";
    }
}

echo "\n2. Checking WordPress hooks...\n";
$hooks_to_check = array(
    'terpedia_plugin_activated',
    'terpedia_plugin_updated',
    'terpedia_generate_terports_background'
);

foreach ($hooks_to_check as $hook) {
    $has_hook = has_action($hook);
    if ($has_hook) {
        echo "   ✓ $hook: REGISTERED\n";
    } else {
        echo "   ✗ $hook: NOT REGISTERED\n";
    }
}

echo "\n3. Checking plugin version...\n";
$current_version = defined('TERPEDIA_AI_VERSION') ? TERPEDIA_AI_VERSION : 'UNDEFINED';
echo "   Current Plugin Version: $current_version\n";

echo "\n4. Checking database tables...\n";
global $wpdb;

$tables_to_check = array(
    $wpdb->prefix . 'terpedia_terports',
    $wpdb->prefix . 'terpedia_conversations',
    $wpdb->prefix . 'terpedia_knowledge_base'
);

foreach ($tables_to_check as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    if ($table_exists) {
        echo "   ✓ $table: EXISTS\n";
    } else {
        echo "   ✗ $table: MISSING\n";
    }
}

echo "\n5. Checking terport post type...\n";
if (post_type_exists('terpedia_terport')) {
    echo "   ✓ terpedia_terport: REGISTERED\n";
    
    // Count existing terports
    $terport_count = wp_count_posts('terpedia_terport');
    echo "   Existing terports: " . ($terport_count->publish + $terport_count->draft) . "\n";
} else {
    echo "   ✗ terpedia_terport: NOT REGISTERED\n";
}

echo "\n6. Testing version tracking...\n";
if (class_exists('Terpedia_Terport_Version_Tracker')) {
    $version_tracker = new Terpedia_Terport_Version_Tracker();
    $last_version = $version_tracker->get_last_version();
    echo "   Last recorded version: " . ($last_version ?: 'None') . "\n";
    
    $should_generate = $version_tracker->should_generate_for_version();
    echo "   Should generate for current version: " . ($should_generate ? 'YES' : 'NO') . "\n";
    
    $stats = $version_tracker->get_generation_stats();
    echo "   Total generation events: " . $stats['total_generation_events'] . "\n";
    echo "   Successful generations: " . $stats['successful_generations'] . "\n";
} else {
    echo "   ✗ Version tracker not available\n";
}

echo "\n7. Testing SPARQL integration availability...\n";
if (class_exists('Terpedia_Terport_SPARQL_Integration')) {
    echo "   ✓ SPARQL integration: AVAILABLE\n";
} else {
    echo "   ✗ SPARQL integration: NOT AVAILABLE\n";
}

echo "\n8. Checking WordPress cron...\n";
$cron_events = wp_get_scheduled_event('terpedia_generate_terports_background');
if ($cron_events) {
    echo "   ✓ Background generation cron: SCHEDULED\n";
    echo "   Next run: " . date('Y-m-d H:i:s', $cron_events->timestamp) . "\n";
} else {
    echo "   ✗ Background generation cron: NOT SCHEDULED\n";
}

echo "\n9. Testing manual activation trigger...\n";
echo "   Triggering plugin activation hook...\n";
do_action('terpedia_plugin_activated');
echo "   ✓ Activation hook triggered\n";

// Check if generation was scheduled
$generation_status = get_option('terpedia_terport_generation_status');
if ($generation_status) {
    echo "   Generation status: " . $generation_status['status'] . "\n";
    echo "   Version: " . $generation_status['version'] . "\n";
} else {
    echo "   No generation status found\n";
}

echo "\n=== Test Complete ===\n";
echo "System Status: ";

$all_good = true;
if (!class_exists('Terpedia_Automatic_Terport_Generator')) $all_good = false;
if (!class_exists('Terpedia_Terport_Version_Tracker')) $all_good = false;
if (!post_type_exists('terpedia_terport')) $all_good = false;

if ($all_good) {
    echo "✓ ALL SYSTEMS OPERATIONAL\n";
} else {
    echo "✗ SOME ISSUES DETECTED\n";
}

echo "\nTo view the admin interface, visit: " . admin_url('admin.php?page=terpedia-generation-log') . "\n";
echo "To view generated terports, visit: " . admin_url('edit.php?post_type=terpedia_terport') . "\n";
?>