<?php
/**
 * Development AJAX Handler for Terproducts
 * Simulates WordPress AJAX functionality for testing
 */

// Mock WordPress environment for development
function wp_send_json_success($data) {
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
    exit;
}

function wp_send_json_error($message) {
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'data' => $message
    ));
    exit;
}

function check_ajax_referer($action, $field) {
    // Mock nonce verification for development
    return true;
}

function current_user_can($capability) {
    return true; // Allow all in dev mode
}

function sanitize_textarea_field($text) {
    return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
}

function esc_url_raw($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

// Handle different AJAX actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'analyze_photo_ingredients':
        // Simulate image analysis response
        $image_urls = $_POST['image_urls'] ?? array();
        $post_id = intval($_POST['post_id'] ?? 0);
        $additional_context = sanitize_textarea_field($_POST['additional_context'] ?? '');
        
        if (empty($image_urls)) {
            wp_send_json_error('No images provided for analysis');
        }
        
        // Simulate AI analysis response
        $mock_analysis = "Product Analysis Results:

Product Name: Lavender Essential Oil Blend
Brand: Terpedia Naturals

Ingredients:
- Lavandula angustifolia (Lavender) oil
- Citrus limon (Lemon) peel oil  
- Eucalyptus globulus leaf oil
- Mentha piperita (Peppermint) oil
- Rosmarinus officinalis (Rosemary) leaf oil

Detected Terpenes:
- Linalool: 35-45% (primary component in lavender)
- Limonene: 8-12% (from lemon peel oil)
- Eucalyptol: 5-8% (from eucalyptus)
- Menthol: 3-5% (from peppermint)
- Pinene: 2-4% (from rosemary)

Concentrations:
- Essential Oil Blend: 100%
- Natural origin: 100%

Warnings:
- For external use only
- Dilute before use
- Avoid contact with eyes
- Keep out of reach of children";

        $parsed_data = array(
            'product_name' => 'Lavender Essential Oil Blend',
            'brand' => 'Terpedia Naturals',
            'ingredients' => array(
                'Lavandula angustifolia (Lavender) oil',
                'Citrus limon (Lemon) peel oil',
                'Eucalyptus globulus leaf oil',
                'Mentha piperita (Peppermint) oil',
                'Rosmarinus officinalis (Rosemary) leaf oil'
            ),
            'terpenes' => array(
                array('name' => 'Linalool', 'concentration' => '35-45%', 'source' => 'vision_analysis'),
                array('name' => 'Limonene', 'concentration' => '8-12%', 'source' => 'vision_analysis'),
                array('name' => 'Eucalyptol', 'concentration' => '5-8%', 'source' => 'vision_analysis'),
                array('name' => 'Menthol', 'concentration' => '3-5%', 'source' => 'vision_analysis'),
                array('name' => 'Pinene', 'concentration' => '2-4%', 'source' => 'vision_analysis')
            ),
            'warnings' => array(
                'For external use only',
                'Dilute before use',
                'Avoid contact with eyes',
                'Keep out of reach of children'
            ),
            'concentrations' => array(
                array('component' => 'Essential Oil Blend', 'percentage' => '100%'),
                array('component' => 'Natural origin', 'percentage' => '100%')
            ),
            'nutritional_info' => array(),
            'extracted_text' => array()
        );
        
        wp_send_json_success(array(
            'raw_analysis' => $mock_analysis,
            'parsed_data' => $parsed_data,
            'model_used' => 'meta-llama/llama-3.2-11b-vision-instruct:free (simulated)',
            'image_count' => count($image_urls),
            'confidence' => 92,
            'post_updated' => false
        ));
        break;
        
    case 'analyze_product_ingredients':
        // Simulate text-based ingredient analysis
        $ingredients_text = sanitize_textarea_field($_POST['ingredients_text'] ?? '');
        
        if (empty($ingredients_text)) {
            wp_send_json_error('No ingredients provided');
        }
        
        // Mock analysis result
        $mock_terpenes = array(
            array('name' => 'Linalool', 'confidence' => 89),
            array('name' => 'Limonene', 'confidence' => 85),
            array('name' => 'Eucalyptol', 'confidence' => 78)
        );
        
        $mock_analysis = "Analysis of ingredients reveals a blend rich in monoterpenes, particularly linalool from lavender oil, which provides relaxing and anti-anxiety properties. The presence of limonene from citrus oils adds uplifting and mood-enhancing effects.";
        
        wp_send_json_success(array(
            'analysis' => $mock_analysis,
            'terpenes' => $mock_terpenes,
            'confidence' => 87
        ));
        break;
        
    case 'generate_terpene_insights':
        // Simulate terpene insights generation
        $post_id = intval($_POST['post_id'] ?? 0);
        
        $mock_insights = array(
            'profile_analysis' => "This terpene profile demonstrates a well-balanced blend of monoterpenes with strong therapeutic potential. The dominant linalool content (35-45%) provides significant calming and anti-anxiety effects, while the supporting terpenes create a synergistic entourage effect.",
            'recommendations' => "Based on the detected terpene profile, this product may be beneficial for:\n• Evening relaxation and stress relief\n• Sleep support and bedtime routines\n• Aromatherapy for anxiety management\n• Natural mood enhancement",
            'enhancements' => "To enhance this terpene profile, consider:\n• Adding bergamot oil for additional limonene\n• Including a small amount of chamomile for bisabolol\n• Incorporating sweet orange oil for enhanced limonene content\n• Adding ylang-ylang for linalyl acetate complexity",
            'benefits' => "Therapeutic Benefits:\n• Linalool: Anxiolytic, sedative, anti-inflammatory\n• Limonene: Mood elevation, stress relief, immune support\n• Eucalyptol: Respiratory support, antimicrobial\n• Menthol: Cooling sensation, decongestant\n• Pinene: Alertness, respiratory support, memory enhancement"
        );
        
        wp_send_json_success($mock_insights);
        break;
        
    case 'search_terproducts':
        // Simulate terproduct search for autocomplete
        $query = sanitize_textarea_field($_GET['q'] ?? $_POST['q'] ?? '');
        $limit = intval($_GET['limit'] ?? $_POST['limit'] ?? 10);
        
        if (empty($query) || strlen($query) < 1) {
            wp_send_json_error('Query too short');
        }
        
        // Mock search results based on query
        $mock_products = array();
        
        $sample_products = array(
            array(
                'id' => 1,
                'title' => 'Lavender Essential Oil Blend',
                'slug' => 'lavender-essential-oil-blend',
                'brand' => 'Terpedia Naturals',
                'category' => 'Essential Oils',
                'excerpt' => 'Pure lavender oil with calming linalool terpenes for relaxation and sleep support.'
            ),
            array(
                'id' => 2,
                'title' => 'Citrus Limonene Extract',
                'slug' => 'citrus-limonene-extract',
                'brand' => 'Terpedia Labs',
                'category' => 'Terpene Isolates',
                'excerpt' => 'High-purity limonene extract from citrus peels, perfect for mood enhancement.'
            ),
            array(
                'id' => 3,
                'title' => 'Pine Forest Aromatherapy Blend',
                'slug' => 'pine-forest-aromatherapy-blend',
                'brand' => 'Terpedia Naturals',
                'category' => 'Aromatherapy Blends',
                'excerpt' => 'Forest-fresh blend with alpha and beta pinene for mental clarity and focus.'
            ),
            array(
                'id' => 4,
                'title' => 'Eucalyptus Refreshing Oil',
                'slug' => 'eucalyptus-refreshing-oil',
                'brand' => 'Terpedia Naturals',
                'category' => 'Essential Oils',
                'excerpt' => 'Pure eucalyptus oil rich in eucalyptol for respiratory support and cooling sensation.'
            ),
            array(
                'id' => 5,
                'title' => 'Lemon Zest Terpene Complex',
                'slug' => 'lemon-zest-terpene-complex',
                'brand' => 'Terpedia Labs',
                'category' => 'Terpene Isolates',
                'excerpt' => 'Concentrated lemon terpenes including limonene and citral for uplifting effects.'
            )
        );
        
        // Filter products based on query
        $results = array();
        foreach ($sample_products as $product) {
            if (stripos($product['title'], $query) !== false || 
                stripos($product['brand'], $query) !== false ||
                stripos($product['category'], $query) !== false) {
                
                $results[] = array(
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'slug' => $product['slug'],
                    'url' => '/terproduct/' . $product['slug'],
                    'brand' => $product['brand'],
                    'category' => $product['category'],
                    'thumbnail' => '',
                    'excerpt' => $product['excerpt']
                );
                
                if (count($results) >= $limit) {
                    break;
                }
            }
        }
        
        wp_send_json_success($results);
        break;
        
    default:
        wp_send_json_error('Unknown action: ' . $action);
}
?>