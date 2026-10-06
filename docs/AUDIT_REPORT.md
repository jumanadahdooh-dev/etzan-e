# Audit fix report: branch `fix/audit-issues`

Date: 2026-10-06. Each problem was reproduced first, then fixed, then re-run. Each change has its own commit.

## Summary

| # | Problem | Status |
|---|---|---|
| 1 | Broken migration | ✅ Fixed |
| 2 | Missing front-end assets | ⚠️ Audit was wrong (all assets tracked). Two dead references fixed |
| 3a | `/admin/article-categories` 500 | ✅ Fixed |
| 3b | `/doctor/notifications` filename case | ✅ Fixed |
| 3c | Carbon 3 `diffInDays()` float | ✅ Fixed. It did not crash on Carbon 3.11, but it produced wrong data |
| 3d | Plans page queried a non-existent `appointments` table | ✅ Fixed |
| 4 | Failing tests | ✅ Fixed: 136/136 passing |
| 5 | Dead or placeholder code | ✅ Reported. Safe ones fixed |
| 6 | Login throttling and private doctor files | ✅ Fixed |
| 7 | README | ✅ Written and followed on a clean clone |

---

## 1. Broken migration: fixed

**What was wrong:** `2026_08_02_145211_create_patient_doctors_table.php` contained a stale copy of `PatientAppointmentController`, not a migration.

```
$ php artisan migrate:fresh   (empty SQLite)
Whoops\Run::handleError("Cannot declare class App\Http\Controllers\Patient\PatientAppointmentController, because the name is already in use", ...create_patient_doctors_table.php)
```

**Decision:** I deleted the file and the `PatientDoctor` model.
- `migrate:status` showed this migration as **Pending**, so it never ran anywhere.
- Nothing reads or writes `patient_doctors`. The only reference to `PatientDoctor` was its own file.
- The model also referenced `App\Models\Patient`, which does not exist.

**Files:** deleted `database/migrations/2026_08_02_145211_create_patient_doctors_table.php` and `app/Models/PatientDoctor.php`.

**Proof:** `php artisan migrate:fresh` on an empty SQLite database ran all 64 migrations with `DONE`, exit 0.

## 2. Front-end assets: the audit claim does not reproduce

- `git ls-files public/front | wc -l` gives **75**, and `find public/front -type f | wc -l` also gives **75**. Everything is tracked, including `calories.js`, `ai-chat.js` and `patient-journey.js`.
- `.gitignore` excludes only `/public/build` and `/public/storage`. `git status --ignored public` shows nothing else.
- A scan of every `asset(...)` and `front/...` path in `resources/views` and `app` found these missing files. None of them has ever existed in git history (`git log --all` is empty for them):
  - `front/css/patient/etzan-patient-premium.css`: linked from `layouts/patient` on every patient page, giving a 404 each time. **Removed the dead link.**
  - `front/css/doctor/doctor-dashboard.css`: used only as the `?v=` cache-buster for `dashboard.css`, so the version was always `1`. **Pointed it at the real file.**
  - `front/css/admin-messages.css`: referenced only by `admin/admin-messages-show.blade.php`, which no route or controller uses. Reported, not changed.
  - `front/image/articles/article-1.jpg`: the fallback cover in `Article::getCoverImageUrlAttribute`. Reported, not changed (it needs a real image).
- A case-sensitive re-check against `git ls-files` found no case mismatches in asset paths.

**Files:** `resources/views/layouts/patient.blade.php`, `resources/views/doctor/dashboard.blade.php`.

## 3. Pages returning HTTP 500

### a) `/admin/article-categories`: fixed
- **Reproduced:** `InvalidArgumentException: View [admin.article-categories.index] not found` → 500.
- **Added:** `admin/article-categories/index.blade.php` (cards, create/edit/delete modals, active toggle, sort order, article count), `edit.blade.php` and a shared `partials/fields.blade.php`. They follow the Specialties page pattern and reuse its CSS. Delete is disabled for categories that still have articles, matching the controller's guard.
- **Also added** a sidebar link under "المحتوى". Without it, the page could only be reached by typing the URL.

### b) `/doctor/notifications`: fixed
- `git mv resources/views/doctor/Notifications.blade.php → notifications.blade.php`. It worked on Windows but returned 500 on Linux, which is case-sensitive.
- A case-sensitive scan of all 73 literal view names found **no other mismatches**.

