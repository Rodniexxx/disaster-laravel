# Unity Android API

Laravel project: `C:\Users\Joshua\Desktop\disaster-laravel\disaster-laravel`
Database: `disaster_game2`. Existing records were preserved. No database reset or reseeding was performed.

## Base URLs

- Existing website/API on this computer: `http://localhost:8000/api`
- Android device on the same Wi-Fi: `http://192.168.8.34:8001/api`
- Android emulator: `http://10.0.2.2:8001/api`

The device address uses the computer's current Wi-Fi IP; update it if the IP changes.
Start MySQL in XAMPP. Run `start-unity-api.ps1` from this folder to start the LAN API server on port 8001. Keep its terminal running. Windows network access must allow the port for device testing. Use HTTPS for deployment. Unity project files have not been modified or device-tested.

## Endpoints

| Method | Path | Authentication | Input |
| --- | --- | --- | --- |
| POST | `/register` | Public, rate limited | `username`, `email`, `password`, optional `password_confirmation` or `confirm_password`, optional `device_name` |
| POST | `/login` | Public, rate limited | `username`, `email`, or `login_identifier`; `password`; optional `device_name` |
| GET | `/me` | Bearer token | None |
| POST | `/logout` | Bearer token | None; revokes the current token |
| POST | `/save_score` | Bearer token | `category`, `score` |
| GET | `/get_leaderboard` | Public, rate limited | Optional `?category=Typhoon`, `Fire`, `Earthquake`, `Volcano`, or `Overall` |
| GET | `/scenarios` | Public, rate limited | None |
| GET | `/announcements` | Public, rate limited | None |

Send `Accept: application/json` and `Content-Type: application/json`.
Login and registration return `success`, `message`, `user_id`, `username`, `email`, `role`, `token`, `token_type`, and `expires_at`.
Use `Authorization: Bearer <token>` for authenticated requests. Tokens expire after 30 days. Website login still uses its existing session and CSRF protection.

Registration passwords require 8–255 characters. Usernames allow up to 50 characters; emails up to 100. Account roles cannot be selected through the API. Inactive accounts cannot log in or submit scores.

Score example:

```json
{"category":"Typhoon","score":80}
```

Scores must be integers from 0 through 100. Category titles/slugs are case insensitive. The authenticated account owns the score; a supplied `user_id` is ignored. Lower scores do not replace a personal best. Score submissions require the `scores:submit` token ability.

```json
{"success":true,"message":"Score saved!","category":"Typhoon","best_score":80}
```

Leaderboard example: `GET /get_leaderboard?category=Fire`

```json
{"success":true,"leaderboard":[{"rank":1,"username":"player","category":"Fire","score":90}]}
```

Errors use JSON. Invalid credentials/expired tokens: 401; disabled accounts or insufficient token abilities: 403; duplicate accounts: 409; field validation: 422; invalid disaster category: 400; rate limit: 429. Login/registration allow 20 requests/minute per IP; other API routes allow 60.

## Website display and verification

Scores use the same `leaderboard` table as `/leaderboard`, `/scores`, `/scores/categories`, and the dashboard. The public leaderboard supports separate tabs for all four disasters. Old PHP endpoints remain untouched.

Verified: 40 tests, 201 assertions, including existing website behavior. MySQL transaction checks verified registration, login, token authentication, all four score categories, JSON leaderboards, website score display, and logout. Temporary MySQL test records were rolled back.

Original PHP files and a full SQL backup are in `storage/app/integration-backups/`. The applied migration only adds `personal_access_tokens`; the four previously missing scenario records were added without replacing existing records.

Client-submitted scores are authenticated and range checked; gameplay correctness still depends on the client. Server-side answer verification would require a future quiz protocol.
