# PROJECT_CONVENTIONS.md
> Behavioral specification derived from direct codebase analysis.
> Generated: 2026-05-13 | Last updated: 2026-05-13 | Commits analyzed: 2 | Files analyzed: 7

**Evidence key:**
- ✅ Observed — confirmed pattern, repeated consistently
- ⚠️ Inferred — likely intentional but limited sample
- ❌ Anti-pattern — present but inconsistent or problematic
- 🔲 Absent — not used at all

---

## [CODE_STYLE]

### Indentation

**Observed Standard:** Hard tabs (`\t`) across all file types — PHP, CSS, JS. No spaces used for indentation anywhere. 1 tab = 1 nesting level, no exceptions observed.

**Inconsistencies Detected:** None.

**Recommended Canonical Style:** Hard tabs. Never mix.

---

### Brace Style

**Observed Standard (all languages):** Opening brace on the same line as the declaration. Closing brace on its own line.

```php
// PHP — same-line brace, consistent across class, method, control structure
class UMP_Admin {
public function enqueue(): void {
    if ( ! current_user_can( 'activate_plugins' ) ) {
```

```js
// JS — same-line brace
function uploadFile( file, callback ) {
    $.ajax( {
        success: function ( response ) {
```

```css
/* CSS — same-line brace */
.ump-modal-box {
    position: relative;
```

**Inconsistencies Detected:** None.

**Recommended Canonical Style:** Same-line opening brace universally.

---

### Naming Conventions

| Target | Convention | Example |
|---|---|---|
| PHP class | `PascalCase` with `_` separator | `UMP_Admin`, `UMP_Installer`, `UMP_Settings` |
| PHP method (public/private) | `snake_case` | `ajax_install()`, `init_filesystem()` |
| PHP constant | `SCREAMING_SNAKE_CASE` | `UMP_VERSION`, `OPTION_KEY`, `NATIVE_DND_BODY_CLASSES` |
| PHP variable | `$snake_case` | `$zip_path`, `$plugin_file`, `$original_name` |
| PHP loop variable | short or descriptive `$snake_case` | `$i`, `$stat`, `$root_dirs` |
| Array keys (PHP) | lowercase, single-quoted | `'auto_activate'`, `'plugin_file'`, `'success'` |
| WP option name | `ump_` prefix + `snake_case` | `'ump_settings'` |
| WP hook name | WordPress native `snake_case` | `'wp_ajax_ump_install'`, `'admin_enqueue_scripts'` |
| WP nonce | matches AJAX action | `'ump_install'` (nonce) ↔ `'ump_install'` (action) |
| WP error code | `snake_case` | `'no_ziparchive'`, `'multiple_roots'`, `'no_plugin_header'` |
| Settings group | `ump_settings_group` | `register_setting( 'ump_settings_group', ... )` |
| Menu slug | `kebab-case` | `'upload-multiple-plugins'` |
| Text domain | `kebab-case` (matches plugin slug) | `'upload-multiple-plugins'` |
| JS variable | `camelCase` | `queue`, `processing`, `dragCounter`, `zipFiles` |
| JS jQuery ref | `$camelCase` ($ prefix) | `$modal`, `$dropZone`, `$fileList` |
| JS function | `camelCase` | `bindDropZone()`, `handleFiles()`, `uploadFile()` |
| JS localized object | `camelCase` | `umpData` |
| CSS class | `ump-` prefix + `kebab-case` | `.ump-modal`, `.ump-drop-zone`, `.ump-file-list` |
| CSS BEM modifier | double-dash `--state` | `.ump-file-item--uploading`, `.ump-file-item--error` |
| HTML element ID | `ump-` prefix + `kebab-case` | `#ump-modal`, `#ump-drop-zone`, `#ump-file-input` |

**Inconsistencies Detected:**
- ✅ **CODIFIED** — JS `$` prefix for jQuery-wrapped objects is now an explicit canonical rule (§JavaScript rule 7). Plain DOM/data variables remain unprefixed camelCase.

**Recommended Canonical Style:** Follow table above exactly. `ump_` prefix for all WP options/actions/nonces. `ump-` prefix for all CSS/HTML identifiers.

---

### PHP-Specific Patterns

**Observed Standard:**

