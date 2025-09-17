<?php
/**
 * OpenRouter.ai API Handler
 * Integrates OpenRouter.ai API for LLM functionality with GPT-OSS-120B free model
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaOpenRouterHandler {
    
    private $api_key;
    private $api_base_url = 'https://openrouter.ai/api/v1';
    private $default_model = 'openai/gpt-oss-120b:free';
    private $default_vision_model = 'meta-llama/llama-3.2-11b-vision-instruct:free';
    
    public function __construct() {
        $this->api_key = $_ENV['OPENROUTER_API_KEY'] ?? get_option('terpedia_openrouter_api_key', '');
        
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_terpedia_openrouter_test', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_terpedia_openrouter_generate', array($this, 'ajax_generate_response'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Add AJAX handlers for image analysis
        add_action('wp_ajax_analyze_product_image', array($this, 'ajax_analyze_product_image'));
        add_action('wp_ajax_nopriv_analyze_product_image', array($this, 'ajax_analyze_product_image'));
    }
    
    public function init() {
        // Hook into existing agent response systems
        add_filter('terpedia_ai_response_provider', array($this, 'provide_ai_response'), 10, 3);
        add_filter('terpedia_agent_ai_response', array($this, 'generate_agent_response'), 10, 4);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('terpedia/v1', '/openrouter/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_chat_completion'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('terpedia/v1', '/openrouter/models', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_models'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    /**
     * Check API permissions
     */
    public function check_permissions() {
        return current_user_can('read') || is_user_logged_in();
    }
    
    /**
     * Generate chat completion using OpenRouter
     */
    public function chat_completion($messages, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }
        
        $default_options = array(
            'model' => $this->default_model,
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        );
        
        $options = wp_parse_args($options, $default_options);
        
        $request_data = array(
            'model' => $options['model'],
            'messages' => $messages,
            'max_tokens' => intval($options['max_tokens']),
            'temperature' => floatval($options['temperature']),
            'top_p' => floatval($options['top_p']),
            'frequency_penalty' => floatval($options['frequency_penalty']),
            'presence_penalty' => floatval($options['presence_penalty'])
        );
        
        $response = $this->make_api_request('POST', '/chat/completions', $request_data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse API response');
        }
        
        return $data;
    }
    
    /**
     * Generate agent response using OpenRouter
     */
    public function generate_agent_response($default_response, $agent_data, $user_query, $context = array()) {
        $system_prompt = $this->build_agent_system_prompt($agent_data);
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt
            ),
            array(
                'role' => 'user', 
                'content' => $user_query
            )
        );
        
        $response = $this->chat_completion($messages);
        
        if (is_wp_error($response)) {
            error_log('OpenRouter AI Error: ' . $response->get_error_message());
            return $default_response; // Fallback to default
        }
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return $default_response;
    }
    
    /**
     * Build system prompt for agent with citation guidelines
     */
    private function build_agent_system_prompt($agent_data) {
        $prompt = "You are " . $agent_data['name'] . ", " . $agent_data['description'] . "\n\n";
        
        $prompt .= "PERSONALITY TRAITS:\n";
        $prompt .= "- Specialty: " . $agent_data['specialty'] . "\n";
        $prompt .= "- Voice Style: " . $agent_data['voice_style'] . "\n";
        $prompt .= "- Speech Pattern: " . $agent_data['speech_pattern'] . "\n\n";
        
        if (isset($agent_data['primary_effects'])) {
            $prompt .= "PRIMARY EFFECTS: " . implode(', ', $agent_data['primary_effects']) . "\n";
        }
        
        if (isset($agent_data['therapeutic_uses'])) {
            $prompt .= "THERAPEUTIC USES: " . implode(', ', $agent_data['therapeutic_uses']) . "\n";
        }
        
        $prompt .= "\nRESPONSE GUIDELINES:\n";
        $prompt .= "- Stay in character as this specific agent\n";
        $prompt .= "- Use your unique voice style and speech patterns\n";
        $prompt .= "- Provide accurate, scientific information within your specialty\n";
        $prompt .= "- Be helpful and educational while maintaining your personality\n";
        $prompt .= "- Keep responses concise but informative (2-3 paragraphs max)\n\n";
        
        // Add citation guidelines for scientific accuracy
        $prompt .= "CITATION GUIDELINES:\n";
        $prompt .= "- Do NOT include inline citations, reference numbers, or bibliography sections\n";
        $prompt .= "- Academic citations are automatically generated from database sources used\n";
        $prompt .= "- Focus on evidence-based content without citation formatting\n";
        $prompt .= "- Sources include UniProt, Gene Ontology, Wikidata, MeSH, PubMed, and kb.terpedia.com\n";
        
        return $prompt;
    }
    
    /**
     * Provide AI response for general queries with citation guidelines
     */
    public function provide_ai_response($default_response, $query, $context = array()) {
        $system_content = 'You are a knowledgeable terpene expert assistant for Terpedia.com. Provide accurate, scientific information about terpenes, their properties, sources, and therapeutic applications. Be helpful and educational.\n\n';
        
        // Add citation guidelines
        $system_content .= 'CITATION GUIDELINES:\n';
        $system_content .= '- Do NOT include inline citations, reference numbers, or bibliography sections in your response\n';
        $system_content .= '- Academic citations will be automatically added based on data sources used\n';
        $system_content .= '- Focus on creating evidence-based, scientifically accurate content\n';
        $system_content .= '- When referencing research findings, use clear language without citation markers\n';
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_content
            ),
            array(
                'role' => 'user',
                'content' => $query
            )
        );
        
        $response = $this->chat_completion($messages);
        
        if (is_wp_error($response)) {
            return $default_response;
        }
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return $default_response;
    }
    
    /**
     * Make API request to OpenRouter
     */
    private function make_api_request($method, $endpoint, $data = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }
        
        $url = $this->api_base_url . $endpoint;
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => home_url(),
            'X-Title' => get_bloginfo('name')
        );
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 60
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 400) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API request failed';
            return new WP_Error('api_error', $error_message, array('status' => $status_code));
        }
        
        return $response;
    }
    
    /**
     * Get available models
     */
    public function get_models() {
        $response = $this->make_api_request('GET', '/models');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse models response');
        }
        
        return $data['data'] ?? array();
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $test_messages = array(
            array(
                'role' => 'user',
                'content' => 'Hello, this is a test message.'
            )
        );
        
        $response = $this->chat_completion($test_messages, array('max_tokens' => 50));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'model' => $response['model'] ?? $this->default_model,
            'response' => $response['choices'][0]['message']['content'] ?? 'Test successful'
        );
    }
    
    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('terpedia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('error' => 'Insufficient permissions')));
        }
        
        $result = $this->test_connection();
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for generating responses
     */
    public function ajax_generate_response() {
        check_ajax_referer('terpedia_openrouter_nonce', 'nonce');
        
        $query = sanitize_textarea_field($_POST['query']);
        $agent_type = sanitize_text_field($_POST['agent_type'] ?? '');
        
        if (empty($query)) {
            wp_die(json_encode(array('error' => 'Query is required')));
        }
        
        // Get agent data if specified
        $agent_data = null;
        if (!empty($agent_type)) {
            if (class_exists('TerpeneBuddyPressAgents')) {
                $terpene_agents = new TerpeneBuddyPressAgents();
                $agents = $terpene_agents->get_terpene_agents();
                $agent_data = isset($agents[$agent_type]) ? $agents[$agent_type] : null;
            }
            
            if (!$agent_data && class_exists('TerpediaBuddyPressMessaging')) {
                $messaging = new TerpediaBuddyPressMessaging();
                $agent_data = $messaging->get_agent_personality($agent_type);
            }
        }
        
        if ($agent_data) {
            $response = $this->generate_agent_response('', $agent_data, $query);
        } else {
            $response = $this->provide_ai_response('', $query);
        }
        
        wp_die(json_encode(array(
            'success' => true,
            'response' => $response,
            'model' => $this->default_model,
            'agent' => $agent_type
        )));
    }
    
    /**
     * REST API: Chat completion
     */
    public function rest_chat_completion($request) {
        $messages = $request->get_param('messages');
        $options = $request->get_param('options') ?: array();
        
        if (empty($messages)) {
            return new WP_REST_Response(array('error' => 'Messages are required'), 400);
        }
        
        $response = $this->chat_completion($messages, $options);
        
        if (is_wp_error($response)) {
            return new WP_REST_Response(array('error' => $response->get_error_message()), 400);
        }
        
        return new WP_REST_Response($response, 200);
    }
    
    /**
     * REST API: Get models
     */
    public function rest_get_models($request) {
        $models = $this->get_models();
        
        if (is_wp_error($models)) {
            return new WP_REST_Response(array('error' => $models->get_error_message()), 400);
        }
        
        return new WP_REST_Response(array('models' => $models), 200);
    }
    
    /**
     * Get API usage stats
     */
    public function get_usage_stats() {
        $response = $this->make_api_request('GET', '/auth/key');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Analyze product image for ingredients using vision model
     */
    public function analyze_product_image($image_url, $additional_context = '') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }

        $system_prompt = "You are an expert in analyzing product labels and ingredient lists. When provided with an image of a product, extract all visible ingredient information, brand names, product names, and any nutritional or chemical information visible on the label. Be thorough and accurate, and identify any terpenes or botanical compounds that may be present.";

        $user_prompt = "Please analyze this product image and extract:
