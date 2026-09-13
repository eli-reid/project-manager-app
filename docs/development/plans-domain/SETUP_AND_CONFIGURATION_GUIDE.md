# Plans Domain - Setup & Configuration Guide

This guide details the complete end-to-end setup, environment configuration, system requirements, queue setup, and rollout instructions for the **Plans** bounded domain (`app/Domains/Plans`).

---

## 1. System Requirements & PDF Rasterizer Engines

The Plans processing pipeline splits uploaded multi-page PDF drawing sets and renders high-resolution page previews and thumbnails. This requires a server-side rasterizer engine.

The domain provides a driver abstraction (`PlanRasterizerContract`) supporting two production drivers:

| Driver | Best Suited For | Requirements |
|---|---|---|
| **Poppler** (`poppler`) *(Recommended)* | High-performance environments, CLI-based rendering | `poppler-utils` (`pdftoppm`, `pdfinfo`) |
| **Imagick** (`imagick`) | Traditional PHP-FPM servers with ImageMagick | `php-imagick` extension + Ghostscript (`gs`) + permissive `policy.xml` |

---

### Option A: Setting up Poppler (Recommended)

#### Linux (Ubuntu / Debian):
```bash
sudo apt-get update
sudo apt-get install -y poppler-utils
```

#### Linux (RHEL / AlmaLinux / Rocky Linux):
```bash
sudo dnf install -y poppler-utils
```

#### Windows Development (Laravel Herd / Local):
1. Download a pre-built Windows release of Poppler (e.g. from GitHub releases: `poppler-windows`).
2. Extract the archive (e.g. `C:\poppler\Library\bin`).
3. Set the binary directory path in your `.env` or settings:
   ```dotenv
   PLANS_POPPLER_BIN_PATH="C:\\poppler\\Library\\bin"
   ```

---

### Option B: Setting up Imagick & Ghostscript

#### Step 1: Install PHP Extension & Ghostscript (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install -y php8.4-imagick ghostscript
```
*(On RHEL/Alma: `sudo dnf install -y php-pecl-imagick ghostscript`)*

#### Step 2: Verify Extension is Active in PHP
```bash
php -m | grep imagick
```

#### Step 3: Enable PDF Delegate in ImageMagick's Security Policy
By default, ImageMagick disables reading PDF files via Ghostscript for security reasons.

1. Open `/etc/ImageMagick-6/policy.xml` (or `/etc/ImageMagick/policy.xml`):
   ```bash
   sudo nano /etc/ImageMagick-6/policy.xml
   ```
2. Find the line:
   ```xml
   <policy domain="coder" rights="none" pattern="PDF" />
   ```
3. Change `rights="none"` to `rights="read"` (or `rights="read|write"`):
   ```xml
   <policy domain="coder" rights="read" pattern="PDF" />
   ```
4. Save the file and restart PHP-FPM:
   ```bash
   sudo systemctl restart php8.4-fpm
   ```

---

### Verifying Rasterizer Setup

Run the built-in diagnostic command:
```bash
php artisan plans:rasterizer-check
```

Expected output when configured:
```text
+---------+-----------+-----------+
| Driver  | Status    | Selection |
+---------+-----------+-----------+
| poppler | available | selected  |
| imagick | available |           |
+---------+-----------+-----------+
Configured rasterizer driver [poppler] is available.
```

---

## 2. Environment Configuration & Settings

Add the following environment variables to `.env` as needed:

```dotenv
# Feature Toggle (defaults to false for dark launch)
PLANS_ENABLED=true

# Rasterizer Driver ('poppler' or 'imagick')
PLANS_RASTERIZER_DRIVER=poppler

# Poppler binary directory (leave empty if binaries are in system $PATH)
PLANS_POPPLER_BIN_PATH=""

# Render DPI (default: 150)
PLANS_RENDER_DPI=150

# Thumbnail and Preview Dimensions
PLANS_THUMBNAIL_WIDTH=320
PLANS_PREVIEW_WIDTH=2000

# Derivative Format ('webp' or 'png')
PLANS_DERIVATIVE_FORMAT=webp

# Private Storage Disk (default: 'local' => storage/app/private)
PLANS_STORAGE_DISK=local

