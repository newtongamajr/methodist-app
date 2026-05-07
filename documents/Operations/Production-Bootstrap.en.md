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

## Suggested first-time deployment sequence

```bash
# 0. Configure .env (DB credentials, APP_KEY, MAIL_*, TINYMCE_API_KEY)

# 1. Composer + Node deps
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. App caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Database
php artisan app:install

# 4. Promote the first super user
php artisan app:make-super --email=admin@yourdomain.com

# 5. Storage symlink (one-time)
php artisan storage:link
```

After step 4 the super user can sign in at `/login` and start configuring
churches and master users from the admin UI.