1. Product name and brand
2. Complete ingredients list (exactly as written on label)
3. Any terpenes or botanical compounds mentioned
4. Nutritional information if visible
5. Any warnings or usage instructions
6. Concentration percentages if visible

Format your response as structured data that can be parsed easily.";

        if (!empty($additional_context)) {
            $user_prompt .= "\n\nAdditional context: " . $additional_context;
        }

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt
            ),
            array(
                'role' => 'user',
                'content' => array(
                    array(
                        'type' => 'text',
                        'text' => $user_prompt
                    ),
                    array(
                        'type' => 'image_url',
                        'image_url' => array(
                            'url' => $image_url
                        )
                    )
                )
            )
        );

        $options = array(
            'model' => $this->default_vision_model,
            'max_tokens' => 2000,
            'temperature' => 0.3  // Lower temperature for more accurate extraction
        );

        return $this->chat_completion($messages, $options);
    }

    /**
     * Extract ingredients from multiple product images
     */
    public function analyze_multiple_product_images($image_urls, $additional_context = '') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }

        $system_prompt = "You are an expert in analyzing product labels and ingredient lists. You will be provided with multiple images of the same product from different angles or different products. Extract and consolidate all visible ingredient information, brand names, product names, and any nutritional or chemical information. Look for terpenes and botanical compounds.";

        $user_prompt = "Please analyze these product images and extract:
