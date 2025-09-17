<?php
/**
 * Debug script to check Terport CPT registration
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "<h1>Terport CPT Debug</h1>";

// Check if the post type is registered
$post_types = get_post_types(array('public' => true), 'names');
echo "<h2>Registered Post Types:</h2>";
echo "<ul>";
foreach ($post_types as $post_type) {
    echo "<li>" . $post_type . "</li>";
}
echo "</ul>";

// Check specifically for terpedia_terport
if (post_type_exists('terpedia_terport')) {
    echo "<p style='color: green;'>✅ terpedia_terport post type exists</p>";
    
    $post_type_obj = get_post_type_object('terpedia_terport');
    echo "<h3>Post Type Details:</h3>";
    echo "<ul>";
    echo "<li>Name: " . $post_type_obj->name . "</li>";
    echo "<li>Label: " . $post_type_obj->label . "</li>";
    echo "<li>Show UI: " . ($post_type_obj->show_ui ? 'Yes' : 'No') . "</li>";
    echo "<li>Show in Menu: " . ($post_type_obj->show_in_menu ? 'Yes' : 'No') . "</li>";
    echo "<li>Menu Position: " . $post_type_obj->menu_position . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ terpedia_terport post type does NOT exist</p>";
}

// Check if the class exists
if (class_exists('Terpedia_Enhanced_Terport_Editor')) {
    echo "<p style='color: green;'>✅ Terpedia_Enhanced_Terport_Editor class exists</p>";
} else {
    echo "<p style='color: red;'>❌ Terpedia_Enhanced_Terport_Editor class does NOT exist</p>";
}

// Check if the file is included
$included_files = get_included_files();
$terport_file_included = false;
foreach ($included_files as $file) {
    if (strpos($file, 'enhanced-terport-editor.php') !== false) {
        $terport_file_included = true;
        break;
    }
}

if ($terport_file_included) {
    echo "<p style='color: green;'>✅ enhanced-terport-editor.php is included</p>";
} else {
    echo "<p style='color: red;'>❌ enhanced-terport-editor.php is NOT included</p>";
}

// Check WordPress admin menu
echo "<h2>WordPress Admin Menu Items:</h2>";
global $menu, $submenu;
echo "<h3>Top Level Menu:</h3>";
echo "<ul>";
foreach ($menu as $item) {
    if (is_array($item) && isset($item[0])) {
        echo "<li>" . strip_tags($item[0]) . " (slug: " . $item[2] . ")</li>";
    }
}
echo "</ul>";

echo "<h3>Terpedia Submenu:</h3>";
if (isset($submenu['terpedia-main'])) {
    echo "<ul>";
    foreach ($submenu['terpedia-main'] as $subitem) {
        echo "<li>" . strip_tags($subitem[0]) . " (slug: " . $subitem[2] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ No Terpedia submenu found</p>";
}
?>