# Limits & Ingestion
PLANS_MAX_PAGES_PER_SET=500
PLANS_MAX_UPLOAD_KILOBYTES=512000
```

> **Note:** All settings can also be dynamically adjusted via the Settings UI in the Admin Dashboard under **Settings > Plans**.

---

## 3. Database Migrations

Run database migrations to create the Plans domain schema:

```bash
php artisan migrate
```

This creates seven ULID-backed tables:
1. `plan_sets` - Uploaded PDF drawing sets, status tracking, page counts.
2. `plan_sheets` - Stable sheet identities (e.g., `A-101`) per project.
3. `plan_sheet_revisions` - Rendered page versions, derivative paths, OCR/text layer data.
4. `plan_annotations` - Vector markup overlays in normalized `0..1` coordinates.
5. `plan_annotation_comments` - Threaded discussion comments on markup.
6. `plan_links` - Hotspot callout links linking details to target plan sheets.
7. `plan_view_states` - Per-user persisted zoom and viewport coordinates per sheet.

---

## 4. User Permissions

The Plans domain registers ten granular permissions through `PlanPermissions`:

| Permission | Description |
|---|---|
| `plans.view` | View project plans and sheet index |
| `plans.upload` | Upload new PDF drawing sets |
| `plans.update` | Edit sheet metadata, numbers, and titles |
| `plans.delete` | Delete plan sets or sheets |
| `plans.publish-revision` | Promote/demote revisions to/from `current` |
| `plans.annotate` | Create and resolve personal markup |
| `plans.manage-annotations` | Edit or delete other users' annotations |
| `plans.manage-links` | Create and confirm hotspot navigation links |
| `plans.compare` | Compare two revisions/sheets side-by-side or difference overlay |
| `plans.export` | Export PDF sheets with burned-in annotations |

Assign these permissions to appropriate roles via **Admin > Roles & Permissions** or programmatically in seeders.

---

## 5. Queue & Worker Configuration

Rasterizing large drawings is CPU and memory intensive.

### Queue Timeout Configuration
In `config/queue.php`, ensure the `retry_after` setting for your queue connection exceeds the 300s job timeout to prevent double-processing:

```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 360, // Must be > 300s
        'after_commit' => false,
    ],
],
```

### Running Workers
In development:
```bash
php artisan queue:listen
```

In production with Supervisor:
```ini
[program:project-manager-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/project-manager/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/project-manager/storage/logs/worker.log
```

---

## 6. Project Tab Cutover

The repository originally contained a placeholder `PlansTab` registered by `DocumentsServiceProvider`. To cut over to the new dedicated Plans domain:

1. In `app/Domains/Documents/Providers/DocumentsServiceProvider.php`:
   Remove `PlansProjectTab::class` from `registerProjectTabs()`.
2. In `app/Domains/Plans/Providers/PlansServiceProvider.php`:
   Register `PlansProjectTab::class` with `ProjectTabRegistry`.
3. Set `PLANS_ENABLED=true` in `.env`.
4. Clear route and config caches:
   ```bash
   php artisan optimize:clear
   ```

---

## 7. Storage Maintenance & Derivative Pruning

Rendered derivatives are stored in private disk storage:
`plans/{project_id}/{plan_set_id}/{page_number}/...`

To clean up orphaned derivatives or temporary files:
```bash
php artisan plans:prune-derivatives
```
*(This command is also scheduled to run automatically in production).*

---

## 8. Troubleshooting & Common Issues

### Issue: `Configured rasterizer driver [imagick] is unavailable`
- **Cause:** PHP does not have the `imagick` extension loaded or the PDF coder policy is blocked.
- **Fix:** Check `php -m | grep imagick` and ensure `/etc/ImageMagick-6/policy.xml` has `<policy domain="coder" rights="read" pattern="PDF" />`.

### Issue: `Configured rasterizer driver [poppler] is unavailable`
- **Cause:** `pdftoppm` and `pdfinfo` binaries are not found in the system `$PATH`.
- **Fix:** Install `poppler-utils` or set `PLANS_POPPLER_BIN_PATH=/path/to/bin` in `.env`.

### Issue: Set status is stuck in `rendering`
- **Cause:** Queue worker is not running, or worker crashed due to PHP memory limits.
- **Fix:** Ensure queue workers are active (`php artisan queue:listen`), check `storage/logs/laravel.log`, and ensure PHP CLI `memory_limit` is at least `512M` for heavy blueprint processing.
