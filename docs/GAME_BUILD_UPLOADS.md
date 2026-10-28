# External game downloads

Game installers are hosted externally. In **Admin > Game Versions**, create or
edit a release and set its **External Download URL** to the real HTTP/HTTPS
installer URL for Windows or Android. Existing URLs are preserved.

The public `/download` page keeps both platform cards, descriptions, instructions,
and release metadata. Each button links to the selected release's external URL.
The `/download/windows`, `/download/android`, and `/download/{platform}/file`
compatibility endpoints redirect to the same URL. Selection prefers a release
marked latest, then the newest release date and ID, and requires a published
release with a usable external URL. Without one, the card stays unavailable.

Installer upload fields and storage handlers have been removed. Release create
and update requests reject the former `file` field. The admin stored-file
endpoint `/admin/versions/{version}/file` no longer exists.

No migration or data conversion is needed. Existing `game_versions` columns,
URLs, and metadata remain intact. Previously uploaded installers remain in
storage; editing or deleting a release does not delete its stored installer.
Profile picture uploads retain their separate validation and storage behavior.

Shared PHP, Apache, and nginx request limits remain unchanged to preserve the
existing deployment configuration. `MANGNO_MAX_UPLOAD_KB`,
`MANGNO_DOWNLOADS_DISK`, and `MANGNO_DOWNLOADS_PATH` are no longer used by the
application's download feature. Rebuild/redeploy the application normally and
refresh Laravel's configuration/route caches through the existing deployment
workflow.

Regression coverage is in `AdminGameVersionTest` and `DownloadPageTest`, including
external link management, redirects for both platforms, upload rejection,
publication/latest selection, metadata retention, and preservation of stored files.
