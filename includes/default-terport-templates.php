<?php
/**
 * Default Terport Templates
 * 
 * Provides default templates for different types of Terports
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Default_Terport_Templates {
    
    public function __construct() {
        add_action('init', array($this, 'create_default_templates'));
    }
    
    /**
     * Create default templates if they don't exist
     */
    public function create_default_templates() {
        // Only create templates if none exist
        $existing_templates = get_posts(array(
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        if (!empty($existing_templates)) {
            return; // Templates already exist
        }
        
        $this->create_research_analysis_template();
        $this->create_compound_profile_template();
        $this->create_clinical_study_template();
        $this->create_market_analysis_template();
        $this->create_regulatory_update_template();
        $this->create_industry_news_template();
    }
    
    /**
     * Create Research Analysis template
     */
    private function create_research_analysis_template() {
        $template_content = '<h1>{{title}}</h1>

<div class="terport-meta">
    <p><strong>Research Type:</strong> {{research_type}}</p>
    <p><strong>Publication Date:</strong> {{publication_date}}</p>
    <p><strong>Authors:</strong> {{authors}}</p>
</div>

<h2>Executive Summary</h2>
{{executive_summary}}

<h2>Introduction</h2>
{{introduction}}

<h2>Methodology</h2>
{{methodology}}

<h2>Key Findings</h2>
{{key_findings}}

<h2>Results and Analysis</h2>
{{results_analysis}}

<h2>Clinical Implications</h2>
{{clinical_implications}}

<h2>Future Research Directions</h2>
{{future_research}}

<h2>Conclusion</h2>
{{conclusion}}

<h2>References</h2>
{{references}}';

        $template_id = wp_insert_post(array(
            'post_title' => 'Research Analysis Template',
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($template_id)) {
            update_post_meta($template_id, '_terpedia_template_type', 'research_analysis');
            update_post_meta($template_id, '_terpedia_template_schema', $this->get_research_analysis_schema());
        }
    }
    
    /**
     * Create Compound Profile template
     */
    private function create_compound_profile_template() {
        $template_content = '<h1>{{compound_name}} Profile</h1>

<div class="terport-meta">
    <p><strong>Chemical Formula:</strong> {{chemical_formula}}</p>
    <p><strong>Molecular Weight:</strong> {{molecular_weight}}</p>
    <p><strong>CAS Number:</strong> {{cas_number}}</p>
</div>

<h2>Chemical Structure</h2>
{{chemical_structure}}

<h2>Biological Activity</h2>
{{biological_activity}}

<h2>Therapeutic Effects</h2>
{{therapeutic_effects}}

<h2>Mechanism of Action</h2>
{{mechanism_of_action}}

<h2>Safety Profile</h2>
{{safety_profile}}

<h2>Clinical Applications</h2>
{{clinical_applications}}

<h2>Drug Interactions</h2>
{{drug_interactions}}

<h2>Research Evidence</h2>
{{research_evidence}}

<h2>Future Research</h2>
{{future_research}}';

        $template_id = wp_insert_post(array(
            'post_title' => 'Compound Profile Template',
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($template_id)) {
            update_post_meta($template_id, '_terpedia_template_type', 'compound_profile');
            update_post_meta($template_id, '_terpedia_template_schema', $this->get_compound_profile_schema());
        }
    }
    
    /**
     * Create Clinical Study template
     */
    private function create_clinical_study_template() {
        $template_content = '<h1>{{study_title}}</h1>

<div class="terport-meta">
    <p><strong>Study Type:</strong> {{study_type}}</p>
    <p><strong>Phase:</strong> {{study_phase}}</p>
    <p><strong>Participants:</strong> {{participants}}</p>
    <p><strong>Duration:</strong> {{study_duration}}</p>
</div>

<h2>Study Overview</h2>
{{study_overview}}

<h2>Objectives</h2>
{{objectives}}

<h2>Methodology</h2>
{{methodology}}

<h2>Results</h2>
{{results}}

<h2>Statistical Analysis</h2>
{{statistical_analysis}}

<h2>Adverse Events</h2>
{{adverse_events}}

<h2>Clinical Significance</h2>
{{clinical_significance}}

<h2>Limitations</h2>
{{limitations}}

<h2>Conclusion</h2>
{{conclusion}}

<h2>References</h2>
{{references}}';

        $template_id = wp_insert_post(array(
            'post_title' => 'Clinical Study Template',
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($template_id)) {
            update_post_meta($template_id, '_terpedia_template_type', 'clinical_study');
            update_post_meta($template_id, '_terpedia_template_schema', $this->get_clinical_study_schema());
        }
    }
    
    /**
     * Create Market Analysis template
     */
    private function create_market_analysis_template() {
        $template_content = '<h1>{{market_title}}</h1>

<div class="terport-meta">
    <p><strong>Market Segment:</strong> {{market_segment}}</p>
    <p><strong>Analysis Period:</strong> {{analysis_period}}</p>
    <p><strong>Geographic Scope:</strong> {{geographic_scope}}</p>
</div>

<h2>Executive Summary</h2>
{{executive_summary}}

<h2>Market Overview</h2>
{{market_overview}}

<h2>Key Trends</h2>
{{key_trends}}

<h2>Market Size and Growth</h2>
{{market_size_growth}}

<h2>Competitive Landscape</h2>
{{competitive_landscape}}

<h2>Key Players</h2>
{{key_players}}

<h2>Market Opportunities</h2>
{{market_opportunities}}

<h2>Challenges and Risks</h2>
{{challenges_risks}}

<h2>Future Outlook</h2>
{{future_outlook}}

<h2>Investment Implications</h2>
{{investment_implications}}';

        $template_id = wp_insert_post(array(
            'post_title' => 'Market Analysis Template',
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($template_id)) {
            update_post_meta($template_id, '_terpedia_template_type', 'market_analysis');
            update_post_meta($template_id, '_terpedia_template_schema', $this->get_market_analysis_schema());
        }
    }
    
    /**
     * Create Regulatory Update template
     */
    private function create_regulatory_update_template() {
        $template_content = '<h1>{{regulatory_title}}</h1>

<div class="terport-meta">
    <p><strong>Jurisdiction:</strong> {{jurisdiction}}</p>
    <p><strong>Effective Date:</strong> {{effective_date}}</p>
    <p><strong>Regulatory Body:</strong> {{regulatory_body}}</p>
</div>

<h2>Update Summary</h2>
{{update_summary}}

<h2>Key Changes</h2>
{{key_changes}}

<h2>Regulatory Details</h2>
{{regulatory_details}}

<h2>Compliance Requirements</h2>
{{compliance_requirements}}

<h2>Impact on Industry</h2>
{{impact_industry}}

<h2>Implementation Timeline</h2>
{{implementation_timeline}}

<h2>Next Steps</h2>
{{next_steps}}

<h2>Resources</h2>
{{resources}}';

        $template_id = wp_insert_post(array(
            'post_title' => 'Regulatory Update Template',
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($template_id)) {
            update_post_meta($template_id, '_terpedia_template_type', 'regulatory_update');
            update_post_meta($template_id, '_terpedia_template_schema', $this->get_regulatory_update_schema());
        }
    }
    
    /**
     * Create Industry News template
     */
    private function create_industry_news_template() {
        $template_content = '<h1>{{news_title}}</h1>

<div class="terport-meta">
    <p><strong>Publication Date:</strong> {{publication_date}}</p>
    <p><strong>Source:</strong> {{news_source}}</p>
    <p><strong>Category:</strong> {{news_category}}</p>
</div>

<h2>News Summary</h2>
{{news_summary}}

<h2>Key Developments</h2>
{{key_developments}}

<h2>Industry Impact</h2>
{{industry_impact}}

<h2>Expert Commentary</h2>
{{expert_commentary}}

<h2>Market Reaction</h2>
{{market_reaction}}

<h2>Related Developments</h2>
{{related_developments}}

<h2>Future Implications</h2>
{{future_implications}}

<h2>Additional Resources</h2>
{{additional_resources}}';

        $template_id = wp_insert_post(array(
            'post_title' => 'Industry News Template',
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($template_id)) {
            update_post_meta($template_id, '_terpedia_template_type', 'industry_news');
            update_post_meta($template_id, '_terpedia_template_schema', $this->get_industry_news_schema());
        }
    }
    
    /**
     * Get Research Analysis schema
     */
    private function get_research_analysis_schema() {
        return array(
            'type' => 'object',
            'properties' => array(
                'title' => array('type' => 'string', 'description' => 'Research title'),
                'research_type' => array('type' => 'string', 'description' => 'Type of research conducted'),
                'publication_date' => array('type' => 'string', 'description' => 'Publication date'),
                'authors' => array('type' => 'string', 'description' => 'Research authors'),
                'executive_summary' => array('type' => 'string', 'description' => 'Comprehensive summary for stakeholders'),
                'introduction' => array('type' => 'string', 'description' => 'Background and context introduction'),
                'methodology' => array('type' => 'string', 'description' => 'Research methodology and approach'),
                'key_findings' => array('type' => 'string', 'description' => 'Key research findings and results'),
                'results_analysis' => array('type' => 'string', 'description' => 'Detailed results and analysis'),
                'clinical_implications' => array('type' => 'string', 'description' => 'Clinical and research implications'),
                'future_research' => array('type' => 'string', 'description' => 'Future research directions'),
                'conclusion' => array('type' => 'string', 'description' => 'Summary and conclusions'),
                'references' => array('type' => 'string', 'description' => 'Academic references and citations')
            ),
            'required' => array('title', 'executive_summary', 'introduction', 'key_findings', 'conclusion'),
            'additionalProperties' => false
        );
    }
    
    /**
     * Get Compound Profile schema
     */
    private function get_compound_profile_schema() {
        return array(
            'type' => 'object',
            'properties' => array(
                'compound_name' => array('type' => 'string', 'description' => 'Name of the compound'),
                'chemical_formula' => array('type' => 'string', 'description' => 'Chemical formula'),
                'molecular_weight' => array('type' => 'string', 'description' => 'Molecular weight'),
                'cas_number' => array('type' => 'string', 'description' => 'CAS registry number'),
                'chemical_structure' => array('type' => 'string', 'description' => 'Chemical structure description'),
                'biological_activity' => array('type' => 'string', 'description' => 'Biological activity and mechanisms'),
                'therapeutic_effects' => array('type' => 'string', 'description' => 'Therapeutic effects and applications'),
                'mechanism_of_action' => array('type' => 'string', 'description' => 'Mechanism of action'),
                'safety_profile' => array('type' => 'string', 'description' => 'Safety and toxicity information'),
                'clinical_applications' => array('type' => 'string', 'description' => 'Clinical applications and uses'),
                'drug_interactions' => array('type' => 'string', 'description' => 'Drug interactions and contraindications'),
                'research_evidence' => array('type' => 'string', 'description' => 'Research evidence and studies'),
                'future_research' => array('type' => 'string', 'description' => 'Future research directions')
            ),
            'required' => array('compound_name', 'chemical_structure', 'biological_activity', 'therapeutic_effects'),
            'additionalProperties' => false
        );
    }
    
    /**
     * Get Clinical Study schema
     */
    private function get_clinical_study_schema() {
        return array(
            'type' => 'object',
            'properties' => array(
                'study_title' => array('type' => 'string', 'description' => 'Clinical study title'),
                'study_type' => array('type' => 'string', 'description' => 'Type of clinical study'),
                'study_phase' => array('type' => 'string', 'description' => 'Clinical trial phase'),
                'participants' => array('type' => 'string', 'description' => 'Number and description of participants'),
                'study_duration' => array('type' => 'string', 'description' => 'Study duration'),
                'study_overview' => array('type' => 'string', 'description' => 'Study overview and background'),
                'objectives' => array('type' => 'string', 'description' => 'Study objectives and endpoints'),
                'methodology' => array('type' => 'string', 'description' => 'Study methodology and design'),
                'results' => array('type' => 'string', 'description' => 'Study results and findings'),
                'statistical_analysis' => array('type' => 'string', 'description' => 'Statistical analysis and significance'),
                'adverse_events' => array('type' => 'string', 'description' => 'Adverse events and safety data'),
                'clinical_significance' => array('type' => 'string', 'description' => 'Clinical significance of results'),
                'limitations' => array('type' => 'string', 'description' => 'Study limitations'),
                'conclusion' => array('type' => 'string', 'description' => 'Study conclusions'),
                'references' => array('type' => 'string', 'description' => 'References and citations')
            ),
            'required' => array('study_title', 'study_overview', 'objectives', 'results', 'conclusion'),
            'additionalProperties' => false
        );
    }
    
    /**
     * Get Market Analysis schema
     */
    private function get_market_analysis_schema() {
        return array(
            'type' => 'object',
            'properties' => array(
                'market_title' => array('type' => 'string', 'description' => 'Market analysis title'),
                'market_segment' => array('type' => 'string', 'description' => 'Market segment analyzed'),
                'analysis_period' => array('type' => 'string', 'description' => 'Analysis time period'),
                'geographic_scope' => array('type' => 'string', 'description' => 'Geographic scope of analysis'),
                'executive_summary' => array('type' => 'string', 'description' => 'Executive summary of market analysis'),
                'market_overview' => array('type' => 'string', 'description' => 'Market overview and current state'),
                'key_trends' => array('type' => 'string', 'description' => 'Key market trends'),
                'market_size_growth' => array('type' => 'string', 'description' => 'Market size and growth projections'),
                'competitive_landscape' => array('type' => 'string', 'description' => 'Competitive landscape analysis'),
                'key_players' => array('type' => 'string', 'description' => 'Key market players'),
                'market_opportunities' => array('type' => 'string', 'description' => 'Market opportunities'),
                'challenges_risks' => array('type' => 'string', 'description' => 'Market challenges and risks'),
                'future_outlook' => array('type' => 'string', 'description' => 'Future market outlook'),
                'investment_implications' => array('type' => 'string', 'description' => 'Investment implications')
            ),
            'required' => array('market_title', 'executive_summary', 'market_overview', 'key_trends', 'future_outlook'),
            'additionalProperties' => false
        );
    }
    
    /**
     * Get Regulatory Update schema
     */
    private function get_regulatory_update_schema() {
        return array(
            'type' => 'object',
            'properties' => array(
                'regulatory_title' => array('type' => 'string', 'description' => 'Regulatory update title'),
                'jurisdiction' => array('type' => 'string', 'description' => 'Jurisdiction or region'),
                'effective_date' => array('type' => 'string', 'description' => 'Effective date of regulation'),
                'regulatory_body' => array('type' => 'string', 'description' => 'Regulatory body or agency'),
                'update_summary' => array('type' => 'string', 'description' => 'Summary of regulatory update'),
                'key_changes' => array('type' => 'string', 'description' => 'Key regulatory changes'),
                'regulatory_details' => array('type' => 'string', 'description' => 'Detailed regulatory information'),
                'compliance_requirements' => array('type' => 'string', 'description' => 'Compliance requirements'),
                'impact_industry' => array('type' => 'string', 'description' => 'Impact on industry'),
                'implementation_timeline' => array('type' => 'string', 'description' => 'Implementation timeline'),
                'next_steps' => array('type' => 'string', 'description' => 'Next steps for compliance'),
                'resources' => array('type' => 'string', 'description' => 'Additional resources and references')
            ),
            'required' => array('regulatory_title', 'update_summary', 'key_changes', 'compliance_requirements'),
            'additionalProperties' => false
        );
    }
    
    /**
     * Get Industry News schema
     */
    private function get_industry_news_schema() {
        return array(
            'type' => 'object',
            'properties' => array(
                'news_title' => array('type' => 'string', 'description' => 'News article title'),
                'publication_date' => array('type' => 'string', 'description' => 'Publication date'),
                'news_source' => array('type' => 'string', 'description' => 'News source'),
                'news_category' => array('type' => 'string', 'description' => 'News category'),
                'news_summary' => array('type' => 'string', 'description' => 'News summary'),
                'key_developments' => array('type' => 'string', 'description' => 'Key developments'),
                'industry_impact' => array('type' => 'string', 'description' => 'Impact on industry'),
                'expert_commentary' => array('type' => 'string', 'description' => 'Expert commentary and analysis'),
                'market_reaction' => array('type' => 'string', 'description' => 'Market reaction'),
                'related_developments' => array('type' => 'string', 'description' => 'Related developments'),
                'future_implications' => array('type' => 'string', 'description' => 'Future implications'),
                'additional_resources' => array('type' => 'string', 'description' => 'Additional resources and links')
            ),
            'required' => array('news_title', 'news_summary', 'key_developments', 'industry_impact'),
            'additionalProperties' => false
        );
    }
}

// Initialize default templates
new Terpedia_Default_Terport_Templates();
