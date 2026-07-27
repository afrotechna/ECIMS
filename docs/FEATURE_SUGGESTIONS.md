# Feature suggestions for COHAS

Ideas for new or improved features, based on the current system. Prioritise by your needs and capacity.

---

## 1. Semester / academic year management (high impact)

**Current:** Semesters exist in the database and are created via `SemesterSeeder` (e.g. "Semester 1", "Semester 2" for current year). There is no UI to add or edit semesters.

**Add:**
- **Semesters CRUD** under Academics: list semesters, add new (name, academic year, number, start/end dates, active), edit, optionally deactivate.
- Lets staff create future semesters (e.g. 2026/2027) and control which are “active” for registration and results.

---

## 2. End or edit accommodation allocation (medium impact)

**Current:** Accommodation allocations have `from_date`, `to_date`, and `status` (active/ended), but the app only has **index, create, store**. Staff cannot end an allocation or set `to_date` from the UI.

**Add:**
- On the allocations list: action **“End allocation”** (set `to_date` = today, `status` = ended).
- Optionally: **Edit allocation** (change room, from/to dates) for corrections.

---

## 3. Bulk result entry (high impact for staff)

**Current:** Results are entered one-by-one (student + course + semester + CA/SE).

**Add:**
- **Bulk entry by course/semester:** Choose semester and course, then a table or form listing all registered students for that course with CA/SE fields; submit once to save/update many results.
- Speeds up exam period data entry.

---

## 4. Transcript print / PDF (medium impact)

**Current:** Transcript is viewable on screen.

**Add:**
- **Print-friendly transcript** (e.g. dedicated print view or route that opens in a new window with minimal chrome).
- Optional **PDF export** (e.g. via browser print-to-PDF or a package like DomPDF / Snappy) for official copies.

---

## 5. Notifications or announcements (medium impact)

**Current:** No in-app notices for students or staff.

**Add:**
- Simple **announcements** module: staff create short notices (title, body, optional “until” date); students (and staff) see them on dashboard or a dedicated page.
- Optional: “Mark as read” and show only unread or latest N.

---

## 6. Email notifications (medium impact, needs mail config)

**Current:** `.env` has `MAIL_MAILER=log`; forgot-password page is static (“contact Academic Office”).

**Add:**
- **Password reset by email:** Use Laravel’s password reset (token, email link) so users can reset without going to the office.
- Optional emails: payment receipt, registration approved/rejected, “results published for Semester X”.

Requires configuring a real mail driver (SMTP, etc.) and possibly queue for sending.

---

## 7. Role-based dashboard (medium impact)

**Current:** Dashboard shows same blocks for everyone (with some role badge); logic already uses `$userRole`.

**Add:**
- **Student:** Emphasis on My Fees balance, My Results, My Registrations (semester registration status), My Accommodation.
- **Account / Bursar:** Emphasis on today’s payments, arrears summary, quick link to payments and reports.
- **Tutor / staff:** Emphasis on semester registrations, results entry, courses.
- **Admin:** Add user management link, system stats (e.g. user count, last login), or other admin shortcuts.

---

## 8. Student “My semester registration” and “My accommodation” (medium impact)

**Current:** Students have My Profile, My Fees, My Results. Semester registration and accommodation are staff-facing.

**Add:**
- **My registrations:** Page listing the student’s semester registrations (semester, status: pending/approved/rejected, date).
- **My accommodation:** Page showing current allocation (room, hostel, from/to, status) if any.

Increases transparency and reduces “where do I stay?” / “is my registration approved?” queries.

---

## 9. More reports (low–medium impact)

**Current:** Enrollment, arrears, income (with CSV export).

**Add:**
- **Payment by programme** (and optionally by semester/academic year).
- **Class list:** Students registered for a given course + semester (for exam lists or attendance).
- **Graduation / completion list:** Students who completed all required credits or years (criteria depends on your rules).
- **Fee collection summary:** By programme, by month, or by academic year.

---

## 10. Audit / activity log (low–medium impact)

**Current:** No built-in record of who did what.

**Add:**
- **Activity log:** Table storing user_id, action (e.g. “payment.created”, “result.updated”), model type/id, optional old/new values, IP, timestamp.
- Use for accountability (finance, result changes, user creation). Can be implemented with a simple `Activity` model and middleware or model observers.

---

## 11. Academic calendar (low impact)

**Current:** Semester start/end are in the DB but not shown as a calendar.

**Add:**
- **Simple academic calendar page:** List of key dates (e.g. semester start/end, exam period, fee deadline) from semesters and optionally from a small “calendar_events” table. Read-only for students; staff or admin maintain dates.

---

## 12. Data backup / export (low impact, high value for admin)

**Current:** No built-in backup or bulk export.

**Add:**
- **Admin-only “Export data”:** Download CSV (or ZIP of CSVs) of students, payments, results, programmes (e.g. for the current or selected academic year). Helps with local backup and reporting outside the system.

---

## 13. Dashboard and UI polish (low effort)

**Current:** Dashboard tabs still use Bootstrap primary blue.

**Add:**
- Use **landing blue** for dashboard tab active state (same as rest of the app).
- Add **quick action** buttons or cards (e.g. “Record payment”, “Enter results”, “New student”) for staff roles.

---

## Suggested order to implement

| Priority | Feature                               | Reason |
|----------|----------------------------------------|--------|
| 1        | Semester management                    | Needed to run future years and control registration/results |
| 2        | Bulk result entry                      | Saves a lot of time for staff |
| 3        | End accommodation allocation           | Completes the allocation workflow |
| 4        | Transcript print / PDF                 | Often required for official use |
| 5        | Role-based dashboard + student “My” pages | Better UX and clarity |
| 6        | Notifications / announcements          | Keeps students and staff informed |
| 7        | Email (password reset, receipts)       | Improves self-service and communication |
| 8        | More reports + audit log + backup      | Better reporting and accountability |

You can mix “quick wins” (e.g. end allocation, dashboard colour, transcript print) with larger items (semesters, bulk results, emails) depending on time and priorities.
