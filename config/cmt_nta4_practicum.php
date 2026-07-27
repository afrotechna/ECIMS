<?php

/**
 * CMT NTA Level 4 clinical practicum catalogue.
 * Source: Musoma COHAS "CMT 4 Tutors Practicum Guide (FINAL)" + NACTVET CMT Level 4.
 *
 * Each procedure row:
 *   [department_code, code, name, description, min_required_count, assessment_modes, practicum_section]
 *
 * assessment_modes — how competence is assessed per NACTVET / college guide (comma-separated).
 * Sync: php artisan clinical:sync-cmt4-practicum
 */
return [
    'source' => 'CMT 4 Tutors Practicum Guide (FINAL)',
    'programme_code' => 'CMT',
    'nta_level' => 4,

    /** Modes of assessment used across the practicum (from guide / NACTVET CMT 4). */
    'assessment_methods' => [
        'Practical (supervised ward/lab)',
        'OSCE',
        'OSPE',
        'Checklist',
        'Written test',
        'Oral questioning',
        'Assignment (written)',
        'Assignment (practical)',
        'Logbook / procedure record (instructor sign-off)',
        'Continuous assessment (CA)',
    ],

    'semester_modules' => [
        'CMT04208' => 'Clinical Nutrition',
        'CMT04209' => 'Clinical Skills',
        'CMT04210' => 'Pathology',
        'CMT04211' => 'Clinical Laboratory',
        'CMT04212' => 'Patient Care',
    ],

    /** Semester I module codes (practicum guide) → display title. */
    'module_titles' => [
        'CMT04101' => 'Communication Skills and Customer Care',
        'CMT04102' => 'Human Anatomy and Physiology',
        'CMT04104' => 'Epidemiology',
        'CMT04105' => 'Computer Applications',
        'CMT04107' => 'Microbiology',
        'CMT04208' => 'Clinical Nutrition',
        'CMT04209' => 'Clinical Skills',
        'CMT04210' => 'Pathology',
        'CMT04211' => 'Clinical Laboratory',
        'CMT04212' => 'Patient Care',
        'SEMESTER_I' => 'Semester I practicum (checklists 1–20)',
    ],
    'rotation_areas' => [
        'clinical_nutrition' => 'Clinical Nutrition',
        'clinical_laboratory' => 'Clinical Laboratory',
        'internal_medicine' => 'Internal Medicine',
        'obstetrics_gynaecology' => 'Obstetrics and Gynaecology',
        'paediatrics' => 'Paediatric and Child Health',
        'patient_care' => 'Patient Care',
    ],
    'procedures' => [
        // Clinical Nutrition — CMT04208
        ['clinical_nutrition', 'CN01', 'Dietary history and nutritional assessment', 'CMT04208 · Diet history and nutritional status', 2, 'Practical, Oral questioning, Checklist, Assignment (written)', 'Clinical Nutrition posting'],
        ['clinical_nutrition', 'CN02', 'Therapeutic diet and meal planning observation', 'CMT04208 · Therapeutic diets for common conditions', 1, 'Practical, Oral questioning, Assignment (written)', 'Clinical Nutrition posting'],
        ['clinical_nutrition', 'CN03', 'Assist patient with feeding', 'CMT04208 · Feeding assistance and swallowing precautions', 2, 'Practical, OSCE, Checklist, Logbook / procedure record (instructor sign-off)', 'Clinical Nutrition posting'],
        ['clinical_nutrition', 'CN04', 'Nutrition health education session', 'CMT04208 · Group or individual nutrition education', 1, 'Practical, Oral questioning, Assignment (written)', 'Clinical Nutrition posting'],

        // Clinical Laboratory posting — CMT04211 (and related skills outcomes)
        ['clinical_laboratory', 'LB01', 'Specimen collection and labelling', 'CMT04211 · 5.3.3(b–c) Blood, stool, urine, sputum specimens', 2, 'Practical, OSPE, Checklist, Assignment (practical)', 'Clinical Laboratory'],
        ['clinical_laboratory', 'LB02', 'Basic laboratory test assistance', 'CMT04211 · 5.3.3(d–g) Stool, urine, sputum, blood tests', 2, 'Practical, OSPE, Checklist, Written test', 'Clinical Laboratory'],
        ['clinical_laboratory', 'LB03', 'Laboratory result interpretation', 'CMT04211 · 5.3.3(g) Interpret and explain results', 1, 'Oral questioning, Written test, OSPE, Checklist', 'Clinical Laboratory'],
        ['clinical_laboratory', 'LB04', 'Laboratory equipment and reagents', 'CMT04211 · 5.3.2 Describe equipment, chemicals, biohazards', 1, 'Practical, OSPE, Written test, Checklist', 'Clinical Laboratory'],
        ['clinical_laboratory', 'LB05', 'Basic diagnostic staining techniques', 'CMT04211 · 5.3.1 Giemsa, Ziehl-Neelsen, Gram stain', 1, 'Practical, OSPE, Checklist', 'Clinical Laboratory'],

        // Patient Care — CMT04212 / 5.3.4
        ['patient_care', 'PC01', 'Personal hygiene and bed bath', 'CMT04212 · 5.3.4 Bed bath, oral care, positioning', 2, 'Practical, OSCE, Checklist, Logbook / procedure record (instructor sign-off)', 'Patient Care posting'],
        ['patient_care', 'PC02', 'Position patient for examination', 'CMT04212 · 5.3.4(b) Position for examination', 2, 'Practical, OSCE, Checklist', 'Patient Care posting'],
        ['patient_care', 'PC03', 'Bed preparation for medical and surgical care', 'CMT04212 · 5.3.4(c) Beds for delivery, theatre, traction', 2, 'Practical, Checklist, Assignment (practical)', 'Patient Care posting'],
        ['patient_care', 'PC04', 'Medication administration (5 Rights)', 'CMT04212 · 5.3.4(e) Five Rights — supervised', 2, 'Practical, OSCE, Checklist, Oral questioning', 'Patient Care posting'],
        ['patient_care', 'PC05', 'Pre- and post-operative nursing care', 'CMT04212 · 5.3.4(f) Pre/post-op and unconscious patient care', 2, 'Practical, OSCE, Checklist, Written test', 'Patient Care posting'],
        ['patient_care', 'PC06', 'Wound dressing', 'CMT04212 · 5.3.4(g) Wound dressing supervised', 2, 'Practical, OSCE, Checklist, Logbook / procedure record (instructor sign-off)', 'Patient Care posting'],
        ['patient_care', 'PC07', 'Urethral catheterization (supervised)', 'CMT04212 · 5.3.4(g) Catheterization assisted', 1, 'Practical, OSCE, Checklist', 'Patient Care posting'],
        ['patient_care', 'PC08', 'Basic life support and IV cannulation', 'CMT04212 · 5.3.4(h) CPR and IV cannulation supervised', 1, 'Practical, OSCE, Checklist', 'Patient Care posting'],
        ['patient_care', 'CS01', 'Vital signs monitoring', 'CMT04209 · BP, pulse, respiration, temperature, SpO2', 3, 'Practical, OSCE, Checklist, Written test', 'Patient Care posting'],
        ['patient_care', 'CS02', 'Patient history taking', 'CMT04213 · 2.1.5 History using communication skills', 3, 'Practical, OSCE, Oral questioning, Checklist', 'Patient Care posting'],
        ['patient_care', 'CS03', 'General physical examination', 'CMT04213 · 5.3.5(a–c) General examination', 2, 'Practical, OSCE, Checklist, Written test', 'Patient Care posting'],
        ['patient_care', 'CS04', 'Systemic physical examination', 'CMT04213 · 5.3.5(d–f) Systemic examination and recording', 2, 'Practical, OSCE, Checklist, Assignment (practical)', 'Patient Care posting'],
        ['patient_care', 'CS05', 'Therapeutic communication', 'CMT04213 · 2.1.6 Therapeutic relationship', 2, 'Practical, OSCE, Oral questioning, Checklist', 'Patient Care posting'],

        // Internal Medicine posting
        ['internal_medicine', 'IM01', 'Adult patient focused assessment', 'Ward · history and examination of medical patient', 3, 'Practical, OSCE, Checklist, Continuous assessment (CA)', 'Internal Medicine posting'],
        ['internal_medicine', 'IM02', 'Document clinical findings', 'Ward · record findings confidentially', 2, 'Assignment (written), Checklist, Logbook / procedure record (instructor sign-off)', 'Internal Medicine posting'],
        ['internal_medicine', 'IM03', 'Medical ward round participation', 'Ward · ward round and care plan', 1, 'Practical, Oral questioning, Continuous assessment (CA)', 'Internal Medicine posting'],

        // Paediatrics posting
        ['paediatrics', 'PD01', 'Child growth monitoring', 'Growth chart plotting and interpretation', 3, 'Practical, OSCE, Checklist, Written test', 'Paediatrics posting'],
        ['paediatrics', 'PD02', 'Sick child assessment (IMNCI basics)', 'IMNCI-style assessment supervised', 2, 'Practical, OSCE, Checklist, Oral questioning', 'Paediatrics posting'],
        ['paediatrics', 'PD03', 'Immunization session observation', 'Routine immunization clinic', 1, 'Practical, Checklist, Oral questioning', 'Paediatrics posting'],

        // Obstetrics & Gynaecology posting
        ['obstetrics_gynaecology', 'OG01', 'Antenatal history and assessment', 'ANC · history and basic examination', 3, 'Practical, OSCE, Checklist, Oral questioning', 'O&G posting'],
        ['obstetrics_gynaecology', 'OG02', 'Antenatal examination supervised', 'ANC · abdominal exam and fundal height', 2, 'Practical, OSCE, Checklist', 'O&G posting'],
        ['obstetrics_gynaecology', 'OG03', 'Postnatal mother and newborn observation', 'Postnatal · mother and newborn review', 2, 'Practical, Checklist, Logbook / procedure record (instructor sign-off)', 'O&G posting'],
    ],
];
