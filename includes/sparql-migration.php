<?php
/**
 * TULIP to SPARQL Knowledge Base Migration
 * Converts MySQL TULIP facts to RDF triples for kb.terpedia.com
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaSPARQLMigration {
    
    private $sparql_endpoint;
    private $namespace = 'https://kb.terpedia.com/ontology#';
    
    public function __construct() {
        $this->sparql_endpoint = 'https://kb.terpedia.com/sparql';
    }
    
    /**
     * Migrate all TULIP facts from MySQL to SPARQL
     */
    public function migrate_all_facts() {
        global $wpdb;
        
        $facts_table = $wpdb->prefix . 'tulip_facts';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$facts_table'") == $facts_table;
        
        if (!$table_exists) {
            return array('success' => false, 'message' => 'TULIP facts table does not exist');
        }
        
        // Get all facts
        $facts = $wpdb->get_results("SELECT * FROM $facts_table");
        
        if (!$facts) {
            return array('success' => false, 'message' => 'No facts found to migrate');
        }
        
        $migrated_count = 0;
        $errors = array();
        
        foreach ($facts as $fact) {
            $result = $this->migrate_single_fact($fact);
            if ($result['success']) {
                $migrated_count++;
            } else {
                $errors[] = "Fact {$fact->fact_id}: {$result['message']}";
            }
        }
        
        return array(
            'success' => true,
            'migrated_count' => $migrated_count,
            'total_count' => count($facts),
            'errors' => $errors
        );
    }
    
    /**
     * Migrate a single fact to SPARQL
     */
    private function migrate_single_fact($fact) {
        $turtle = $this->generate_turtle_for_fact($fact);
        
        // Send to SPARQL endpoint
        $result = $this->insert_turtle_to_sparql($turtle);
        
        return $result;
    }
    
    /**
     * Generate Turtle RDF for a single fact
     */
    private function generate_turtle_for_fact($fact) {
        $fact_uri = $this->namespace . 'fact_' . $fact->fact_id;
        $terpene_uri = $this->namespace . $this->sanitize_terpene_name($fact->terpene_name);
        
        $turtle = "@prefix terpedia: <{$this->namespace}> .\n";
        $turtle .= "@prefix dc: <http://purl.org/dc/elements/1.1/> .\n";
        $turtle .= "@prefix dcterms: <http://purl.org/dc/terms/> .\n";
        $turtle .= "@prefix schema: <https://schema.org/> .\n";
        $turtle .= "@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .\n\n";
        
        // Fact definition
        $turtle .= "<{$fact_uri}> a terpedia:TULIPFact ;\n";
        $turtle .= "    dc:title \"{$this->escape_turtle_string($fact->title)}\" ;\n";
        $turtle .= "    dcterms:description \"{$this->escape_turtle_string($fact->statement)}\" ;\n";
        
        // Evidence level
        $evidence_level = $this->map_evidence_level($fact->evidence_level);
        $turtle .= "    terpedia:hasEvidenceLevel terpedia:{$evidence_level} ;\n";
        
        // Terpene
        if (!empty($fact->terpene_name)) {
            $turtle .= "    terpedia:aboutTerpene <{$terpene_uri}> ;\n";
        }
        
        // Category
        if (!empty($fact->category)) {
            $category_uri = $this->namespace . $this->sanitize_category_name($fact->category);
            $turtle .= "    terpedia:hasCategory <{$category_uri}> ;\n";
        }
        
        // Status
        $status_uri = $this->namespace . ucfirst($fact->status);
        $turtle .= "    terpedia:hasStatus <{$status_uri}> ;\n";
        
        // Confidence level (derived from evidence level)
        $confidence = $this->calculate_confidence($fact->evidence_level);
        $turtle .= "    terpedia:hasConfidenceLevel {$confidence} ;\n";
        
        // Sources
        if (!empty($fact->sources)) {
            $sources = explode("\n", $fact->sources);
            foreach ($sources as $source) {
                $source = trim($source);
                if (!empty($source)) {
                    $turtle .= "    terpedia:hasSource <{$source}> ;\n";
                }
            }
        }
        
        // Citations
        if (!empty($fact->citations)) {
            $citations = explode("\n", $fact->citations);
            foreach ($citations as $citation) {
                $citation = trim($citation);
                if (!empty($citation)) {
                    $turtle .= "    terpedia:hasCitation \"{$this->escape_turtle_string($citation)}\" ;\n";
                }
            }
        }
        
        // Timestamps
        if (!empty($fact->created_at)) {
            $turtle .= "    dcterms:created \"{$fact->created_at}\"^^xsd:dateTime ;\n";
        }
        if (!empty($fact->updated_at)) {
            $turtle .= "    dcterms:modified \"{$fact->updated_at}\"^^xsd:dateTime ;\n";
        }
        
        // Remove trailing semicolon and add period
        $turtle = rtrim($turtle, " ;\n") . " .\n\n";
        
        // Add terpene definition if not empty
        if (!empty($fact->terpene_name)) {
            $turtle .= "<{$terpene_uri}> a terpedia:Terpene ;\n";
            $turtle .= "    rdfs:label \"{$this->escape_turtle_string($fact->terpene_name)}\" .\n\n";
        }
        
        return $turtle;
    }
    
    /**
     * Insert Turtle data to SPARQL endpoint
     */
    private function insert_turtle_to_sparql($turtle) {
        $url = $this->sparql_endpoint;
        
        $data = array(
            'update' => "INSERT DATA { {$turtle} }"
        );
        
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Failed to connect to SPARQL endpoint');
        }
        
        return array('success' => true, 'message' => 'Successfully inserted to SPARQL');
    }
    
    /**
     * Query SPARQL endpoint
     */
    public function query_sparql($sparql_query) {
        $url = $this->sparql_endpoint . '?query=' . urlencode($sparql_query);
        
        $context = stream_context_create(array(
            'http' => array(
                'header' => "Accept: application/sparql-results+json\r\n"
            )
        ));
        
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return array('success' => false, 'message' => 'Failed to query SPARQL endpoint');
        }
        
        $data = json_decode($result, true);
        return array('success' => true, 'data' => $data);
    }
    
    /**
     * Helper functions
     */
    private function map_evidence_level($level) {
        $mapping = array(
            'verified' => 'Verified',
            'peer_reviewed' => 'PeerReviewed',
            'preliminary' => 'Preliminary',
            'anecdotal' => 'Anecdotal'
        );
        
        return isset($mapping[$level]) ? $mapping[$level] : 'Preliminary';
    }
    
    private function calculate_confidence($evidence_level) {
        $confidence_map = array(
            'verified' => 0.95,
            'peer_reviewed' => 0.85,
            'preliminary' => 0.60,
            'anecdotal' => 0.30
        );
        
        return isset($confidence_map[$evidence_level]) ? $confidence_map[$evidence_level] : 0.50;
    }
    
    private function sanitize_terpene_name($name) {
        return preg_replace('/[^a-zA-Z0-9]/', '', $name);
    }
    
    private function sanitize_category_name($name) {
        return preg_replace('/[^a-zA-Z0-9]/', '', $name);
    }
    
    private function escape_turtle_string($string) {
        return str_replace(array('"', '\\'), array('\\"', '\\\\'), $string);
    }
    
    /**
     * Get facts from SPARQL endpoint
     */
    public function get_facts_from_sparql($filters = array()) {
        $sparql = "PREFIX terpedia: <{$this->namespace}>\n";
        $sparql .= "PREFIX dc: <http://purl.org/dc/elements/1.1/>\n";
        $sparql .= "SELECT ?fact ?title ?description ?terpene ?confidence ?evidenceLevel WHERE {\n";
        $sparql .= "    ?fact a terpedia:TULIPFact ;\n";
        $sparql .= "          dc:title ?title ;\n";
        $sparql .= "          dcterms:description ?description ;\n";
        $sparql .= "          terpedia:hasConfidenceLevel ?confidence ;\n";
        $sparql .= "          terpedia:hasEvidenceLevel ?evidenceLevel .\n";
        $sparql .= "    OPTIONAL { ?fact terpedia:aboutTerpene ?terpene . }\n";
        
        // Add filters
        if (isset($filters['min_confidence'])) {
            $sparql .= "    FILTER(?confidence >= {$filters['min_confidence']})\n";
        }
        
        if (isset($filters['evidence_level'])) {
            $sparql .= "    FILTER(?evidenceLevel = terpedia:{$filters['evidence_level']})\n";
        }
        
        if (isset($filters['terpene'])) {
            $sparql .= "    FILTER(?terpene = terpedia:{$filters['terpene']})\n";
        }
        
        $sparql .= "}\n";
        $sparql .= "ORDER BY DESC(?confidence)\n";
        
        if (isset($filters['limit'])) {
            $sparql .= "LIMIT {$filters['limit']}\n";
        }
        
        return $this->query_sparql($sparql);
    }
}
