# Player progress: Laravel and Unity integration

## Root cause and compatibility audit

The existing admin page queried `player_progress` records per scenario. Registration creates a `users` row but no `player_progress` row, so new players were missing. No existing API wrote scenario-start events to `player_progress`. The readiness API instead writes `readiness_attempts` and `readiness_progress`; the old admin query never read those tables. The old page also had no automatic refresh.

The current routes are under **`/api`**, not the historical `/api/v1` examples in README.md. Inspected authentication uses Sanctum bearer tokens with the existing `scores:submit` ability, active-account middleware and existing API throttling. Current authentication responses, token creation, parameters and score/leaderboard logic are unchanged. `POST /api/save_score` writes a legacy best score, not an explicit completion event. `POST /api/readiness/attempts` already validates gameplay evidence and an explicit `completed` boolean, records attempts and preserves completed personal bests. Its existing contract and response are unchanged. No Unity source files are present in this Laravel repository, so actual client call sites cannot be edited or confirmed here.

No migrations, backfills, deletions, resets or changes to existing records are required. The new start endpoint creates/updates only the authenticated player's existing progress-table entry. MySQL read expressions normalize collations across older/newer tables without changing their schema. A read-only query against the configured local MySQL database verified compatibility.

## Calculation and data sources

The four configured disasters are Earthquake, Fire, Typhoon and Volcano (displayed as Volcanic Eruption). Existing aliases, case variations, spaces, hyphens and underscores normalize to one disaster. The query groups by player and normalized disaster, using explicit completion evidence from:

1. `player_progress.status = completed` (historical records).
2. `readiness_attempts.completed = true` (accepted gameplay evidence, including preserved history).
3. `readiness_progress.completed = true` (saved personal bests).

Each disaster counts at most once, even across multiple sources or retries. A failed retry or a new start cannot undo completion. Retaining attempt evidence also means deleting a leaderboard personal-best record does not erase historical completion. Unknown scenarios are ignored. Quiz scores, score-only submissions, stars, registrations and logins do not imply completion or active gameplay.

`percentage = distinct completed disasters / 4 × 100`

- **Not Started**: no known-disaster start, accepted attempt or explicit completion has been recorded.
- **In Progress**: a start/attempt/completion exists and fewer than four disasters are complete.
- **Completed**: all four disasters have explicit completion evidence.

Last activity is the newest recorded start timestamp, attempt timestamp or completion timestamp, shown with its timezone. It is not online presence. Absent activity is shown as “None recorded”. Completed history with missing timestamps remains completed. Players with no progress rows still appear. Admin accounts are excluded from the player list.

## New endpoint: scenario start

```http
POST /api/progress/start
Authorization: Bearer <existing-login-token>
Accept: application/json
Content-Type: application/json

{"scenario":"Earthquake"}
```

Use `Earthquake`, `Fire`, `Typhoon` or `Volcano`. Existing catalog aliases such as `Volcanic Eruption` and `volcanic-eruption` are accepted and normalized. The token needs its existing `scores:submit` ability and an active player account. No new token format or ability is needed. Do not send a player ID, status, completion flag, score or stars; those fields are prohibited.

HTTP 200 response for the first start (timestamp is illustrative):

```json
{
  "success": true,
  "scenario": "Earthquake",
  "progress": {
    "completed_scenarios": 0,
    "total_scenarios": 4,
    "completed_names": [],
    "progress_percentage": 0,
    "status": "in_progress",
    "status_label": "In Progress",
    "last_activity_at": "2026-10-28T09:00:00+00:00"
  }
}
```

Repeated starts reuse the unique `(user_id, scenario_slug)` record and preserve its completed status and saved score. `last_activity_at` is receipt time, so a retried start can refresh it. A player who already completed all four scenarios still receives `status: completed` and `progress_percentage: 100`.

Missing/invalid tokens return the existing HTTP 401 authentication response. Inactive accounts, missing token capability or admin accounts return 403. Invalid scenario values, wrong types and prohibited fields return the existing HTTP 422 validation envelope:

```json
{"success":false,"message":"The given data was invalid.","errors":{"scenario":["Choose Earthquake, Fire, Typhoon, or Volcano."]}}
```

## Required Unity changes

**Yes: call the new start endpoint when a player actually begins a scenario**, after authentication and when the playable scenario begins—not at login or menu selection. The server cannot observe an unsent start. Keep the current login, score and leaderboard calls.

A minimal Unity coroutine, to adapt inside the existing authenticated API client:

```csharp
using System;
using System.Collections;
using System.Text;
using UnityEngine;
using UnityEngine.Networking;

[Serializable]
public class MangnoStartBody { public string scenario; }

// apiBaseUrl includes /api, for example https://your-host.example/api
public static class MangnoProgressReporter
{
    public static IEnumerator ReportStart(string apiBaseUrl, string bearerToken, string scenario)
    {
        string json = JsonUtility.ToJson(new MangnoStartBody { scenario = scenario });
        using (var request = new UnityWebRequest(apiBaseUrl.TrimEnd('/') + "/progress/start", "POST"))
        {
            request.uploadHandler = new UploadHandlerRaw(Encoding.UTF8.GetBytes(json));
            request.downloadHandler = new DownloadHandlerBuffer();
            request.timeout = 10;
            request.SetRequestHeader("Authorization", "Bearer " + bearerToken);
            request.SetRequestHeader("Content-Type", "application/json");
            request.SetRequestHeader("Accept", "application/json");
            yield return request.SendWebRequest();
            if (request.result != UnityWebRequest.Result.Success)
                Debug.LogWarning("Progress start was not recorded. HTTP " + request.responseCode);
        }
    }
}
```