### c) Carbon 3 `diffInDays()`: fixed (it did not crash, but it produced wrong data)
- With Carbon 3.11.1, `subDays(1.25)` is accepted, so the page **did not 500** on any of the 7 dates tested.
- It was still wrong. With a fractional offset, the calorie trend could skip today: the "yesterday late" case failed the "last point is today" assertion before the fix.
- **The same pattern was in `DoctorAlertsController`.** Alert titles read "ما سجّل وجبات من 10.6 يوم".
- **Fix:** both now use `(int) $start->copy()->startOfDay()->diffInDays(today())`. The existing alerts test now asserts the exact `من 10 يوم` text, and it fails on the old code.
- `SendAppointmentReminders` already casts to `(int)`.

### d) Plans "upcoming appointment": fixed
- The query targeted `appointments` behind a `Schema::hasTable()` guard, so it never crashed, but the column was **always empty**.
- It now reads `patient_appointments` for future `pending`/`confirmed` appointments, filtered to this doctor's profile.
- The test confirms that a rejected appointment and another doctor's appointment are excluded.

**Tests:** `tests/Feature/AuditPagesTest.php`. Before the fix: 2 failed (the 3c label and 3d null). After: all pass.

## 4. Failing tests: fixed

- **AiChatArabicMessageTest:** reproduced with a fresh-clone-style env (empty key). It received the "not configured" message instead of the faked reply. I added `<env name="OPENROUTER_API_KEY" value="test-fake-openrouter-key" force="true"/>` to `phpunit.xml`. Production code was not touched, and `force` also guarantees tests never use a real key.
- **DoctorRealPagesTest (plans):** the view was deliberately redesigned in `81a93ff`: the heading 'بدون خطة سعرات' became 'مرضى بحاجة لخطة سعرات' and the behaviour stayed the same. **The view is correct.** I updated the test, and it now also asserts the `patientsWithoutPlan` data.

**Proof:**
```
before:  Tests: 1 failed, 109 passed
after:   Tests: 136 passed (431 assertions)
```

## 5. Dead or placeholder code

**Unused tables and models (report only, nothing dropped):**

| Table / model | Reads | Writes |
|---|---|---|
| `patient_doctor_requests` / `PatientDoctorRequest` | Admin dashboard KPI "طلبات ربط طبيب معلقة" (**always 0**); unused relations on `User` and `DoctorProfile` | none |
| `doctor_patient_notes` | none | none |
| `patient_support_messages` | none | none |
| `PatientDoctor` | removed in #1 | none |

Real patient-to-doctor requests live in `patient_profiles.doctor_request_status`.

**Fake fallbacks:** fixed, in `PatientContextHelpers`.
- `fallbackRecommendedDoctors` (3 fake doctors), `fallbackTasks` (2 fake tasks) and `fallbackArticles` (placeholder article) are gone. They now return `[]`, so the views' existing empty states show.
- The doctors empty-state copy was written for admins ("أضيفي الأطباء من لوحة الإدارة") and is now written for patients: "لا يوجد أطباء بعد".

**Weight progress:** now calculated as `(start − current) / (start − target)`, clamped 0–100.
- Start is the first log, current is the latest log, and both fall back to the profile weight. Target is `target_weight_kg`.
- It works for both loss and gain goals.
- Note: `homeStats` is passed to the view but **never rendered**, so users never saw the 65%.

**Task repeat:** **removed from the form.** `repeat_type` was saved but never read, and completion is one status per row. Implementing repeats needs per-occurrence state, which is a new feature. The column stays, and new tasks are stored as `once`.

**Tests:** `PatientEmptyStatesTest` (9 cases, all fail on the old code) and `PatientTaskRepeatOptionTest`.

## 6. Security

### Login throttling: fixed
- Named limiter `login`: 5 per minute per email+IP, and 20 per minute per IP, applied to `POST /login`.
- Throttled requests get an Arabic `errors/429.blade.php`.
- `LoginThrottleTest`: the 6th attempt returns **429**; the correct password is also blocked until the minute passes; the 21st attempt from one IP across different emails returns **429**.
- Without the middleware, both tests fail with `Expected 429 but received 302`.

### Doctor licence and CV: fixed
- New uploads go to the private `local` disk (`storage/app/private`). The profile photo stays public because it becomes the doctor's public avatar.
- The admin preview, download and viewer look on the private disk first, then fall back to public, so old records still resolve.
- New command: `php artisan doctor-applications:make-files-private [--dry-run]`. Paths are unchanged, so there's no DB update, and the public copy is deleted only after the private write succeeds.

