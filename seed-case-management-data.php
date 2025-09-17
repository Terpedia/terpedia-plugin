<?php
/**
 * Seed Case Management System with Realistic Veterinary Data
 * 
 * This script creates 4 comprehensive veterinary cases with:
 * - Complete patient information
 * - 8-12 chat messages per case
 * - 15-20 vital sign data points per case
 * - 6-10 interventions per case
 * - References to terpene research
 */

require_once(__DIR__ . '/terpedia.php');

class TerpediaCaseSeeder {
    
    public function __construct() {
        // Ensure WordPress environment is loaded
        if (!function_exists('wp_insert_post')) {
            die('WordPress environment not loaded properly.');
        }
    }
    
    public function seed_all_cases() {
        echo "🏥 Starting Case Management System Data Seeding...\n\n";
        
        // Remove existing cases
        $this->cleanup_existing_cases();
        
        // Create the 4 comprehensive cases
        $case1_id = $this->create_bella_case();
        $case2_id = $this->create_thunder_case();
        $case3_id = $this->create_whiskers_case();
        $case4_id = $this->create_emergency_case();
        
        echo "\n✅ All veterinary cases created successfully!\n";
        echo "📋 Case IDs: {$case1_id}, {$case2_id}, {$case3_id}, {$case4_id}\n";
        echo "🌐 Visit /cases to view all cases\n\n";
        
        return [$case1_id, $case2_id, $case3_id, $case4_id];
    }
    
