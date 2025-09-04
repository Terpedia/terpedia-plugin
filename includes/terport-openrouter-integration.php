<?php
/**
 * Terport OpenRouter Integration
 * 
 * Handles OpenRouter API integration for Terport content generation
 * with structured outputs and multimodal capabilities
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_OpenRouter_API {
    
    private $api_key;
    private $api_url = 'https://openrouter.ai/api/v1/chat/completions';
    
    public function __construct() {
        $this->api_key = get_option('terpedia_openrouter_api_key');
    }
    
    /**
     * Generate Terport type and description analysis
     */
    public function generate_terport_analysis($title, $description_only = false) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }
        
        $system_prompt = "You are an expert terpene researcher and content analyst. Analyze the given title and determine the most appropriate Terport type and generate a compelling description.";
        
        $user_prompt = "Analyze this title and provide the appropriate Terport type and description: '{$title}'";
        
        if ($description_only) {
            $user_prompt = "Generate a compelling description for this Terport title: '{$title}'";
        }
        
        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'terport_analysis',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'type' => array(
                            'type' => 'string',
                            'enum' => array('research_analysis', 'compound_profile', 'clinical_study', 'market_analysis', 'regulatory_update', 'industry_news'),
                            'description' => 'The most appropriate Terport type for this title'
                        ),
                        'description' => array(
                            'type' => 'string',
                            'description' => 'A compelling 2-3 sentence description of what this Terport will cover'
                        ),
                        'confidence' => array(
                            'type' => 'number',
                            'minimum' => 0,
                            'maximum' => 1,
                            'description' => 'Confidence level in the type classification'
                        )
                    ),
                    'required' => $description_only ? array('description') : array('type', 'description', 'confidence'),
                    'additionalProperties' => false
                )
            )
        );
        
        $response = $this->make_api_request($system_prompt, $user_prompt, $response_format);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Generate Terport content using structured outputs
     */
    public function generate_terport_content($template, $prompt, $type, $description) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }
        
        // Extract sections from template
        $sections = $this->extract_template_sections($template['content']);
        
        // Build structured output schema based on template sections
        $schema_properties = array();
        $required_sections = array();
        
        foreach ($sections as $section) {
            $schema_properties[$section] = array(
                'type' => 'string',
                'description' => "Content for the {$section} section"
            );
            $required_sections[] = $section;
        }
        
        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'terport_content',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => $schema_properties,
                    'required' => $required_sections,
                    'additionalProperties' => false
                )
            )
        );
        
        $system_prompt = $this->get_system_prompt_for_type($type);
        $user_prompt = $this->build_content_prompt($prompt, $type, $description, $sections);
        
        $response = $this->make_api_request($system_prompt, $user_prompt, $response_format);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Convert structured response to formatted content
        $formatted_content = $this->format_structured_content($response, $template['content']);
        
        return array(
            'content' => $formatted_content,
            'sections' => $response
        );
    }
    
    /**
     * Generate Terport feature image using multimodal LLM
     */
    public function generate_terport_image($title, $type, $description) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured');
        }
        
        // For now, we'll use a text-to-image service or return a placeholder
        // In a full implementation, you would integrate with DALL-E, Midjourney, or similar
        
        $image_prompt = $this->build_image_prompt($title, $type, $description);
        
        // This is a placeholder - in production you would call an image generation API
        $image_url = $this->generate_placeholder_image($title, $type);
        
        return array(
            'image_url' => $image_url,
            'prompt' => $image_prompt
        );
    }
    
    /**
     * Make API request to OpenRouter
     */
    private function make_api_request($system_prompt, $user_prompt, $response_format = null) {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => home_url(),
            'X-Title' => 'Terpedia Terport Generator'
        );
        
        $body = array(
            'model' => 'openai/gpt-4o',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $user_prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 4000
        );
        
        if ($response_format) {
            $body['response_format'] = $response_format;
        }
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'OpenRouter API error: ' . $response_code . ' - ' . $response_body);
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', 'OpenRouter API error: ' . $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid response from OpenRouter API');
        }
        
        $content = $data['choices'][0]['message']['content'];
        
        // Parse JSON response if it's structured output
        if ($response_format && $response_format['type'] === 'json_schema') {
            $parsed_content = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('json_error', 'Failed to parse JSON response: ' . json_last_error_msg());
            }
            return $parsed_content;
        }
        
        return $content;
    }
    
    /**
     * Extract sections from template content
     */
    private function extract_template_sections($content) {
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? array());
    }
    
    /**
     * Get system prompt for content type
     */
    private function get_system_prompt_for_type($type) {
        $prompts = array(
            'research_analysis' => 'You are an expert terpene researcher. Generate comprehensive, scientifically accurate research analysis content. Focus on methodology, findings, and implications.',
            'compound_profile' => 'You are a terpene chemist and pharmacologist. Generate detailed compound profiles with chemical properties, biological activity, and therapeutic potential.',
            'clinical_study' => 'You are a clinical researcher specializing in terpene therapeutics. Generate evidence-based clinical study content with proper methodology and statistical analysis.',
            'market_analysis' => 'You are a cannabis industry analyst. Generate market analysis content focusing on trends, opportunities, and business implications.',
            'regulatory_update' => 'You are a regulatory affairs expert. Generate clear, accurate regulatory update content with compliance guidance and implications.',
            'industry_news' => 'You are a cannabis industry journalist. Generate engaging, informative industry news content with proper context and analysis.'
        );
        
        return isset($prompts[$type]) ? $prompts[$type] : 'Generate high-quality, informative content based on the provided prompt.';
    }
    
    /**
     * Build content generation prompt
     */
    private function build_content_prompt($prompt, $type, $description, $sections) {
        $sections_list = implode(', ', $sections);
        
        return "Generate comprehensive content for a {$type} Terport with the following details:

Title/Subject: {$prompt}
Description: {$description}

Please generate content for each of these sections: {$sections_list}

Requirements:
- Use scientific, professional language appropriate for the terpene research community
- Include specific data, studies, or examples where relevant
- Ensure each section is substantial and informative
- Maintain consistency in tone and style throughout
- Include proper scientific terminology and concepts";
    }
    
    /**
     * Format structured content into final output
     */
    private function format_structured_content($structured_data, $template_content) {
        $formatted_content = $template_content;
        
        foreach ($structured_data as $section => $content) {
            $placeholder = '{{' . $section . '}}';
            $formatted_content = str_replace($placeholder, $content, $formatted_content);
        }
        
        // Remove any remaining placeholders
        $formatted_content = preg_replace('/\{\{[^}]+\}\}/', '', $formatted_content);
        
        // Clean up extra whitespace
        $formatted_content = preg_replace('/\n\s*\n/', "\n\n", $formatted_content);
        
        return trim($formatted_content);
    }
    
    /**
     * Build image generation prompt
     */
    private function build_image_prompt($title, $type, $description) {
        $type_descriptions = array(
            'research_analysis' => 'scientific research laboratory with molecular structures and data charts',
            'compound_profile' => 'molecular structure visualization with chemical formulas and 3D models',
            'clinical_study' => 'medical research setting with clinical trial data and healthcare professionals',
            'market_analysis' => 'business charts, graphs, and market trend visualizations',
            'regulatory_update' => 'legal documents, compliance symbols, and regulatory framework imagery',
            'industry_news' => 'cannabis industry equipment, cultivation facilities, and product displays'
        );
        
        $visual_context = isset($type_descriptions[$type]) ? $type_descriptions[$type] : 'professional terpene research imagery';
        
        return "Create a professional, scientific image for a terpene research article titled '{$title}'. 
        Visual style: {$visual_context}. 
        Context: {$description}. 
        Style: Clean, modern, scientific aesthetic with terpene/cannabis research theme. 
        Colors: Professional blues, greens, and whites. 
        No text overlay needed.";
    }
    
    /**
     * Generate placeholder image (for development)
     */
    private function generate_placeholder_image($title, $type) {
        // This is a placeholder implementation
        // In production, you would integrate with DALL-E, Midjourney, or similar services
        
        $placeholder_url = 'https://via.placeholder.com/800x400/2c5aa0/ffffff?text=' . urlencode($title);
        
        return $placeholder_url;
    }
}