| Feature | Usage |
|---|---|
| Array syntax | Short `[]` exclusively. `array()` never used. |
| Type hints (parameters) | Used on class methods: `string $zip_path`, `WP_Admin_Bar $wp_admin_bar` |
| Return types | Declared on all class methods: `: void`, `: array`, `: bool`, `: string` |
| Visibility | Explicit `public` / `private` on all class methods. No `protected`. |
| Static | Used only in `UMP_Installer` (all-static class). `UMP_Admin` and `UMP_Settings` are instance-based. |
| Short tags | Never used. Only `<?php`. |
| `echo` vs `print` | Neither used inline; HTML rendered directly without `echo` in heredoc-style modal output. |
| Require style | `require_once` for class files in entry point. |

**Inconsistencies Detected:**
- ✅ **RESOLVED** — `ump_init()` now declares `: void` return type, consistent with all class methods.

**Recommended Canonical Style:** All class methods and top-level plugin functions must declare visibility (class methods), parameter types where unambiguous, and return types. Short `[]` only.

> **PHP 7.0 union type constraint:** `UMP_Installer::validate_zip()` returns `array|WP_Error`. PHP 7.0 does not support union return types (added in PHP 8.0). The return type is therefore documented via PHPDoc only (`@return array{...}|WP_Error`) and omitted from the function signature. This is the sole permitted exception. If the minimum PHP version is raised to 8.0, the signature must be updated to `: array|WP_Error`.

> **Settings API sanitize exception:** `UMP_Settings::sanitize( $input )` intentionally omits a parameter type hint. The WP Settings API passes unvalidated `$_POST` data; the actual type is mixed and cannot be declared in PHP 7.x without `mixed` (PHP 8.0+). This exception is bounded to Settings API sanitize callbacks only.

---

### WordPress-Specific Patterns

**Observed Standard:**

| Pattern | Implementation |
|---|---|
| Hook registration (class methods) | Array syntax: `[ $this, 'method_name' ]` |
| Hook registration (standalone functions) | String: `'function_name'` |
| AJAX capability check | `current_user_can( 'activate_plugins' )` — checked first, before any processing |
| Nonce verification | `check_ajax_referer( 'action_name', 'nonce' )` immediately at AJAX handler entry |
| AJAX response | `wp_send_json_success( $data )` / `wp_send_json_error( $data )` exclusively |
| Uninstall cleanup | `delete_option()` in `uninstall.php` |
| Options reading | `get_option( 'ump_settings', [] )` with `array_merge()` against defaults |
| Filesystem | `WP_Filesystem()` with `'direct'` method check |
| Settings API | `register_setting()` + `add_settings_section()` + `add_settings_field()` |
| Permission denied | `wp_die()` on capability failure in settings page |

**Inconsistencies Detected:** None.

---

### JavaScript-Specific Patterns

**Observed Standard:**

| Feature | Usage |
|---|---|
| Module pattern | IIFE: `( function ( $ ) { 'use strict'; ... } )( jQuery );` |
| Variable declarations | `var` exclusively. No `let`, no `const`. |
| Functions | Regular `function` keyword. No arrow functions. |
| String quotes | Single quotes `'...'` everywhere. |
| String concatenation | `+` operator. No template literals. |
| Semicolons | Present on all statements. |
| jQuery selectors | Cached into `$`-prefixed variables at ready. Not re-queried. |
| jQuery ready | `$( document ).ready( function () { ... } )` |
| jQuery AJAX | `$.ajax( { ... } )` with explicit `type: 'POST'` |
| jQuery chaining | Multi-line: `$item\n    .removeClass()\n    .addClass()` |
| File upload | `FormData` + `processData: false, contentType: false` |
| XHR progress | `xhr.upload.addEventListener( 'progress', ... )` inside `xhr` factory |
| State | Module-level `var` — no global variables outside IIFE |
| Global comment | `/* global umpData, jQuery */` at file top |

**Inconsistencies Detected:**
- ✅ **ACCEPTED BY CANONICAL RULE** — `var` is ES5-era, but retained intentionally for WP's conservative jQuery environment. `const`/`let`/arrow functions are not introduced. See canonical rules §JavaScript rule 2–3.

**Recommended Canonical Style:** Maintain `var` + regular functions + single quotes. Do not introduce `const`/`let`/arrow functions without deliberate project-level decision.

---

### CSS-Specific Patterns

**Observed Standard:**

**Property ordering within rule blocks (position → display → sizing → color → typography → effects):**
```css
.example {
    position: fixed;
    inset: 0;
    z-index: 160100;
    display: flex;
    align-items: center;
    width: min( 560px, calc(100vw - 40px) );
    padding: 28px 28px 24px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 4px 24px rgba( 0,0,0,0.18 );
    font-size: 14px;
    animation: umpFadeIn 0.15s ease;
}
```

