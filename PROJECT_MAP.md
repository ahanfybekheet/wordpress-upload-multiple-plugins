# PROJECT_MAP.md
> Single source of architectural truth. Keep synchronized with every non-trivial change.
> Last updated: 2026-05-13 | Version: 1.0.0

---

## [PROJECT_OVERVIEW]

**Purpose:** WordPress admin plugin for drag-and-drop batch installation and activation of multiple plugins via `.zip` upload — without navigating to the native upload page.

**Core business goals:**
- Reduce friction when seeding development/staging environments with many plugins.
- Allow sequential multi-file upload with per-file real-time status feedback.
- Configurable auto-activation and overwrite protection.

**Scope boundaries (intentionally excluded):**
- ❌ Plugin update/rollback — install only, not upgrade workflow.
- ❌ Remote URL installation — local ZIP files only.
- ❌ Theme installation — plugins only.
- ❌ Front-end usage — admin-only surface.
- ❌ Multisite network activation — single-site scope.
- ❌ Non-`direct` filesystem methods (FTP/SSH) — WP installs requiring credentials are unsupported by design.

---

## [TECH_STACK]

**Runtime requirements:**

| Dependency | Minimum | Notes |
|---|---|---|
| PHP | 7.0 | Uses `ZipArchive`, typed returns |
| WordPress | 5.0 | Uses `WP_Filesystem`, `wp_ajax_*`, Settings API |
| PHP `zip` extension | any | Required for `ZipArchive`; `UMP_Installer::validate_zip()` hard-fails without it |
| jQuery | bundled (WP) | JS depends on `wp_enqueue_script` dependency chain |

**No Composer/NPM dependencies.** Pure PHP + vanilla WP APIs + jQuery.

**External services/integrations:** None. Fully self-contained.

**Deprecated/rejected technologies:**

| Rejected | Reason |
|---|---|
| `plupload` / `wp.Plupload` | Native WP uploader; overkill for a plain AJAX endpoint; conflicts with existing upload pages |
| WP Upgrader skin UI | Provides its own visual chrome — incompatible with custom modal UX |
| FTP/SSH filesystem | Requires credential prompt; complexity not justified for a dev-tool plugin |

---

## [SYSTEM_FLOW]

### Install request lifecycle

```
User action (drag-drop OR admin-bar button click)
  │
  ▼
openModal()                         [ump-admin.js]
  │
  ▼
handleFiles(fileList)               [ump-admin.js]
  └─ filters .zip, pushes to queue[]
  │
  ▼
processQueue() → uploadFile(file)   [ump-admin.js]
  └─ sequential (one file at a time)
  │
  ▼
POST admin-ajax.php                 [AJAX: action=ump_install]
  │ FormData: {action, nonce, ump_plugin: File}
  ▼
UMP_Admin::ajax_install()           [class-ump-admin.php]
  ├─ verify_nonce('ump_install')
  ├─ current_user_can('activate_plugins')
  ├─ validate $_FILES['ump_plugin'] error code
  ├─ validate .zip extension
  └─ validate MIME type (whitelist of 4)
  │
  ▼
UMP_Installer::install($zip, $name) [class-ump-installer.php]
  ├─ validate_zip()
  │   ├─ ZipArchive open
  │   ├─ traversal check
  │   ├─ single root dir check
  │   └─ "Plugin Name:" header detection
  ├─ UMP_Settings::get() → read auto_activate, preserve_existing
  ├─ [if preserve_existing] → return {skipped:true}
  ├─ init_filesystem() → WP_Filesystem('direct')
  ├─ unzip_file() → WP_PLUGIN_DIR/{folder}
  └─ [if auto_activate] activate_plugin()
  │
  ▼
JSON response → updateFileItem()    [ump-admin.js]
  └─ status: success | skipped | error
  │
  ▼
processQueue() → next file in queue
```

### Global drag-drop overlay (not on native DnD pages)

