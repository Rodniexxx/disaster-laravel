# Optional profile pictures: website, storage and Unity

## Implemented behavior

Registration accepts an optional picture. Registering without one keeps the existing account/login flow and uses a shared PNG avatar. Profile & settings has an independent picture form for previewing, uploading, replacing or removing a photo, so saving a picture does not edit account fields. Clear selection and remove-current controls work before saving. The existing account-details and password forms retain their validation and routes.

Circular avatars appear in account navigation, the profile summary, public/admin leaderboard rows, admin player management and admin player progress. List controllers batch-load profile data; the score/ranking services and their Unity response shapes are unchanged. Missing image loads fall back to the shared avatar. The existing colors, fonts, cards, tables and responsive layout remain.

## Existing schema reused

No new migration or users-table column was added. The existing `player_profiles` table already provides `user_id`, `display_name`, nullable `avatar_path`, `avatar_disk`, `avatar_version` and `revision`. The user model now has a `playerProfile` relation and a computed `profile_picture_url`; binary image data never enters the database.

The local MySQL database did not yet have this table. After inspecting the SQL, the existing additive migration was applied:

```powershell
C:\xampp\php\php.exe artisan migrate --path=database/migrations/2026_10_22_000001_create_player_profiles_table.php --force
```

It created the profile table and its user foreign key without resetting or changing user, score or progress records. The migration already accounts for legacy signed user IDs. Deployments that have applied it need no schema change. Deployments that have not must apply it before enabling this feature. No fake players or pictures were added to the live database for testing.

## Upload validation and lifecycle

- Website uploads: actual JPG/JPEG, PNG or WebP, at most 2 MB, 4096 pixels per side and 12 megapixels.
- Laravel validates the upload MIME/type, size and dimensions; GD then fully decodes it. Invalid/corrupt files, SVG and unsupported formats are rejected. Filenames/extensions supplied by users never become storage paths.
- Images are resized proportionally to at most 512px and re-encoded as JPEG, stripping source metadata and trailing payloads. White fills transparency in uploaded images. The shared default remains PNG, which Unity's texture loader supports.
- Unique storage paths are `avatars/<authenticated-user-id>/<UUID>.jpg`. The path/disk are private internal fields. API responses expose only versioned URLs.
- Profile writes lock the owning user, use a database transaction and maintain the existing revision number. A stale save cannot overwrite a newer edit. Failed file/database writes keep the previous profile and clean up the new file. Photo registration failures roll back the new account.
- A committed replacement/removal deletes only an old file owned by that user. Shared defaults, arbitrary paths and another player's directory are never deleted. If cleanup storage is unavailable, an orphaned private old file may require later cleanup; its former URL is invalidated by the version check.
- Website forms keep Laravel CSRF protection. Picture edits require an active authenticated account and do not accept a target player ID or client-supplied path. Picture data does not change scores, progress, achievements or account information.
- Profile photos are displayed publicly with usernames on leaderboards. The versioned public route serves only the currently associated JPEG for an active user, with `image/jpeg`, `nosniff` and a five-minute cache lifetime. It does not expose filesystem paths or permit script execution. Replaced/removed URLs return 404 from the server; the new URL is different, so a profile refresh avoids old cached pictures.

## API contract: backward-compatible additions

Use the existing authenticated endpoint:

```http
GET /api/profile
Authorization: Bearer <existing token>
Accept: application/json
```

Example with a saved photo (IDs/version are illustrative):

```json
{
  "success": true,
  "profile": {
    "user_id": 123,
    "username": "mangno_player",
    "display_name": "Prepared Citizen",
    "avatar_url": "/api/profile/avatar?v=550e8400-e29b-41d4-a716-446655440000",
    "revision": 2,
    "avatar_upload_enabled": true,
    "profile_picture_url": "https://mangno.example/profile-pictures/123/550e8400-e29b-41d4-a716-446655440000"
  }
}
```

Without a picture, `profile_picture_url` is `https://mangno.example/images/default-avatar.png`; the old `avatar_url` stays `null`. Existing fields and their data types remain intact. Existing private `GET /api/profile/avatar`, `POST /api/profile`, expected revisions and avatar removal continue working. The older base64 API upload contract still accepts PNG/JPEG up to 256 KB and 1024px per side; the website's larger multipart upload limit does not silently change that contract.

`profile_picture_url` is also added to the existing login/register user-information response and to `GET /api/me` under `user`. Tokens, scopes, expiry, authentication parameters and existing API endpoints remain unchanged. Score, leaderboard and readiness APIs retain their existing responses.