**Live HTTP proof** (`php artisan serve`):
```
GET /storage/doctor-applications/cv-files/3IuK...pdf          -> 200 application/pdf   (legacy file, no login)
GET /storage/doctor-applications/license-files/zz-probe.pdf   -> 403                   (file on private disk)
```

**Tests:** `DoctorApplicationPrivateFilesTest` covers private storage, the direct URL, admin preview/download/viewer returning 200, guest/doctor/patient blocked, the legacy fallback, and command dry-run, move and idempotency.

## 7. README: fixed

Covers what the project is, features by role, the stack (Laravel 12, Bootstrap 5 RTL, Chart.js, OpenRouter via Prism, SQLite/MySQL; no Vite or Tailwind), fresh-clone setup, the seeded account, how to create an admin, tests, the private-files command and an honest known-limitations section. Screenshot placeholders are included.

---

## Final verification (clean clone)

Clean clone of `fix/audit-issues` into `C:\xampp-dash\htdocs\atzaan-clean-verify`, following the README.

**One deviation:** `composer install` failed three times on network drops while downloading `laravel/pint` (`curl 18 transfer closed`). I copied `vendor/` from the working copy after checking that `composer.lock` is identical (`cmp`) and that `composer install --dry-run` says "Nothing to install". `composer install` then completed.

```
cp .env.example .env && php artisan key:generate      OK
touch database/database.sqlite
php artisan migrate --seed                             exit=0 (64 migrations)
php artisan migrate:fresh --seed                       exit=0
php artisan storage:link                               OK
php artisan test  (OPENROUTER_API_KEY empty)           Tests: 136 passed (431 assertions)
route:list check                                       157 controller routes, 0 missing class/method
view name check                                        73 names, 0 missing / case mismatches
```

**Pages:** 58 parameter-free GET routes × 4 roles = 232 real HTTP requests after logging in through `/login`.
- **0** responses with 5xx, 404 or 419.
- Every admin, doctor and patient page returns **200** to its own role.
- Expected redirects: `/patient` → `/patient/home`, `/patient/journey/tasks` → `/patient/journey`, `/admin/articles/create` → `/admin/articles` (create is a modal), `/article-details` → `/articles`, and OAuth redirects to Google/Facebook.
- 403 appears only for cross-role access, and for `/forgot-password/direct-setup`, which requires a signed URL.
- The OAuth callback routes were excluded because they need the provider's response.

| Section | admin | doctor | patient | guest |
|---|---|---|---|---|
| `/admin/*` (13) | 200 (create → 302) | 403 | 403 | 302 → login |
| `/doctor/*` (16) | 403 | 200 | 403 | 302 → login |
| `/patient/*` (15) | 403 | 403 | 200 / 302 | 302 → login |
| Public (`/`, `/articles`, `/quiz`, `/contact`, `/join-doctor`, `/login`, `/register`, `/terms`, `/privacy`, `/forgot-password`) | 200 or 302 | 200 or 302 | 200 or 302 | 200 |

---

## Needs your decision
1. **33 legacy licence and CV files** in the local `storage/app/public` are publicly reachable today (proven above with a 200). Run `php artisan doctor-applications:make-files-private` (only a dry run has been done). Run it on any server too.
2. **Seeder:** it creates only `test@example.com` (a patient with no profile). It creates no admin, doctor, specialties, article categories or quiz rules, and `QuizRecommendationRuleSeeder` is never called. Should it seed demo accounts and reference data?
3. Should the admin "pending doctor-link requests" KPI count `patient_profiles.doctor_request_status = 'pending'`?

## Noticed but not changed
- `POST /patient/journey/tasks` without `reminder_minutes` returns 500 (NOT NULL column). The real form always sends it.
- `admin/admin-messages-show.blade.php` is an orphan view.
- `article-1.jpg` (the fallback cover) is missing from the repo.
- `PatientContextHelpers::todayTasks()` is never called. Several methods at the end of the trait are mis-indented.
- `package.json` has unused Vite/Tailwind scaffolding.
- `PatientSplitControllersSmokeTest` still uses doc-comment `@dataProvider`, which PHPUnit 12 will drop.
- `/register` has no rate limit (only `/login` was in scope).
