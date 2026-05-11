# Production bootstrap

The application ships with two artisan commands that make a fresh production
deployment safe to run without touching the database manually.

## 1) Run migrations and seed canonical data

```bash
php artisan app:install
```

Equivalent of:

```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=EcclesiasticalRegionSeeder --force
```

`app:install` is **idempotent**:

- Roles (`global_manager`, `local_manager`, `user`) and the permission set
  used by the policies are upserted via `Role::findOrCreate` /
  `syncPermissions`. Re-running the command never duplicates rows.
- The 10 ecclesiastical regions (RE1–RE8, REMA, REMNE) are upserted by
  unique `code`.

Pass `--fresh` to drop and recreate every table (will prompt for confirmation
in `production`):

```bash
php artisan app:install --fresh
```

`DemoChurchSeeder` and `DemoUserSeeder` only run in `local` / `testing`
environments — production never gets demo data.

## 2) Promote (or create) the first super user

There is no UI to grant `global_manager` because by definition no one has
admin powers yet. Use:

```bash
php artisan app:make-super --email=admin@yourdomain.com
```

If the email belongs to an existing account, the user is **promoted** to
`global_manager`. If the email is new, the command **creates** the account —
prompting for name + password (or accepts `--name=… --password=…`).

Once that user signs in they can:

- Manage ecclesiastical regions and **churches** at `/admin/churches`. When
  creating a church, the form also creates that church's **master user**
  (a `local_manager` bound to that church).
- Manage **administrators** at `/admin/users` for any church.

The master user can then sign in and create more `local_manager` admins for
**their** church only via the same `/admin/users` page (church + role are
pinned server-side).

## System-level dependencies

Two shell scripts under `scripts/` handle the bits that need `sudo` on a
fresh host. They're idempotent — running them twice is harmless.

```bash
# PHP upload limits (100M / 100M), php-fpm reload, storage/ + bootstrap/cache
# perms for the www-data Docker user.
sudo bash scripts/setup-host.sh

# Media pipeline: php8.4-imagick + imagemagick + ghostscript + ffmpeg, plus
# the ImageMagick policy patch that unlocks PDF rendering. After this lands,
# Spatie's Pdf and Video image generators produce first-page / first-frame
# thumbs for every PDF and video upload.
sudo bash scripts/install-media-deps.sh
```

The composer packages those generators depend on (`spatie/pdf-to-image`,
`php-ffmpeg/php-ffmpeg`) ride with the project — `composer install` is
enough on the application side.

## Suggested first-time deployment sequence

```bash
# 0. Configure .env (DB credentials, APP_KEY, MAIL_*, TINYMCE_API_KEY)

# 1. System dependencies (sudo)
sudo bash scripts/setup-host.sh
sudo bash scripts/install-media-deps.sh

# 2. Composer + Node deps
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. App caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Database
php artisan app:install

# 5. Promote the first super user
php artisan app:make-super --email=admin@yourdomain.com

# 6. Storage symlink (one-time)
php artisan storage:link

# 7. (Optional) backfill thumb conversions for any media already in the DB,
#     e.g. when adding the media stack to a host that already had uploads.
php artisan media-library:regenerate
```

After step 5 the super user can sign in at `/login` and start configuring
churches and master users from the admin UI.