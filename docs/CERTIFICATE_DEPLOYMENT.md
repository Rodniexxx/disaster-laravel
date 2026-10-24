# MANGNO certificate deployment preparation

Prepared October 25, 2026. Production deployment and migrations require separate approval.

## Readiness

The complete Laravel suite passes after three test-only regression repairs. The certificate and profile code is ready locally. Production deployment is held for access to the correct Railway project/service, production migration-state verification, and deployment approval.

Inspected repository: `https://github.com/Rodniexxx/disaster-laravel.git`.
Local path: `C:\Users\Joshua\Desktop\disaster-laravel\disaster-laravel`.
Inspected branch: `main`, HEAD `4dbf6468dc72b58d97f42cb2d9b788aecad711da`; live GitHub `main` matched this SHA.
Prepared branch: `codex/certificate-completion-prep`.

## Git commit scope

Stage the following 15 certificate files:

```text
app/Http/Controllers/Api/CompletionCertificateController.php
app/Http/Controllers/PlayerCertificateController.php
app/Http/Controllers/DashboardController.php
app/Services/CompletionCertificateService.php
app/Services/CompletionCertificatePdf.php
database/migrations/2026_10_24_000001_create_completion_certificates_table.php
public/images/MANGNO_Certificate_Template.jpg
resources/css/app.css
resources/css/completion-certificate-dashboard.css
resources/views/player/dashboard.blade.php
resources/views/player/certificate.blade.php
resources/views/player/partials/completion-certificate-dashboard.blade.php
routes/api.php
routes/web.php
tests/Feature/CompletionCertificateTest.php
```

Include these six existing uncommitted profile prerequisites. Certificate names read `player_profiles`; the shared `routes/api.php` also requires `routes/profile.php`. The live profile endpoint currently returns 404, and these files are absent from committed HEAD.

```text
app/Http/Controllers/Api/PlayerProfileController.php
app/Models/PlayerProfile.php
config/profile.php
database/migrations/2026_10_22_000001_create_player_profiles_table.php
routes/profile.php
tests/Feature/PlayerProfileTest.php
```

Include these three regression test repairs. They preserve the configured `volcano.webp` paths and assert leaderboard player-cell text independently of its nested styling markup. No application behavior, images, scoring, or UI markup was changed for these fixes.

```text
tests/Unit/DisasterCatalogTest.php
tests/Feature/PlayerDashboardTest.php
tests/Feature/AdminLeaderboardScoreDeletionTest.php
```

Include this guide: `docs/CERTIFICATE_DEPLOYMENT.md` (25 paths total).
Keep the pre-existing `Dockerfile`, `docker-compose.yml`, `docker/nginx/default.conf`, and `.opencode/` changes outside this commit. The Dockerfile also has pre-existing trailing whitespace; it does not affect the selected certificate patch.

Prepared commit message:

```text
feat: add completion certificates with saved player profiles

Add authenticated certificate eligibility, unique issuance, dashboard viewing,
and exact-artwork PDF downloads using saved readiness and badge records.
Include the existing profile prerequisite for registered display names.
Preserve existing scores, achievement conditions, and player progress.
Repair stale image and leaderboard HTML assertions without application changes.
```

Review the final local commit before any approved push:

```powershell
Set-Location 'C:\Users\Joshua\Desktop\disaster-laravel\disaster-laravel'
git branch --show-current
git log -1 --oneline
git show --check HEAD
git show --stat HEAD
git show HEAD
```

No push or production deployment is part of this preparation. Confirm the Railway deployment branch and auto-deploy settings before any push/merge that could trigger production.

## Migration safety

`2026_10_24_000001_create_completion_certificates_table.php` creates only a new table:

- `id`, unsigned `user_id`, `milestone`, unique `certificate_id`, and `issued_at`.
- Unique `(user_id, milestone)` prevents duplicate issuance.
- The `user_id` type matches the existing unsigned integer `users.id`.
- No updates, deletes, backfills, renames, or alterations of existing tables.
- No changes to accounts, readiness, badges, stars, leaderboard rows, or gameplay progress.

Both migrations' MySQL SQL was compiled with an explicitly forbidden PDO connection. Statements create only `player_profiles` and `completion_certificates`, add the profile foreign key referencing existing `users.id`, and add the two certificate unique indexes. No database connection was opened. Tests exercised the schemas on isolated SQLite, not production MySQL.

The prerequisite profile migration also creates a new table only. It must be reviewed against production migration records and schema before running. Production schema and migration status have not been accessed. If either table already exists without the corresponding migration record, stop and reconcile its schema/history; do not drop, recreate, or overwrite it.

Do not run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, database seeders, or `composer setup` in production. A rollback of this feature should restore application code while retaining the additive tables and issued certificate records.