```
document dragenter (zip file detected)
  └─ show #ump-global-overlay (fullscreen visual cue)
document drop
  └─ hide overlay → openModal() → handleFiles()
document dragleave (dragCounter === 0)
  └─ hide overlay
```

**No background jobs, queues, or cron.** All processing is synchronous within a single AJAX request per file.

---

## [ARCHITECTURE]

### Directory structure

```
upload-multiple-plugins/
├── upload-multiple-plugins.php   # Entry point: constants, ump_init(), plugins_loaded hook
├── uninstall.php                 # Cleanup: deletes ump_settings option on plugin deletion
├── includes/
│   ├── class-ump-admin.php       # Hooks: enqueue, admin bar, modal HTML, AJAX handler
│   ├── class-ump-installer.php   # Pure logic: ZIP validation + WP install/activate (all static)
│   └── class-ump-settings.php   # Settings API: DB read/write, settings page UI
└── assets/
    ├── css/ump-admin.css         # All .ump-* styles, animations, RTL support
    └── js/ump-admin.js           # Modal, DnD, queue, AJAX, DOM updates
```

### Module boundaries

| Module | Responsibility | Dependencies |
|---|---|---|
| `UMP_Admin` | UI surface, enqueue, AJAX entry | `UMP_Settings::get()`, `UMP_Installer::install()` |
| `UMP_Installer` | File validation + installation logic | `UMP_Settings::get()`, WP core filesystem/plugin APIs |
| `UMP_Settings` | Config persistence + settings page | WP Settings API, `get_option()` |
| `ump-admin.js` | Client UI, DnD, sequential queue, AJAX | `umpData` (localized), jQuery |

**`UMP_Installer` has no WordPress UI dependencies** — could be tested in isolation with WP test fixtures.

### State management

**Server-side:** Stateless per request. Settings read from DB on each AJAX call.

**Client-side (JS):**

| Variable | Type | Purpose |
|---|---|---|
| `queue` | `File[]` | Pending files to upload |
| `processing` | `boolean` | Mutex lock — prevents parallel uploads |
| `dragCounter` | `number` | Tracks nested dragenter/dragleave to avoid flicker |

### Database / storage

| Option | Type | Default | Location |
|---|---|---|---|
| `ump_settings` | `array` | `{auto_activate: true, preserve_existing: false}` | `wp_options` |

Written via WP Settings API (`register_setting`, `sanitize` callback). Deleted on uninstall.

No custom tables. No transients. No object cache.

### Security boundaries

| Layer | Mechanism |
|---|---|
| Capability | All UI + AJAX gates on `current_user_can('activate_plugins')` |
| Nonce | `ump_install` nonce on every AJAX request |
| File type | `.zip` extension + MIME whitelist (`application/zip`, `application/x-zip`, `application/x-zip-compressed`, `application/octet-stream`) |
| ZIP integrity | `ZipArchive` open check + single root dir + no traversal paths + Plugin Name header required |
| Filesystem | Requires `FS_METHOD = direct`; fails safely if not |

**Trust zones:**
- PHP server-side code is the trust boundary — JS is untrusted input; all validation re-done in `ajax_install()`.
- ZIP contents are untrusted until `validate_zip()` passes all checks.

### Logging / error handling

- AJAX errors return structured `WP_Error` or `wp_send_json_error()` with a message string.
- PHP upload errors mapped to user strings in `UMP_Admin::upload_error_message()`.
- No server-side logging — errors surface to the UI only.
- `WP_Error` codes used in `validate_zip()`: `no_ziparchive`, `invalid_zip`, `traversal`, `empty_zip`, `multiple_roots`, `no_plugin_header`.

---

## [DECISIONS]