    private function cleanup_existing_cases() {
        echo "🧹 Cleaning up existing case data...\n";
        
        $existing_cases = get_posts([
            'post_type' => 'terpedia_case',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($existing_cases as $case) {
            wp_delete_post($case->ID, true);
        }
        
        echo "   Removed " . count($existing_cases) . " existing cases\n";
    }
    
    /**
     * Case 1: Bella - Golden Retriever Seizure Management
     */
    private function create_bella_case() {
        echo "🐕 Creating Case 1: Bella - Golden Retriever with Epilepsy...\n";
        
        // Create the case post
        $case_id = wp_insert_post([
            'post_type' => 'terpedia_case',
            'post_title' => 'Case #001: Bella - Seizure Management',
            'post_content' => 'Bella is a 4-year-old spayed female Golden Retriever presenting with a 6-month history of generalized tonic-clonic seizures. Initial presentation showed seizures occurring 2-3 times weekly, lasting 45-90 seconds each. Pre-ictal behavior includes restlessness and excessive panting. Post-ictal confusion lasts approximately 15 minutes.

Current management includes phenobarbital 2.5mg/kg BID with therapeutic levels maintained at 25-30 μg/mL. We have implemented a novel terpene protocol incorporating linalool (5mg/kg daily) and β-caryophyllene (3mg/kg daily) based on recent research showing neuroprotective effects and seizure threshold elevation in canine epilepsy models.

Owner reports significant improvement in seizure frequency and severity since initiation of terpene therapy. Bella\'s quality of life has markedly improved with increased activity levels and better appetite.',
            'post_status' => 'publish',
            'post_author' => 1
        ]);
        
        // Patient Information
        update_post_meta($case_id, 'patient_name', 'Bella');
        update_post_meta($case_id, 'species', 'Canine');
        update_post_meta($case_id, 'breed', 'Golden Retriever');
        update_post_meta($case_id, 'age', '4 years');
        update_post_meta($case_id, 'weight', '28.5 kg');
        update_post_meta($case_id, 'owner_name', 'Sarah & Mark Johnson');
        update_post_meta($case_id, 'owner_contact', 'Phone: (555) 123-4567
Email: sarah.johnson@email.com
Address: 123 Oak Street, Springfield, IL 62701
Emergency Contact: Mark Johnson (555) 987-6543');
        update_post_meta($case_id, 'case_status', 'active');
        
        // Chat Messages (realistic veterinary team discussion)
        $messages = [
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Bella came in for her 2-week follow-up since starting the linalool protocol. Owner reports only 1 seizure this week compared to 3 seizures the previous week.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-8 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'That\'s encouraging progress! Have we checked her phenobarbital levels recently? Want to make sure we\'re not seeing interaction effects.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-8 days +15 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'The linalool-phenobarbital combination shows promising results. Linalool\'s GABA-ergic effects may potentiate anticonvulsant activity while potentially allowing for lower phenobarbital doses long-term. Consider monitoring hepatic function closely given the dual pathway metabolism.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-8 days +30 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Blood work scheduled for tomorrow. Owner is very happy with Bella\'s improvement - she\'s been more playful and alert between episodes.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 3,
                'user_type' => 'human',
                'message' => 'Lab results in: Phenobarbital level at 28 μg/mL (therapeutic), ALT slightly elevated at 95 U/L but within acceptable range for phenobarbital therapy.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Should we consider adding β-caryophyllene to the protocol? The CB2 receptor activation might provide additional neuroprotective benefits.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'β-caryophyllene addition is well-supported by current research. Start with 3mg/kg daily divided BID. Its CB2 agonist activity provides anti-inflammatory neuroprotection without psychoactive effects. Monitor for any changes in seizure frequency or duration.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-5 days +20 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Owner approved β-caryophyllene addition. Starting today with morning and evening dosing mixed with food.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Bella had a mild seizure yesterday evening - about 30 seconds duration, much shorter than typical. Recovery time was also reduced to about 8 minutes.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'The shorter duration and faster recovery are very positive signs. The terpene protocol appears to be providing significant benefit.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days +45 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Owner scheduling follow-up appointment for next week. Wants to continue current protocol. Bella is doing great overall!',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'message_type' => 'chat',
                'metadata' => null
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_messages', $messages);
        
        // Vital Signs (realistic progression over 3 weeks)
        $vitals = [];
        $base_date = strtotime('-21 days');
        
        for ($i = 0; $i < 18; $i++) {
            $date = date('Y-m-d H:i:s', $base_date + ($i * 86400) + rand(28800, 64800)); // Random time each day
            $day_factor = $i / 17; // 0 to 1 progression
            
            // Simulate gradual improvement in vitals
            $stress_factor = 1 - ($day_factor * 0.3); // Decreasing stress over time
            
            $vitals[] = [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => rand(1, 3),
                'recorded_date' => $date,
                'heart_rate' => round(95 + rand(-10, 15) - ($day_factor * 8)), // Improving heart rate
                'blood_pressure_systolic' => round(140 + rand(-15, 20) * $stress_factor),
                'blood_pressure_diastolic' => round(85 + rand(-10, 15) * $stress_factor),
                'weight' => round((28.5 + rand(-2, 3) * 0.1) * 10) / 10, // Stable weight
                'temperature' => round((38.7 + rand(-5, 5) * 0.1) * 10) / 10, // Normal temp
                'respiratory_rate' => round(22 + rand(-5, 8) - ($day_factor * 3)), // Improving resp rate
                'notes' => $i < 5 ? 'Pre-seizure monitoring' : 
                          ($i < 12 ? 'Linalool protocol initiated, monitoring response' : 
                           'Combined terpene therapy, good response noted')
            ];
        }
        
        update_post_meta($case_id, '_terpedia_case_vitals', $vitals);
        
        // Interventions (realistic veterinary interventions)
        $interventions = [
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-21 days')),
                'intervention_type' => 'Initial Neurological Assessment',
                'intervention_category' => 'diagnosis',
                'description' => 'Complete neurological examination performed. Cranial nerves II-XII normal. No focal neurological deficits noted. Reflexes appropriate and symmetrical. Recommended MRI to rule out structural abnormalities.',
                'outcome' => 'MRI scheduled, phenobarbital therapy initiated at 2.5mg/kg BID',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-14 days')),
                'status' => 'completed',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-19 days')),
                'intervention_type' => 'MRI Brain Scan',
                'intervention_category' => 'diagnosis',
                'description' => 'MRI brain scan performed under general anesthesia. No structural abnormalities detected. No evidence of neoplasia, inflammation, or vascular abnormalities. Findings consistent with idiopathic epilepsy.',
                'outcome' => 'Confirmed idiopathic epilepsy diagnosis, continued phenobarbital therapy',
                'follow_up_required' => false,
                'follow_up_date' => null,
                'status' => 'completed',
                'metadata' => '{"anesthesia_time": "45 minutes", "contrast_used": false}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-14 days')),
                'intervention_type' => 'Linalool Therapy Initiation',
                'intervention_category' => 'treatment',
                'description' => 'Started linalool supplementation at 5mg/kg daily based on recent research showing GABAergic effects and seizure threshold elevation in canine models. Explained protocol to owner, provided detailed administration instructions.',
                'outcome' => 'Owner compliant with therapy, initial tolerance good',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-7 days')),
                'status' => 'active',
                'metadata' => '{"research_reference": "Veterinary Terpene Research 2024", "dosage_form": "oral liquid"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 3,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'intervention_type' => 'Blood Chemistry Panel',
                'intervention_category' => 'diagnosis',
                'description' => 'Complete blood chemistry panel including phenobarbital level, hepatic function panel, and electrolytes. Monitoring for drug-related hepatotoxicity and therapeutic levels.',
                'outcome' => 'Phenobarbital level therapeutic at 28 μg/mL, mild ALT elevation at 95 U/L acceptable',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'completed',
                'metadata' => '{"phenobarbital_level": "28 ug/mL", "ALT": "95 U/L", "reference_range": "15-45 U/L"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'intervention_type' => 'β-Caryophyllene Addition',
                'intervention_category' => 'treatment',
                'description' => 'Added β-caryophyllene at 3mg/kg daily divided BID to existing protocol. CB2 receptor agonist providing additional neuroprotective and anti-inflammatory effects without psychoactive properties.',
                'outcome' => 'Well tolerated, no adverse effects noted',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'active',
                'metadata' => '{"CB2_selectivity": true, "anti_inflammatory": true, "neuroprotective": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'intervention_type' => 'Seizure Response Assessment',
                'intervention_category' => 'treatment',
                'description' => 'Evaluated seizure characteristics post-terpene therapy. Seizure duration reduced from average 60-90 seconds to 30-45 seconds. Post-ictal recovery time improved from 15 minutes to 8-10 minutes.',
                'outcome' => 'Significant clinical improvement in seizure severity and recovery',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+14 days')),
                'status' => 'active',
                'metadata' => '{"seizure_duration_reduction": "50%", "recovery_time_reduction": "40%"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'intervention_type' => 'Owner Education Session',
                'intervention_category' => 'treatment',
                'description' => 'Comprehensive education session with owners regarding seizure recognition, emergency protocols, and terpene therapy compliance. Provided seizure diary for home monitoring.',
                'outcome' => 'Owners demonstrate good understanding, high compliance expected',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'active',
                'metadata' => '{"seizure_diary_provided": true, "emergency_protocol_reviewed": true}'
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_interventions', $interventions);
        
        echo "   ✅ Bella's case created with " . count($messages) . " messages, " . count($vitals) . " vital signs, and " . count($interventions) . " interventions\n";
        
        return $case_id;
    }
    
    /**
     * Case 2: Thunder - Thoroughbred Anxiety Treatment
     */
    private function create_thunder_case() {
        echo "🐎 Creating Case 2: Thunder - Thoroughbred Performance Anxiety...\n";
        
        $case_id = wp_insert_post([
            'post_type' => 'terpedia_case',
            'post_title' => 'Case #002: Thunder - Performance Anxiety Protocol',
            'post_content' => 'Thunder is an 8-year-old Thoroughbred gelding competing in eventing who has developed significant performance anxiety over the past 4 months. Symptoms include excessive sweating, elevated heart rate pre-competition, reluctance to load in trailer, and decreased performance scores.

Initial behavioral assessment revealed no physical abnormalities contributing to anxiety. Stress-related behaviors began following a minor trailer accident 5 months ago. Traditional anxiolytic medications were ineffective and caused sedation affecting athletic performance.

Implemented novel terpene-based protocol using limonene (8mg/kg daily) for its anxiolytic D-limonene effects and myrcene (6mg/kg daily) for muscle relaxation and calming properties. Both terpenes selected for absence of prohibited substances in equine competition. Protocol initiated 3 weeks prior to next scheduled competition.',
            'post_status' => 'publish',
            'post_author' => 1
        ]);
        
        // Patient Information  
        update_post_meta($case_id, 'patient_name', 'Thunder');
        update_post_meta($case_id, 'species', 'Equine');
        update_post_meta($case_id, 'breed', 'Thoroughbred');
        update_post_meta($case_id, 'age', '8 years');
        update_post_meta($case_id, 'weight', '545 kg');
        update_post_meta($case_id, 'owner_name', 'Riverside Equestrian Center - Amanda Sterling');
        update_post_meta($case_id, 'owner_contact', 'Phone: (555) 234-5678
Email: amanda@riversideequestrian.com  
Address: 456 County Road 12, Lexington, KY 40511
Trainer: Marcus Rodriguez (555) 345-6789
Emergency: Dr. Patricia Hayes (555) 555-EQUI');
        update_post_meta($case_id, 'case_status', 'active');
        
        // Chat Messages
        $messages = [
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Thunder arrived for pre-competition assessment. Heart rate at rest is 48 BPM but jumps to 85+ when trailer is mentioned. Clear anxiety response.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-12 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Classic post-traumatic stress response. The limonene protocol should help with the limbic system regulation. Have we cleared this with competition authorities?',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-12 days +20 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'Limonene and myrcene are both naturally occurring terpenes not listed on FEI or USEF prohibited substances lists. The combination provides anxiolytic effects through 5-HT1A receptor modulation and GABA potentiation without performance-impairing sedation.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-12 days +35 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Started Thunder on limonene 8mg/kg daily this morning. Owner reports he was noticeably calmer during routine handling this afternoon.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 3,
                'user_type' => 'human',
                'message' => 'Adding myrcene today at 6mg/kg. The muscle relaxation properties should help with the tension he carries in his back and neck.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-8 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Excellent progress report from Amanda. Thunder loaded into the trailer voluntarily yesterday for the first time in months!',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Took Thunder to a small local show yesterday as a test run. HR stayed below 60 BPM during warm-up, much improved from baseline readings.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'The heart rate improvement is significant clinical evidence of reduced anxiety. Consider monitoring cortisol levels to quantify stress reduction. The terpene protocol appears to be effectively modulating the HPA axis response.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 days +45 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Competition is this weekend. Amanda is very optimistic. Thunder\'s training scores have returned to pre-incident levels.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Just got word - Thunder placed 3rd in his division! First podium finish since the accident. Amanda is thrilled with the results.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'message_type' => 'chat',
                'metadata' => null
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_messages', $messages);
        
        // Vital Signs (showing improvement in anxiety markers)
        $vitals = [];
        $base_date = strtotime('-15 days');
        
        for ($i = 0; $i < 16; $i++) {
            $date = date('Y-m-d H:i:s', $base_date + ($i * 86400) + rand(21600, 61200));
            $improvement_factor = $i / 15; // Gradual improvement
            
            // Simulate anxiety reduction over time
            $anxiety_reduction = 1 - ($improvement_factor * 0.4);
            
            $vitals[] = [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => rand(1, 3),
                'recorded_date' => $date,
                'heart_rate' => round(42 + rand(5, 20) * $anxiety_reduction), // Decreasing baseline HR
                'blood_pressure_systolic' => null, // Not typically measured in horses
                'blood_pressure_diastolic' => null,
                'weight' => round(545 + rand(-15, 10)), // Stable weight
                'temperature' => round((37.8 + rand(-3, 4) * 0.1) * 10) / 10,
                'respiratory_rate' => round(12 + rand(0, 8) * $anxiety_reduction), // Decreasing resp rate
                'notes' => $i < 3 ? 'Pre-treatment anxiety baseline' :
                          ($i < 8 ? 'Limonene therapy initiated, monitoring response' :
                           ($i < 12 ? 'Myrcene added to protocol, muscle tension reducing' :
                            'Pre-competition assessment, significant improvement noted'))
            ];
        }
        
        update_post_meta($case_id, '_terpedia_case_vitals', $vitals);
        
        // Interventions
        $interventions = [
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'intervention_type' => 'Behavioral Assessment',
                'intervention_category' => 'diagnosis',
                'description' => 'Comprehensive behavioral evaluation including stress response testing, trailer loading assessment, and performance anxiety scoring. Baseline measurements taken for heart rate variability and cortisol response.',
                'outcome' => 'Confirmed performance anxiety with trauma-associated triggers, initiated terpene protocol',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-7 days')),
                'status' => 'completed',
                'metadata' => '{"anxiety_score": "8/10", "trauma_trigger": "trailer loading", "baseline_hr": "48-85 BPM"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-12 days')),
                'intervention_type' => 'Limonene Therapy Initiation',
                'intervention_category' => 'treatment',
                'description' => 'Started D-limonene at 8mg/kg daily mixed with grain ration. Anxiolytic properties through 5-HT1A receptor modulation. Competition-safe natural terpene with no withdrawal requirements.',
                'outcome' => 'Immediate tolerance good, initial calming effects noted within 2 hours',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-8 days')),
                'status' => 'active',
                'metadata' => '{"receptor_target": "5-HT1A", "competition_legal": true, "administration": "oral with feed"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 3,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-8 days')),
                'intervention_type' => 'Myrcene Addition',
                'intervention_category' => 'treatment',
                'description' => 'Added myrcene 6mg/kg daily to existing protocol. Muscle relaxant and sedative properties through GABA potentiation. Addresses physical tension component of anxiety response.',
                'outcome' => 'Notable reduction in muscle tension, improved flexibility in training',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-4 days')),
                'status' => 'active',
                'metadata' => '{"mechanism": "GABA potentiation", "muscle_relaxant": true, "synergistic_effect": true}'
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-6 days')),
                'intervention_type' => 'Trailer Loading Desensitization',
                'intervention_category' => 'treatment',
                'description' => 'Systematic desensitization protocol for trailer loading while on terpene therapy. Gradual exposure with positive reinforcement techniques. Measured heart rate response throughout sessions.',
                'outcome' => 'Successful voluntary loading achieved, HR remained below 65 BPM',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-2 days')),
                'status' => 'completed',
                'metadata' => '{"sessions_required": "3", "success_rate": "100%", "hr_improvement": "23 BPM reduction"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'intervention_type' => 'Practice Competition Assessment',
                'intervention_category' => 'treatment',
                'description' => 'Trial competition at local facility to assess response to competitive environment while on terpene protocol. Monitored vital signs and performance metrics throughout event.',
                'outcome' => 'Excellent performance, normal vital signs, confidence restored',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+1 day')),
                'status' => 'completed',
                'metadata' => '{"competition_level": "local", "performance_score": "82%", "anxiety_incidents": "0"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'intervention_type' => 'Pre-Competition Preparation',
                'intervention_category' => 'treatment',
                'description' => 'Final assessment and protocol adjustment before major competition. Confirmed optimal terpene dosing, reviewed competition day schedule with owner and trainer.',
                'outcome' => 'Thunder cleared for competition, anxiety management protocol optimized',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'active',
                'metadata' => '{"competition_ready": true, "protocol_optimized": true, "confidence_level": "high"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'intervention_type' => 'Competition Performance Evaluation',
                'intervention_category' => 'treatment',
                'description' => 'Post-competition assessment following 3rd place finish. Evaluated performance metrics, anxiety levels, and overall protocol effectiveness. Thunder performed at pre-incident levels.',
                'outcome' => 'Outstanding success - podium finish achieved, anxiety fully managed',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'completed',
                'metadata' => '{"competition_result": "3rd place", "performance_level": "pre-incident", "protocol_success": "excellent"}'
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_interventions', $interventions);
        
        echo "   ✅ Thunder's case created with " . count($messages) . " messages, " . count($vitals) . " vital signs, and " . count($interventions) . " interventions\n";
        
        return $case_id;
    }
    
    /**
     * Case 3: Whiskers - Maine Coon Cancer Support
     */
    private function create_whiskers_case() {
        echo "🐱 Creating Case 3: Whiskers - Maine Coon Palliative Care...\n";
        
        $case_id = wp_insert_post([
            'post_type' => 'terpedia_case',
            'post_title' => 'Case #003: Whiskers - Feline Lymphoma Support Care',
            'post_content' => 'Whiskers is a 12-year-old neutered male Maine Coon diagnosed with intermediate-grade alimentary lymphoma 6 weeks ago. Initial presentation included weight loss, intermittent vomiting, and decreased appetite. Staging workup revealed localized disease primarily affecting the jejunum and ileum.

Family opted for palliative care approach rather than aggressive chemotherapy due to Whiskers\' age and quality of life concerns. Treatment goals focus on comfort, appetite stimulation, and maintaining dignity throughout end-of-life care.

Initiated supportive care protocol including geraniol (2mg/kg BID) for its anti-inflammatory and potential anti-neoplastic properties, and β-caryophyllene (1.5mg/kg BID) for pain management and appetite stimulation through CB2 receptor activation. Protocol designed to enhance quality of life while minimizing medication burden.',
            'post_status' => 'publish',
            'post_author' => 1
        ]);
        
        // Patient Information
        update_post_meta($case_id, 'patient_name', 'Whiskers');
        update_post_meta($case_id, 'species', 'Feline');
        update_post_meta($case_id, 'breed', 'Maine Coon');
        update_post_meta($case_id, 'age', '12 years');
        update_post_meta($case_id, 'weight', '5.2 kg');
        update_post_meta($case_id, 'owner_name', 'Eleanor and Robert Chen');
        update_post_meta($case_id, 'owner_contact', 'Phone: (555) 345-6789
Email: eleanor.chen@email.com
Address: 789 Maple Avenue, Portland, OR 97205
Secondary Contact: Robert Chen (555) 456-7890
Preferred Contact Time: Evenings after 6 PM');
        update_post_meta($case_id, 'case_status', 'critical');
        
        // Chat Messages (palliative care focused)
        $messages = [
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Whiskers came in today for his 2-week recheck. Weight has stabilized at 5.2kg. Eleanor reports he\'s eating small meals more frequently since starting the geraniol.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'That\'s encouraging. Has there been any change in the vomiting episodes? The geraniol should help with gastric inflammation.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-10 days +25 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'Geraniol\'s anti-inflammatory effects on gastric mucosa can significantly reduce nausea and vomiting in lymphoma patients. Consider monitoring liver enzymes as geraniol undergoes hepatic metabolism. The appetite stimulation suggests good therapeutic response.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-10 days +40 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Eleanor says vomiting reduced from daily to 2-3 times per week. She\'s very pleased with his comfort level. Should we add the β-caryophyllene now?',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-9 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Yes, let\'s start β-caryophyllene at 1.5mg/kg BID. The CB2 activation should help with any underlying discomfort and further stimulate appetite.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-8 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 3,
                'user_type' => 'human',
                'message' => 'Family meeting scheduled for tomorrow to discuss long-term care plans and quality of life indicators. Want to ensure we\'re all aligned on goals.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Family meeting went well. They understand the prognosis but want to focus on quality time. Whiskers is still enjoying sunbathing and purrs when petted.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'Behavioral indicators like purring and seeking comfort suggest good quality of life. Consider implementing a quality of life scale for objective monitoring. The terpene protocol appears to be providing meaningful comfort.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 days +30 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Eleanor called - Whiskers had a really good day yesterday. Played with his feather toy for the first time in weeks. The β-caryophyllene seems to be helping.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Blood work shows stable kidney function and no concerning changes. The Chen family feels very supported by our palliative care approach.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 3,
                'user_type' => 'human',
                'message' => 'This is such a beautiful example of how terpene therapy can enhance comfort care. Whiskers is maintaining his personality and the family feels empowered in his care.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'message_type' => 'chat',
                'metadata' => null
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_messages', $messages);
        
        // Vital Signs (showing initial decline then stabilization)  
        $vitals = [];
        $base_date = strtotime('-20 days');
        
        for ($i = 0; $i < 20; $i++) {
            $date = date('Y-m-d H:i:s', $base_date + ($i * 86400) + rand(25200, 64800));
            
            // Simulate initial decline then stabilization with treatment
            if ($i < 8) {
                // Initial decline
                $decline_factor = $i / 8;
                $weight = 5.8 - ($decline_factor * 0.6); // Weight loss
                $temp = 38.5 + rand(-5, 3) * 0.1;
                $hr = 180 + rand(0, 20);
                $notes = 'Pre-treatment monitoring, disease progression noted';
            } else {
                // Stabilization with treatment
                $weight = 5.2 + rand(-2, 1) * 0.1; // Stable weight
                $temp = 38.3 + rand(-3, 5) * 0.1;
                $hr = 160 + rand(-10, 15);
                $notes = $i < 12 ? 'Geraniol therapy initiated, monitoring response' : 
                        'Combined terpene protocol, comfort care focus';
            }
            
            $vitals[] = [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => rand(1, 3),
                'recorded_date' => $date,
                'heart_rate' => round($hr),
                'blood_pressure_systolic' => null, // Not typically measured in cats
                'blood_pressure_diastolic' => null,
                'weight' => round($weight * 10) / 10,
                'temperature' => round($temp * 10) / 10,
                'respiratory_rate' => round(24 + rand(-6, 12)),
                'notes' => $notes
            ];
        }
        
        update_post_meta($case_id, '_terpedia_case_vitals', $vitals);
        
        // Interventions (palliative care focused)
        $interventions = [
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-18 days')),
                'intervention_type' => 'Lymphoma Diagnosis and Staging',
                'intervention_category' => 'diagnosis',
                'description' => 'Confirmed intermediate-grade alimentary lymphoma through intestinal biopsy and histopathology. Staging workup including abdominal ultrasound, thoracic radiographs, and bone marrow aspirate completed.',
                'outcome' => 'Localized disease, good candidate for palliative care approach',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-14 days')),
                'status' => 'completed',
                'metadata' => '{"tumor_grade": "intermediate", "location": "jejunum/ileum", "staging": "localized"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'intervention_type' => 'Family Consultation - Treatment Options',
                'intervention_category' => 'treatment',
                'description' => 'Extensive consultation with Chen family regarding treatment options including chemotherapy vs. palliative care. Discussed prognosis, quality of life considerations, and treatment goals.',
                'outcome' => 'Family chose palliative care approach, initiated comfort care protocol',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-12 days')),
                'status' => 'completed',
                'metadata' => '{"treatment_choice": "palliative", "family_support": "excellent", "goals": "comfort and quality"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-12 days')),
                'intervention_type' => 'Geraniol Therapy Initiation',
                'intervention_category' => 'treatment',
                'description' => 'Started geraniol 2mg/kg BID for anti-inflammatory and potential anti-neoplastic effects. Formulated in palatable liquid for easy administration. Explained protocol to family.',
                'outcome' => 'Well tolerated, initial reduction in vomiting frequency noted',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-8 days')),
                'status' => 'active',
                'metadata' => '{"anti_inflammatory": true, "anti_neoplastic": "potential", "palatability": "good"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-8 days')),
                'intervention_type' => 'β-Caryophyllene Addition',
                'intervention_category' => 'treatment',
                'description' => 'Added β-caryophyllene 1.5mg/kg BID for CB2-mediated appetite stimulation and pain management. Dosing adjusted for feline metabolism and palatability concerns.',
                'outcome' => 'Improved appetite and increased activity levels observed',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-4 days')),
                'status' => 'active',
                'metadata' => '{"CB2_activation": true, "appetite_stimulant": true, "pain_management": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 3,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-6 days')),
                'intervention_type' => 'Quality of Life Assessment',
                'intervention_category' => 'treatment',
                'description' => 'Implemented standardized feline quality of life scale (HHHHHMM Scale). Evaluated mobility, appetite, hygiene, happiness, and more. Established baseline for ongoing monitoring.',
                'outcome' => 'Baseline QoL score: 22/35 - Good quality with room for support',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'active',
                'metadata' => '{"QoL_scale": "HHHHHMM", "baseline_score": "22/35", "category": "good"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'intervention_type' => 'Nutritional Support Protocol',
                'intervention_category' => 'treatment',
                'description' => 'Implemented high-calorie, easily digestible diet with frequent small meals. Added probiotics for GI support and omega-3 supplements for anti-inflammatory benefits.',
                'outcome' => 'Weight stabilization achieved, improved energy levels',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+14 days')),
                'status' => 'active',
                'metadata' => '{"diet_type": "high-calorie", "probiotics": true, "omega3": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'intervention_type' => 'Kidney Function Monitoring',
                'intervention_category' => 'diagnosis',
                'description' => 'Serial monitoring of kidney function given age and potential effects of lymphoma. Chemistry panel including BUN, creatinine, and SDMA. Urinalysis for protein and specific gravity.',
                'outcome' => 'Kidney function stable, no concerning changes noted',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+21 days')),
                'status' => 'active',
                'metadata' => '{"BUN": "normal", "creatinine": "normal", "SDMA": "normal"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'intervention_type' => 'Family Support and Education',
                'intervention_category' => 'treatment',
                'description' => 'Ongoing support for Chen family including comfort care techniques, medication administration, and end-of-life planning. Provided resources for pet loss support.',
                'outcome' => 'Family feels confident and supported in Whiskers\' care',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'active',
                'metadata' => '{"family_education": true, "comfort_care": true, "support_resources": true}'
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_interventions', $interventions);
        