1. Product name(s) and brand(s)
2. Complete consolidated ingredients list
3. Any terpenes or botanical compounds mentioned
4. Nutritional information if visible
5. Any warnings or usage instructions
6. Concentration percentages if visible

If multiple products are shown, list them separately. Format your response as structured data.";

        if (!empty($additional_context)) {
            $user_prompt .= "\n\nAdditional context: " . $additional_context;
        }

        // Build content array with text and multiple images
        $content = array(
            array(
                'type' => 'text',
                'text' => $user_prompt
            )
        );

        // Add each image to the content array
        foreach ($image_urls as $image_url) {
            $content[] = array(
                'type' => 'image_url',
                'image_url' => array(
                    'url' => $image_url
                )
            );
        }

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt
            ),
            array(
                'role' => 'user',
                'content' => $content
            )
        );

        $options = array(
            'model' => $this->default_vision_model,
            'max_tokens' => 3000,
            'temperature' => 0.3
        );

        return $this->chat_completion($messages, $options);
    }

    /**
     * AJAX handler for product image analysis
     */
    public function ajax_analyze_product_image() {
        check_ajax_referer('terpedia_terproducts_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }

        $image_urls = $_POST['image_urls'] ?? array();
        $additional_context = sanitize_textarea_field($_POST['additional_context'] ?? '');

        if (empty($image_urls)) {
            wp_send_json_error('No images provided for analysis');
        }

        // Sanitize image URLs
        $image_urls = array_map('esc_url_raw', (array) $image_urls);

        if (count($image_urls) === 1) {
            $result = $this->analyze_product_image($image_urls[0], $additional_context);
        } else {
            $result = $this->analyze_multiple_product_images($image_urls, $additional_context);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Extract the response content
        $analysis_content = '';
        if (isset($result['choices'][0]['message']['content'])) {
            $analysis_content = $result['choices'][0]['message']['content'];
        }

        // Parse the analysis to extract structured data
        $parsed_data = $this->parse_ingredient_analysis($analysis_content);

        wp_send_json_success(array(
            'raw_analysis' => $analysis_content,
            'parsed_data' => $parsed_data,
            'model_used' => $result['model'] ?? $this->default_vision_model,
            'image_count' => count($image_urls)
        ));
    }

    /**
     * Parse ingredient analysis to extract structured data
     */
    private function parse_ingredient_analysis($analysis_text) {
        $parsed = array(
            'product_name' => '',
            'brand' => '',
            'ingredients' => array(),
            'terpenes' => array(),
            'warnings' => array(),
            'nutritional_info' => array(),
            'concentrations' => array()
        );

        // Extract product name (look for patterns like "Product: " or "Product Name: ")
        if (preg_match('/(?:Product(?:\s+Name)?|Brand):\s*([^\n\r]+)/i', $analysis_text, $matches)) {
            $parsed['product_name'] = trim($matches[1]);
        }

        // Extract brand (look for "Brand: ")
        if (preg_match('/Brand:\s*([^\n\r]+)/i', $analysis_text, $matches)) {
            $parsed['brand'] = trim($matches[1]);
        }

        // Extract ingredients list
        if (preg_match('/(?:Ingredients?|Components?):\s*([^\n\r]+(?:\n[^\n\r:]+)*)/i', $analysis_text, $matches)) {
            $ingredients_text = $matches[1];
            // Split by commas and clean up
            $ingredients = array_map('trim', preg_split('/[,\n]+/', $ingredients_text));
            $parsed['ingredients'] = array_filter($ingredients);
        }

        // Extract terpenes (look for common terpene names)
        $terpene_names = array('limonene', 'linalool', 'myrcene', 'pinene', 'caryophyllene', 'humulene', 'terpinolene', 'ocimene', 'camphene', 'valencene');
        foreach ($terpene_names as $terpene) {
            if (stripos($analysis_text, $terpene) !== false) {
                // Try to extract concentration if present
                if (preg_match('/' . $terpene . '[^\d]*(\d+(?:\.\d+)?)\s*%/i', $analysis_text, $matches)) {
                    $parsed['terpenes'][] = array(
                        'name' => ucfirst($terpene),
                        'concentration' => $matches[1] . '%'
                    );
                } else {
                    $parsed['terpenes'][] = array(
                        'name' => ucfirst($terpene),
                        'concentration' => 'Unknown'
                    );
                }
            }
        }

        // Extract warnings
        if (preg_match('/(?:Warning|Caution|Note):\s*([^\n\r]+(?:\n[^\n\r:]+)*)/i', $analysis_text, $matches)) {
            $parsed['warnings'][] = trim($matches[1]);
        }

        return $parsed;
    }

    /**
     * Generate terpene-specific content
     */
    public function generate_terpene_content($terpene_name, $content_type, $parameters = array()) {
        $system_prompt = "You are an expert in terpenes, specifically {$terpene_name}. Generate {$content_type} content that is scientifically accurate, engaging, and educational.";
        
        $user_prompt = "Generate {$content_type} about {$terpene_name}";
        if (!empty($parameters['topic'])) {
            $user_prompt .= " focusing on " . $parameters['topic'];
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        );
        
        $options = array(
            'max_tokens' => $parameters['max_tokens'] ?? 1500,
            'temperature' => $parameters['temperature'] ?? 0.7
        );
        
        return $this->chat_completion($messages, $options);
    }
}

// Initialize OpenRouter handler
new TerpediaOpenRouterHandler();