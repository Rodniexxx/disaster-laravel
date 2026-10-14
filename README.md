# MANGNO — Companion Web Portal

**Ready Before It Happens.**

MANGNO is an educational Unity 3D game that teaches disaster preparedness. This
repository is the **web side of the project**: a Laravel application that gives
players a place to review their performance, compare on leaderboards, and gives
administrators a console for managing accounts and the quiz question bank. It
also exposes a versioned REST API that the Unity client uses to authenticate,
fetch questions, and submit results.

> The web portal and the API are a real, database-backed application — not a
> mock-up. Everything below is wired up and covered by tests.

---

## Contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database and seeding](#database-and-seeding)
- [Development accounts](#development-accounts)
- [Running the application](#running-the-application)
- [Unity integration API](#unity-integration-api)
- [Scoring and leaderboards](#scoring-and-leaderboards)
- [Security notes](#security-notes)
- [Testing](#testing)
- [Removing demo data](#removing-demo-data)
- [Project structure](#project-structure)
- [Known limitations](#known-limitations)

---

## Features

**Public**

- Marketing landing page describing the game and its four disaster categories.
- Register, log in, forgot/reset password.

**Players**

- Dashboard with readiness summary, recent results and per-category progress.
- My Scores, Category Performance, and full Game History with a per-question
  review (correct answer, the player's answer, and the explanation).
- Global leaderboards (overall and per category).
- Profile and password management.

**Administrators**

- Dashboard with platform statistics, category participation and score
  distribution.
- Full user management: create, edit, activate/deactivate, reset passwords,
  delete — with protection so the **last active administrator** can never be
  removed, demoted or deactivated.
- Quiz question management (four-choice questions with difficulty, explanation,
  reference and publish/unpublish).
- Read-only view of every game record.
- Audit log of administrative actions.

**API (for the Unity client)**

- Token authentication (Sanctum), scoped to the caller's own data.
- Category and question listing (answers are never leaked).
- Server-graded gameplay sessions.

---

## Tech stack

| Layer      | Choice                                    |
| ---------- | ----------------------------------------- |
| Framework  | Laravel 12 (PHP 8.2+)                     |
| Front end  | Blade + Alpine.js, no SPA build           |
| Styling    | Tailwind CSS v4 (custom design tokens)    |
| Database   | MySQL / MariaDB (SQLite in tests)         |
| Auth       | Session (web) + Laravel Sanctum (API)     |
| Build tool | Vite                                      |

---

## Requirements

- PHP **8.2+** with the `pdo_mysql`, `mbstring`, `openssl` and `fileinfo`
  extensions.
- Composer 2.
- Node.js 18+ and npm.
- MySQL 8 / MariaDB 10.4+ (bundled with XAMPP on Windows).

---

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Install and build front-end assets
npm install
npm run build

# 3. Create the environment file and application key
copy .env.example .env      # Windows
# cp .env.example .env      # macOS / Linux
php artisan key:generate

# 4. Create the database (see Configuration), then migrate and seed
php artisan migrate --seed
```

### XAMPP / Windows quick start

1. Start **MySQL** from the XAMPP control panel (or run
   `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini`).
2. Create the database:
   ```sql
   CREATE DATABASE mangno CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Follow the steps above. The default `.env.example` already targets
   `127.0.0.1:3306`, database `mangno`, user `root`, empty password.

---

## Configuration

Key values in `.env`:

| Key                            | Default        | Purpose                                        |
| ------------------------------ | -------------- | ---------------------------------------------- |
| `APP_NAME`                     | `MANGNO`       | Application name                               |
| `APP_URL`                      | `http://localhost:8000` | Base URL                              |
| `DB_CONNECTION`                | `mysql`        | Database driver                                |
| `DB_DATABASE`                  | `mangno`       | Database name                                  |
| `MANGNO_TAGLINE`               | `Ready Before It Happens.` | Brand tagline used across the UI     |
| `MANGNO_API_TOKEN_TTL`         | `10080`        | API token lifetime in **minutes** (7 days)     |
| `MANGNO_SESSION_QUESTION_COUNT`| `10`           | Default number of questions per session        |

Branding, API and leaderboard settings are centralised in
[`config/mangno.php`](config/mangno.php).

---

## Database and seeding

Migrations create the users, disaster categories, questions and answer choices,
game sessions, session questions (with per-session answer snapshots) and audit
log tables.

Seeders populate a working environment:

- **DisasterCategorySeeder** — the four categories (Typhoon, Earthquake,
  Volcanic Eruption, Fire).
- **QuizQuestionSeeder** — sample questions across all categories, flagged as
  samples.
- **DemoUserSeeder** — the two required development accounts (idempotent).
- **DemoDataSeeder** — optional demo gameplay history and rival players, all
  tagged so they can be removed later (see
  [Removing demo data](#removing-demo-data)).

```bash
php artisan migrate:fresh --seed
```

---

## Development accounts

These accounts are created by `DemoUserSeeder` for local development only.

| Role         | Email                  | Password            |
| ------------ | ---------------------- | ------------------- |
| Player       | `player@mangno.local`  | `MangnoPlayer123!`  |
| Administrator| `admin@mangno.local`   | `MangnoAdmin123!`   |

> **Change or remove these before any real deployment.** See
> [Security notes](#security-notes).

---

## Running the application

```bash
# Terminal 1 — application server
php artisan serve

# Terminal 2 — Vite dev server (only for local hot-reloading)
npm run dev
```

Then open <http://localhost:8000>.

For a production-style asset build use `npm run build` and serve through your web
server of choice.

---

## Unity integration API

Base URL: **`/api/v1`**. All responses share one envelope:

```json
{ "success": true, "data": {}, "message": null }
```

Authenticate with `POST /api/v1/auth/login`, then send the returned token as
`Authorization: Bearer <token>` on every subsequent request. Tokens expire after
`MANGNO_API_TOKEN_TTL` minutes and are scoped to the caller's own data.

| Method | Endpoint                        | Description                                |
| ------ | ------------------------------- | ------------------------------------------ |
| POST   | `/auth/login`                   | Exchange credentials for a token           |
| POST   | `/auth/logout`                  | Revoke the current token                   |
| GET    | `/auth/user`                    | Current player profile                      |
| GET    | `/categories`                   | Disaster categories + published counts     |
| GET    | `/questions?category=&difficulty=&limit=` | Published questions (no answers)  |
| POST   | `/sessions`                     | Start a session and receive its questions  |
| GET    | `/sessions/{uuid}`              | Session state                              |
| POST   | `/sessions/{uuid}/submit`       | Submit results (server-graded)             |
| GET    | `/me/scores`                    | The caller's own score history             |
| GET    | `/leaderboard?category=&per_page=` | Overall or category leaderboard         |

### Example: login

```http
POST /api/v1/auth/login
Content-Type: application/json

{ "email": "player@mangno.local", "password": "MangnoPlayer123!", "device_name": "quest-2" }
```

### Example: start and submit a session

```http
POST /api/v1/sessions
Authorization: Bearer <token>

{ "category": "typhoon", "question_count": 10 }
```

The response contains a `session_id`, a one-time `nonce`, and the `questions`
array. Submit the player's answers keyed by position:

```http
POST /api/v1/sessions/<session_id>/submit
Authorization: Bearer <token>

{
  "nonce": "<nonce from session start>",
  "duration_seconds": 214,
  "client_result_id": "round-42",
  "answers": [
    { "position": 1, "selected_key": "b" },
    { "position": 2, "selected_key": "a" }
  ]
}
```

The server grades the answers using the immutable question snapshot stored when
the session was created and returns the verified result. Rate limits:
`/auth/login` 10/min per IP, most game endpoints 60/min, `/sessions/{id}/submit`
30/min (per user or IP).

---

## Scoring and leaderboards

Ranking is computed from **verified, completed** sessions only.

- **Category leaderboard** — each player's **best percentage**
  (`score / max_score`) within the category, so categories with different
  maximum scores stay comparable.
- **Overall leaderboard** — the **average of each player's best percentage**
  across the categories they have played.

When per-answer data is available the score is graded on the server
(`verification = server_verified`). If the game cannot provide per-answer data it
may fall back to a client-reported score (`verification = client_reported`),
which is validated against the session maximum but cannot be independently
verified.

---

## Security notes

- Passwords are hashed with bcrypt; the app enforces a minimum of 10 characters
  with letters, numbers and a symbol.
- Administrators are protected by a "last active administrator" rule across
  update, deactivate and delete operations.
- Every administrative action is written to the `audit_logs` table.
- Session-based web auth and token-based API auth are kept separate.
- Public and API responses never expose email addresses, passwords or the
  correct answers to unanswered questions.

**Before deploying to production:**

1. Change or delete the demo accounts.
2. Set a strong `APP_KEY` and `APP_ENV=production`, `APP_DEBUG=false`.
3. Serve over HTTPS and configure a real mailer for password resets.

---

## Testing

Tests run against an in-memory SQLite database and do not touch your MySQL data.

```bash
php artisan test
```

The suite covers authentication and registration, role-based access to the
player and admin areas, user management (including the last-admin rule), the
full API gameplay flow, and the scoring/leaderboard services.

---

## Removing demo data

The optional demo history and rival accounts can be removed in one step without
touching the required development accounts or the question bank:

```bash
php artisan mangno:remove-demo
```

---

## Project structure

```
app/
├── Console/Commands/      mangno:remove-demo
├── Http/
│   ├── Controllers/       Auth, player and Admin controllers
│   └── Controllers/Api/V1 Versioned REST API for the Unity client
├── Middleware/            EnsureUserIsAdmin, EnsureUserIsActive
├── Models/                User, DisasterCategory, QuizQuestion, AnswerChoice,
│                          GameSession, GameSessionQuestion, AuditLog
├── Services/              GameSessionService, LeaderboardService,
│                          PlayerStatsService, AdminStatsService
└── Support/ApiResponse.php
config/mangno.php          Branding, API and leaderboard settings
database/
├── migrations/            Schema
├── factories/             Test/seed factories
└── seeders/               Categories, questions, demo accounts and data
resources/
├── css/app.css            Tailwind theme + component classes
├── js/app.js              Alpine.js bootstrap
└── views/                 layouts, components, player, admin, auth, api-free
tests/
├── Feature/               Auth, player area, admin, API, landing
└── Unit/                  GameSessionService, LeaderboardService
```

---

## Known limitations

- The API's client-reported submission path is inherently less trustworthy than
  the server-graded path; it exists only as a fallback and is flagged
  accordingly in the data.
- Password reset emails require a configured mailer; the local `MAIL_MAILER=log`
  setting simply writes the link to the log file.
- The leaderboard queries are optimised for the scale of a classroom or small
  deployment; very large datasets would benefit from cached/materialised ranks.

---

*MANGNO — Educational Game for Disaster Preparedness.*
