# SignWise ASL

SignWise ASL is a small web app for learning and practising American Sign Language (ASL). Learners can browse beginner lessons with written sign references and practice tips, use an optional camera preview while practising, take a short quiz, and save lesson and quiz progress to an account.

The camera is a local preview for practice; the app does not automatically recognize or grade signs.

## What You Can Do

- Browse a catalog of short lessons grouped by topic.
- Read a sign reference and practice tip for each lesson.
- Create an account or sign in to save progress.
- Mark lessons as practised and review completion and quiz results on the dashboard.
- Take a five-question quiz based on the lesson catalog.
- Try the optional camera preview from the home page.

## Requirements

- PHP 8.3 or newer with the SQLite PDO extension enabled.
- Composer.
- Node.js and npm.

## Run Locally (Windows PowerShell)

Run these commands from the project root, the folder containing `artisan` and `composer.json`.

1. Install PHP dependencies:

   ```powershell
   composer install
   ```

2. Install JavaScript dependencies:

   ```powershell
   npm install
   ```

3. Create your local environment file and application key:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   If `.env` already exists, keep it and do not overwrite it with the example file.

4. Create the SQLite database file if it does not already exist, then create the tables and load sample data:

   ```powershell
   if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
   php artisan migrate --seed
   ```

   The default configuration uses SQLite, so no separate database server is needed.

5. Build the front-end assets:

   ```powershell
   npm run build
   ```

6. Start the app, queue worker, and front-end development server:

   ```powershell
   composer run dev
   ```

   Open [http://localhost:8000](http://localhost:8000). Leave the command running while using the app. Stop it with `Ctrl+C`.

## Database

SQLite is the default database. The local database is `database/database.sqlite`; it is created by the setup step above and should not be committed. Database connection settings are read from `.env`.

The migrations create these main application tables:

| Table | Purpose |
| --- | --- |
| `users` | Learner accounts and authentication details. |
| `lessons` | Lesson titles, categories, difficulty, estimated duration, sign references, and practice tips. |
| `user_progress` | Per-user lesson completion, quiz score, and last-practised time. Each user has at most one progress record per lesson. |

Laravel also uses supporting tables for sessions, password resets, cache, and queued jobs in the default local configuration.

To use MySQL instead, create a database first, set `DB_CONNECTION=mysql` and the appropriate `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` values in `.env`, then run:

```powershell
php artisan migrate --seed
```

Make sure PHP has the MySQL PDO extension enabled when using MySQL.

### Sample Data and Demo Account

The seeders add 10 sample lessons and a demo learner with progress records. After running `php artisan migrate --seed`, you can sign in with:

- Email: `learner@signwise.test`
- Password: `password`

These credentials are for local development only. Do not use them in a deployed environment.

## Useful Commands

```powershell
# Apply new migrations
php artisan migrate

# Load or refresh the sample data
php artisan db:seed

# Run the test suite
php artisan test

# Rebuild front-end assets
npm run build
```

## Main Pages

- `/` — Home page and featured lessons.
- `/how-it-works` — Learning overview.
- `/lessons` — Lesson catalog.
- `/lessons/{lesson}` — Lesson details.
- `/register` and `/login` — Learner account access.
- `/dashboard` and `/quiz` — Signed-in learner progress and quizzes.
