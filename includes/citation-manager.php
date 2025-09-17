<?php
/**
 * Terpedia Citation Manager
 * 
 * Comprehensive citation tracking and formatting system for terports and cyc encyclopedia
 * Tracks data sources from federated databases and provides academic-style citations
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Citation_Manager {
    
    private $db_table_name;
    private $supported_formats = array('apa', 'mla', 'chicago', 'bibtex');
    private $federated_endpoints;
    
    public function __construct() {
        global $wpdb;
        $this->db_table_name = $wpdb->prefix . 'terpedia_citations';
        
        // Initialize federated endpoints data
        $this->federated_endpoints = array(
            'uniprot_proteins' => array(
                'name' => 'UniProt',
                'full_name' => 'Universal Protein Resource',
                'url' => 'https://www.uniprot.org/',
                'sparql_endpoint' => 'https://sparql.uniprot.org/sparql',
                'type' => 'protein_database',
                'description' => 'Protein sequence and functional information'
            ),
            'gene_ontology' => array(
                'name' => 'Gene Ontology',
                'full_name' => 'Gene Ontology Consortium',
                'url' => 'http://geneontology.org/',
                'sparql_endpoint' => 'http://sparql.hegroup.org/sparql',
                'type' => 'ontology',
                'description' => 'Gene product function classification'
            ),
            'disease_ontology' => array(
                'name' => 'Disease Ontology',
                'full_name' => 'Human Disease Ontology',
                'url' => 'https://disease-ontology.org/',
                'sparql_endpoint' => 'http://sparql.hegroup.org/sparql',
                'type' => 'ontology',
                'description' => 'Human disease classification and terminology'
            ),
            'wikidata_compounds' => array(
                'name' => 'Wikidata',
                'full_name' => 'Wikidata Knowledge Base',
                'url' => 'https://www.wikidata.org/',
                'sparql_endpoint' => 'https://query.wikidata.org/sparql',
                'type' => 'knowledge_base',
                'description' => 'Collaborative knowledge base'
            ),
            'mesh_terms' => array(
                'name' => 'MeSH',
                'full_name' => 'Medical Subject Headings',
                'url' => 'https://www.ncbi.nlm.nih.gov/mesh/',
                'sparql_endpoint' => 'https://id.nlm.nih.gov/mesh/sparql',
                'type' => 'controlled_vocabulary',
                'description' => 'Biomedical vocabulary thesaurus'
            ),
            'pubmed' => array(
                'name' => 'PubMed',
                'full_name' => 'PubMed/MEDLINE Database',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/',
                'sparql_endpoint' => null,
                'type' => 'literature_database',
                'description' => 'Biomedical literature database'
            ),
            'kb_terpedia' => array(
                'name' => 'Terpedia Knowledge Base',
                'full_name' => 'Terpedia Federated Knowledge Base',
                'url' => 'https://kb.terpedia.com/',
                'sparql_endpoint' => 'https://kb.terpedia.com:3030/biodb/sparql',
                'type' => 'specialized_database',
                'description' => 'Comprehensive terpene research database with federated biological data'
            )
        );
        
        add_action('init', array($this, 'create_citations_table'));
        add_action('wp_ajax_export_citations', array($this, 'ajax_export_citations'));
        add_action('wp_ajax_get_post_citations', array($this, 'ajax_get_post_citations'));
        add_action('wp_ajax_validate_citation_links', array($this, 'ajax_validate_citation_links'));
        
        // WordPress post integration hooks
        add_action('add_meta_boxes', array($this, 'add_citation_meta_boxes'));
        add_filter('the_content', array($this, 'append_citations_to_content'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_citation_styles'));
    }
    
    /**
     * Create citations database table
     */
    public function create_citations_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db_table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            post_type varchar(50) NOT NULL,
            source_type varchar(100) NOT NULL,
            source_identifier varchar(500) NOT NULL,
            source_url varchar(1000) DEFAULT NULL,
            query_executed text DEFAULT NULL,
            results_count int(11) DEFAULT 0,
            access_date datetime DEFAULT CURRENT_TIMESTAMP,
            metadata json DEFAULT NULL,
            citation_text text DEFAULT NULL,
            is_primary_source tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY post_type (post_type),
            KEY source_type (source_type),
            KEY access_date (access_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Track a citation for a specific post
     */
    public function add_citation($post_id, $post_type, $source_type, $source_identifier, $options = array()) {
        global $wpdb;
        
        $defaults = array(
            'source_url' => null,
            'query_executed' => null,
            'results_count' => 0,
            'metadata' => array(),
            'is_primary_source' => false
        );
        $options = wp_parse_args($options, $defaults);
        
        // Generate citation URL if not provided
        if (empty($options['source_url'])) {
            $options['source_url'] = $this->generate_source_url($source_type, $source_identifier);
        }
        
        // Generate citation text
        $citation_text = $this->format_citation($source_type, $source_identifier, $options['metadata']);
        
        $data = array(
            'post_id' => $post_id,
            'post_type' => $post_type,
            'source_type' => $source_type,
            'source_identifier' => $source_identifier,
            'source_url' => $options['source_url'],
            'query_executed' => $options['query_executed'],
            'results_count' => $options['results_count'],
            'metadata' => json_encode($options['metadata']),
            'citation_text' => $citation_text,
            'is_primary_source' => $options['is_primary_source'] ? 1 : 0,
            'access_date' => current_time('mysql')
        );
        
        // Check if citation already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->db_table_name} 
             WHERE post_id = %d AND source_type = %s AND source_identifier = %s",
            $post_id, $source_type, $source_identifier
        ));
        
        if ($existing) {
            // Update existing citation
            $wpdb->update($this->db_table_name, $data, array('id' => $existing));
            return $existing;
        } else {
            // Insert new citation
            $wpdb->insert($this->db_table_name, $data);
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Get all citations for a post
     */
    public function get_post_citations($post_id, $format = 'array') {
        global $wpdb;
        
        $citations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->db_table_name} 
             WHERE post_id = %d 
             ORDER BY is_primary_source DESC, source_type ASC, created_at ASC",
            $post_id
        ), ARRAY_A);
        
        if ($format === 'formatted') {
            return $this->format_citations_list($citations);
        }
        
        return $citations;
    }
    
    /**
     * Generate formatted citations HTML for display
     */
    public function format_citations_list($citations, $format = 'apa') {
        if (empty($citations)) {
            return '';
        }
        
        $html = '<div class="terpedia-citations-section">';
        $html .= '<h3 class="citations-title">Sources</h3>';
        $html .= '<div class="citations-list">';
        
        $grouped_citations = $this->group_citations_by_type($citations);
        
        foreach ($grouped_citations as $type => $type_citations) {
            if (count($grouped_citations) > 1) {
                $type_info = $this->federated_endpoints[$type] ?? array('name' => ucfirst($type));
                $html .= '<h4 class="citation-type-header">' . $type_info['name'] . '</h4>';
            }
            
            $html .= '<ol class="citation-entries">';
            foreach ($type_citations as $citation) {
                $html .= '<li class="citation-entry" data-source="' . esc_attr($citation['source_type']) . '">';
                $html .= '<div class="citation-text">' . $citation['citation_text'] . '</div>';
                
                if (!empty($citation['source_url'])) {
                    $html .= '<div class="citation-link">';
                    $html .= '<a href="' . esc_url($citation['source_url']) . '" target="_blank" rel="noopener">';
                    $html .= 'View Source <span class="external-link-icon">↗</span></a>';
                    $html .= '</div>';
                }
                
                $html .= '<div class="citation-metadata">';
                $html .= '<small>Accessed: ' . date('F j, Y', strtotime($citation['access_date'])) . '</small>';
                if ($citation['results_count'] > 0) {
                    $html .= '<small> • ' . $citation['results_count'] . ' results found</small>';
                }
                $html .= '</div>';
                $html .= '</li>';
            }
            $html .= '</ol>';
        }
        
        $html .= '</div>';
        $html .= '<div class="citations-export">';
        $html .= '<button type="button" class="export-citations-btn" data-post-id="' . $citations[0]['post_id'] . '">';
        $html .= 'Export Citations</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Group citations by source type
     */
    private function group_citations_by_type($citations) {
        $grouped = array();
        foreach ($citations as $citation) {
            $type = $citation['source_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = array();
            }
            $grouped[$type][] = $citation;
        }
        return $grouped;
    }
    
    /**
     * Format individual citation based on source type and format
     */
    public function format_citation($source_type, $source_identifier, $metadata = array(), $format = 'apa') {
        $source_info = $this->federated_endpoints[$source_type] ?? array();
        $current_date = date('Y, F j');
        
        switch ($source_type) {
            case 'uniprot_proteins':
                return $this->format_uniprot_citation($source_identifier, $metadata, $format);
            
            case 'gene_ontology':
                return $this->format_gene_ontology_citation($source_identifier, $metadata, $format);
            
            case 'disease_ontology':
                return $this->format_disease_ontology_citation($source_identifier, $metadata, $format);
            
            case 'wikidata_compounds':
                return $this->format_wikidata_citation($source_identifier, $metadata, $format);
            
            case 'mesh_terms':
                return $this->format_mesh_citation($source_identifier, $metadata, $format);
            
            case 'pubmed':
                return $this->format_pubmed_citation($source_identifier, $metadata, $format);
            
            case 'kb_terpedia':
                return $this->format_kb_terpedia_citation($source_identifier, $metadata, $format);
            
            default:
                return $this->format_generic_citation($source_type, $source_identifier, $metadata, $format);
        }
    }
    
    /**
     * Format UniProt citation
     */
    private function format_uniprot_citation($identifier, $metadata, $format) {
        $protein_name = $metadata['protein_name'] ?? $identifier;
        $organism = $metadata['organism'] ?? 'Unknown organism';
        
        switch ($format) {
            case 'apa':
                return "UniProt Consortium. (2023). {$protein_name} [{$identifier}]. UniProt. https://www.uniprot.org/uniprot/{$identifier}";
            
            case 'bibtex':
                return "@misc{uniprot_{$identifier},\n" .
                       "  title={{$protein_name}},\n" .
                       "  author={{UniProt Consortium}},\n" .
                       "  year={2023},\n" .
                       "  url={https://www.uniprot.org/uniprot/{$identifier}},\n" .
                       "  note={{$organism}}\n" .
                       "}";
            
            default:
                return "UniProt Consortium. {$protein_name} [{$identifier}]. UniProt, 2023. https://www.uniprot.org/uniprot/{$identifier}";
        }
    }
    
    /**
     * Format Gene Ontology citation
     */
    private function format_gene_ontology_citation($identifier, $metadata, $format) {
        $term_name = $metadata['term_name'] ?? $identifier;
        
        switch ($format) {
            case 'apa':
                return "Gene Ontology Consortium. (2023). {$term_name} [{$identifier}]. Gene Ontology. http://amigo.geneontology.org/amigo/term/{$identifier}";
            
            case 'bibtex':
                return "@misc{go_{$identifier},\n" .
                       "  title={{$term_name}},\n" .
                       "  author={{Gene Ontology Consortium}},\n" .
                       "  year={2023},\n" .
                       "  url={http://amigo.geneontology.org/amigo/term/{$identifier}}\n" .
                       "}";
            
            default:
                return "Gene Ontology Consortium. {$term_name} [{$identifier}]. Gene Ontology, 2023. http://amigo.geneontology.org/amigo/term/{$identifier}";
        }
    }
    
    /**
     * Format Disease Ontology citation
     */
    private function format_disease_ontology_citation($identifier, $metadata, $format) {
        $term_name = $metadata['term_name'] ?? $identifier;
        
        switch ($format) {
            case 'apa':
                return "Schriml, L. M., et al. (2023). {$term_name} [{$identifier}]. Human Disease Ontology. https://disease-ontology.org/term/{$identifier}";
            
            case 'bibtex':
                return "@misc{do_{$identifier},\n" .
                       "  title={{$term_name}},\n" .
                       "  author={{Schriml, Lynn Marie and others}},\n" .
                       "  year={2023},\n" .
                       "  url={https://disease-ontology.org/term/{$identifier}}\n" .
                       "}";
            
            default:
                return "Schriml, L. M., et al. {$term_name} [{$identifier}]. Human Disease Ontology, 2023. https://disease-ontology.org/term/{$identifier}";
        }
    }
    
    /**
     * Format Wikidata citation
     */
    private function format_wikidata_citation($identifier, $metadata, $format) {
        $item_label = $metadata['item_label'] ?? $identifier;
        
        switch ($format) {
            case 'apa':
                return "Wikidata contributors. (2023). {$item_label} [{$identifier}]. Wikidata. https://www.wikidata.org/wiki/{$identifier}";
            
            case 'bibtex':
                return "@misc{wd_{$identifier},\n" .
                       "  title={{$item_label}},\n" .
                       "  author={{Wikidata contributors}},\n" .
                       "  year={2023},\n" .
                       "  url={https://www.wikidata.org/wiki/{$identifier}}\n" .
                       "}";
            
            default:
                return "Wikidata contributors. {$item_label} [{$identifier}]. Wikidata, 2023. https://www.wikidata.org/wiki/{$identifier}";
        }
    }
    
    /**
     * Format MeSH citation
     */
    private function format_mesh_citation($identifier, $metadata, $format) {
        $term_name = $metadata['term_name'] ?? $identifier;
        
        switch ($format) {
            case 'apa':
                return "National Library of Medicine. (2023). {$term_name} [{$identifier}]. Medical Subject Headings. https://www.ncbi.nlm.nih.gov/mesh/{$identifier}";
            
            case 'bibtex':
                return "@misc{mesh_{$identifier},\n" .
                       "  title={{$term_name}},\n" .
                       "  author={{National Library of Medicine}},\n" .
                       "  year={2023},\n" .
                       "  url={https://www.ncbi.nlm.nih.gov/mesh/{$identifier}}\n" .
                       "}";
            
            default:
                return "National Library of Medicine. {$term_name} [{$identifier}]. Medical Subject Headings, 2023. https://www.ncbi.nlm.nih.gov/mesh/{$identifier}";
        }
    }
    
    /**
     * Format PubMed citation
     */
    private function format_pubmed_citation($identifier, $metadata, $format) {
        $title = $metadata['title'] ?? 'PubMed Article';
        $authors = $metadata['authors'] ?? 'Authors';
        $journal = $metadata['journal'] ?? 'Journal';
        $year = $metadata['year'] ?? date('Y');
        
        switch ($format) {
            case 'apa':
                return "{$authors} ({$year}). {$title}. {$journal}. https://pubmed.ncbi.nlm.nih.gov/{$identifier}/";
            
            case 'bibtex':
                return "@article{pubmed_{$identifier},\n" .
                       "  title={{$title}},\n" .
                       "  author={{$authors}},\n" .
                       "  journal={{$journal}},\n" .
                       "  year={{$year}},\n" .
                       "  url={https://pubmed.ncbi.nlm.nih.gov/{$identifier}/}\n" .
                       "}";
            
            default:
                return "{$authors}. {$title}. {$journal}, {$year}. https://pubmed.ncbi.nlm.nih.gov/{$identifier}/";
        }
    }
    
    /**
     * Format kb.terpedia.com citation
     */
    private function format_kb_terpedia_citation($identifier, $metadata, $format) {
        $query_type = $metadata['query_type'] ?? 'federated query';
        $terpene_name = $metadata['terpene_name'] ?? $identifier;
        
        switch ($format) {
            case 'apa':
                return "Terpedia Knowledge Base. (2023). {$terpene_name} federated database query results [{$identifier}]. Terpedia. https://kb.terpedia.com/query/{$identifier}";
            
            case 'bibtex':
                return "@misc{kb_terpedia_{$identifier},\n" .
                       "  title={{$terpene_name} federated database query results},\n" .
                       "  author={{Terpedia Knowledge Base}},\n" .
                       "  year={2023},\n" .
                       "  url={https://kb.terpedia.com/query/{$identifier}}\n" .
                       "}";
            
            default:
                return "Terpedia Knowledge Base. {$terpene_name} federated database query results [{$identifier}]. Terpedia, 2023. https://kb.terpedia.com/query/{$identifier}";
        }
    }
    
    /**
     * Format generic citation for unknown sources
     */
    private function format_generic_citation($source_type, $identifier, $metadata, $format) {
        $source_info = $this->federated_endpoints[$source_type] ?? array();
        $source_name = $source_info['name'] ?? ucfirst(str_replace('_', ' ', $source_type));
        $source_url = $source_info['url'] ?? '#';
        
        switch ($format) {
            case 'apa':
                return "{$source_name}. (2023). Data entry [{$identifier}]. {$source_url}";
            
            case 'bibtex':
                return "@misc{{$source_type}_{$identifier},\n" .
                       "  title={{Data entry}},\n" .
                       "  author{{{$source_name}}},\n" .
                       "  year={2023},\n" .
                       "  url{{$source_url}}\n" .
                       "}";
            
            default:
                return "{$source_name}. Data entry [{$identifier}]. 2023. {$source_url}";
        }
    }
    
    /**
     * Generate source URL based on source type and identifier
     */
    public function generate_source_url($source_type, $identifier) {
        switch ($source_type) {
            case 'uniprot_proteins':
                return "https://www.uniprot.org/uniprot/{$identifier}";
            
            case 'gene_ontology':
                return "http://amigo.geneontology.org/amigo/term/{$identifier}";
            
            case 'disease_ontology':
                return "https://disease-ontology.org/term/{$identifier}";
            
            case 'wikidata_compounds':
                return "https://www.wikidata.org/wiki/{$identifier}";
            
            case 'mesh_terms':
                return "https://www.ncbi.nlm.nih.gov/mesh/{$identifier}";
            
            case 'pubmed':
                return "https://pubmed.ncbi.nlm.nih.gov/{$identifier}/";
            
            case 'kb_terpedia':
                return "https://kb.terpedia.com/query/{$identifier}";
            
            default:
                $source_info = $this->federated_endpoints[$source_type] ?? array();
                return $source_info['url'] ?? '#';
        }
    }
    
    /**
     * Export citations in various formats
     */
    public function export_citations($post_id, $format = 'bibtex') {
        $citations = $this->get_post_citations($post_id);
        $output = '';
        
        switch ($format) {
            case 'bibtex':
                $output = $this->export_bibtex($citations);
                break;
            
            case 'apa':
                $output = $this->export_apa($citations);
                break;
            
            case 'mla':
                $output = $this->export_mla($citations);
                break;
            
            case 'json':
                $output = json_encode($citations, JSON_PRETTY_PRINT);
                break;
            
            default:
                $output = $this->export_bibtex($citations);
        }
        
        return $output;
    }
    
    /**
     * Export citations in BibTeX format
     */
    private function export_bibtex($citations) {
        $output = "% Terpedia Citation Export - BibTeX Format\n";
        $output .= "% Generated on " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($citations as $citation) {
            $bibtex = $this->format_citation(
                $citation['source_type'], 
                $citation['source_identifier'], 
                json_decode($citation['metadata'], true) ?? array(), 
                'bibtex'
            );
            $output .= $bibtex . "\n\n";
        }
        
        return $output;
    }
    
    /**
     * Export citations in APA format
     */
    private function export_apa($citations) {
        $output = "Terpedia Citation Export - APA Format\n";
        $output .= "Generated on " . date('F j, Y') . "\n\n";
        $output .= "References:\n\n";
        
        $counter = 1;
        foreach ($citations as $citation) {
            $apa = $this->format_citation(
                $citation['source_type'], 
                $citation['source_identifier'], 
                json_decode($citation['metadata'], true) ?? array(), 
                'apa'
            );
            $output .= "{$counter}. {$apa}\n\n";
            $counter++;
        }
        
        return $output;
    }
    
    /**
     * Export citations in MLA format
     */
    private function export_mla($citations) {
        $output = "Terpedia Citation Export - MLA Format\n";
        $output .= "Generated on " . date('j F Y') . "\n\n";
        $output .= "Works Cited:\n\n";
        
        foreach ($citations as $citation) {
            $mla = $this->format_citation(
                $citation['source_type'], 
                $citation['source_identifier'], 
                json_decode($citation['metadata'], true) ?? array(), 
                'mla'
            );
            $output .= "{$mla}\n\n";
        }
        
        return $output;
    }
    
    /**
     * AJAX handler for exporting citations
     */
    public function ajax_export_citations() {
        check_ajax_referer('terpedia_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $format = sanitize_text_field($_POST['format'] ?? 'bibtex');
        
        if (!$post_id) {
            wp_die('Invalid post ID');
        }
        
        $exported_citations = $this->export_citations($post_id, $format);
        
        $filename = "terpedia_citations_{$post_id}_{$format}.txt";
        
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($exported_citations));
        
        echo $exported_citations;
        exit;
    }
    
    /**
     * AJAX handler for getting post citations
     */
    public function ajax_get_post_citations() {
        check_ajax_referer('terpedia_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $format = sanitize_text_field($_POST['format'] ?? 'formatted');
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $citations = $this->get_post_citations($post_id, $format);
        
        wp_send_json_success(array(
            'citations' => $citations,
            'count' => count($citations)
        ));
    }
    
    /**
     * AJAX handler for validating citation links
     */
    public function ajax_validate_citation_links() {
        check_ajax_referer('terpedia_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $citations = $this->get_post_citations($post_id);
        $validation_results = array();
        
        foreach ($citations as $citation) {
            if (!empty($citation['source_url'])) {
                $response = wp_remote_head($citation['source_url'], array('timeout' => 10));
                $is_valid = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
                
                $validation_results[] = array(
                    'id' => $citation['id'],
                    'source_url' => $citation['source_url'],
                    'is_valid' => $is_valid,
                    'response_code' => is_wp_error($response) ? 'error' : wp_remote_retrieve_response_code($response)
                );
            }
        }
        
        wp_send_json_success($validation_results);
    }
    
    /**
     * Get summary statistics for citation usage
     */
    public function get_citation_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total citations
        $stats['total_citations'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->db_table_name}");
        
        // Citations by source type
        $stats['by_source_type'] = $wpdb->get_results(
            "SELECT source_type, COUNT(*) as count 
             FROM {$this->db_table_name} 
             GROUP BY source_type 
             ORDER BY count DESC",
            ARRAY_A
        );
        
        // Citations by post type
        $stats['by_post_type'] = $wpdb->get_results(
            "SELECT post_type, COUNT(*) as count 
             FROM {$this->db_table_name} 
             GROUP BY post_type 
             ORDER BY count DESC",
            ARRAY_A
        );
        
        // Recent citations
        $stats['recent_citations'] = $wpdb->get_results(
            "SELECT * FROM {$this->db_table_name} 
             ORDER BY created_at DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        return $stats;
    }
    
    /**
     * Clean up orphaned citations (for posts that no longer exist)
     */
    public function cleanup_orphaned_citations() {
        global $wpdb;
        
        $orphaned = $wpdb->query("
            DELETE c FROM {$this->db_table_name} c
            LEFT JOIN {$wpdb->posts} p ON c.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        return $orphaned;
    }
    
    /**
     * Add citation meta boxes to WordPress admin
     */
    public function add_citation_meta_boxes() {
        $post_types = array('terpedia_terport', 'encyclopedia_entry', 'post', 'page');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'terpedia_citations',
                'Sources & Citations',
                array($this, 'render_citation_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Render citation meta box
     */
    public function render_citation_meta_box($post) {
        $citations = $this->get_post_citations($post->ID);
        
        echo '<div id="terpedia-citations-meta">';
        
        if (empty($citations)) {
            echo '<p><em>No citations recorded for this post. Citations are automatically added when content is generated using federated databases.</em></p>';
        } else {
            echo '<h4>Recorded Sources (' . count($citations) . ')</h4>';
            echo '<div class="citations-preview">';
            echo $this->format_citations_list($citations, 'apa');
            echo '</div>';
            
            echo '<div class="citation-actions" style="margin-top: 15px;">';
            echo '<button type="button" class="button" onclick="exportCitations(' . $post->ID . ', \'bibtex\')">Export BibTeX</button> ';
            echo '<button type="button" class="button" onclick="exportCitations(' . $post->ID . ', \'apa\')">Export APA</button> ';
            echo '<button type="button" class="button" onclick="validateCitations(' . $post->ID . ')">Validate Links</button>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add JavaScript for citation actions
        echo '<script>
        function exportCitations(postId, format) {
            var url = ajaxurl + "?action=export_citations&post_id=" + postId + "&format=" + format + "&nonce=" + "' . wp_create_nonce('terpedia_nonce') . '";
            window.open(url, "_blank");
        }
        
        function validateCitations(postId) {
            jQuery.post(ajaxurl, {
                action: "validate_citation_links",
                post_id: postId,
                nonce: "' . wp_create_nonce('terpedia_nonce') . '"
            }, function(response) {
                if (response.success) {
                    var validCount = 0;
                    var totalCount = response.data.length;
                    response.data.forEach(function(result) {
                        if (result.is_valid) validCount++;
                    });
                    alert("Citation validation complete: " + validCount + "/" + totalCount + " links are accessible.");
                } else {
                    alert("Error validating citations: " + response.data);
                }
            });
        }
        </script>';
    }
    
    /**
     * Append citations to post content on frontend
     */
    public function append_citations_to_content($content) {
        if (!is_single() && !is_page()) {
            return $content;
        }
        
        global $post;
        if (!$post || !in_array($post->post_type, array('terpedia_terport', 'encyclopedia_entry'))) {
            return $content;
        }
        
        $citations = $this->get_post_citations($post->ID);
        if (empty($citations)) {
            return $content;
        }
        
        $citations_html = $this->format_citations_list($citations, 'apa');
        
        return $content . $citations_html;
    }
    
    /**
     * Enqueue citation styles for frontend
     */
    public function enqueue_citation_styles() {
        wp_add_inline_style('wp-block-library', '
            .terpedia-citations-section {
                margin-top: 40px;
                padding: 20px;
                border-top: 2px solid #e1e1e1;
                background: #f9f9f9;
            }
            
            .citations-title {
                font-size: 1.2em;
                font-weight: bold;
                margin-bottom: 15px;
                color: #333;
            }
            
            .citation-type-header {
                font-size: 1.1em;
                font-weight: 600;
                margin: 20px 0 10px 0;
                color: #0073aa;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
            }
            
            .citation-entries {
                margin: 0 0 20px 20px;
                padding: 0;
            }
            
            .citation-entry {
                margin-bottom: 15px;
                line-height: 1.4;
            }
            
            .citation-text {
                margin-bottom: 5px;
            }
            
            .citation-link a {
                color: #0073aa;
                text-decoration: none;
                font-size: 0.9em;
            }
            
            .citation-link a:hover {
                text-decoration: underline;
            }
            
            .external-link-icon {
                font-size: 0.8em;
                margin-left: 3px;
            }
            
            .citation-metadata {
                font-size: 0.85em;
                color: #666;
                margin-top: 5px;
            }
            
            .citation-metadata small {
                margin-right: 15px;
            }
            
            .citations-export {
                margin-top: 20px;
                text-align: center;
            }
            
            .export-citations-btn {
                background: #0073aa;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.9em;
            }
            
            .export-citations-btn:hover {
                background: #005a87;
            }
        ');
    }
}