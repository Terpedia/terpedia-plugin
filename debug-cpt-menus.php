<?php
/**
 * Debug CPT menu registrations
 * Run this to see what's happening with CPT menu settings
 */

// Check if we're in WordPress admin
if (!is_admin()) {
    die('This script must be run from WordPress admin');
}

echo "<h2>CPT Menu Debug Information</h2>";

// Get all registered post types
global $wp_post_types;

$our_cpts = ['terport', 'terpedia_terproduct', 'terpedia_rx', 'terpedia_podcast', 'terpedia_newsletter'];

foreach ($our_cpts as $cpt) {
    if (isset($wp_post_types[$cpt])) {
        $post_type = $wp_post_types[$cpt];
        echo "<h3>$cpt</h3>";
        echo "<ul>";
        echo "<li><strong>show_in_menu:</strong> " . var_export($post_type->show_in_menu, true) . "</li>";
        echo "<li><strong>show_ui:</strong> " . var_export($post_type->show_ui, true) . "</li>";
        echo "<li><strong>public:</strong> " . var_export($post_type->public, true) . "</li>";
        echo "<li><strong>menu_position:</strong> " . var_export($post_type->menu_position, true) . "</li>";
        echo "<li><strong>menu_icon:</strong> " . var_export($post_type->menu_icon, true) . "</li>";
        echo "<li><strong>labels->menu_name:</strong> " . var_export($post_type->labels->menu_name ?? 'Not set', true) . "</li>";
        echo "</ul>";
    } else {
        echo "<h3>$cpt</h3><p style='color: red;'>❌ Not registered</p>";
    }
}

// Check admin menu structure
echo "<h2>Current Admin Menu Structure</h2>";
global $menu, $submenu;

echo "<h3>Top-level menu items:</h3>";
echo "<ul>";
foreach ($menu as $item) {
    if (is_array($item) && !empty($item[0])) {
        echo "<li>" . strip_tags($item[0]) . " (slug: " . $item[2] . ")</li>";
    }
}
echo "</ul>";

echo "<h3>Terpedia submenu items:</h3>";
if (isset($submenu['terpedia-main'])) {
    echo "<ul>";
    foreach ($submenu['terpedia-main'] as $item) {
        echo "<li>" . strip_tags($item[0]) . " (slug: " . $item[2] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ No Terpedia submenu found</p>";
}
