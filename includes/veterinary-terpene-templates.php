<?php
/**
 * Veterinary Terpene Research Templates
 * 
 * Comprehensive templates for veterinary terpene research reports
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Veterinary_Terpene_Templates {
    
    public function __construct() {
        add_action('init', array($this, 'create_veterinary_templates'));
    }
    
    /**
     * Create comprehensive veterinary terpene templates
     */
    public function create_veterinary_templates() {
        $this->create_cancer_efficacy_template();
        $this->create_physiological_dosing_template();
        $this->create_topical_application_template();
        $this->create_oral_administration_template();
        $this->create_condition_specific_template();
        $this->create_comprehensive_veterinary_report_template();
    }
    
    /**
     * Template 1: Terpene Cancer Efficacy Research
     */
    private function create_cancer_efficacy_template() {
        $template_content = '<h1>Terpene Efficacy Against Cancer in Veterinary Medicine</h1>

<div class="terport-meta">
    <p><strong>Research Focus:</strong> {{research_focus}}</p>
    <p><strong>Animal Species:</strong> {{target_species}}</p>
    <p><strong>Cancer Types:</strong> {{cancer_types}}</p>
    <p><strong>Review Date:</strong> {{review_date}}</p>
</div>

<h2>Executive Summary</h2>
{{executive_summary}}

<h2>Terpenes with Anti-Cancer Properties</h2>

<h3>Primary Anti-Cancer Terpenes</h3>
{{primary_anticancer_terpenes}}

<h3>Mechanism of Action</h3>
{{mechanism_of_action}}

<h2>Cancer Type Specificity</h2>

<h3>Carcinomas</h3>
{{carcinoma_efficacy}}

<h3>Sarcomas</h3>
{{sarcoma_efficacy}}

<h3>Round Cell Tumors</h3>
{{round_cell_efficacy}}

<h3>Brain Tumors</h3>
{{brain_tumor_efficacy}}

<h3>Other Cancer Types</h3>
{{other_cancer_types}}

<h2>Species-Specific Considerations</h2>

<h3>Canine Cancer Applications</h3>
{{canine_applications}}

<h3>Feline Cancer Applications</h3>
{{feline_applications}}

<h3>Equine Cancer Applications</h3>
{{equine_applications}}

<h2>Research Evidence</h2>
{{research_evidence}}

<h2>Clinical Case Reports</h2>
{{case_reports}}

<h2>Safety Considerations</h2>
{{safety_considerations}}

<h2>Future Research Directions</h2>
{{future_research}}

<h2>References</h2>
{{references}}';

        $fields = array(
            'research_focus' => array('type' => 'text', 'label' => 'Research Focus', 'placeholder' => 'e.g., Anti-cancer terpenes in small animals'),
            'target_species' => array('type' => 'select', 'label' => 'Target Species', 'options' => array('Dogs', 'Cats', 'Horses', 'All Species')),
            'cancer_types' => array('type' => 'text', 'label' => 'Cancer Types', 'placeholder' => 'e.g., Carcinomas, Sarcomas, Round Cell'),
            'review_date' => array('type' => 'text', 'label' => 'Review Date', 'placeholder' => 'Date of literature review'),
            'executive_summary' => array('type' => 'textarea', 'label' => 'Executive Summary Overview'),
            'primary_anticancer_terpenes' => array('type' => 'textarea', 'label' => 'Primary Anti-Cancer Terpenes'),
            'mechanism_of_action' => array('type' => 'textarea', 'label' => 'Mechanisms of Action'),
            'carcinoma_efficacy' => array('type' => 'textarea', 'label' => 'Carcinoma Treatment'),
            'sarcoma_efficacy' => array('type' => 'textarea', 'label' => 'Sarcoma Treatment'),
            'round_cell_efficacy' => array('type' => 'textarea', 'label' => 'Round Cell Tumor Treatment'),
            'brain_tumor_efficacy' => array('type' => 'textarea', 'label' => 'Brain Tumor Treatment'),
            'other_cancer_types' => array('type' => 'textarea', 'label' => 'Other Cancer Types'),
            'canine_applications' => array('type' => 'textarea', 'label' => 'Canine Applications'),
            'feline_applications' => array('type' => 'textarea', 'label' => 'Feline Applications'),
            'equine_applications' => array('type' => 'textarea', 'label' => 'Equine Applications'),
            'research_evidence' => array('type' => 'textarea', 'label' => 'Research Evidence'),
            'case_reports' => array('type' => 'textarea', 'label' => 'Case Reports'),
            'safety_considerations' => array('type' => 'textarea', 'label' => 'Safety Considerations'),
            'future_research' => array('type' => 'textarea', 'label' => 'Future Research'),
            'references' => array('type' => 'textarea', 'label' => 'References')
        );

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'executive_summary' => array('type' => 'string', 'description' => 'Comprehensive executive summary of terpene cancer efficacy'),
                'primary_anticancer_terpenes' => array('type' => 'string', 'description' => 'List and description of primary anti-cancer terpenes'),
                'mechanism_of_action' => array('type' => 'string', 'description' => 'Detailed mechanisms of anti-cancer action'),
                'carcinoma_efficacy' => array('type' => 'string', 'description' => 'Specific efficacy against carcinomas'),
                'sarcoma_efficacy' => array('type' => 'string', 'description' => 'Specific efficacy against sarcomas'),
                'round_cell_efficacy' => array('type' => 'string', 'description' => 'Specific efficacy against round cell tumors'),
                'brain_tumor_efficacy' => array('type' => 'string', 'description' => 'Specific efficacy against brain tumors'),
                'other_cancer_types' => array('type' => 'string', 'description' => 'Efficacy against other cancer types'),
                'canine_applications' => array('type' => 'string', 'description' => 'Specific applications in dogs'),
                'feline_applications' => array('type' => 'string', 'description' => 'Specific applications in cats'),
                'equine_applications' => array('type' => 'string', 'description' => 'Specific applications in horses'),
                'research_evidence' => array('type' => 'string', 'description' => 'Summary of research evidence'),
                'case_reports' => array('type' => 'string', 'description' => 'Clinical case reports and outcomes'),
                'safety_considerations' => array('type' => 'string', 'description' => 'Safety profile and contraindications'),
                'future_research' => array('type' => 'string', 'description' => 'Areas needing further research'),
                'references' => array('type' => 'string', 'description' => 'Academic references and citations')
            ),
            'required' => array('executive_summary', 'primary_anticancer_terpenes', 'mechanism_of_action', 'carcinoma_efficacy', 'sarcoma_efficacy', 'round_cell_efficacy', 'brain_tumor_efficacy', 'research_evidence', 'safety_considerations'),
            'additionalProperties' => false
        );

        $this->create_template_post('Veterinary Cancer Terpene Efficacy', $template_content, $fields, $schema, 'Cancer research and terpene efficacy in veterinary oncology');
    }
    
    /**
     * Template 2: Physiological Dosing Guide
     */
    private function create_physiological_dosing_template() {
        $template_content = '<h1>Veterinary Terpene Physiological Dosing Guide</h1>

<div class="terport-meta">
    <p><strong>Dosing Focus:</strong> {{dosing_focus}}</p>
    <p><strong>Target Species:</strong> {{target_species}}</p>
    <p><strong>Administration Route:</strong> {{administration_route}}</p>
    <p><strong>Update Date:</strong> {{update_date}}</p>
</div>

<h2>Overview</h2>
{{overview}}

<h2>Species-Specific Dosing</h2>

<h3>Canine Dosing Guidelines</h3>
{{canine_dosing}}

<h3>Feline Dosing Guidelines</h3>
{{feline_dosing}}

<h3>Equine Dosing Guidelines</h3>
{{equine_dosing}}

<h2>Terpene-Specific Dosing</h2>

<h3>Linalool Dosing</h3>
{{linalool_dosing}}

<h3>β-Caryophyllene Dosing</h3>
{{caryophyllene_dosing}}

<h3>Myrcene Dosing</h3>
{{myrcene_dosing}}

<h3>Limonene Dosing</h3>
{{limonene_dosing}}

<h3>α-Pinene Dosing</h3>
{{pinene_dosing}}

<h2>Body Weight Calculations</h2>
{{weight_calculations}}

<h2>Safety Margins</h2>
{{safety_margins}}

<h2>Monitoring Parameters</h2>
{{monitoring_parameters}}

<h2>Adverse Effects</h2>
{{adverse_effects}}

<h2>Drug Interactions</h2>
{{drug_interactions}}

<h2>Clinical References</h2>
{{clinical_references}}';

        $fields = array(
            'dosing_focus' => array('type' => 'text', 'label' => 'Dosing Focus', 'placeholder' => 'e.g., Therapeutic dosing guidelines'),
            'target_species' => array('type' => 'select', 'label' => 'Target Species', 'options' => array('Dogs', 'Cats', 'Horses', 'All Species')),
            'administration_route' => array('type' => 'select', 'label' => 'Administration Route', 'options' => array('Oral', 'Topical', 'Both')),
            'update_date' => array('type' => 'text', 'label' => 'Update Date', 'placeholder' => 'Date of dosing review')
        );

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'overview' => array('type' => 'string', 'description' => 'Overview of veterinary terpene dosing principles'),
                'canine_dosing' => array('type' => 'string', 'description' => 'Comprehensive dosing guidelines for dogs including weight-based calculations'),
                'feline_dosing' => array('type' => 'string', 'description' => 'Comprehensive dosing guidelines for cats including species-specific considerations'),
                'equine_dosing' => array('type' => 'string', 'description' => 'Comprehensive dosing guidelines for horses including large animal considerations'),
                'linalool_dosing' => array('type' => 'string', 'description' => 'Specific dosing guidelines for linalool across species'),
                'caryophyllene_dosing' => array('type' => 'string', 'description' => 'Specific dosing guidelines for β-caryophyllene across species'),
                'myrcene_dosing' => array('type' => 'string', 'description' => 'Specific dosing guidelines for myrcene across species'),
                'limonene_dosing' => array('type' => 'string', 'description' => 'Specific dosing guidelines for limonene across species'),
                'pinene_dosing' => array('type' => 'string', 'description' => 'Specific dosing guidelines for α-pinene across species'),
                'weight_calculations' => array('type' => 'string', 'description' => 'Body weight-based dosing calculations and formulas'),
                'safety_margins' => array('type' => 'string', 'description' => 'Safety margins and therapeutic windows'),
                'monitoring_parameters' => array('type' => 'string', 'description' => 'Clinical monitoring parameters and frequency'),
                'adverse_effects' => array('type' => 'string', 'description' => 'Potential adverse effects and management'),
                'drug_interactions' => array('type' => 'string', 'description' => 'Known drug interactions and contraindications'),
                'clinical_references' => array('type' => 'string', 'description' => 'Clinical studies and dosing references')
            ),
            'required' => array('overview', 'canine_dosing', 'feline_dosing', 'equine_dosing', 'weight_calculations', 'safety_margins', 'monitoring_parameters'),
            'additionalProperties' => false
        );

        $this->create_template_post('Veterinary Terpene Dosing Guide', $template_content, $fields, $schema, 'Comprehensive physiological dosing guidelines for veterinary terpene therapy');
    }
    
    /**
     * Template 3: Topical Application Guide
     */
    private function create_topical_application_template() {
        $template_content = '<h1>Veterinary Terpene Topical Application Guide</h1>

<div class="terport-meta">
    <p><strong>Application Focus:</strong> {{application_focus}}</p>
    <p><strong>Target Conditions:</strong> {{target_conditions}}</p>
    <p><strong>Formulation Type:</strong> {{formulation_type}}</p>
    <p><strong>Review Date:</strong> {{review_date}}</p>
</div>

<h2>Introduction</h2>
{{introduction}}

<h2>Topical Terpenes Overview</h2>
{{topical_overview}}

<h2>Treatable Conditions</h2>

<h3>Dermatological Conditions</h3>
{{dermatological_conditions}}

<h3>Autoimmune Skin Conditions</h3>
{{autoimmune_conditions}}

<h3>Cancer-Related Topical Applications</h3>
{{cancer_applications}}

<h3>Pruritic and Inflammatory Conditions</h3>
{{pruritic_conditions}}

<h2>Topical Dosing Guidelines</h2>
{{topical_dosing}}

<h2>Formulation Considerations</h2>
{{formulation_considerations}}

<h2>Clinical Case Reports</h2>
{{case_reports}}

<h2>Application Protocols</h2>
{{application_protocols}}

<h2>Species-Specific Considerations</h2>
{{species_considerations}}

<h2>Safety and Contraindications</h2>
{{safety_contraindications}}

<h2>Efficacy Studies</h2>
{{efficacy_studies}}

<h2>Clinical References</h2>
{{clinical_references}}';

        $fields = array(
            'application_focus' => array('type' => 'text', 'label' => 'Application Focus', 'placeholder' => 'e.g., Topical therapeutic applications'),
            'target_conditions' => array('type' => 'text', 'label' => 'Target Conditions', 'placeholder' => 'e.g., Dermatitis, cancer, autoimmune'),
            'formulation_type' => array('type' => 'select', 'label' => 'Formulation Type', 'options' => array('Cream', 'Ointment', 'Spray', 'All Formulations')),
            'review_date' => array('type' => 'text', 'label' => 'Review Date', 'placeholder' => 'Date of review')
        );

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'introduction' => array('type' => 'string', 'description' => 'Introduction to topical terpene applications in veterinary medicine'),
                'topical_overview' => array('type' => 'string', 'description' => 'Overview of terpenes suitable for topical use'),
                'dermatological_conditions' => array('type' => 'string', 'description' => 'Dermatological conditions treatable with topical terpenes'),
                'autoimmune_conditions' => array('type' => 'string', 'description' => 'Autoimmune skin conditions and terpene applications'),
                'cancer_applications' => array('type' => 'string', 'description' => 'Cancer-related topical applications and specific cancer types'),
                'pruritic_conditions' => array('type' => 'string', 'description' => 'Pruritic and inflammatory skin conditions'),
                'topical_dosing' => array('type' => 'string', 'description' => 'Detailed topical dosing guidelines including concentrations and frequency'),
                'formulation_considerations' => array('type' => 'string', 'description' => 'Formulation considerations for different carriers and vehicles'),
                'case_reports' => array('type' => 'string', 'description' => 'Clinical case reports with outcomes and follow-up data'),
                'application_protocols' => array('type' => 'string', 'description' => 'Step-by-step application protocols and techniques'),
                'species_considerations' => array('type' => 'string', 'description' => 'Species-specific considerations for topical applications'),
                'safety_contraindications' => array('type' => 'string', 'description' => 'Safety profile and contraindications for topical use'),
                'efficacy_studies' => array('type' => 'string', 'description' => 'Clinical efficacy studies and research data'),
                'clinical_references' => array('type' => 'string', 'description' => 'Clinical references and research citations')
            ),
            'required' => array('introduction', 'topical_overview', 'dermatological_conditions', 'autoimmune_conditions', 'cancer_applications', 'pruritic_conditions', 'topical_dosing', 'case_reports'),
            'additionalProperties' => false
        );

        $this->create_template_post('Veterinary Topical Terpene Applications', $template_content, $fields, $schema, 'Comprehensive guide for topical terpene applications in veterinary medicine');
    }
    
    /**
     * Template 4: Oral Administration Guide
     */
    private function create_oral_administration_template() {
        $template_content = '<h1>Veterinary Terpene Oral Administration Guide</h1>

<div class="terport-meta">
    <p><strong>Administration Focus:</strong> {{administration_focus}}</p>
    <p><strong>Target Species:</strong> {{target_species}}</p>
    <p><strong>Safety Level:</strong> {{safety_level}}</p>
    <p><strong>Review Date:</strong> {{review_date}}</p>
</div>

<h2>Introduction</h2>
{{introduction}}

<h2>Oral Terpenes Overview</h2>
{{oral_overview}}

<h2>Safety Assessment</h2>
{{safety_assessment}}

<h2>Dosing Ranges and Guidelines</h2>
{{dosing_ranges}}

<h2>Species-Specific Safety</h2>

<h3>Canine Oral Administration</h3>
{{canine_oral}}

<h3>Feline Oral Administration</h3>
{{feline_oral}}

<h3>Equine Oral Administration</h3>
{{equine_oral}}

<h2>Concentration Guidelines</h2>
{{concentration_guidelines}}

<h2>Bioavailability and Metabolism</h2>
{{bioavailability}}

<h2>Therapeutic Applications</h2>
{{therapeutic_applications}}

<h2>Monitoring and Follow-up</h2>
{{monitoring_followup}}

<h2>Contraindications</h2>
{{contraindications}}

<h2>Clinical Studies</h2>
{{clinical_studies}}

<h2>References</h2>
{{references}}';

        $fields = array(
            'administration_focus' => array('type' => 'text', 'label' => 'Administration Focus', 'placeholder' => 'e.g., Oral terpene therapy'),
            'target_species' => array('type' => 'select', 'label' => 'Target Species', 'options' => array('Dogs', 'Cats', 'Horses', 'All Species')),
            'safety_level' => array('type' => 'select', 'label' => 'Safety Level', 'options' => array('High', 'Moderate', 'Requires Monitoring')),
            'review_date' => array('type' => 'text', 'label' => 'Review Date', 'placeholder' => 'Date of review')
        );

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'introduction' => array('type' => 'string', 'description' => 'Introduction to oral terpene administration in veterinary medicine'),
                'oral_overview' => array('type' => 'string', 'description' => 'Overview of terpenes suitable for oral administration'),
                'safety_assessment' => array('type' => 'string', 'description' => 'Comprehensive safety assessment for oral terpenes'),
                'dosing_ranges' => array('type' => 'string', 'description' => 'Detailed dosing ranges in mg/ml and percentage concentrations'),
                'canine_oral' => array('type' => 'string', 'description' => 'Specific guidelines for oral administration in dogs'),
                'feline_oral' => array('type' => 'string', 'description' => 'Specific guidelines for oral administration in cats'),
                'equine_oral' => array('type' => 'string', 'description' => 'Specific guidelines for oral administration in horses'),
                'concentration_guidelines' => array('type' => 'string', 'description' => 'Concentration guidelines and formulation recommendations'),
                'bioavailability' => array('type' => 'string', 'description' => 'Bioavailability and metabolic considerations'),
                'therapeutic_applications' => array('type' => 'string', 'description' => 'Therapeutic applications and indications'),
                'monitoring_followup' => array('type' => 'string', 'description' => 'Monitoring protocols and follow-up procedures'),
                'contraindications' => array('type' => 'string', 'description' => 'Contraindications and precautions'),
                'clinical_studies' => array('type' => 'string', 'description' => 'Clinical studies supporting oral administration'),
                'references' => array('type' => 'string', 'description' => 'Academic references and citations')
            ),
            'required' => array('introduction', 'oral_overview', 'safety_assessment', 'dosing_ranges', 'canine_oral', 'feline_oral', 'equine_oral', 'concentration_guidelines'),
            'additionalProperties' => false
        );

        $this->create_template_post('Veterinary Oral Terpene Administration', $template_content, $fields, $schema, 'Comprehensive guide for oral terpene administration in veterinary medicine');
    }
    
    /**
     * Template 5: Condition-Specific Treatment Guide
     */
    private function create_condition_specific_template() {
        $template_content = '<h1>Condition-Specific Veterinary Terpene Treatment Guide</h1>

<div class="terport-meta">
    <p><strong>Condition Focus:</strong> {{condition_focus}}</p>
    <p><strong>Primary Terpenes:</strong> {{primary_terpenes}}</p>
    <p><strong>Target Species:</strong> {{target_species}}</p>
    <p><strong>Review Date:</strong> {{review_date}}</p>
</div>

<h2>Overview</h2>
{{overview}}

<h2>Seizure Management</h2>

<h3>Linalool for Seizures</h3>
{{linalool_seizures}}

<h3>β-Caryophyllene for Seizures</h3>
{{caryophyllene_seizures}}

<h3>Myrcene for Seizures</h3>
{{myrcene_seizures}}

<h3>Limonene for Seizures</h3>
{{limonene_seizures}}

<h2>Cancer Treatment Protocols</h2>

<h3>Carcinoma Treatment</h3>
{{carcinoma_treatment}}

<h3>Sarcoma Treatment</h3>
{{sarcoma_treatment}}

<h3>Round Cell Tumor Treatment</h3>
{{round_cell_treatment}}

<h3>Brain Tumor Treatment</h3>
{{brain_tumor_treatment}}

<h2>Anxiety Management</h2>
{{anxiety_management}}

<h2>Inflammation Control</h2>
{{inflammation_control}}

<h2>Pain Management</h2>
{{pain_management}}

<h2>Additional Conditions</h2>
{{additional_conditions}}

<h2>Combination Protocols</h2>
{{combination_protocols}}

<h2>Monitoring and Adjustment</h2>
{{monitoring_adjustment}}

<h2>Clinical Outcomes</h2>
{{clinical_outcomes}}

<h2>References</h2>
{{references}}';

        $fields = array(
            'condition_focus' => array('type' => 'text', 'label' => 'Condition Focus', 'placeholder' => 'e.g., Neurological conditions'),
            'primary_terpenes' => array('type' => 'text', 'label' => 'Primary Terpenes', 'placeholder' => 'e.g., Linalool, Caryophyllene'),
            'target_species' => array('type' => 'select', 'label' => 'Target Species', 'options' => array('Dogs', 'Cats', 'Horses', 'All Species')),
            'review_date' => array('type' => 'text', 'label' => 'Review Date', 'placeholder' => 'Date of review')
        );

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'overview' => array('type' => 'string', 'description' => 'Overview of condition-specific terpene treatments'),
                'linalool_seizures' => array('type' => 'string', 'description' => 'Detailed protocol for using linalool in seizure management including dosing and monitoring'),
                'caryophyllene_seizures' => array('type' => 'string', 'description' => 'Detailed protocol for using β-caryophyllene in seizure management'),
                'myrcene_seizures' => array('type' => 'string', 'description' => 'Detailed protocol for using myrcene in seizure management'),
                'limonene_seizures' => array('type' => 'string', 'description' => 'Detailed protocol for using limonene in seizure management'),
                'carcinoma_treatment' => array('type' => 'string', 'description' => 'Specific terpene protocols for carcinoma treatment'),
                'sarcoma_treatment' => array('type' => 'string', 'description' => 'Specific terpene protocols for sarcoma treatment'),
                'round_cell_treatment' => array('type' => 'string', 'description' => 'Specific terpene protocols for round cell tumor treatment'),
                'brain_tumor_treatment' => array('type' => 'string', 'description' => 'Specific terpene protocols for brain tumor treatment'),
                'anxiety_management' => array('type' => 'string', 'description' => 'Comprehensive anxiety management protocols using terpenes'),
                'inflammation_control' => array('type' => 'string', 'description' => 'Anti-inflammatory terpene protocols and dosing'),
                'pain_management' => array('type' => 'string', 'description' => 'Pain management protocols using analgesic terpenes'),
                'additional_conditions' => array('type' => 'string', 'description' => 'Other conditions treatable with terpenes'),
                'combination_protocols' => array('type' => 'string', 'description' => 'Multi-terpene combination protocols and synergistic effects'),
                'monitoring_adjustment' => array('type' => 'string', 'description' => 'Monitoring protocols and dose adjustment guidelines'),
                'clinical_outcomes' => array('type' => 'string', 'description' => 'Clinical outcomes and efficacy data'),
                'references' => array('type' => 'string', 'description' => 'Clinical references and research citations')
            ),
            'required' => array('overview', 'linalool_seizures', 'caryophyllene_seizures', 'myrcene_seizures', 'limonene_seizures', 'carcinoma_treatment', 'anxiety_management', 'inflammation_control', 'pain_management'),
            'additionalProperties' => false
        );

        $this->create_template_post('Condition-Specific Veterinary Terpene Treatments', $template_content, $fields, $schema, 'Comprehensive condition-specific treatment protocols using terpenes');
    }
    
    /**
     * Create comprehensive veterinary report template
     */
    private function create_comprehensive_veterinary_report_template() {
        $template_content = '<h1>Comprehensive Veterinary Terpene Research Report</h1>

<div class="terport-meta">
    <p><strong>Report Type:</strong> {{report_type}}</p>
    <p><strong>Scope:</strong> {{scope}}</p>
    <p><strong>Target Audience:</strong> {{target_audience}}</p>
    <p><strong>Publication Date:</strong> {{publication_date}}</p>
</div>

<h2>Executive Summary</h2>
{{executive_summary}}

<h2>Introduction and Background</h2>
{{introduction_background}}

<h2>Section 1: Cancer Efficacy Research</h2>
{{cancer_efficacy_section}}

<h2>Section 2: Physiological Dosing Guidelines</h2>
{{dosing_guidelines_section}}

<h2>Section 3: Topical Applications</h2>
{{topical_applications_section}}

<h2>Section 4: Oral Administration</h2>
{{oral_administration_section}}

<h2>Section 5: Condition-Specific Protocols</h2>
{{condition_specific_section}}

<h2>Clinical Case Studies</h2>
{{clinical_case_studies}}

<h2>Safety and Regulatory Considerations</h2>
{{safety_regulatory}}

<h2>Future Research Priorities</h2>
{{future_research_priorities}}

<h2>Conclusions and Recommendations</h2>
{{conclusions_recommendations}}

<h2>Appendices</h2>
{{appendices}}

<h2>References</h2>
{{references}}';

        $fields = array(
            'report_type' => array('type' => 'select', 'label' => 'Report Type', 'options' => array('Comprehensive Review', 'Clinical Guidelines', 'Research Summary')),
            'scope' => array('type' => 'text', 'label' => 'Scope', 'placeholder' => 'e.g., All veterinary species and conditions'),
            'target_audience' => array('type' => 'select', 'label' => 'Target Audience', 'options' => array('Veterinarians', 'Researchers', 'Clinical Practitioners')),
            'publication_date' => array('type' => 'text', 'label' => 'Publication Date', 'placeholder' => 'Date of report publication')
        );

        $schema = array(
            'type' => 'object',
            'properties' => array(
                'executive_summary' => array('type' => 'string', 'description' => 'Comprehensive executive summary covering all major findings'),
                'introduction_background' => array('type' => 'string', 'description' => 'Introduction and background on veterinary terpene research'),
                'cancer_efficacy_section' => array('type' => 'string', 'description' => 'Detailed section on cancer efficacy including all cancer types and species'),
                'dosing_guidelines_section' => array('type' => 'string', 'description' => 'Comprehensive dosing guidelines for dogs, cats, and horses'),
                'topical_applications_section' => array('type' => 'string', 'description' => 'Complete topical applications including diseases, case reports, and dosing'),
                'oral_administration_section' => array('type' => 'string', 'description' => 'Complete oral administration guidelines including safety and dosing ranges'),
                'condition_specific_section' => array('type' => 'string', 'description' => 'All condition-specific protocols including seizures, anxiety, inflammation, and pain'),
                'clinical_case_studies' => array('type' => 'string', 'description' => 'Compilation of clinical case studies and outcomes'),
                'safety_regulatory' => array('type' => 'string', 'description' => 'Safety considerations and regulatory framework'),
                'future_research_priorities' => array('type' => 'string', 'description' => 'Identified gaps and future research priorities'),
                'conclusions_recommendations' => array('type' => 'string', 'description' => 'Clinical conclusions and practice recommendations'),
                'appendices' => array('type' => 'string', 'description' => 'Supporting appendices including dosing tables and quick reference guides'),
                'references' => array('type' => 'string', 'description' => 'Comprehensive reference list')
            ),
            'required' => array('executive_summary', 'introduction_background', 'cancer_efficacy_section', 'dosing_guidelines_section', 'topical_applications_section', 'oral_administration_section', 'condition_specific_section'),
            'additionalProperties' => false
        );

        $this->create_template_post('Comprehensive Veterinary Terpene Report', $template_content, $fields, $schema, 'Complete comprehensive report covering all aspects of veterinary terpene research and clinical applications');
    }
    
    /**
     * Create template post in database
     */
    private function create_template_post($title, $content, $fields, $schema, $description) {
        // Check if template already exists
        $existing = get_posts(array(
            'post_type' => 'terpedia_terport_template',
            'title' => $title,
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        if (!empty($existing)) {
            return; // Template already exists
        }
        
        $template_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'terpedia_terport_template',
            'meta_input' => array(
                '_terpedia_template_fields' => $fields,
                '_terpedia_template_schema' => $schema,
                '_terpedia_template_description' => $description,
                '_terpedia_template_content_template' => $content,
                '_terpedia_template_category' => 'Veterinary Research'
            )
        ));
        
        return $template_id;
    }
}

// Initialize the veterinary terpene templates
new Terpedia_Veterinary_Terpene_Templates();