**Section headers:**
```css
/* ----------------------------- Section Name ------------------------------ */
```

**BEM modifier pattern:**
```css
.ump-file-item {}
.ump-file-item--uploading {}
.ump-file-item--success {}
.ump-file-item--skipped {}
.ump-file-item--error {}
```

**Shorthand:**
- `inset: 0` used (not 4× `top/right/bottom/left: 0`)
- `padding: 28px 28px 24px` (3-value shorthand)
- `transition: color 0.15s, background 0.15s`
- `font: 400 20px/1 dashicons` for dashicons

**No ID selectors.** Class-only targeting.

**RTL block at file bottom** — `.rtl .ump-*` selectors grouped in single section.

**No media queries** — responsive sizing via `min()` + `calc()` inline.

**Color palette (all WP admin standard):**

| Variable | Hex |
|---|---|
| Primary blue | `#0073aa` |
| Success green | `#00a32a` |
| Warning amber | `#dba617` |
| Error red | `#d63638` |
| Body text | `#1d2327` |
| Muted text | `#646970` |
| Border | `#c3c4c7` |

**Inconsistencies Detected:** None.

---

### Comment Patterns

**PHP:**
```php
// PHPDoc on class-level constants and all class methods
/**
 * @param string $zip_path
 * @return array{folder:string,plugin_file:string}|WP_Error
 */

// Section dividers inside classes:
// -------------------------------------------------------------------------
// Method Group Name
// -------------------------------------------------------------------------

// Inline only when non-obvious (why, not what):
// Prevent directory traversal and absolute paths.
// Main plugin file: sits directly inside the root folder (depth == 2).
// Read first 8 KB to check for Plugin Name header.
```

**CSS:**
```css
/* ----------------------------- Section Name ------------------------------ */
```

**JS:**
```js
/* global umpData, jQuery */  // top of file

// -----------------------------------
// Section Name
// -----------------------------------

/** Single-line JSDoc for exported/utility functions */

// Inline for non-obvious constraints:
// can't read type on dragenter in all browsers
// allow re-selecting same file
```

**Recommended Canonical Style:** Comment the WHY. Skip comments that describe WHAT the next line obviously does.

---

## [ARCHITECTURAL_PATTERNS]

### Dominant Patterns

**Layer separation (strictly observed):**

```
Entry Point (upload-multiple-plugins.php)
  └─ Admin Surface / Hook Registration (UMP_Admin)
       ├─ Settings Read (UMP_Settings::get) — stateless call
       └─ Install Logic (UMP_Installer::install) — stateless call
            └─ Settings Read (UMP_Settings::get) — same stateless call
```

- ✅ All hooks registered in constructors; no loose `add_action` calls outside class context.
- ✅ Logic layer (`UMP_Installer`) is fully static — zero shared mutable state.
- ✅ UI layer (`UMP_Admin`) is instance-based, wires hooks, delegates to logic layer.
- ✅ Config layer (`UMP_Settings`) is the only class with both static reads and instance hooks.
- ✅ `uninstall.php` is isolated — no class instantiation, only `delete_option()`.

**Dependency flow:**
```
UMP_Admin → UMP_Installer
UMP_Admin → UMP_Settings::get()
UMP_Installer → UMP_Settings::get()
UMP_Settings → (none — only WP APIs)
```

No circular dependencies. Settings is always a leaf.

**AJAX handler pattern:**
1. Nonce check first (`check_ajax_referer`)
2. Capability check second (`current_user_can`)
3. Input validation third (`$_FILES`, extension, MIME)
4. Delegate to logic class
5. Return structured JSON

**Client-side state machine:**
```
idle → [file selected/dropped] → queued → uploading → success|skipped|error → idle (next file)
```
Sequential. One file at a time. `processing` flag as mutex.

### Anti-Patterns — Resolved

- ✅ **RESOLVED** — `UMP_Settings::get()` now uses `static $cache` to memoize the DB read within a single request. All callers (`UMP_Admin::enqueue()`, `UMP_Admin::ajax_install()` via `UMP_Installer`, `UMP_Settings::field_*`) share one `get_option()` call per request.
- ✅ **RESOLVED** — Magic number `8192` extracted as `UMP_Installer::PLUGIN_HEADER_READ_BYTES = 8192`. All references updated.
- ⚠️ **ACCEPTED** — `// phpcs:ignore WordPress.Security.ValidatedSanitizedInput` on `$_FILES` access in `UMP_Admin::ajax_install()`. WP has no sanitization helper for raw file upload arrays; the suppression is bounded to a single line and is the accepted WP pattern. Not a defect.