        echo "   ✅ Whiskers' case created with " . count($messages) . " messages, " . count($vitals) . " vital signs, and " . count($interventions) . " interventions\n";
        
        return $case_id;
    }
    
    /**
     * Case 4: Emergency Multi-trauma Case
     */
    private function create_emergency_case() {
        echo "🚨 Creating Case 4: Emergency Multi-trauma Case...\n";
        
        $case_id = wp_insert_post([
            'post_type' => 'terpedia_case',
            'post_title' => 'Case #004: Emergency - Multi-trauma Critical Care',
            'post_content' => 'Emergency presentation of 3-year-old mixed breed dog following motor vehicle accident. Patient arrived in hypovolemic shock with multiple injuries including pneumothorax, pelvic fractures, and significant soft tissue trauma.

Initial stabilization required immediate thoracostomy tube placement, aggressive fluid resuscitation, and multimodal pain management. Patient responded well to initial emergency interventions with stabilization of cardiovascular parameters.

Implemented emergency terpene protocol incorporating β-caryophyllene (4mg/kg q8h) for analgesic and anti-inflammatory effects, and linalool (3mg/kg q12h) for anxiolytic and muscle relaxant properties during critical care period. Protocol designed to complement traditional emergency medications while providing additional comfort and healing support.',
            'post_status' => 'publish',
            'post_author' => 1
        ]);
        
        // Patient Information
        update_post_meta($case_id, 'patient_name', 'Rocky (Emergency #E2024-089)');
        update_post_meta($case_id, 'species', 'Canine');
        update_post_meta($case_id, 'breed', 'Mixed Breed (Shepherd/Lab)');
        update_post_meta($case_id, 'age', '3 years');
        update_post_meta($case_id, 'weight', '32.1 kg');
        update_post_meta($case_id, 'owner_name', 'Michael Rodriguez (Emergency Contact)');
        update_post_meta($case_id, 'owner_contact', 'Phone: (555) 789-0123
Emergency: (555) 789-0124
Email: m.rodriguez.emergency@email.com
Address: UNKNOWN - Emergency case
Contact Person: Officer Johnson, Springfield PD
Reference #: SPD-2024-4567');
        update_post_meta($case_id, 'case_status', 'critical');
        
        // Chat Messages (emergency team coordination)
        $messages = [
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'EMERGENCY: MVA victim arriving in 5 minutes. Police report indicates multiple injuries, patient conscious but in apparent shock.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'ER team assembled. IV access established, vitals: HR 180, RR 40, pale MM, CRT >3sec. Suspect pneumothorax on right side.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours +10 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 3,
                'user_type' => 'human',
                'message' => 'Thoracostomy tube placed, immediate improvement in respiratory effort. Starting fluid resuscitation with LRS. Need pain management ASAP.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours +25 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'For multimodal pain management in trauma, consider β-caryophyllene 4mg/kg q8h for CB2-mediated analgesia and anti-inflammatory effects. Can complement opioids while potentially reducing requirements and side effects.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours +30 minutes')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Starting β-caryophyllene protocol now. Patient showing signs of anxiety/stress. Should we add linalool for anxiolytic effects?',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days +3 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Yes, add linalool 3mg/kg q12h. Will help with stress response and muscle tension. X-rays confirm pelvic fractures but stable.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days +4 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Patient stable overnight. HR down to 110, RR 24. Pain score improved from 8/10 to 5/10. Terpene protocol seems to be helping.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days +8 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 3,
                'user_type' => 'human',
                'message' => 'Owner located! Michael Rodriguez confirmed as owner. Very grateful for emergency care. Approved all treatment including terpene protocol.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days +12 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 0,
                'user_type' => 'ai',
                'message' => 'Excellent progress in the first 24 hours. The combination therapy appears to be providing effective pain management and stress reduction. Monitor for any respiratory depression with multimodal approach.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days +13 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 1,
                'user_type' => 'human',
                'message' => 'Rocky ate voluntarily this morning! First solid food since the accident. Owner reports he\'s recognizing voices and wagging slightly.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day +10 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ],
            [
                'id' => uniqid(),
                'user_id' => 2,
                'user_type' => 'human',
                'message' => 'Remarkable recovery considering the initial presentation. Planning discharge tomorrow with continued terpene support and orthopedic follow-up.',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'message_type' => 'chat',
                'metadata' => null
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_messages', $messages);
        
        // Vital Signs (critical care monitoring with improvement)
        $vitals = [];
        $base_time = strtotime('-3 days +2 hours');
        
        // Every 2 hours for first 24 hours (critical period)
        for ($i = 0; $i < 12; $i++) {
            $time = $base_time + ($i * 7200); // Every 2 hours
            
            // Simulate critical to stable progression
            $improvement = $i / 11;
            $critical_factor = 1 - ($improvement * 0.6);
            
            $vitals[] = [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => rand(1, 3),
                'recorded_date' => date('Y-m-d H:i:s', $time),
                'heart_rate' => round(180 - ($improvement * 70)), // 180 down to 110
                'blood_pressure_systolic' => round(80 + ($improvement * 50)), // 80 up to 130
                'blood_pressure_diastolic' => round(40 + ($improvement * 35)), // 40 up to 75
                'weight' => 32.1, // Stable
                'temperature' => round((36.8 + ($improvement * 1.2)) * 10) / 10, // 36.8 to 38.0
                'respiratory_rate' => round(40 - ($improvement * 16)), // 40 down to 24
                'notes' => $i < 3 ? 'Critical - immediate post-trauma' :
                          ($i < 8 ? 'Stabilizing, terpene protocol initiated' :
                           'Stable, good response to treatment')
            ];
        }
        
        // Then every 8 hours for next 2 days
        for ($i = 0; $i < 6; $i++) {
            $time = $base_time + 86400 + ($i * 28800); // Every 8 hours after first day
            
            $vitals[] = [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => rand(1, 3),
                'recorded_date' => date('Y-m-d H:i:s', $time),
                'heart_rate' => round(90 + rand(-10, 15)), // Stable range
                'blood_pressure_systolic' => round(125 + rand(-15, 10)),
                'blood_pressure_diastolic' => round(75 + rand(-10, 10)),
                'weight' => 32.1,
                'temperature' => round((38.1 + rand(-3, 3) * 0.1) * 10) / 10,
                'respiratory_rate' => round(20 + rand(-5, 8)),
                'notes' => 'Recovery phase, multimodal pain management effective'
            ];
        }
        
        update_post_meta($case_id, '_terpedia_case_vitals', $vitals);
        
        // Interventions (emergency and critical care)
        $interventions = [
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours')),
                'intervention_type' => 'Emergency Triage and Stabilization',
                'intervention_category' => 'procedure',
                'description' => 'Immediate assessment of MVA victim. Primary survey revealed pneumothorax, shock, and multiple trauma. Established IV access, initiated oxygen support, administered emergency analgesics.',
                'outcome' => 'Patient stabilized for further evaluation and treatment',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d H:i:s', strtotime('-3 days +3 hours')),
                'status' => 'completed',
                'metadata' => '{"triage_level": "critical", "initial_vitals": "HR 180, RR 40", "shock_present": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours +15 minutes')),
                'intervention_type' => 'Thoracostomy Tube Placement',
                'intervention_category' => 'procedure',
                'description' => 'Emergency thoracostomy tube placed for right-sided pneumothorax. Immediate evacuation of air with significant improvement in respiratory effort and oxygen saturation.',
                'outcome' => 'Successful resolution of pneumothorax, improved breathing',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('-2 days')),
                'status' => 'completed',
                'metadata' => '{"location": "right thorax", "tube_size": "14Fr", "immediate_improvement": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 3,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours +30 minutes')),
                'intervention_type' => 'β-Caryophyllene Emergency Protocol',
                'intervention_category' => 'treatment',
                'description' => 'Initiated β-caryophyllene at 4mg/kg q8h for multimodal pain management and anti-inflammatory support. CB2 receptor activation provides analgesia without respiratory depression.',
                'outcome' => 'Good initial response, pain score reduction noted',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d H:i:s', strtotime('-3 days +10 hours')),
                'status' => 'active',
                'metadata' => '{"mechanism": "CB2 agonist", "respiratory_safe": true, "multimodal": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-3 days +4 hours')),
                'intervention_type' => 'Linalool Anxiolytic Support',
                'intervention_category' => 'treatment',
                'description' => 'Added linalool 3mg/kg q12h for anxiolytic and muscle relaxant effects during critical care period. GABA-ergic activity helps reduce stress response and promote healing.',
                'outcome' => 'Notable reduction in stress behaviors and muscle tension',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'status' => 'active',
                'metadata' => '{"GABA_agonist": true, "stress_reduction": true, "muscle_relaxant": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-3 days +6 hours')),
                'intervention_type' => 'Diagnostic Imaging',
                'intervention_category' => 'diagnosis',
                'description' => 'Complete radiographic survey including thorax, abdomen, and pelvis. Identified pelvic fractures (stable), confirmed pneumothorax resolution, no abdominal bleeding evident.',
                'outcome' => 'Fractures stable, no surgical intervention required immediately',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'completed',
                'metadata' => '{"pelvic_fractures": "stable", "pneumothorax": "resolved", "abdominal_bleeding": "negative"}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-2 days +8 hours')),
                'intervention_type' => 'Pain Assessment and Adjustment',
                'intervention_category' => 'treatment',
                'description' => 'Formal pain scoring using Glasgow Composite Pain Scale. Score improved from 8/10 to 5/10 with terpene protocol. Adjusted conventional analgesics accordingly.',
                'outcome' => 'Effective pain control achieved, patient comfort improved',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'active',
                'metadata' => '{"pain_scale": "Glasgow", "improvement": "8/10 to 5/10", "protocol_effective": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 3,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-2 days +12 hours')),
                'intervention_type' => 'Owner Communication and Consent',
                'intervention_category' => 'treatment',
                'description' => 'Located and contacted owner Michael Rodriguez. Provided comprehensive update on injuries, treatment, and prognosis. Obtained consent for continued care including terpene protocol.',
                'outcome' => 'Owner fully informed and supportive of treatment plan',
                'follow_up_required' => false,
                'follow_up_date' => null,
                'status' => 'completed',
                'metadata' => '{"owner_located": true, "consent_obtained": true, "supportive": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 1,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-1 day +10 hours')),
                'intervention_type' => 'Nutritional Support Initiation',
                'intervention_category' => 'treatment',
                'description' => 'Encouraged voluntary eating with high-calorie, easily digestible food. First solid food intake since accident. Added probiotics and digestive support supplements.',
                'outcome' => 'Successful food intake, normal digestion restored',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+3 days')),
                'status' => 'active',
                'metadata' => '{"voluntary_eating": true, "first_meal": "successful", "digestive_support": true}'
            ],
            [
                'id' => uniqid(),
                'case_id' => $case_id,
                'recorded_by' => 2,
                'intervention_date' => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'intervention_type' => 'Discharge Planning',
                'intervention_category' => 'treatment',
                'description' => 'Preparing discharge plan with continued terpene support protocol, orthopedic follow-up scheduled, and home care instructions provided to owner.',
                'outcome' => 'Comprehensive discharge plan established, owner educated',
                'follow_up_required' => true,
                'follow_up_date' => date('Y-m-d', strtotime('+7 days')),
                'status' => 'active',
                'metadata' => '{"home_care": true, "orthopedic_referral": true, "terpene_continuation": true}'
            ]
        ];
        
        update_post_meta($case_id, '_terpedia_case_interventions', $interventions);
        
        echo "   ✅ Rocky's emergency case created with " . count($messages) . " messages, " . count($vitals) . " vital signs, and " . count($interventions) . " interventions\n";
        
        return $case_id;
    }
}

// Run the seeder
echo "🚀 Initializing Terpedia Case Management Data Seeder...\n";
$seeder = new TerpediaCaseSeeder();
$case_ids = $seeder->seed_all_cases();

echo "\n🎉 Data seeding completed successfully!\n";
echo "📊 Summary:\n";
echo "   • 4 comprehensive veterinary cases created\n";
echo "   • 40+ realistic chat messages across all cases\n";  
echo "   • 70+ vital sign data points for meaningful trends\n";
echo "   • 30+ professional veterinary interventions\n";
echo "   • Terpene research references integrated throughout\n";
echo "\n🌐 Visit your case management system:\n";
echo "   • All Cases: /cases\n";
echo "   • Individual Cases: /case/[ID]\n";
echo "   • Chat Interface: /case/[ID]/chat\n";
echo "   • Vital Signs: /case/[ID]/vitals\n";
echo "   • Interventions: /case/[ID]/interventions\n";
echo "\n✅ Case Management System fully populated and ready for demonstration!\n";