<?php
/**
 * Terport SPARQL Knowledge Base Integration
 * Integrates with kb.terpedia.com for comprehensive terpene research data
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Terport_SPARQL_Integration {
    
    private $kb_base_url = 'https://kb.terpedia.com';
    private $sparql_endpoint = 'https://kb.terpedia.com:3030/biodb/sparql';
    private $chat_api_endpoint = 'https://kb.terpedia.com/api/chat';
    private $openrouter_api;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_generate_comprehensive_terport', array($this, 'ajax_generate_comprehensive_terport'));
        add_action('wp_ajax_query_terpene_research', array($this, 'ajax_query_terpene_research'));
        
        // Initialize OpenRouter API
        if (class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_api = new TerpediaOpenRouterHandler();
        }
    }
    
    public function init() {
        // Hook into terport generation process
        add_filter('terpedia_generate_terport_content', array($this, 'generate_research_based_content'), 10, 3);
    }
    
    /**
     * Query federated SPARQL endpoints for terpene research data
     */
    public function query_federated_terpene_research($terpene_name, $research_focus = '') {
        $queries = array(
            'uniprot_proteins' => $this->build_uniprot_query($terpene_name),
            'gene_ontology' => $this->build_gene_ontology_query($terpene_name),
            'disease_ontology' => $this->build_disease_ontology_query($terpene_name),
            'wikidata_compounds' => $this->build_wikidata_query($terpene_name),
            'mesh_terms' => $this->build_mesh_query($terpene_name)
        );
        
        $results = array();
        foreach ($queries as $endpoint => $query) {
            $results[$endpoint] = $this->execute_federated_query($query, $endpoint);
        }
        
        return $results;
    }
    
    /**
     * Use natural language querying via kb.terpedia.com chat API
     */
    public function query_natural_language($research_question) {
        $payload = array(
            'message' => $research_question,
            'include_visualizations' => true,
            'federated_search' => true
        );
        
        $response = wp_remote_post($this->chat_api_endpoint, array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Generate comprehensive terport using federated data + AI
     */
    public function generate_comprehensive_terport($title, $terport_type, $research_questions = array()) {
        // Step 1: Collect research data from federated endpoints
        $federated_data = array();
        foreach ($research_questions as $question) {
            $federated_data[] = $this->query_natural_language($question);
        }
        
        // Step 2: Extract key terpenes from the research questions
        $terpenes = $this->extract_terpene_names($research_questions);
        $sparql_data = array();
        foreach ($terpenes as $terpene) {
            $sparql_data[$terpene] = $this->query_federated_terpene_research($terpene);
        }
        
        // Step 3: Generate structured content using OpenRouter
        $system_prompt = $this->build_terport_system_prompt($terport_type);
        $research_context = $this->consolidate_research_data($federated_data, $sparql_data);
        
        $user_prompt = "Generate a comprehensive $terport_type terport titled '$title' using the following research data:\n\n";
        $user_prompt .= "Research Context:\n" . json_encode($research_context, JSON_PRETTY_PRINT) . "\n\n";
        $user_prompt .= "Focus on answering these specific questions:\n";
        foreach ($research_questions as $i => $question) {
            $user_prompt .= ($i + 1) . ". $question\n";
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        );
        
        // Try models with fallback mechanism
        $ai_response = $this->generate_with_fallback_models($messages, $terport_type);
        
        if (is_wp_error($ai_response)) {
            return array('error' => 'AI generation failed: ' . $ai_response->get_error_message());
        }
        
        if (isset($ai_response['error'])) {
            return array('error' => 'All AI models failed: ' . $ai_response['error']);
        }
        
        // Step 4: Create and publish the terport
        $terport_id = $this->create_terport_post($title, $ai_response['choices'][0]['message']['content'], $terport_type);
        
        // Step 5: Store research metadata
        $this->store_terport_metadata($terport_id, array(
            'research_questions' => $research_questions,
            'federated_data_sources' => array_keys($federated_data),
            'sparql_queries_executed' => count($sparql_data),
            'generated_timestamp' => current_time('mysql'),
            'knowledge_base_version' => $this->get_kb_version()
        ));
        
        return array(
            'terport_id' => $terport_id,
            'research_data' => $research_context,
            'ai_response' => $ai_response
        );
    }
    
    /**
     * Build system prompt for specific terport types
     */
    private function build_terport_system_prompt($terport_type) {
        $base_prompt = "You are an expert veterinary researcher specializing in terpene applications in veterinary medicine. ";
        
        switch ($terport_type) {
            case 'Veterinary Cancer Research':
                return $base_prompt . "Focus on anticancer terpenes, mechanisms of action, dosing for dogs/cats/horses, clinical evidence, and safety profiles. Include specific cancer types, dosage ranges, and contraindications.";
            
            case 'Veterinary Seizure Management':
                return $base_prompt . "Concentrate on anticonvulsant terpenes (especially linalool, limonene, β-caryophyllene, myrcene), mechanisms, veterinary dosing protocols, safety considerations, and integration with standard seizure medications.";
            
            case 'Topical Terpene Applications':
                return $base_prompt . "Focus on dermal/topical terpene applications, skin penetration, topical dosing, dermatological conditions, autoimmune disorders, and case study evidence in veterinary medicine.";
            
            case 'Oral Terpene Safety & Dosing':
                return $base_prompt . "Emphasize oral bioavailability, species-specific metabolism, safe dosage ranges, drug interactions, hepatic considerations, and oral administration protocols for veterinary patients.";
            
            default:
                return $base_prompt . "Provide comprehensive, evidence-based information with emphasis on veterinary applications, safety, and practical clinical implementation.";
        }
    }
    
    /**
     * Extract terpene names from research questions
     */
    private function extract_terpene_names($questions) {
        $common_terpenes = array(
            'linalool', 'limonene', 'myrcene', 'pinene', 'beta-caryophyllene', 
            'caryophyllene', 'humulene', 'terpinolene', 'ocimene', 'camphene',
            'geraniol', 'citronellol', 'nerolidol', 'bisabolol', 'borneol'
        );
        
        $found_terpenes = array();
        $text = strtolower(implode(' ', $questions));
        
        foreach ($common_terpenes as $terpene) {
            if (strpos($text, $terpene) !== false) {
                $found_terpenes[] = $terpene;
            }
        }
        
        return array_unique($found_terpenes);
    }
    
    /**
     * Build SPARQL queries for different endpoints
     */
    private function build_uniprot_query($terpene_name) {
        return "
            PREFIX up: <http://purl.uniprot.org/core/>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            
            SELECT ?protein ?label ?organism ?function WHERE {
                ?protein a up:Protein .
                ?protein up:mnemonic ?label .
                ?protein up:organism/up:scientificName ?organism .
                ?protein up:annotation ?annotation .
                ?annotation a up:Function_Annotation .
                ?annotation rdfs:comment ?function .
                FILTER(
                    CONTAINS(LCASE(STR(?label)), LCASE('" . esc_sql($terpene_name) . "')) || 
                    CONTAINS(LCASE(STR(?function)), LCASE('" . esc_sql($terpene_name) . "')) ||
                    CONTAINS(LCASE(STR(?function)), 'terpene') ||
                    CONTAINS(LCASE(STR(?function)), 'monoterpene') ||
                    CONTAINS(LCASE(STR(?function)), 'sesquiterpene')
                )
            } LIMIT 20
        ";
    }
    
    private function build_gene_ontology_query($terpene_name) {
        return "
            PREFIX obo: <http://purl.obolibrary.org/obo/>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
            PREFIX IAO: <http://purl.obolibrary.org/obo/IAO_>
            
            SELECT ?term ?label ?definition ?category WHERE {
                ?term rdfs:label ?label .
                ?term IAO:0000115 ?definition .
                ?term rdfs:subClassOf ?category .
                FILTER(
                    CONTAINS(LCASE(STR(?label)), LCASE('" . esc_sql($terpene_name) . "')) || 
                    CONTAINS(LCASE(STR(?definition)), LCASE('" . esc_sql($terpene_name) . "')) ||
                    CONTAINS(LCASE(STR(?definition)), 'terpene') ||
                    CONTAINS(LCASE(STR(?definition)), 'terpenoid') ||
                    CONTAINS(LCASE(STR(?definition)), 'isoprenoid')
                )
            } LIMIT 20
        ";
    }
    
    private function build_disease_ontology_query($terpene_name) {
        return "
            PREFIX obo: <http://purl.obolibrary.org/obo/>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
            PREFIX IAO: <http://purl.obolibrary.org/obo/IAO_>
            
            SELECT ?disease ?label ?definition ?category WHERE {
                ?disease rdfs:label ?label .
                ?disease IAO:0000115 ?definition .
                ?disease rdfs:subClassOf ?category .
                FILTER(
                    CONTAINS(LCASE(STR(?definition)), 'cancer') || 
                    CONTAINS(LCASE(STR(?definition)), 'seizure') ||
                    CONTAINS(LCASE(STR(?definition)), 'inflammation') ||
                    CONTAINS(LCASE(STR(?definition)), 'pain') ||
                    CONTAINS(LCASE(STR(?definition)), 'anxiety') ||
                    CONTAINS(LCASE(STR(?definition)), 'epilepsy') ||
                    CONTAINS(LCASE(STR(?definition)), 'tumor') ||
                    CONTAINS(LCASE(STR(?definition)), 'arthritis')
                )
            } LIMIT 30
        ";
    }
    
    private function build_wikidata_query($terpene_name) {
        return "
            PREFIX wd: <http://www.wikidata.org/entity/>
            PREFIX wdt: <http://www.wikidata.org/prop/direct/>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
            
            SELECT ?compound ?compoundLabel ?molecularFormula ?smiles ?inchikey ?pubchemId ?cas WHERE {
                ?compound wdt:P31/wdt:P279* wd:Q407595 .  # instance of terpene
                ?compound rdfs:label ?compoundLabel .
                OPTIONAL { ?compound wdt:P274 ?molecularFormula . }
                OPTIONAL { ?compound wdt:P233 ?smiles . }
                OPTIONAL { ?compound wdt:P235 ?inchikey . }
                OPTIONAL { ?compound wdt:P662 ?pubchemId . }
                OPTIONAL { ?compound wdt:P231 ?cas . }
                FILTER(
                    CONTAINS(LCASE(STR(?compoundLabel)), LCASE('" . esc_sql($terpene_name) . "')) &&
                    LANG(?compoundLabel) = 'en'
                )
            } LIMIT 10
        ";
    }
    
    private function build_mesh_query($terpene_name) {
        return "
            PREFIX meshv: <http://id.nlm.nih.gov/mesh/vocab#>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
            PREFIX mesh: <http://id.nlm.nih.gov/mesh/>
            
            SELECT ?concept ?label ?scopeNote ?treeNumber WHERE {
                ?concept a meshv:TopicalDescriptor .
                ?concept rdfs:label ?label .
                ?concept meshv:scopeNote ?scopeNote .
                ?concept meshv:treeNumber ?treeNumber .
                FILTER(
                    CONTAINS(LCASE(STR(?label)), LCASE('" . esc_sql($terpene_name) . "')) || 
                    CONTAINS(LCASE(STR(?scopeNote)), LCASE('" . esc_sql($terpene_name) . "')) ||
                    CONTAINS(LCASE(STR(?scopeNote)), 'terpene') ||
                    CONTAINS(LCASE(STR(?scopeNote)), 'terpenoid') ||
                    CONTAINS(LCASE(STR(?label)), 'terpene')
                )
            } LIMIT 15
        ";
    }
    
    /**
     * Execute SPARQL query against federated endpoints
     */
    private function execute_federated_query($query, $endpoint) {
        $endpoint_urls = $this->get_sparql_endpoints();
        
        // Use our local SPARQL endpoint with SERVICE clauses for federated queries
        $federated_query = $this->build_federated_query($query, $endpoint, $endpoint_urls);
        
        return $this->execute_sparql_http_request($federated_query);
    }
    
    /**
     * Get available SPARQL endpoints
     */
    private function get_sparql_endpoints() {
        return array(
            'uniprot_proteins' => 'https://sparql.uniprot.org/sparql',
            'gene_ontology' => 'http://sparql.hegroup.org/sparql',
            'disease_ontology' => 'http://sparql.hegroup.org/sparql',
            'wikidata_compounds' => 'https://query.wikidata.org/sparql',
            'mesh_terms' => 'https://id.nlm.nih.gov/mesh/sparql'
        );
    }
    
    /**
     * Build federated SPARQL query with SERVICE clause
     */
    private function build_federated_query($query, $endpoint, $endpoint_urls) {
        if (!isset($endpoint_urls[$endpoint])) {
            // Fallback: execute query directly on our endpoint
            return $query;
        }
        
        $service_url = $endpoint_urls[$endpoint];
        
        // Wrap the original query in a SERVICE clause
        return "
            SELECT * WHERE {
                SERVICE <{$service_url}> {
                    {$query}
                }
            }
        ";
    }
    
    /**
     * Execute SPARQL HTTP request
     */
    private function execute_sparql_http_request($query) {
        $response = wp_remote_post($this->sparql_endpoint, array(
            'body' => array(
                'query' => $query,
                'format' => 'json'
            ),
            'headers' => array(
                'Accept' => 'application/sparql-results+json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'error' => 'SPARQL request failed: ' . $response->get_error_message(),
                'query' => $query
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array(
                'error' => 'SPARQL endpoint returned error: ' . $status_code,
                'query' => $query,
                'response' => wp_remote_retrieve_body($response)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'error' => 'Failed to parse SPARQL response: ' . json_last_error_msg(),
                'query' => $query,
                'raw_response' => $body
            );
        }
        
        return $this->process_sparql_results($data);
    }
    
    /**
     * Process SPARQL results into a standardized format
     */
    private function process_sparql_results($data) {
        if (!isset($data['results']['bindings'])) {
            return array(
                'results' => array(),
                'count' => 0,
                'success' => true
            );
        }
        
        $results = array();
        foreach ($data['results']['bindings'] as $binding) {
            $result = array();
            foreach ($binding as $var => $value) {
                $result[$var] = $value['value'] ?? '';
            }
            $results[] = $result;
        }
        
        return array(
            'results' => $results,
            'count' => count($results),
            'success' => true,
            'variables' => $data['head']['vars'] ?? array()
        );
    }
    
    /**
     * Create terport post in WordPress
     */
    private function create_terport_post($title, $content, $terport_type) {
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'terport',
            'post_author' => get_current_user_id()
        );
        
        $terport_id = wp_insert_post($post_data);
        
        if (!is_wp_error($terport_id)) {
            update_post_meta($terport_id, '_terport_type', $terport_type);
            update_post_meta($terport_id, '_generated_via_sparql', true);
            update_post_meta($terport_id, '_generation_timestamp', current_time('mysql'));
        }
        
        return $terport_id;
    }
    
    /**
     * Store research metadata for the terport
     */
    private function store_terport_metadata($terport_id, $metadata) {
        foreach ($metadata as $key => $value) {
            update_post_meta($terport_id, '_' . $key, $value);
        }
    }
    
    /**
     * Generate content using fallback models
     */
    private function generate_with_fallback_models($messages, $terport_type) {
        $models = $this->get_model_fallback_hierarchy();
        $last_error = '';
        
        foreach ($models as $model_config) {
            $options = array(
                'model' => $model_config['model'],
                'max_tokens' => $model_config['max_tokens'],
                'temperature' => 0.3
            );
            
            $response = $this->openrouter_api->chat_completion($messages, $options);
            
            if (!is_wp_error($response) && isset($response['choices'][0]['message']['content'])) {
                // Success! Add metadata about which model was used
                $response['model_used'] = $model_config['model'];
                $response['model_type'] = $model_config['type'];
                return $response;
            }
            
            // Log the error and try next model
            $error_msg = is_wp_error($response) ? $response->get_error_message() : 'Unknown response format';
            $last_error .= "Model {$model_config['model']} failed: {$error_msg}; ";
            
            // Add small delay between attempts
            if ($model_config['type'] === 'premium') {
                sleep(1);
            }
        }
        
        return array('error' => 'All models failed: ' . $last_error);
    }
    
    /**
     * Get model fallback hierarchy
     */
    private function get_model_fallback_hierarchy() {
        return array(
            // Try premium models first
            array(
                'model' => 'anthropic/claude-3.5-sonnet',
                'max_tokens' => 4000,
                'type' => 'premium'
            ),
            array(
                'model' => 'openai/gpt-4o-mini',
                'max_tokens' => 4000,
                'type' => 'premium'
            ),
            // Fall back to free models
            array(
                'model' => 'meta-llama/llama-3.1-8b-instruct:free',
                'max_tokens' => 2000,
                'type' => 'free'
            ),
            array(
                'model' => 'microsoft/phi-3-mini-128k-instruct:free',
                'max_tokens' => 2000,
                'type' => 'free'
            ),
            array(
                'model' => 'google/gemma-2-9b-it:free',
                'max_tokens' => 1500,
                'type' => 'free'
            )
        );
    }
    
    /**
     * Consolidate research data from multiple sources
     */
    private function consolidate_research_data($federated_data, $sparql_data) {
        $processed_data = array(
            'federated_research' => array(),
            'sparql_results' => array(),
            'data_sources' => array(),
            'consolidation_timestamp' => current_time('mysql'),
            'total_results' => 0
        );
        
        // Process federated data
        foreach ($federated_data as $data) {
            if (isset($data['results']) && is_array($data['results'])) {
                $processed_data['federated_research'][] = $data;
                $processed_data['total_results'] += $data['count'] ?? count($data['results']);
            }
        }
        
        // Process SPARQL data
        foreach ($sparql_data as $source => $data) {
            if (isset($data['results']) && is_array($data['results'])) {
                $processed_data['sparql_results'][$source] = $data;
                $processed_data['data_sources'][] = ucfirst(str_replace('_', ' ', $source));
                $processed_data['total_results'] += $data['count'] ?? count($data['results']);
            }
        }
        
        // Add standard data sources
        $processed_data['data_sources'] = array_merge(
            $processed_data['data_sources'],
            array('UniProt', 'Gene Ontology', 'Disease Ontology', 'Wikidata', 'MeSH')
        );
        $processed_data['data_sources'] = array_unique($processed_data['data_sources']);
        
        return $processed_data;
    }
    
    /**
     * Get knowledge base version info
     */
    private function get_kb_version() {
        $response = wp_remote_get($this->kb_base_url . '/api/health');
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            return $data['version'] ?? 'unknown';
        }
        return 'unknown';
    }
    
    /**
     * Generate research-based content for terports (filter hook)
     */
    public function generate_research_based_content($content, $terport_id, $terport_type) {
        // Check if this is a SPARQL-enhanced terport
        $is_sparql_enhanced = get_post_meta($terport_id, '_sparql_enhanced', true);
        
        if (!$is_sparql_enhanced) {
            return $content; // Return original content if not SPARQL-enhanced
        }
        
        // Get research metadata for this terport
        $research_data = get_post_meta($terport_id, '_research_data', true);
        $terpenes = get_post_meta($terport_id, '_terpenes_analyzed', true);
        
        if (!$research_data || !$terpenes) {
            return $content; // Return original if no research data
        }
        
        // Enhance the content with research insights
        $enhanced_content = $content;
        
        // Add research summary section
        $enhanced_content .= "\n\n## Research Data Summary\n\n";
        $enhanced_content .= "This terport was generated using federated SPARQL queries across multiple biomedical databases.\n\n";
        
        if (isset($research_data['total_results'])) {
            $enhanced_content .= "**Total Research Results:** " . $research_data['total_results'] . " entries\n\n";
        }
        
        if (isset($research_data['data_sources']) && is_array($research_data['data_sources'])) {
            $enhanced_content .= "**Data Sources:** " . implode(', ', $research_data['data_sources']) . "\n\n";
        }
        
        // Add terpenes analyzed section
        if (is_array($terpenes) && !empty($terpenes)) {
            $enhanced_content .= "**Terpenes Analyzed:** " . implode(', ', $terpenes) . "\n\n";
        }
        
        return $enhanced_content;
    }
    
    /**
     * AJAX handler for terpene research queries
     */
    public function ajax_query_terpene_research() {
        check_ajax_referer('terpedia_terport_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $terpene_name = sanitize_text_field($_POST['terpene_name'] ?? '');
        $research_focus = sanitize_text_field($_POST['research_focus'] ?? '');
        
        if (empty($terpene_name)) {
            wp_send_json_error('Terpene name is required');
            return;
        }
        
        try {
            $research_data = $this->query_federated_terpene_research($terpene_name, $research_focus);
            
            // Process the data for frontend display
            $processed_results = array(
                'terpene' => $terpene_name,
                'research_focus' => $research_focus,
                'results' => $research_data,
                'summary' => $this->generate_research_summary($research_data),
                'timestamp' => current_time('mysql')
            );
            
            wp_send_json_success($processed_results);
            
        } catch (Exception $e) {
            error_log('Terpene research query error: ' . $e->getMessage());
            wp_send_json_error('Research query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a summary of research results
     */
    private function generate_research_summary($research_data) {
        $summary = array(
            'total_endpoints' => 0,
            'total_results' => 0,
            'successful_queries' => 0,
            'failed_queries' => 0,
            'key_findings' => array()
        );
        
        foreach ($research_data as $endpoint => $data) {
            $summary['total_endpoints']++;
            
            if (isset($data['error'])) {
                $summary['failed_queries']++;
            } else {
                $summary['successful_queries']++;
                $result_count = $data['count'] ?? (isset($data['results']) ? count($data['results']) : 0);
                $summary['total_results'] += $result_count;
                
                if ($result_count > 0) {
                    $summary['key_findings'][] = ucfirst(str_replace('_', ' ', $endpoint)) . ": {$result_count} results";
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * AJAX handler for comprehensive terport generation
     */
    public function ajax_generate_comprehensive_terport() {
        check_ajax_referer('terpedia_terport_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $terport_type = sanitize_text_field($_POST['terport_type']);
        $questions = array_map('sanitize_textarea_field', $_POST['research_questions']);
        
        $result = $this->generate_comprehensive_terport($title, $terport_type, $questions);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
    }
}

// Initialize the SPARQL integration
new Terpedia_Terport_SPARQL_Integration();