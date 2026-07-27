# COHAS — Further Features & Improvement Ideas

Suggestions beyond the items already in `FEATURE_SUGGESTIONS.md` (most of which are implemented). Prioritise by your needs and capacity.

---

## New features

### 1. Class list / exam list report (medium impact)

- **Report:** “Class list” — select semester + course; show all students **registered and approved** for that course in that semester (reg no, name, programme).
- **Use:** Exam lists, attendance sheets, invigilation.
- **Implementation:** Reuse `SemesterRegistration` (approved) + course–semester link; optionally CSV export.

### 2. Fee collection summary report (medium impact)

- **Report:** Fee collection by programme, by month, or by academic year (total collected vs expected from fee structure).
- **Use:** Bursar summaries, reconciliation with fee structure.
- **Data:** Sum payments in date range; optionally compare to `FeeStructure` totals × student count.

### 3. Activity log viewer (low–medium impact)

- **Current:** Activity log is written (payments, results, users, announcements) but not viewed in the UI.
- **Add:** Admin (or staff) page to list/filter activity log (user, action, subject, date, IP). Optional: export or “view details” for subject.

### 4. Result locking UI (low impact)

- **Current:** `Result` has `is_locked`; bulk/single update checks it. No UI to lock/unlock.
- **Add:** On result list or bulk page: “Lock results” for a semester (or course+semester) so they cannot be edited; “Unlock” for authorised roles (e.g. admin/account_bursar).

### 5. Announcements: edit & “show until” (low impact)

- **Current:** Announcements have create, list, delete. Optional: add **edit** and ensure “show until” is clearly used in list/dashboard.
- **Enhancement:** Sort dashboard announcements by `show_until` (soonest first) or “no end date” last.

### 6. Semester registration: bulk approve/reject (medium impact for staff)

- **Current:** Registrations are approved/rejected one by one.
- **Add:** On semester registrations index: select semester, filter “pending”, then “Approve all” / “Reject all” (with confirmation). Speeds up start-of-semester workflow.

### 7. Student search / filters (medium impact)

- **Current:** Students list is paginated; filters may be minimal.
- **Add:** Search by reg no, name, email, programme; filter by status, intake year, programme. Same for payments (e.g. by date range, student, programme).

### 8. Graduation / completion list (lower priority, higher effort)

- **Report:** List students who completed all required credits or years (criteria depends on your rules: e.g. all courses passed, total credits, NTA level 6 completed).
- **Requires:** Clear definition of “graduation” and possibly a `required_credits` or similar on programmes/courses.

---

## Code quality & architecture

### 9. Form Request classes (medium impact)

- **Current:** Validation lives in controllers (`$request->validate([...])`).
- **Improvement:** Move to `App\Http\Requests\*Request` (e.g. `StorePaymentRequest`, `UpdateUserRequest`). Cleaner controllers, reusable rules, easier to test.

### 10. Policies for authorization (medium impact)

- **Current:** Authorization is role-based via middleware (`not_student`, `admin`); no per-resource “can edit this result” checks.
- **Add:** Laravel Policies (e.g. `ResultPolicy`, `PaymentPolicy`) and `authorize()` in controllers. Enables “students can only see own data”, “only admin can unlock results”, etc.

### 11. Automated tests (high long-term value)

- **Current:** No `tests/` directory.
- **Add:** Feature tests for critical flows: login, record payment, create/update result, semester CRUD, bulk result entry, export. Even a few tests reduce regressions and document behaviour.

### 12. Activity log: optional queue (low priority)

- **Current:** `ActivityLog::log()` runs synchronously.
- **Optional:** Dispatch a job to write to `activity_log` so heavy traffic does not slow down the main request. Only needed at scale.

---

## UX & UI

### 13. Pagination and “back” state

- **Improvement:** Keep list filters and page number in the URL (you already use `withQueryString()` in some places). Ensure “Back” from show/edit returns to the same list page and filters.

### 14. Confirmation for destructive actions

- **Current:** Delete (announcements, users, etc.) may go straight to POST.
- **Improvement:** Confirm “Are you sure?” (JS confirm or Bootstrap modal) before delete to avoid accidents.

### 15. Success/error messages

- **Improvement:** Ensure all redirects after create/update/delete use `session()->flash('success', ...)` or `flash('error', ...)` and that the layout shows them (e.g. toast or alert at top of page).

### 16. Mobile: key flows

- **Check:** Student “My” pages, payment receipt, and transcript print are usable on small screens (sidebar already has mobile behaviour). Add touch-friendly targets where needed.

### 17. Accessibility (a11y)

- **Improvement:** Ensure forms have correct `<label>` and `aria-*` where needed; sufficient colour contrast; focus order and keyboard navigation for modals and tabs.

---

## Security & compliance

### 18. Password reset by email (medium impact, needs mail config)

- **Current:** Forgot-password is static (“contact Academic Office”).
- **Add:** Laravel password reset (token, email link). Requires real mail driver (SMTP, etc.). High value for self-service.

### 19. Rate limiting

- **Current:** Default Laravel throttling may apply to login only.
- **Review:** Throttle sensitive routes (login, password reset, export, bulk result submit) to reduce brute force and abuse.

### 20. Session timeout and “remember me”

- **Review:** Session lifetime in `config/session.php`; “Remember me” on login. Balance security (shorter timeout) vs convenience for lab/shared PCs.

### 21. HTTPS and secure cookies

- **Production:** Ensure `APP_URL` uses `https`, and `config/session.php` has `secure => true` in production so cookies are not sent over HTTP.

---

## Optional enhancements

### 22. Email notifications (needs mail config)

- Payment receipt (to student email).
- Registration approved/rejected.
- “Results published for Semester X” (e.g. when semester results are locked or a flag is set).

### 23. Dashboard widgets by role (refinement)

- **Current:** Dashboard already has role-aware quick actions and tabs.
- **Refinement:** Hide “Payment Details” tab for students who have no payments; show “Pending registrations” count for students; show “Pending approvals” count for staff.

### 24. Academic calendar: editable events (low impact)

- **Current:** Calendar shows semesters (start/end).
- **Optional:** Small `calendar_events` table (title, date, type: exam_period, fee_deadline, etc.) and CRUD for staff so key dates are in one place.

### 25. Localisation (i18n)

- **Optional:** If the app will be used in multiple languages, wrap UI strings in `__()` and add language files. Start with one extra language (e.g. Swahili) for key screens.

---

## Suggested order (after current feature set)

| Priority | Item | Reason |
|----------|------|--------|
| 1 | Class list report | High practical use for exams and attendance |
| 2 | Activity log viewer | Makes existing audit data usable |
| 3 | Form Requests + Policies | Cleaner code and finer-grained security |
| 4 | Student/search filters | Daily use for staff |
| 5 | Password reset by email | Self-service, fewer office visits |
| 6 | A few feature tests | Prevents regressions, documents behaviour |
| 7 | Fee collection summary | Bursar reporting |
| 8 | Result locking UI | Protects final results |
| 9 | Bulk approve/reject registrations | Saves time at semester start |
| 10 | Confirm destructive actions + UX polish | Fewer mistakes, better UX |

You can mix “quick wins” (e.g. confirm dialogs, one new report) with larger items (tests, policies, email) depending on time and priorities.