Website picture changes produce a new UUID URL. Refreshing `/api/profile` returns it immediately. The image URL itself is public and does not require a bearer token. The new delivery route is `GET /profile-pictures/{user}/{version}`; the owner-edit route is `PATCH /profile/picture` (website CSRF/session authentication).

## HTTPS and durable storage deployment

Production URLs use a canonical HTTPS base: `PROFILE_PICTURE_BASE_URL` if set, otherwise the application's `APP_URL` with HTTPS. Set it to the actual public host and any application subdirectory. Local Docker preserves the configured HTTP scheme and port for localhost/loopback hosts. Local XAMPP uses the request's HTTP URL for local development; it is not a public TLS deployment. HTTPS URLs alone cannot provision DNS or certificates—your reverse proxy/hosting service must serve that host over HTTPS.

Production uploads fail closed by default until configured. The production volume root must be absolute and outside the application directory. This prevents accidentally relying on the image's ephemeral application storage. Operators must mount a genuinely persistent volume at that root and make it writable by PHP. Existing S3 support remains available when its Laravel filesystem adapter and bucket credentials are configured; no new package was installed. The supplied Docker setup uses a private local volume instead.

For Docker Compose, this change adds the named volume `profile_avatars` at `/var/lib/mangno/avatars`, enables the existing `profile_volume` disk and sets that root. The entrypoint initializes ownership of this fixed directory for `www-data`. Existing database and app-storage volumes remain unchanged. Docker already builds GD with JPEG/PNG/WebP support. Laravel serves images through the versioned route, so no public storage symlink is needed for avatars.

For Railway or another container host:

1. Attach a persistent volume to **the application service**, mounted at `/var/lib/mangno/avatars`.
2. Set the following environment variables with your real HTTPS host:

```dotenv
APP_URL=https://your-real-mangno-host.example
PROFILE_AVATAR_UPLOADS_ENABLED=true
PROFILE_AVATAR_DISK=profile_volume
PROFILE_AVATAR_VOLUME_ROOT=/var/lib/mangno/avatars
PROFILE_PICTURE_BASE_URL=https://your-real-mangno-host.example
```

3. Ensure the existing profile migration is applied, deploy the code/built assets, and rebuild Laravel configuration caches. The existing Docker entrypoint refreshes caches at startup.
4. Confirm PHP can write the mount and that a real uploaded image URL is reachable over HTTPS. Back up the avatar volume alongside the database. Never run `docker compose down -v` or delete the avatar volume during a routine deployment. Multiple application replicas must share image storage, or use the existing shared object-storage option.

Local XAMPP stores avatars in `storage/app/profile-avatars`, which persists on the local filesystem across Apache restarts. GD was disabled locally; `extension=gd` was enabled in `C:\xampp\php\php.ini` and the verified XAMPP Apache console processes were restarted. The original ini was saved locally at `.taste-skill/php.ini.before-avatar`. No production host or Railway deployment was changed from this workspace.

## Unity integration

No Unity project/C# source is present in this workspace, so the game itself was **not modified**. Add this to the existing authenticated player-profile UI client, retaining its current token handling. Fetch `/api/profile` after login and when returning to the profile screen, and refresh after a website picture change. Refresh on foregrounding the game if that matches the existing UI lifecycle. Compare URLs and download only when they change. Use a circular `Mask` around the existing RawImage if the UI requires a circle.

```csharp
using System;
using System.Collections;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

public class MangnoAvatarView : MonoBehaviour
{
    [SerializeField] private RawImage avatar;
    private string loadedUrl;
    private Texture2D ownedTexture;

    [Serializable] private class ProfileEnvelope { public bool success; public ProfileInfo profile; }
    [Serializable] private class ProfileInfo { public string profile_picture_url; }

    // apiBaseUrl includes /api. Pass the existing login token; never hardcode it.
    public IEnumerator RefreshPicture(string apiBaseUrl, string bearerToken)
    {
        using (var request = UnityWebRequest.Get(apiBaseUrl.TrimEnd('/') + "/profile"))
        {
            request.timeout = 10;
            request.SetRequestHeader("Authorization", "Bearer " + bearerToken);
            request.SetRequestHeader("Accept", "application/json");
            yield return request.SendWebRequest();
            if (request.result != UnityWebRequest.Result.Success) yield break;

            var envelope = JsonUtility.FromJson<ProfileEnvelope>(request.downloadHandler.text);
            string url = envelope != null && envelope.success ? envelope.profile?.profile_picture_url : null;
            if (string.IsNullOrEmpty(url) || url == loadedUrl) yield break;

            using (var imageRequest = UnityWebRequestTexture.GetTexture(url))
            {
                imageRequest.timeout = 10;
                // The versioned photo/default image is public; do not forward the token.
                yield return imageRequest.SendWebRequest();
                if (imageRequest.result != UnityWebRequest.Result.Success) yield break;
                var next = DownloadHandlerTexture.GetContent(imageRequest);
                avatar.texture = next;
                if (ownedTexture != null) Destroy(ownedTexture);
                ownedTexture = next;
                loadedUrl = url;
            }
        }
    }

    private void OnDestroy()
    {
        if (ownedTexture != null) Destroy(ownedTexture);
    }
}
```

