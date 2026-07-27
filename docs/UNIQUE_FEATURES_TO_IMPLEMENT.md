# Unique features to implement (COHAS)

Ideas that would make COHAS stand out or address common institutional needs. Prioritise by your context (TVET, NACTVET reporting, multi-campus, etc.).

---

## 1. **SMS notifications** (high impact, Tanzania-friendly)

- **Payment reminder:** Auto or manual SMS to students in arrears (e.g. “Balance: X TZS. Pay before [date]”).
- **Registration approved/rejected:** Notify student when semester registration is approved or rejected.
- **Results published:** “Results for Semester X are now available. Log in to view.”
- **Implementation:** Use a gateway (e.g. Africa’s Talking, Hubtel, or local provider). Store `phone` on students; add a simple “Send SMS” action or scheduled job. Log sent messages.

---

## 2. **Parent/guardian contact and optional alerts**

- **Student profile:** Add guardian name, phone, relationship (optional).
- **Optional:** Copy guardian on critical SMS (e.g. arrears, discipline) or allow staff to “Notify guardian” from student profile.
- **Use case:** Improves follow-up on fees and absenteeism.

---

## 3. **Fee payment plans / commitment tracking**

- **Student-level:** “Pledge to complete tuition by [date]” stored (you already have `tuition_completion_pledge_date`).
- **Extend:** List of instalment dates/amounts per student (e.g. 30% by date1, 70% by date2). Report: “Students who missed pledged date” or “Instalment compliance.”
- **Links to:** Admission control sheet “When will complete tuition?” and commitment letters.

---

## 4. **Graduation clearance checklist**

- **Checklist per student:** Library clearance, finance (no arrears), accommodation (room key returned), academic (all results in). Staff mark each item done.
- **Report:** “Cleared for graduation” list. Block or flag graduation until cleared.
- **Unique:** Common in universities; less common in college systems; reduces disputes.

---

## 5. **NACTVET-compliant export**

- **Export in exact format** required by NACTVET for submissions (e.g. student register, results, finance) so you don’t re-type or reshape data.
- **Implementation:** Get the official template/CSV format from NACTVET; add a report “Export for NACTVET” that maps your DB columns to their columns.

---

## 6. **Attendance (course/session)**

- **Per course + semester:** Mark present/absent for each student (from class list) per date or per “session.”
- **Reports:** Attendance rate per student, per course; list of chronic absentees. Optional: link attendance to “allowed to sit exam.”
- **Implementation:** `attendance` table (student_id, course_id, semester_id, date, status); simple “Take attendance” page from class list + date.

---

## 7. **Timetable / schedule**

- **Define:** Course X in room Y on days/times for semester Z. Optional: link to staff (who teaches).
- **Views:** Student “My timetable”; staff “My classes”; public “Semester timetable” (PDF or print).
- **Use case:** Reduces “when is the class?” queries; supports room conflict checks.

---

## 8. **Document uploads (student)**

- **Store:** ID copy, photo, signed joining instructions, etc. per student (file storage + link on student profile).
- **Optional:** “Document checklist” (e.g. ID, birth cert, photos) and report “Students with incomplete documents.”
- **Implementation:** `student_documents` table; store path or use Laravel storage; restrict access by role.

---

## 9. **Leave of absence**

- **Student applies** for leave (from–to, reason). Staff approve/reject.
- **Effect:** Optional “on leave” status or flag so they’re excluded from active lists (e.g. fee reminders, class list) during leave.
- **Report:** “Students on leave” and return dates.

---

## 10. **Academic standing / progression rules**

- **Define:** e.g. “Pass if average ≥ X” or “Repeat year if failed courses ≥ Y.” Compute per student per semester.
- **Status:** Good standing, probation, repeat year. Show on student profile and transcript.
- **Report:** “Students on probation” or “Eligible to progress.”

---

## 11. **Bulk SMS/email to selected students**

- **Staff:** Select students by programme, intake, or “from report” (e.g. arrears list). Compose one message; send to all (SMS or email).
- **Use case:** “Fee reminder to all Level 2” or “Exam dates to all registered for course X.”
- **Implementation:** Reuse SMS/email infrastructure; queue for large lists.

---

## 12. **Dashboard charts (visual)**

- **Enrollment trend** (bar/line by year or programme).
- **Payment trend** (monthly collection).
- **Registration status** (pie: pending/approved/rejected).
- **Implementation:** Simple (e.g. Chart.js) with data from existing reports; no new backend logic.

---

## 13. **Exam timetable**

- **Per semester:** Exam date/time/room per course (or per course+slot). Optional: “Sitting” (which group of students).
- **Reports:** “My exams” (student); “Exam timetable” (print/PDF); “Room usage.”
- **Links to:** Class list (who sits which exam).

---

## 14. **Discipline / conduct**

- **Record:** Warnings, sanctions (e.g. suspension, fine) per student with date, reason, entered by.
- **Report:** “Discipline record” per student; “Students with active sanctions.” Optional: block registration until sanction cleared.

---

## 15. **Receipt / document generation (PDF)**

- **Payment receipt as PDF** (not only print view) for download and print.
- **Offer letter / joining instructions** (template with student data).
- **Implementation:** DomPDF or similar; template views with student/payment data.

---

## Suggested order (by uniqueness + value)

| Priority | Feature                    | Why unique / valuable                          |
|----------|----------------------------|-------------------------------------------------|
| 1        | SMS notifications          | High impact in Tanzania; few college systems do it well |
| 2        | NACTVET-compliant export   | Saves time and errors on official submissions  |
| 3        | Guardian contact + alerts | Improves fee and discipline follow-up          |
| 4        | Graduation clearance       | Clear process, fewer disputes                   |
| 5        | Attendance                 | Supports quality and “allowed to sit exam”      |
| 6        | Timetable                  | Daily use; “My timetable” is a strong student feature |
| 7        | Payment plans / instalments| Matches how many students actually pay         |
| 8        | Dashboard charts           | Quick wins; good for management                 |
| 9        | Leave of absence           | Clean handling of returning students            |
| 10       | PDF receipts / documents   | Professional and auditable                      |

---

You can mix one “quick win” (e.g. dashboard charts, PDF receipt) with one larger item (SMS or NACTVET export) depending on time and budget.