### Stability Assessment

- Architecture: **STABLE** — clear layer separation, no hidden coupling, easy to extend without touching existing classes.
- JS module: **STABLE** — IIFE scope, no globals, clear state variables.

---

## [GIT_CONVENTIONS]

### Observed Commit History

| SHA | Message |
|---|---|
| `dcb2136` | `Add PROJECT_MAP.md: architectural documentation and single source of truth` |
| `199bb18` | `Initial commit` |

**Sample size: 2 commits. Patterns are inferred, not confirmed dominant.**

### Observed Format

| Aspect | Observed |
|---|---|
| Tense | Imperative present (`Add`, `Initial`) |
| Format | Free-form prose, no prefix/type tags |
| Scope | Not scoped (no `feat:`, `fix:`, `docs:`) |
| Ticket refs | None |
| Co-authors | None |
| Length | Subject only, no body |

**Consistency Level:** LOW — insufficient history; one generic, one descriptive.

**Recommended Canonical Format (inferred intention, not confirmed):**

```
<Verb> <what>: <context if needed>

# Examples:
Add UMP_Installer: ZIP validation and WP filesystem extraction
Fix ajax_install: reject non-ZIP MIME types before unzip
Refactor UMP_Settings: extract defaults() as static method
Update ump-admin.js: fix dragCounter underflow on fast drags
```

Rules:
- Imperative verb, sentence case.
- Subject line ≤ 72 characters.
- No period at end.
- No conventional commits prefix (`feat:`, `fix:`) — not established.
- Body optional; use for non-obvious rationale only.

---

## [TESTING_CONVENTIONS]

**🔲 No tests exist.** No `tests/` directory, no `phpunit.xml`, no `package.json` with test scripts.

**Observed:** None.

**Gap:** `UMP_Installer::validate_zip()` is the highest-value unit test target — pure logic, no UI, deterministic inputs.

---

## [ERROR_HANDLING]

### PHP

**Observed Standard:**

| Layer | Pattern |
|---|---|
| Logic errors | `return new WP_Error( 'snake_case_code', 'Human message.' )` |
| Error detection | `is_wp_error( $result )` → `$result->get_error_message()` |
| AJAX errors | `wp_send_json_error( [ 'message' => '...' ] )` then `return` |
| AJAX success | `wp_send_json_success( $result )` — only after all validations pass |
| Permission denied | `wp_die()` on settings page capability failure |
| PHP upload errors | Mapped to strings in `upload_error_message( int $code ): string` |
| Filesystem fail | `init_filesystem()` returns `false`; caller checks and returns error |

**No `try/catch` blocks anywhere.** WP_Error is the sole error-propagation mechanism.

**No server-side logging.** All errors surface to user via AJAX JSON or UI.

**WP_Error codes used:**