| # | Decision | Rationale | Rejected alternative |
|---|---|---|---|
| D-1 | Sequential upload queue (one file at a time) | Avoids race conditions on `WP_PLUGIN_DIR`, predictable status reporting | Parallel AJAX — risk of concurrent writes to same plugin folder |
| D-2 | All-static `UMP_Installer` | Logic has no shared mutable state; easier to call from AJAX without instantiation overhead | Instance class — no benefit for stateless install operation |
| D-3 | `dragCounter` for global overlay | Browsers fire nested `dragenter`/`dragleave` on child elements; counter prevents flicker | Boolean toggle — unreliable with nested DOM elements |
| D-4 | Detect native DnD pages via body class | WP upload pages (`upload-php`, `async-upload-php`) already handle drops; our overlay must yield | Blacklisting by URL — fragile against permalink changes |
| D-5 | `FS_METHOD = direct` requirement | Avoids credential prompt UI complexity; plugin is explicitly for dev environments | Full filesystem abstraction — adds UI scope beyond plugin purpose |
| D-6 | Single `ump_settings` option (array) | One DB read for all config; Settings API handles field registration cleanly | Separate options per setting — unnecessary `get_option` calls |
| D-7 | Modal injected in `admin_footer` | Ensures modal is always available regardless of current admin page | Page-specific injection — limits admin-bar button to specific pages |

---

## [MILESTONES]

### M-1: Core install flow ✅ COMPLETE
- [x] ZIP MIME + extension validation in AJAX handler
- [x] `ZipArchive` structural validation (traversal, single root, plugin header)
- [x] `WP_Filesystem` direct extraction to `WP_PLUGIN_DIR`
- [x] Optional `activate_plugin()` call

### M-2: Admin UI ✅ COMPLETE
- [x] Modal renders on all admin pages via `admin_footer`
- [x] Admin bar button opens modal
- [x] Drop zone + click-to-browse file input
- [x] Global drag overlay (suppressed on native DnD pages)
- [x] Per-file progress bar with real-time XHR progress events
- [x] Status states: uploading / success / skipped / error

### M-3: Settings ✅ COMPLETE
- [x] `auto_activate` toggle (default: on)
- [x] `preserve_existing` toggle (default: off)
- [x] Settings page under Plugins menu
- [x] Settings persisted to `wp_options`, cleaned on uninstall

### M-4: Hardening & polish ✅ COMPLETE
- [x] Nonce + capability checks on AJAX
- [x] RTL CSS support
- [x] Keyboard accessibility (Escape, Enter/Space on drop zone, aria attributes)
- [x] i18n-ready strings via `wp_localize_script`

### M-5: Future (not started)
- [ ] Multisite compatibility audit
- [ ] Unit tests for `UMP_Installer::validate_zip()` (PHPUnit + WP_Mock)
- [ ] JS tests for queue/upload logic (Jest or QUnit)
- [ ] `.pot` file generation for translations

---

## [ORPHANS & PENDING]

| # | Item | Type | Notes |
|---|---|---|---|
| O-1 | No automated tests | Tech debt | All validation logic is manually testable; no test harness exists |
| O-2 | `FS_METHOD = direct` assumption | Risky assumption | Hosting environments using FTP filesystem silently fail at `init_filesystem()` — no user-facing explanation beyond generic error |
| O-3 | MIME check uses `finfo` implicitly via WP | Assumption | WP's `wp_check_filetype_and_ext()` behavior varies; current code validates MIME manually — confirm it covers all server configs |
| O-4 | No `.pot` / translation files | Missing | Strings are i18n-ready in PHP but no `languages/` directory; no `load_plugin_textdomain()` call in `ump_init()` |
| O-5 | Max upload size not surfaced to user | Missing | If PHP `upload_max_filesize` or `post_max_size` is too low, the AJAX request fails silently or with a generic error |
| O-6 | `preserve_existing` checks folder name only | Limitation | Checks `WP_PLUGIN_DIR/{folder}` existence; does not compare plugin versions — a newer ZIP for an existing plugin is silently skipped |
| O-7 | No progress indication for extraction step | UX gap | Progress bar reaches 100% on upload complete, but `unzip_file()` + `activate_plugin()` happen after — user sees no feedback during that gap |
