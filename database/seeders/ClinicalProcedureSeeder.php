<?php

namespace Database\Seeders;

use App\Models\ClinicalProcedure;
use Illuminate\Database\Seeder;

class ClinicalProcedureSeeder extends Seeder
{
    public function run(): void
    {
        if (ClinicalProcedure::query()->exists()) {
            return;
        }

        $rows = [
            // NTA 4 — core skills
            [4, 'clinical_laboratory', 'LB01', 'Specimen collection and labelling', 'Collect and label blood, stool, urine, sputum specimens'],
            [4, 'clinical_laboratory', 'LB02', 'Basic laboratory test assistance', 'Assist basic lab tests under supervision'],
            [4, 'internal_medicine', 'IM01', 'Adult patient assessment', 'Focused assessment of medical ward patient'],
            [4, 'paediatrics', 'PD01', 'Child growth monitoring', 'Plot weight/height and interpret growth chart'],
            [4, 'obstetrics_gynaecology', 'OG01', 'Antenatal assessment', 'Antenatal history and basic examination'],
            [4, 'patient_care', 'PC01', 'Nursing care procedure', 'Bed bath, oral care, or positioning under supervision'],
            // NTA 5–6 — clinical medicine diploma
            [5, 'internal_medicine', 'IM10', 'Adult history and examination', 'Full history and systems review'],
            [5, 'internal_medicine', 'IM11', 'IV cannulation / fluid management', 'Under direct supervision'],
            [5, 'surgery', 'SU01', 'Surgical wound care', 'Dressing change and wound assessment'],
            [5, 'surgery', 'SU02', 'Pre-operative preparation', 'Checklist and patient preparation'],
            [5, 'obstetrics_gynaecology', 'OG10', 'Normal delivery observation', 'Observed or assisted normal delivery'],
            [5, 'obstetrics_gynaecology', 'OG11', 'Postnatal care', 'Mother and newborn postnatal review'],
            [5, 'paediatrics', 'PD10', 'Paediatric assessment', 'IMNCI-style sick child assessment'],
            [5, 'community_health', 'CH01', 'Community outreach visit', 'Home visit or community clinic activity'],
            [5, 'community_health', 'CH02', 'Health education session', 'Group health talk documentation'],
            [6, 'internal_medicine', 'IM20', 'Emergency triage', 'Triage and stabilization under supervision'],
            [6, 'surgery', 'SU10', 'Suturing (simulation or supervised)', 'Simple wound closure'],
            [6, 'obstetrics_gynaecology', 'OG20', 'Complication recognition', 'Identify danger signs in pregnancy/labour'],
            [6, 'paediatrics', 'PD20', 'Neonatal examination', 'Newborn assessment within 24 hours'],
            [6, 'community_health', 'CH10', 'Outbreak / surveillance activity', 'Public health field activity log'],
        ];

        $order = 0;
        foreach ($rows as [$nta, $dept, $code, $name, $desc]) {
            ClinicalProcedure::create([
                'programme_id' => null,
                'nta_level' => $nta,
                'department_code' => $dept,
                'code' => $code,
                'name' => $name,
                'description' => $desc,
                'sort_order' => $order++,
                'is_active' => true,
                'min_required_count' => $nta >= 5 ? 2 : 1,
            ]);
        }
    }
}