| Code | Trigger |
|---|---|
| `no_ziparchive` | PHP ZipArchive extension missing |
| `invalid_zip` | ZipArchive can't open file |
| `traversal` | Path contains `..`, `./`, `:\`, or `/` prefix |
| `empty_zip` | ZIP has no files |
| `multiple_roots` | ZIP contains more than one root directory |
| `no_plugin_header` | No PHP file with `Plugin Name:` found at root depth |

### JavaScript

**Observed Standard:**
- No `try/catch`.
- Early return on invalid state: `if ( ! hasZipFiles( e ) ) return;`
- AJAX `.error` callback updates file item to `'error'` state.
- All error states produce visible UI feedback — no silent failures.

---

## [LOGGING_CONVENTIONS]

**🔲 No logging infrastructure exists.** No `error_log()`, no custom logger, no WP debug logging, no structured log output.

**Observed:** All operational feedback is user-facing (JSON response → JS UI update).

**Note:** Plugin is explicitly scoped as a dev-environment tool. Silent server errors are the only gap.

---

## [DEPENDENCY_STRATEGY]

**Observed Standard:**

| Aspect | Status |
|---|---|
| Composer | 🔲 Absent. No `composer.json`. |
| NPM / Node | 🔲 Absent. No `package.json`, no `node_modules`. |
| External libraries | None. Zero third-party dependencies. |
| PHP extension deps | `ZipArchive` (PHP `zip` extension) — hard fail with `WP_Error` if absent. |
| WP core coupling | High — uses WP Settings API, WP_Filesystem, WP_Error, AJAX API, plugin functions. Intentional. |
| jQuery | Bundled WP jQuery. Not imported separately. Script registered with `[ 'jquery' ]` dependency. |
| Framework coupling | Total WP coupling by design — this is a WP admin plugin, not a portable library. |
| Deprecated packages | None present. |

**Philosophy (inferred):** Zero external dependencies. Everything via WP core APIs. Maintainability over feature richness.

---

## [CONFIGURATION_PATTERNS]

**Observed Standard:**

| Aspect | Implementation |
|---|---|
| Plugin constants | Defined in entry point: `UMP_VERSION`, `UMP_FILE`, `UMP_DIR`, `UMP_URL` |
| Runtime config | Single `wp_options` row: `ump_settings` (`array`) |
| Config schema | Defined as static `UMP_Settings::defaults()` — merge-pattern on read |
| Config access | `UMP_Settings::get()` — always merges saved + defaults |
| Config write | WP Settings API (admin UI only) |
| Env vars | None used. |
| Feature flags | None. |
| Secrets | None. Plugin has no external API keys. |
| Config option naming | `ump_` prefix, `snake_case`, stored as array under single key |

**No `.env` file. No `wp-config.php` constants required beyond `FS_METHOD = 'direct'`** (documented as system requirement, not plugin config).

---

## [FILE_ORGANIZATION]

**Observed Standard:**

```
{plugin-slug}/
├── {plugin-slug}.php           # Entry point only: constants + require + bootstrap hook
├── uninstall.php               # Cleanup only: delete_option() calls
├── includes/                   # PHP class files
│   └── class-{class-name}.php  # One class per file; filename is WP convention snake_case
└── assets/                     # Frontend assets
    ├── css/
    │   └── {prefix}-{context}.css
    └── js/
        └── {prefix}-{context}.js
```

**File naming:**
- PHP classes: `class-ump-{name}.php` (WP coding standards convention)
- Assets: `ump-{context}.{ext}` (plugin prefix + context)

**File size tendencies:**

| File | Lines | Assessment |
|---|---|---|
| `class-ump-admin.php` | ~220 | Acceptable; manages one concern (admin surface) |
| `class-ump-installer.php` | ~200 | Acceptable; pure install logic |
| `class-ump-settings.php` | ~165 | Acceptable; settings + UI |
| `ump-admin.js` | ~315 | Slightly large; single-file acceptable for this scope |
| `ump-admin.css` | ~360 | Appropriate given full UI system |

**Co-location:** CSS and JS are co-located in `assets/` rather than per-component. Appropriate for a single-surface plugin.

**Module boundaries:**
- `includes/` = PHP logic/wiring only.
- `assets/` = frontend only. No PHP logic in assets.
- No `src/` or `build/` — no compile step.

**Fragmentation risks:** None at current scale. Would become an issue if admin surfaces multiply beyond 1-2 pages.

---

## [CONSISTENCY_SCORE]

| Dimension | Score | Justification |
|---|---|---|
| **Architecture** | HIGH | Clean layer separation, no circular deps, consistent hook registration pattern, clear entry point. Redundant DB read eliminated via static caching. |
| **Naming** | HIGH | Fully consistent prefix strategy (`ump_`/`ump-`), casing rules per language/context followed without exceptions. `$` prefix for jQuery refs now explicit. |
| **Commit** | LOW | Only 2 commits — insufficient to confirm a format. One is generic `Initial commit`. |
| **Testing** | LOW | No tests exist. Consistency is vacuously undefined. |
| **Operational** | HIGH | Error handling consistent in PHP (WP_Error + JSON). All detected anti-patterns resolved. Remaining `phpcs:ignore` is bounded and accepted. |

---

## [CANONICAL_RULES]

The following rules are binding for all contributors and AI sessions working in this repository.

### PHP

1. **Indentation:** Hard tabs. Never spaces.
2. **Arrays:** Short syntax `[]` only. Never `array()`.
3. **Class naming:** `UMP_PascalCase`. All classes prefixed `UMP_`.
4. **Method naming:** `snake_case`. No camelCase PHP methods.
5. **Constants:** `SCREAMING_SNAKE_CASE` inside classes. Top-level plugin constants prefixed `UMP_`. Magic numbers must be extracted as named class constants.
6. **Visibility:** Explicit `public` or `private` on all class methods. No omissions.
7. **Return types:** Declare `: void`, `: bool`, `: array`, `: string` on all class methods and top-level plugin functions. **Exception:** union return types (e.g., `array|WP_Error`) are not expressible in PHP 7.0 — use PHPDoc `@return` only until minimum PHP is raised to 8.0.
8. **Parameter types:** Declare where type is unambiguous. **Exception:** Settings API sanitize callbacks receive unvalidated `$_POST` input whose type cannot be declared in PHP 7.x — omit the hint only for WP Settings API sanitize callbacks.
9. **Static methods:** Only when the method has zero shared mutable state. `UMP_Installer` is the reference implementation.
10. **Error propagation:** Use `WP_Error` for all recoverable failures. Error codes must be `snake_case` strings. Never throw PHP exceptions.
11. **AJAX handler structure:** nonce → capability → input validation → delegate → `wp_send_json_success/error`. Never skip or reorder.
12. **Hook registration (methods):** `[ $this, 'method_name' ]` syntax. No string callbacks for instance methods.
13. **Nonce naming:** Match nonce name to AJAX action name exactly.
14. **WP options:** Single array option per plugin (`ump_settings`). Always read via `UMP_Settings::get()`, never `get_option()` directly. `get()` uses static caching — no redundant DB reads.
15. **Comments:** PHPDoc blocks on all public class methods. Section dividers inside classes. Inline comments only for non-obvious WHY.
16. **File naming:** `class-ump-{context}.php` in `includes/`. One class per file.

### JavaScript

1. **Module:** All code inside `( function ( $ ) { 'use strict'; ... } )( jQuery );` IIFE.
2. **Variables:** `var` only. No `let` or `const`.
3. **Functions:** Regular `function` declarations. No arrow functions.
4. **Quotes:** Single quotes `'...'` everywhere. No double quotes, no template literals.
5. **String building:** `+` concatenation. No template literals.
6. **Semicolons:** Required on all statements.
7. **jQuery refs:** Cache all jQuery-wrapped objects into `$camelCase` variables at `document.ready`. Plain DOM/data variables are unprefixed `camelCase`. Never re-query the DOM in event handlers.
8. **jQuery AJAX:** Use `$.ajax()` with explicit `type`, `processData`, `contentType`. No `$.post()` shorthand for file uploads.
9. **Global comment:** File must begin with `/* global umpData, jQuery */`.
10. **State:** All module state as top-level `var` inside IIFE. No actual globals.
11. **Error handling:** Early return on invalid conditions. No `try/catch`. Surface errors via `updateFileItem()` or `addStatusMessage()`.
12. **Section structure:** Use `// ----\n// Name\n// ----` section headers between logical function groups.

