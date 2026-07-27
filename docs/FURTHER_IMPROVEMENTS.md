# Further feature ideas and improvement areas (COHAS)

Suggestions beyond those in [FEATURE_SUGGESTIONS.md](FEATURE_SUGGESTIONS.md). Mix of new features, UX improvements, and technical debt.

---

## New features

### 1. Activity log viewer (admin)

**Current:** Activity log is written (payments, results, users, announcements) but there is no UI to view it.

**Add:** Admin-only page to browse activity log: filter by action, user, date range; paginated table (user, action, subject, description, IP, time). Optional CSV export for audits.

---

### 2. Class list report

**Current:** No report that lists “all students registered for course X in semester Y”.

**Add:** Report: select semester + course → table of registered students (reg_no, name, programme) with optional CSV export. Useful for exam lists, attendance, or printing class lists.

---

### 3. Fee collection summary report

**Current:** Income report is by date range; payment by programme exists. No structured “fee collection summary” by programme, month, or academic year.

**Add:** Report showing total collected per programme (and optionally per month or academic year), compared to expected (from fee structures × student count) to show collection rate or shortfalls.

---

### 4. Graduation / completion list (optional)

**Current:** Students have programmes, results, NTA level; no formal “graduation” or “completed programme” flag.

**Add:** Define completion rules (e.g. all required credits, or years completed). Report or list of students who meet criteria. Optionally add a `status` value like `graduated` or a `graduated_at` column and set it when criteria are met (manually or via a job).

---

### 5. Announcements: edit and “show until”

**Current:** Announcements have create and delete; no edit. `show_until` exists in DB but confirm it’s used in the visible scope and in the create form.

**Add:** Edit announcement (title, body, show_until). Ensure create form includes “Show until” date and that the dashboard/list only shows announcements where `show_until` is null or in the future.

---

### 6. Semester registration: bulk approve / reject

**Current:** Approve/reject one registration at a time.

**Add:** On the semester registrations index, allow selecting multiple pending registrations and “Approve selected” / “Reject selected” with an optional reason, to speed up semester opening.

---

### 7. Result locking: bulk lock by course/semester

**Current:** Results are locked one by one.

**Add:** On bulk result page (or a dedicated “Lock results” page), select semester + course and “Lock all results” for that combination, so staff can close a course in one action.

---

### 8. Payment reversal or refund (with audit)

**Current:** Payments are recorded; no built-in reversal.

**Add:** “Reverse payment” or “Refund” that creates an opposite ledger entry (and optionally a refund payment record), with reason and activity log. Restrict to account/bursar or admin; consider requiring a second approval for large amounts.

---

### 9. Student search and filters

**Current:** Students index is paginated; filters may be limited.

**Add:** Global search by reg_no, name, email, NTA reg no; filters by programme, intake year, status. Same idea for payments (by date range, student, amount range) and results (by semester, course, grade).

---

### 10. Dashboard widgets and KPIs

**Current:** Dashboard already has role-based content and quick actions.

**Add:** Optional KPIs: e.g. “Registration rate this semester”, “Arrears total”, “Payments this week vs last week”. Configurable or role-based widgets so bursar sees finance KPIs, academics see registration/result stats.

---

## UX and accessibility

- **Keyboard navigation:** Ensure all main flows (login, dashboard, forms) are usable with keyboard and that focus order and visible focus styles are clear.
- **Screen readers:** Add or review ARIA labels on icons-only buttons, nav, and tables; ensure form labels are associated and errors are announced.
- **Loading and feedback:** For slow actions (bulk result save, export), show a spinner or “Processing…” and disable double submit.
- **Empty states:** Every list (students, payments, results, announcements, etc.) should have a clear empty state with a primary action (e.g. “Add first student”) where relevant.
- **Confirmation for destructive actions:** Delete (announcements, semesters when allowed), end allocation, lock results, payment reversal: confirm with a modal or dedicated confirmation page and state what will happen.

---

## Security and robustness

- **Rate limiting:** Throttle login and sensitive endpoints (e.g. password change, export) to reduce brute force and abuse.
- **Authorization checks:** Consistently use policy or middleware so that students cannot hit staff routes and staff cannot access admin-only actions; ensure “show” routes (e.g. student profile, ledger) check that the current user is allowed to see that resource.
- **Input validation:** Keep using validated arrays for all user input; for CSV/imports, validate row limits and column types to avoid memory or injection issues.
- **Sensitive data in logs:** Avoid logging full payment amounts or PII in application logs; activity log table is for audit and access should be restricted.

---

## Code and maintainability

- **Form requests:** Extract validation from controllers into `FormRequest` classes (e.g. `StorePaymentRequest`, `StoreResultRequest`) for reuse and clearer controllers.
- **Policies:** Use Laravel policies for Student, Payment, Result, SemesterRegistration, etc., and `authorize()` in controllers so permission logic is in one place.
- **Tests:** Add PHPUnit feature tests for critical flows (login, record payment, add result, approve registration, export) and unit tests for grade computation and fee/balance logic so refactors are safe.
- **API (optional):** If you ever need mobile apps or external integrations, add a small API (e.g. `api/` routes, token or Sanctum) for read-only or scoped actions (e.g. “my results”, “my fees”) and keep web UI as the main interface.

---

## Data and reporting

- **Scheduled reports:** Use Laravel scheduler to generate daily/weekly summary emails or stored reports (e.g. “arrears list”, “payments today”) for bursar or admin.
- **Backup reminder:** Document or automate DB backup (e.g. cron + `mysqldump` or Laravel backup package); admin export is for CSV, not a full backup.
- **Academic year in exports:** Where relevant, include academic_year or semester in CSV exports so files are self-explanatory (e.g. “payment_by_programme_2025.csv”).

---

## Optional “nice to have”

- **Announcement “Mark as read”:** Store read status per user so students can hide or collapse read announcements.
- **Calendar events:** Extend the academic calendar with a simple `calendar_events` table (title, date, type, optional semester_id) for exam periods, fee deadlines, holidays.
- **Two-factor authentication (2FA):** For admin or bursar accounts, add TOTP (e.g. Laravel Fortify or a package) to reduce risk of account takeover.
- **Dark mode:** If desired, a CSS theme toggle (e.g. `data-theme="dark"`) for the app layout without changing the rest of the stack.

---

## Suggested order (by impact vs effort)

| Priority | Item | Reason |
|----------|------|--------|
| 1 | Activity log viewer | Makes existing audit data usable |
| 2 | Class list report | High demand for exam/attendance |
| 3 | Student/search filters | Used daily by staff |
| 4 | Form requests + policies | Improves security and maintainability |
| 5 | Fee collection summary | Better financial oversight |
| 6 | Bulk approve/reject registrations | Saves time at semester start |
| 7 | Payment reversal + audit | Completes finance workflow |
| 8 | Basic tests for payments/results | Safer changes and deployments |
| 9 | Confirmations for destructive actions | Fewer mistakes |
| 10 | Rate limiting on login/export | Low effort, good security gain |

You can pick a few “quick wins” (e.g. activity log viewer, class list, filters) and then tackle one larger item (e.g. payment reversal or tests) as capacity allows.
