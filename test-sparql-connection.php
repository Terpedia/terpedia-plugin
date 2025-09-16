<?php
/**
 * Test SPARQL Connection to kb.terpedia.com
 * Run this script to verify the connection and test queries
 */

// Test basic SPARQL connection
function test_sparql_connection() {
    $sparql_endpoint = 'https://kb.terpedia.com/sparql';
    
    // Simple test query
    $test_query = "SELECT ?s ?p ?o WHERE { ?s ?p ?o } LIMIT 5";
    $url = $sparql_endpoint . '?query=' . urlencode($test_query);
    
    $context = stream_context_create(array(
        'http' => array(
            'header' => "Accept: application/sparql-results+json\r\n",
            'timeout' => 10
        )
    ));
    
    echo "🔍 Testing SPARQL connection to: $sparql_endpoint\n";
    echo "📋 Test query: $test_query\n\n";
    
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        echo "❌ Connection failed\n";
        return false;
    }
    
    $data = json_decode($result, true);
    
    if (!$data) {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . substr($result, 0, 200) . "...\n";
        return false;
    }
    
    echo "✅ Connection successful!\n";
    echo "📊 Response structure:\n";
    print_r($data);
    
    return true;
}

// Test TULIP-specific queries
function test_tulip_queries() {
    $sparql_endpoint = 'https://kb.terpedia.com/sparql';
    
    // Test if TULIP facts exist
    $tulip_query = "PREFIX terpedia: <https://kb.terpedia.com/ontology#>
                    SELECT (COUNT(*) as ?count) WHERE {
                        ?fact a terpedia:TULIPFact .
                    }";
    
    $url = $sparql_endpoint . '?query=' . urlencode($tulip_query);
    
    $context = stream_context_create(array(
        'http' => array(
            'header' => "Accept: application/sparql-results+json\r\n",
            'timeout' => 10
        )
    ));
    
    echo "\n🔍 Testing TULIP facts query...\n";
    echo "📋 Query: $tulip_query\n\n";
    
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        echo "❌ TULIP query failed\n";
        return false;
    }
    
    $data = json_decode($result, true);
    
    if (!$data) {
        echo "❌ Invalid JSON response for TULIP query\n";
        return false;
    }
    
    $count = $data['results']['bindings'][0]['count']['value'] ?? '0';
    echo "✅ TULIP query successful!\n";
    echo "📊 Found $count TULIP facts in the knowledge base\n";
    
    return true;
}

// Test sample TULIP fact insertion
function test_tulip_insertion() {
    $sparql_endpoint = 'https://kb.terpedia.com/sparql';
    
    // Sample TULIP fact in Turtle format
    $turtle_data = "@prefix terpedia: <https://kb.terpedia.com/ontology#> .
@prefix dc: <http://purl.org/dc/elements/1.1/> .
@prefix dcterms: <http://purl.org/dc/terms/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .

terpedia:fact_test_001 a terpedia:TULIPFact ;
    dc:title \"Test TULIP Fact - Linalool has anxiolytic properties\" ;
    dcterms:description \"This is a test fact to verify SPARQL insertion works correctly\" ;
    terpedia:hasEvidenceLevel terpedia:Preliminary ;
    terpedia:aboutTerpene terpedia:Linalool ;
    terpedia:hasConfidenceLevel 0.75 ;
    dcterms:created \"2025-09-16T23:00:00Z\"^^xsd:dateTime .

terpedia:Linalool a terpedia:Terpene ;
    rdfs:label \"Linalool\" .";
    
    $data = array(
        'update' => "INSERT DATA { $turtle_data }"
    );
    
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        )
    );
    
    echo "\n🔍 Testing TULIP fact insertion...\n";
    echo "📋 Inserting test fact: fact_test_001\n\n";
    
    $context = stream_context_create($options);
    $result = file_get_contents($sparql_endpoint, false, $context);
    
    if ($result === false) {
        echo "❌ Insertion failed\n";
        return false;
    }
    
    echo "✅ Insertion successful!\n";
    echo "📊 Response: " . substr($result, 0, 200) . "\n";
    
    return true;
}

// Run all tests
echo "🚀 Starting SPARQL Connection Tests for kb.terpedia.com\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$connection_ok = test_sparql_connection();
$tulip_ok = test_tulip_queries();
$insertion_ok = test_tulip_insertion();

echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 Test Results Summary:\n";
echo "🔗 Basic Connection: " . ($connection_ok ? "✅ PASS" : "❌ FAIL") . "\n";
echo "🔍 TULIP Queries: " . ($tulip_ok ? "✅ PASS" : "❌ FAIL") . "\n";
echo "📝 Fact Insertion: " . ($insertion_ok ? "✅ PASS" : "❌ FAIL") . "\n";

if ($connection_ok && $tulip_ok && $insertion_ok) {
    echo "\n🎉 All tests passed! SPARQL knowledge base is ready for TULIP migration.\n";
} else {
    echo "\n⚠️ Some tests failed. Check the SPARQL endpoint configuration.\n";
}
?>
