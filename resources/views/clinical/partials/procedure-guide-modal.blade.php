@once
@php
    $practicumPdfUrl = $practicum_guide['url'] ?? null;
    $practicumPdfLabel = $practicum_guide['source'] ?? 'CMT 4 Tutors Practicum Guide';
@endphp
<div class="modal fade procedure-guide-modal" id="procedureGuideModal" tabindex="-1" aria-labelledby="procedureGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="procedure-guide-modal__hero px-4 pt-4 pb-3 text-white position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="d-flex align-items-start gap-3 pe-4">
                    <div class="procedure-guide-modal__icon flex-shrink-0">
                        <i class="bi bi-journal-medical"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="small text-white text-opacity-75 mb-1 text-uppercase tracking-wide">Procedure instructions</p>
                        <h5 class="modal-title fw-semibold mb-1 lh-sm" id="procedureGuideModalLabel">—</h5>
                        <span class="badge bg-white bg-opacity-25 text-white font-monospace" id="procedureGuideModalCode"></span>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0">
                <div class="px-4 py-3 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <p class="small text-muted mb-0" id="procedureGuideModalIntro">
                            <i class="bi bi-info-circle me-1"></i>
                            Complete all steps below under supervision. Your logbook mark is for the <strong>whole procedure</strong>, not each step.
                        </p>
                        <a href="#" class="btn btn-sm btn-outline-danger procedure-guide-modal__pdf-btn d-none" id="procedureGuideModalPdfBtn" target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Open in PDF
                        </a>
                    </div>
                </div>

                <div class="px-4 py-3" id="procedureGuideModalAssessment" style="display: none;">
                    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing: .04em;">How you will be assessed</div>
                    <div class="d-flex flex-wrap gap-2" id="procedureGuideModalAssessmentBadges"></div>
                </div>

                <div class="px-4 pb-2" id="procedureGuideModalStepsWrap">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-check text-primary me-2"></i>What to do</h6>
                        <span class="badge rounded-pill bg-primary-subtle text-primary" id="procedureGuideModalStepCount">0 steps</span>
                    </div>
                    <div class="procedure-guide-modal__steps" id="procedureGuideModalSteps"></div>
                </div>

                <div class="px-4 pb-4 d-none" id="procedureGuideModalNotes">
                    <div class="procedure-guide-modal__notes card border-0 bg-light">
                        <div class="card-body py-3">
                            <div class="small fw-semibold text-muted text-uppercase mb-2">Additional notes</div>
                            <p class="small mb-0 text-secondary" id="procedureGuideModalNotesText"></p>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4 d-none" id="procedureGuideModalEmpty">
                    <div class="text-center py-4 px-3 procedure-guide-modal__empty">
                        <i class="bi bi-journal-x display-6 text-muted opacity-50"></i>
                        <p class="mt-3 mb-2 fw-medium">No step list imported for this procedure</p>
                        <p class="small text-muted mb-3">Use the official practicum guide PDF for the full checklist criteria.</p>
                        <a href="#" class="btn btn-primary btn-sm procedure-guide-modal__pdf-btn-empty d-none" target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View full practicum PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 px-4 py-3 d-flex flex-wrap justify-content-between gap-2">
                <span class="small text-muted align-self-center" id="procedureGuideModalFooterHint">Reference only — not marked per step</span>
                <div class="d-flex gap-2 ms-auto">
                    @if($practicumPdfUrl)
                    <a href="{{ $practicumPdfUrl }}" class="btn btn-outline-primary btn-sm procedure-guide-modal__pdf-footer" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Full guide (PDF)
                    </a>
                    @endif
                    <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.procedure-guide-modal .modal-content { border-radius: 1rem; }
