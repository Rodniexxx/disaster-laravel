# Game build upload limits

Game releases accept one APK/EXE (or another allowed platform build type) per
request, up to **2 GiB = 2,147,483,648 bytes = 2,097,152 KiB**. The admin UI
displays this as 2048 MB. Existing admin authorization and extension validation
continue to apply. Profile pictures retain their separate 2 MB limit.

- Laravel: `MANGNO_MAX_UPLOAD_KB=2097152`, default in `config/mangno.php`.
- XAMPP Apache/mod_php: project-scoped settings in `public/.htaccess` apply
  without changing the machine-wide PHP configuration or restarting Apache.
- Docker PHP: `upload_max_filesize=2G`, `post_max_size=2112M`.
- Docker nginx: `client_max_body_size 2112M`.
- PHP input/execution and nginx upstream timeouts: 3600 seconds. These are
  ceilings, not a guarantee that every slow connection will finish.

The request limit includes 64 MiB above the file limit for multipart fields.
Apache's `LimitRequestBody 0` lets PHP enforce the request cap because Apache's
largest finite value is below the required size. Uploads stream to temporary
storage; PHP memory does not need to equal the file size. Keep sufficient free
disk space for temporary and stored copies, and persistent storage for builds.

Rebuild/redeploy the Docker image to activate its changed configuration. On
other hosts, configure PHP and the web server or upstream proxy to accept this
request size; `.htaccess` PHP values apply only to Apache/mod_php. Rebuild any
Laravel configuration cache after changing environment values.

These are standard single-request uploads, without chunking or resume. A
connection interruption requires restarting the upload. Boundary tests use
reported fake file sizes to check acceptance at 2 GiB and rejection above it for
both APK and EXE without allocating gigabytes of test data. Local Apache/PHP
settings were verified over HTTP; a full 2 GiB transfer was not performed.