### CSS

1. **Indentation:** Hard tabs.
2. **Selectors:** Class-only. No ID selectors inside component styles.
3. **Prefix:** All custom classes use `ump-` prefix.
4. **BEM modifiers:** State variants use double-dash: `.ump-file-item--{state}`.
5. **Property ordering:** position → display/flex → sizing → background/border → typography → effects/animation.
6. **Section headers:** `/* ---- Section Name ---- */` between major component sections.
7. **Colors:** Use the established palette (`#0073aa`, `#00a32a`, `#dba617`, `#d63638`, `#1d2327`, `#646970`, `#c3c4c7`). No ad-hoc colors.
8. **Animations:** Name with `ump` prefix: `umpFadeIn`, `umpBounce`, `umpSlideIn`.
9. **RTL support:** All directional overrides in a single RTL section at file bottom.
10. **Responsive:** Use `min()` and `calc()` inline. No media queries for component sizing.

### Git

1. **Commit messages:** Imperative verb, sentence case, ≤ 72 characters. No period.
2. **No conventional commits prefix** (`feat:`, `fix:`, `docs:`) — not established.
3. **Body optional:** Use only for non-obvious rationale.
4. **No ticket references** — not established.

### Architecture

1. **Layer rule:** Admin class wires hooks and handles I/O. Installer class contains logic. Settings class handles persistence. Never mix.
2. **No logic in constructors** — only hook registration.
3. **Settings access:** Always via `UMP_Settings::get()`. Never direct `get_option()` outside `UMP_Settings`.
4. **AJAX responses:** Always `wp_send_json_success()` / `wp_send_json_error()`. Never `echo` raw JSON.
5. **No external dependencies** — WP core + ZipArchive only.
6. **New PHP classes:** Go in `includes/class-ump-{name}.php`, required in entry point.
7. **New frontend files:** Go in `assets/{css|js}/ump-{context}.{ext}`, enqueued in `UMP_Admin::enqueue()`.

---

*Base all conclusions on observed evidence above. When in doubt, check the source files — this document describes what exists, not what might be ideal.*