Keep the existing/default UI texture when requests fail. Use the project's existing login-expiry flow for 401 and backoff for transient errors; do not retry invalid credentials indefinitely. Do not disable TLS certificate validation. Run one refresh at a time in the existing profile UI lifecycle. This example only changes the profile image and does not touch gameplay, scores or progress.

## Files changed or created

New:
- `app/Services/AvatarService.php`
- `app/Http/Controllers/ProfilePictureController.php`
- `resources/views/components/player-avatar.blade.php`
- `resources/views/partials/avatar-upload.blade.php`
- `resources/js/avatars.js`
- `resources/css/avatars.css`
- `public/images/default-avatar.png`
- `tests/Feature/ProfilePictureTest.php`
- `docs/PROFILE_PICTURES.md`

Updated:
- `app/Models/User.php` — relationship and computed URL.
- `app/Http/Controllers/Auth/RegisterController.php` — optional website upload.
- `app/Http/Controllers/Api/AuthController.php` — additive URL field.
- `app/Http/Controllers/Api/PlayerProfileController.php` — reuse shared avatar lifecycle while preserving the API.
- `app/Http/Controllers/LeaderboardController.php`, `Admin/LeaderboardController.php`, `Admin/UserController.php`, `Admin/ProgressController.php` — batch-load avatar presentation data; no ranking/progress formula change.
- `routes/web.php` — picture edit/delivery routes; existing API routes were not renamed or removed.
- `resources/views/auth/register.blade.php`, `player/profile.blade.php`, `partials/site-header.blade.php`, `leaderboard/index.blade.php`, `admin/leaderboard/index.blade.php`, `admin/users/index.blade.php`, `admin/progress/results.blade.php` — upload controls/avatar display.
- `resources/js/app.js`, `resources/css/app.css` — initialize/import the scoped feature.
- `config/profile.php`, `.env.example`, `docker-compose.yml`, `docker/php/entrypoint.sh` — local defaults and explicit persistent production storage.

The existing profile migration was applied locally but no new migration was created for this feature. Existing unrelated workspace edits were retained.

## Verification

- Final compatibility run: **144 tests, 1,241 assertions passed**. This includes **11 new picture tests, 98 assertions**, the six original profile API tests, and existing authentication, registration, leaderboard, scores, readiness, certificates and player-progress coverage. The same two unrelated pre-existing download-copy assertions were excluded; they were not changed or hidden by modifying their tests.
- Original private profile API tests passed, including legacy upload limits, revisions, replacement/removal and failed storage.
- New feature tests cover registration with/without a picture, JPG/PNG/WebP processing, real type/size/dimension validation, corrupt files, trailing-payload stripping, unique paths, owner isolation, shared-default protection, rollback on failure, unchanged game data, HTTPS API fields and refresh after replacement/removal.
- Docker persistence was tested using the existing application image and an isolated labeled volume: one container wrote the PNG, exited and was removed; a second container, running as `www-data`, read the exact same SHA-256 through a read-only mount. The test volume was removed afterward. Existing app/database volumes were not touched. This verifies the persistence mechanism, not an unperformed hosted Railway rollout.
- Docker Compose configuration validates. XAMPP serves registration and the PNG fallback successfully. Local schema/GD/storage readiness checks pass.
- **21 browser checks passed**, covering register, profile, leaderboard, admin users and admin progress at 320, 390, 768 and 1440px, plus file preview, clearing, removal, replacement, oversize rejection and fallback display. No horizontal overflow or JavaScript exceptions were found. Screenshots are in `.taste-skill/avatar-browser`; fixtures use isolated SQLite/storage, not live user data.
- Production build and Blade compilation pass. The compatibility run excludes the same two pre-existing unrelated download-copy assertions described in `PLAYER_PROGRESS.md`; no download code was changed for this feature.
