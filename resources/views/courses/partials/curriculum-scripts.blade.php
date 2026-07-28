{{-- Curriculum: collapsible NTA 4–6 → Semester I/II; multi-select checkboxes; POST curriculum_modules[] --}}
<script>
(function() {
    var jsonEl = document.getElementById('curriculum-modules-json');
    if (!jsonEl) return;
    var CUR = {};
    try { CUR = JSON.parse(jsonEl.textContent || '{}'); } catch (e) {}

    var progEl = document.getElementById('programme_id');
    var ntaEl = document.getElementById('nta_level');
    var ntaLevelWrap = document.getElementById('ntaLevelWrap');
    var mount = document.getElementById('curriculumTablesMount');
    var manualBlock = document.getElementById('manualModuleFields');
    var curriculumSummary = document.getElementById('curriculumSelectedSummary');
    var semesterManualWrap = document.getElementById('semesterCheckboxesWrap');
    var formEl = document.getElementById('courseCreateForm');
    var curriculumNotesEl = document.getElementById('curriculumNotes');
    var creditsWrap = document.getElementById('creditsWrap');
    var creditsInput = document.getElementById('credits');
    var assessmentCard = document.getElementById('assessmentOptionsCard');

    var fields = {
        code: document.getElementById('field_code'),
        name: document.getElementById('field_name'),
        ca_weight: document.getElementById('field_ca_weight'),
        exam_weight: document.getElementById('field_exam_weight'),
        has_practical: document.getElementById('field_has_practical'),
        practical_type: document.getElementById('field_practical_assessment_type'),
        practicalWrap: document.getElementById('practicalTypeWrap'),
    };

    function programmeCode() {
        var opt = progEl && progEl.options[progEl.selectedIndex];
        return opt ? (opt.getAttribute('data-programme-code') || '').toUpperCase() : '';
    }

    function programmeHasCurriculumGrid(code) {
        if (!code || !CUR.programmes || !CUR.programmes[code]) return false;
        for (var n = 4; n <= 6; n++) {
            var pkg = CUR.programmes[code][n];
            if (pkg && ((pkg[1] && pkg[1].length) || (pkg[2] && pkg[2].length))) return true;
        }
        return false;
    }

    function pct(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v);
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function buildSemesterTable(ntaLevel, termNum, rows) {
        var caPct = (CUR.defaults && CUR.defaults.ca_weight) || 40;
        var sePct = (CUR.defaults && CUR.defaults.exam_weight) || 60;
        var h = '<div class="curriculum-table-card mb-0">';
        h += '<p class="small text-muted mb-2">Weighting: Continuous Assessment (CA) = ' + caPct + '% · End of Semester Exam (SE) = ' + sePct + '%</p>';
        h += '<div class="table-responsive"><table class="table table-bordered table-sm curriculum-matrix mb-0 align-middle">';
        h += '<thead><tr class="table-light text-center">';
        h += '<th rowspan="2" class="align-middle small" style="width:2.5rem;">Add</th>';
        h += '<th rowspan="2" class="align-middle">Code</th>';
        h += '<th rowspan="2" class="align-middle text-start">Module Title</th>';
        h += '<th colspan="3">CA (' + caPct + '%)</th>';
        h += '<th colspan="2">SE (' + sePct + '%)</th>';
        h += '<th rowspan="2" class="align-middle">TOTAL</th>';
        h += '</tr><tr class="table-light text-center small">';
        h += '<th>WR</th><th>AS</th><th>CLN/PR/OSCE/OSPE</th>';
        h += '<th>WR</th><th>CLN/OSCE/PR</th>';
        h += '</tr></thead><tbody>';

        rows.forEach(function(row, idx) {
            var rid = 'cur_cb_' + ntaLevel + '_' + termNum + '_' + idx;
            var token = ntaLevel + '|' + termNum + '|' + String(row.code || '').replace(/\|/g, '');
            h += '<tr>';
            h += '<td class="text-center"><input type="checkbox" name="curriculum_modules[]" class="form-check-input curriculum-module-cb mt-0" id="' + rid + '" value="' + escAttr(token) + '"></td>';
            h += '<td class="font-monospace small"><label class="mb-0 fw-semibold" for="' + rid + '">' + escHtml(row.code) + '</label></td>';
            h += '<td class="text-start"><label class="mb-0 fw-normal" for="' + rid + '">' + escHtml(row.title) + '</label></td>';
            h += '<td class="text-center">' + pct(row.ca_wr) + '</td>';
            h += '<td class="text-center">' + pct(row.ca_as) + '</td>';
            h += '<td class="text-center">' + pct(row.ca_cln) + '</td>';
            h += '<td class="text-center">' + pct(row.se_wr) + '</td>';
            h += '<td class="text-center">' + pct(row.se_cln) + '</td>';
            h += '<td class="text-center fw-semibold">100</td>';
            h += '</tr>';
        });

        h += '</tbody></table></div></div>';
        return h;
    }

    function updateSelectionSummary() {
        if (!curriculumSummary) return;
        var n = mount ? mount.querySelectorAll('.curriculum-module-cb:checked').length : 0;
        if (n < 1) {
            curriculumSummary.classList.add('d-none');
            curriculumSummary.innerHTML = '';
            return;
        }
        curriculumSummary.innerHTML = '<div class="alert alert-light border mb-0 py-2"><strong>' + n + '</strong> module(s) selected — click Save to add them all.</div>';
        curriculumSummary.classList.remove('d-none');
    }

    function clearFields() {
        if (fields.code) fields.code.value = '';
        if (fields.name) fields.name.value = '';
        if (curriculumSummary) {
            curriculumSummary.innerHTML = '';
            curriculumSummary.classList.add('d-none');
        }
    }

    function syncNtaRequired(code) {
        if (!ntaEl) return;
        if (programmeHasCurriculumGrid(code)) {
            ntaEl.removeAttribute('required');
        } else {
            ntaEl.setAttribute('required', 'required');
        }
    }

    function render() {
        if (!mount) return;
        var code = programmeCode();

        if (!programmeHasCurriculumGrid(code)) {
            mount.innerHTML = '';
            mount.classList.add('d-none');
            if (manualBlock) manualBlock.classList.remove('d-none');
            if (curriculumSummary) curriculumSummary.classList.add('d-none');
            if (semesterManualWrap) semesterManualWrap.classList.remove('d-none');
            if (curriculumNotesEl) curriculumNotesEl.classList.add('d-none');
            if (creditsWrap) creditsWrap.classList.remove('d-none');
            if (assessmentCard) assessmentCard.classList.remove('d-none');
            if (ntaLevelWrap) ntaLevelWrap.classList.remove('d-none');
            clearFields();
            syncNtaRequired(code);
            return;
        }

        mount.classList.remove('d-none');
        if (manualBlock) manualBlock.classList.add('d-none');
        if (semesterManualWrap) semesterManualWrap.classList.add('d-none');
        if (curriculumNotesEl) curriculumNotesEl.classList.remove('d-none');
        if (creditsWrap) creditsWrap.classList.add('d-none');
        if (creditsInput) creditsInput.value = '0';
        if (assessmentCard) assessmentCard.classList.add('d-none');
        if (ntaLevelWrap) ntaLevelWrap.classList.add('d-none');

        var levelOrder = [4, 5, 6];
        var firstLevelOpen = true;
        var html = '<div id="curriculum-collapse-scope">';

        levelOrder.forEach(function(nta) {
            var pkg = CUR.programmes[code] && CUR.programmes[code][nta];
            if (!pkg || (!(pkg[1] && pkg[1].length) && !(pkg[2] && pkg[2].length))) return;

            var levelId = 'cur_lvl_' + code + '_' + nta;
            var levelShow = firstLevelOpen;
            firstLevelOpen = false;

            html += '<div class="card card-landing mb-3">';
            html += '<div class="card-header py-2 d-flex justify-content-between align-items-center curriculum-fold-trigger ' + (levelShow ? '' : 'collapsed') + '" data-bs-toggle="collapse" data-bs-target="#' + levelId + '" aria-expanded="' + (levelShow ? 'true' : 'false') + '" role="button" tabindex="0">';
            html += '<span class="fw-semibold"><i class="bi bi-layers me-2 text-primary"></i>NTA Level ' + nta + '</span>';
            html += '<i class="bi bi-chevron-down curriculum-fold-icon flex-shrink-0"></i></div>';
            html += '<div id="' + levelId + '" class="collapse ' + (levelShow ? 'show' : '') + '"><div class="card-body pt-3 pb-2">';

            var firstSemOpen = true;
            [1, 2].forEach(function(term) {
                if (!pkg[term] || !pkg[term].length) return;
                var semId = levelId + '_sem' + term;
                var semShow = levelShow && firstSemOpen;
                if (semShow) firstSemOpen = false;

                html += '<div class="mb-4">';
                html += '<div class="d-flex justify-content-between align-items-center py-2 small fw-semibold text-uppercase text-muted curriculum-fold-trigger border-bottom ' + (semShow ? '' : 'collapsed') + '" data-bs-toggle="collapse" data-bs-target="#' + semId + '" aria-expanded="' + (semShow ? 'true' : 'false') + '" role="button" tabindex="0">';
                html += '<span>Semester ' + (term === 1 ? 'I' : 'II') + '</span>';
                html += '<i class="bi bi-chevron-down curriculum-fold-icon flex-shrink-0"></i></div>';
                html += '<div id="' + semId + '" class="collapse ' + (semShow ? 'show' : '') + ' mt-2">';
                html += buildSemesterTable(nta, term, pkg[term]);
                html += '</div></div>';
            });

            html += '</div></div></div>';
        });

        html += '</div>';
        mount.innerHTML = html;

        updateSelectionSummary();
        syncNtaRequired(code);
    }

    if (formEl) {
        formEl.addEventListener('change', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('curriculum-module-cb')) {
                updateSelectionSummary();
            }
        });
        formEl.addEventListener('submit', function(e) {
            var code = programmeCode();
            if (!programmeHasCurriculumGrid(code)) return;
            var n = mount ? mount.querySelectorAll('.curriculum-module-cb:checked').length : 0;
            if (n < 1) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'No modules selected', text: 'Please tick one or more modules in the curriculum tables below.' });
            }
        });
    }

    function onProgrammeChange() {
        if (programmeCode() === 'CMT' && ntaEl) {
            ntaEl.value = '4';
        }
        render();
    }

    if (progEl) progEl.addEventListener('change', onProgrammeChange);
    if (ntaEl) ntaEl.addEventListener('change', render);

    document.addEventListener('DOMContentLoaded', function() {
        render();
    });
})();
</script>