## Tests and verification

- Full suite with GD enabled for this PHP process: **120 passed, 0 failed, 866 assertions**.
- Certificate/readiness/website leaderboard: **18 passed, 272 assertions**.
- Required profile suite: **6 passed, 48 assertions**.
- Three stale failures had reproduced on an isolated export of the previous HEAD. They are now repaired and passing:
  - `DisasterCatalogTest::test_it_builds_scenario_models_with_images_from_configuration`: asserts the real configured `volcano.webp` path and retains asset-existence checks.
  - `PlayerDashboardTest::test_the_dashboard_lists_the_four_fixed_scenarios_with_images`: asserts the same existing dashboard image path.
  - `AdminLeaderboardScoreDeletionTest::test_only_the_targeted_scenario_is_removed_and_rankings_recalculate`: parses rendered player-cell text and verifies only the remaining player appears, while retaining score/history and recalculated-total assertions.
- Initial default-CLI run also had four profile image-test failures because GD was disabled. Enabling GD with `php -d extension=gd` for the test process resolved all four. The existing production Dockerfile already installs GD; no machine PHP configuration was changed.
- Tests used SQLite `:memory:` and an isolated configuration-cache path. No production data was used.
- Vite production build passed: 58 modules. Output was written to the validation folder, not deployed assets.
- Dashboard locked/unlocked rendering, PDF routes, actual names, 10/12 versus 12/12, account isolation, rejected score payloads, persistent ID/date, and duplicate issuance passed.
- Unity and website template images are identical. The PDF preserves the supplied artwork.

## Unity API route mapping

Unity release asset base URL: `https://mangno.casaul.com/api`.

| Method | Unity URL | Laravel route | Protection |
| --- | --- | --- | --- |
| GET | `/api/readiness/certificate` | certificate eligibility/status | Sanctum + active account + API throttle |
| POST | `/api/readiness/certificate/issue` | explicit issuance | Sanctum + active account + API throttle |
| GET | `/api/readiness/certificate/pdf` | own certificate PDF | Sanctum + active account + API throttle |

All three match local `php artisan route:list --path=certificate -v`. The web dashboard/view/claim/PDF routes also register correctly with session authentication and active-account middleware.

Live unauthenticated GET checks on October 25, 2026: `/api/readiness` returns 401; `/api/profile` and `/api/readiness/certificate` return 404. The new endpoints are not deployed yet. After deployment, an unauthenticated certificate GET should return 401; authenticated eligibility and PDF checks require a real eligible account. Do not fabricate or modify production progress to test them.

## Railway configuration verification

A Railway connection is now available and was inspected using read-only project/service/config tools. Its accessible services do not include MANGNO or `Rodniexxx/disaster-laravel`. Therefore the actual MANGNO deployment branch, auto-deploy setting, and pre-deploy/start commands remain unverified. GitHub `main` is confirmed, but it must not be assumed to be the Railway deployment branch. Access to the correct Railway account/workspace or the project/service URL is required. No Railway configuration was changed.

## Railway release steps — only after approval

1. Review the final local feature/profile/test commit. All regression tests now pass.
2. Identify the existing Laravel service, deployment branch, and existing build/start/pre-deploy commands. Confirm its database backup and read-only migration status/schema. Keep existing `APP_KEY`, database credentials, storage settings, and `APP_URL=https://mangno.casaul.com`.
3. Use the existing Docker build. It builds Vite assets and includes the template in `public/images`. Its entrypoint refreshes config, route, and view caches; it does not run migrations. No new Railway configuration has been applied.
4. Review only the approved pending migrations. Use Railway's pre-deploy step so a migration failure stops promotion of the new application. If the profile migration is already recorded, omit its command. If it is pending and its table is absent, the approved commands are:

```sh
php artisan migrate --path=database/migrations/2026_10_22_000001_create_player_profiles_table.php --force && php artisan migrate --path=database/migrations/2026_10_24_000001_create_completion_certificates_table.php --force
```

5. Configure the reviewed pre-deploy step before an approved merge/push can auto-deploy. Preserve any existing service settings. Do not use an unscoped migration command when other pending migrations have not been reviewed.
6. After approval, push/review the feature branch and release the reviewed commit through the existing Railway service. No push, merge, deployment, or production migration has been executed during preparation.
7. Verify login/logout, dashboard lock requirements, all existing scores/badges/leaderboards, the exact certificate artwork, registered name, real star total, stable certificate ID/date, PDF download, and Unity status/PDF synchronization. Test issuance only with a genuinely eligible account. Android public-Downloads saving still needs a physical-device test.

References: [Railway pre-deploy commands](https://docs.railway.com/deployments/pre-deploy-command), [Laravel 12 migrations](https://laravel.com/framework/docs/12.x/migrations).