.procedure-guide-modal__hero {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 45%, #084298 100%);
}
.procedure-guide-modal__icon {
    width: 3rem; height: 3rem;
    border-radius: .75rem;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem;
}
.tracking-wide { letter-spacing: .06em; }
.procedure-guide-modal__steps {
    display: flex; flex-direction: column; gap: .65rem;
    max-height: min(52vh, 420px); overflow-y: auto;
    padding-right: .25rem;
}
.procedure-guide-modal__step {
    display: flex; gap: .85rem; align-items: flex-start;
    padding: .85rem 1rem;
    background: #fff;
    border: 1px solid #e8eef4;
    border-radius: .65rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    transition: border-color .15s ease, box-shadow .15s ease;
}
.procedure-guide-modal__step:hover {
    border-color: #b6d4fe;
    box-shadow: 0 2px 8px rgba(13, 110, 253, .08);
}
.procedure-guide-modal__step-num {
    flex-shrink: 0;
    width: 1.75rem; height: 1.75rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: #fff;
    font-size: .75rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.procedure-guide-modal__step-text {
    font-size: .9rem; line-height: 1.45; color: #334155; padding-top: .1rem;
}
.procedure-guide-modal__assess-badge {
    font-size: .75rem; font-weight: 500;
    padding: .35rem .65rem;
    border-radius: 2rem;
    background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0;
}
.procedure-guide-modal__steps::-webkit-scrollbar { width: 6px; }
.procedure-guide-modal__steps::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.procedure-guide-modal__empty { border-radius: .75rem; background: #f8fafc; border: 1px dashed #dee2e6; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const guides = @json($procedure_guides ?? []);
    const defaultPdfUrl = @json($practicumPdfUrl);
    const defaultPdfLabel = @json($practicumPdfLabel);

    const modalEl = document.getElementById('procedureGuideModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const titleEl = document.getElementById('procedureGuideModalLabel');
    const codeEl = document.getElementById('procedureGuideModalCode');
    const stepsContainer = document.getElementById('procedureGuideModalSteps');
    const stepsWrap = document.getElementById('procedureGuideModalStepsWrap');
    const stepCountEl = document.getElementById('procedureGuideModalStepCount');
    const notesWrap = document.getElementById('procedureGuideModalNotes');
    const notesText = document.getElementById('procedureGuideModalNotesText');
    const emptyEl = document.getElementById('procedureGuideModalEmpty');
    const assessWrap = document.getElementById('procedureGuideModalAssessment');
    const assessBadges = document.getElementById('procedureGuideModalAssessmentBadges');
    const pdfBtnTop = document.getElementById('procedureGuideModalPdfBtn');
    const pdfBtnEmpty = document.querySelector('.procedure-guide-modal__pdf-btn-empty');

    function setPdfLink(anchor, url) {
        if (!anchor) return;
        if (url) {
            anchor.href = url;
            anchor.classList.remove('d-none');
            anchor.title = defaultPdfLabel;
        } else {
            anchor.href = '#';
            anchor.classList.add('d-none');
        }
    }

    function renderSteps(steps) {
        stepsContainer.innerHTML = '';
        steps.forEach(function (step, index) {
            const text = typeof step === 'string' ? step : (step.name || step);
            const row = document.createElement('div');
            row.className = 'procedure-guide-modal__step';
            row.innerHTML =
                '<span class="procedure-guide-modal__step-num">' + (index + 1) + '</span>' +
                '<span class="procedure-guide-modal__step-text"></span>';
            row.querySelector('.procedure-guide-modal__step-text').textContent = text;
            stepsContainer.appendChild(row);
        });
        const n = steps.length;
        stepCountEl.textContent = n === 1 ? '1 step' : n + ' steps';
    }

    function openProcedureGuide(procedureId) {
        const guide = guides[procedureId] || guides[String(procedureId)];
        if (!guide) return;

        titleEl.textContent = guide.title || 'Procedure instructions';
        codeEl.textContent = guide.code || '—';

        const steps = guide.steps || [];
        const pdfUrl = guide.pdf_url || defaultPdfUrl || null;

        setPdfLink(pdfBtnTop, pdfUrl);
        setPdfLink(pdfBtnEmpty, pdfUrl);

        if (steps.length > 0) {
            stepsWrap.classList.remove('d-none');
            emptyEl.classList.add('d-none');
            renderSteps(steps);
        } else {
            stepsWrap.classList.add('d-none');
            emptyEl.classList.remove('d-none');
        }

        assessBadges.innerHTML = '';
        const modes = guide.assessment_modes || [];
        if (modes.length > 0) {
            assessWrap.style.display = '';
            modes.forEach(function (mode) {
                const span = document.createElement('span');
                span.className = 'procedure-guide-modal__assess-badge';
                span.textContent = mode;
                assessBadges.appendChild(span);
            });
        } else {
            assessWrap.style.display = 'none';
        }

        if (guide.notes && String(guide.notes).trim().length > 15) {
            notesWrap.classList.remove('d-none');
            notesText.textContent = guide.notes;
        } else {
            notesWrap.classList.add('d-none');
        }

        modal.show();
    }

    document.querySelectorAll('.procedure-guide-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openProcedureGuide(btn.dataset.procedureId);
        });
    });

    window.openClinicalProcedureGuide = openProcedureGuide;
})();
</script>
@endpush
@endonce