Use the existing token held by the client; never embed credentials or log the token. On temporary connection errors, queue/retry the same scenario start with backoff after reconnecting. Do not loop rapidly or retry 401/403/422 indefinitely. The endpoint is safe to retry. It must never be used to claim completion.

**Completion:** retain `POST /api/readiness/attempts`. If the current Unity build sends only `save_score`, that is insufficient to establish completion: wire the existing readiness endpoint to actual gameplay outcomes. It already accepts this exact structure:

```json
{
  "attempt_id": "a-new-UUID-for-this-playthrough",
  "scenario": "Earthquake",
  "completed": true,
  "quiz_correct": 8,
  "quiz_total": 10,
  "metrics": [
    {"id":"drop","earned":1,"possible":1},
    {"id":"cover","earned":1,"possible":1},
    {"id":"hold","earned":1,"possible":1}
  ]
}
```

The illustrative UUID must be replaced by `Guid.NewGuid().ToString()` once per playthrough. All results/evidence must come from actual gameplay; the sample values are not defaults. Required criteria:

| API scenario | Metric IDs |
| --- | --- |
| Typhoon | preparation, family_evacuation |
| Earthquake | drop, cover, hold |
| Fire | objectives, family_evacuation, safe_area |
| Volcano | mask, goggles, official_order, family_evacuation, shelter |

Existing server validation, count/binary criteria rules and completion safety checks still apply. `quiz_total` remains 10; `quiz_correct` is the actual correct-answer count. Send `completed: false` for unfinished attempts. Persist the original UUID and exact evidence for retrying a completed submission; the existing endpoint rejects reusing an ID with changed evidence and accepts exact retries without double counting. A network failure must not be converted into a completion event. No new completion endpoint or changes to leaderboard calculations were added.

The existing `GET /api/readiness` response remains unchanged. The new start response reports overall administrative completion status, not a replacement readiness scoring contract.

## Admin automatic updates

Open **Admin → Progress** (`/admin/progress`). It shows ten players per page, with username search, overall-status filters, completed names/count, percentage, native progress bar, status and last activity. Layout, fonts and colors follow the existing admin design.

`GET /admin/progress/data` is protected by the same session authentication, active-account and admin middleware. It returns `{"html":"<escaped Blade-rendered table and pagination>"}`. It does not expose email, password hashes or tokens. User content is escaped by Blade. Pagination links lead back to `/admin/progress`, preserving the filters.

The JavaScript waits 15 seconds after the server-rendered page before fetching. It polls only this page, pauses while the tab is hidden, allows one request at a time and times out after 10 seconds. ETag/If-None-Match returns HTTP 304 when the rendered page is unchanged. Error retries back off to 30, 60 and at most 120 seconds; existing rows remain visible. Reconnection resumes immediately. Expired authentication stops polling and asks for reload. A single aggregate query supplies the current page, plus the paginator count query; there is no per-player query loop. ETags reduce payload and DOM work, not database reads.

Last-activity timestamps are absolute so unchanged rows do not produce different ETags on every poll. This is near-real-time polling, not a continuous “currently playing” indicator. No websocket infrastructure or new dependencies were added.

## Files

New:
- `app/Services/PlayerProgressService.php`
- `app/Http/Controllers/Api/ProgressController.php`
- `resources/views/admin/progress/results.blade.php`
- `resources/js/player-progress.js`
- `tests/Feature/PlayerProgressTrackingTest.php`
- `docs/PLAYER_PROGRESS.md`

Updated:
- `app/Http/Controllers/Admin/ProgressController.php`
- `routes/api.php` (additive route only)
- `routes/web.php` (additive admin feed route only)
- `resources/views/admin/progress/index.blade.php`
- `resources/js/app.js` (initialize the progress module)
- `resources/css/admin.css` (scoped progress styling)

## Verification

- New feature suite: **10 tests, 98 assertions passed**. Covers registration, start, 25/50/75/100%, duplicate and failed attempts, aliases, preserved historical completions, logout/login, authorization, validation, filters, ten-row pagination, ETags, escaped output and bounded query count.
- Compatibility suite with GD enabled for the PHP process: **133 tests, 1,143 assertions passed**, including existing authentication, Unity integration, API endpoints, scores, readiness, leaderboards, profiles and certificates.
- Final focused rerun after the MySQL/readability refinements: **46 tests, 356 assertions passed** across player progress, Unity integration, readiness, API endpoints, authentication and admin access.
- Full unfiltered run exposed two unrelated existing download-content assertions: `AdminGameVersionTest::test_an_admin_can_save_download_page_content` and `DownloadPageTest::test_the_download_page_is_visible_to_guests`. The existing edited download template no longer renders the expected heading/introduction. It was not changed for this task. These two tests alone were excluded from the compatibility rerun. Four initial profile test errors were caused by disabled GD; enabling GD for that process resolved them without editing php.ini.
- Read-only local MySQL aggregation passed after accounting for mixed legacy collations. Unauthenticated XAMPP admin-feed and readiness requests returned 401. No live player records were changed for verification.
- Browser checks at 320/390/768/1440px: no horizontal overflow. A real 15-second poll changed a fixture player from Not Started to In Progress without navigating; subsequent updates showed 25% and 100%. Conditional 304 responses preserved the DOM, simulated 503 kept existing results and recovered, and 401 stopped polling. Browser fixtures use an isolated in-memory database.
- Production Vite build and Blade compilation passed. No schema migrations are needed.

Browser evidence is in `.taste-skill/progress-browser`; isolated fixture generation and checks are local QA scripts. On this machine, the active preview uses XAMPP at `http://localhost/disaster-laravel/public`; the previous Docker app container no longer exists. No Docker database was created or reset. Deploy the changed source and built assets through the project's usual deployment process to update any hosted Unity API.
