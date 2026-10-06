# Atzaan (اتزان)

Atzaan is an Arabic (RTL) web platform for nutrition and chronic-condition follow-up. It connects
**patients** with **doctors**. Patients log meals (with AI-assisted calorie analysis), weight and
daily tasks. Doctors set calorie goals, review meals and assign tasks. **Admins** moderate doctors,
content and support conversations.

> Screenshots: _placeholder_
>
> | Patient home | Doctor dashboard | Admin dashboard |
> |---|---|---|
> | ![patient](docs/screenshots/patient-home.png) | ![doctor](docs/screenshots/doctor-dashboard.png) | ![admin](docs/screenshots/admin-dashboard.png) |

---

## Features by role

### Visitor
- Landing page, public articles, terms/privacy pages
- Quiz that recommends a care path / specialty (`/quiz`)
- Contact form (guest conversations land in the admin inbox)
- Doctor application form (`/join-doctor`) with licence and CV upload
- Register / login (email + password, optional Google and Facebook)

### Patient (`/patient/...`)
- Health profile completion (weight, height, target weight, conditions, goal)
- Home dashboard: today's tasks, weight card, next appointment, recommended articles
- Calories: log meals by text or photo, analysed by AI through OpenRouter; daily goal tracking
- Journey: personal daily tasks, plus tasks assigned by the doctor
- Recommended doctors, selecting a doctor, doctor details and reviews
- Follow-up appointments with the selected doctor (request / respond to reschedule)
- Messages with the doctor, support chat with the admin team
- AI chat assistant (OpenRouter)
- In-app notifications

### Doctor (`/doctor/...`)
- Dashboard with items needing attention
- Patient follow-up requests (approve / reject / complete)
- Patient list and patient detail: calorie trend, meals, weight log, tasks
- Set daily calorie goals, log weight, assign tasks
- Plans overview (calorie goal, recent meals, open task, last weight, upcoming appointment)
- Meal reviews with notes, alerts (no meals logged, overdue tasks), reports
- Appointments (confirm / reject / suggest another time, meeting link)
- Messages, articles (submitted for admin review), profile, settings, notifications

### Admin (`/admin/...`)
- Dashboard KPIs
- Users (filter, suspend / activate)
- Doctor applications: review, approve or reject; licence/CV viewer (admin-only, private storage)
- Articles (with an optional AI draft generator), article categories, medical specialties
- Messages (patient support and guest contact), notifications, site settings (including maintenance mode)

## Tech stack

Only what the code actually uses:

| Layer | Used |
|---|---|
| Backend | PHP 8.2+, **Laravel 12** |
| Views | Blade, **Bootstrap 5 RTL** (CDN), plain CSS and JS in `public/front/` |
| Charts / icons | **Chart.js** (CDN), Font Awesome, Bootstrap Icons, Lucide (CDN) |
| AI | **OpenRouter** through **Prism** (`prism-php/prism`) for chat, meal analysis and article drafts |
| Auth extras | Laravel Socialite (Google, Facebook) |
| Database | **SQLite** (default in `.env.example`, used by tests) or **MySQL** |

There is no front-end build step. `package.json` still has Laravel's default Vite/Tailwind
scaffolding, but no view loads it, so you don't need `npm install`.

## Setup (fresh clone)

Requirements: PHP 8.2+ with the `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `fileinfo` and
`openssl` extensions, and Composer 2.

```bash
git clone https://github.com/jumanadahdooh-dev/etzan-e.git atzaan
cd atzaan
composer install
cp .env.example .env
php artisan key:generate
```

**Database.** The default is SQLite. Create the file, then migrate and seed:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

To use MySQL instead, create an empty database and set these values in `.env` before migrating:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atzaan
DB_USERNAME=root
DB_PASSWORD=
```

**Public storage link.** Needed for profile photos and article covers:

```bash
php artisan storage:link
```

**AI (optional).** Without a key the app still works. The AI chat, meal photo analysis and article
drafts show a "not configured" message instead. To enable them, add your key to `.env`:

```dotenv
OPENROUTER_API_KEY=sk-or-...
# optional, these are the defaults used when empty:
OPENROUTER_MODEL=openai/gpt-oss-20b:free
OPENROUTER_VISION_MODEL=google/gemma-4-26b-a4b-it:free
```

**Run:**

```bash
php artisan serve
```

Open http://127.0.0.1:8000.

Appointment reminders, task notifications and calorie alerts are scheduled commands. In development
you can run them with:

```bash
php artisan schedule:work
```

### Optional reference data

A fresh database has no specialties, article categories or quiz rules. Add specialties and
categories from the admin panel. Load the quiz recommendation rules with:

```bash
php artisan db:seed --class=QuizRecommendationRuleSeeder
```

## Default accounts

`php artisan migrate --seed` creates **one** account:

| Role | Email | Password |
|---|---|---|
| Patient (no health profile yet) | `test@example.com` | `password` |

The seeder does **not** create an admin. Create one with:

```bash
php artisan tinker --execute="App\Models\User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('change-me'), 'role' => 'admin', 'email_verified_at' => now()]);"
```

Doctors normally join through `/join-doctor`, and an admin approves the application. That flow
creates the doctor account and sends a password-setup link by mail; with the default
`MAIL_MAILER=log`, the link is written to `storage/logs/laravel.log`.

For linked demo data (5 doctors, 15 patients with appointments, tasks and articles), run the seeder
interactively and answer **yes** to the prompt. All demo users have the password `password` and
random emails. You can list them with
`php artisan tinker --execute="App\Models\User::pluck('role', 'email')"`.

```bash
php artisan db:seed
```

## Running tests

Tests use an in-memory SQLite database and a fake OpenRouter key (see `phpunit.xml`), so they
need no `.env` changes and never call the real AI API.

```bash
php artisan test
```

### Moving old doctor files to private storage

Doctor licences and CVs are stored on the private disk (`storage/app/private`) and served only
through admin routes. Files uploaded before this change may still sit on the public disk. The admin
viewer still finds them there, but they stay reachable through `/storage/...` until you move them:

```bash
php artisan doctor-applications:make-files-private --dry-run
php artisan doctor-applications:make-files-private
```

## Known limitations

- **No admin or doctor in the default seed.** See "Default accounts" above. Demo data needs the
  interactive prompt.
- **No reference data seeded.** Specialties, article categories and quiz rules start empty, so the
  doctor application form has no specialties until an admin adds some.
- **Admin KPI "pending doctor-link requests" always shows 0.** It counts the
  `patient_doctor_requests` table, but patient-to-doctor requests are stored in
  `patient_profiles.doctor_request_status`.
- **Unused tables.** `patient_doctor_requests`, `doctor_patient_notes` and
  `patient_support_messages` exist in the schema but no code writes to them.
- **Recurring tasks are not supported.** Every task is a single occurrence.
- **No front-end build or asset pipeline.** CSS and JS are hand-written files under `public/front/`.
  Several libraries load from CDNs, so the UI needs internet access.
- **The missing article fallback image** (`public/front/image/articles/article-1.jpg`) is
  referenced for articles without a cover but is not in the repository.
- The UI is Arabic only.
