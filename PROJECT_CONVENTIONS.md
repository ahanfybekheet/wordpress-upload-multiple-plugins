# PROJECT_CONVENTIONS.md
> Binding rules for all contributors and AI sessions. Instructions only — no analysis.
> Last updated: 2026-05-13

---

## PHP

1. **Indentation:** Hard tabs. Never spaces.
2. **Arrays:** Short syntax `[]` only. Never `array()`.
3. **Braces:** Opening brace on the same line as the declaration. Closing brace on its own line.
4. **Short tags:** Never. Only `<?php`.
5. **Requires:** `require_once` for class files.
6. **Class naming:** `UMP_PascalCase`. All plugin classes prefixed `UMP_`.
7. **Method naming:** `snake_case`. No camelCase PHP methods.
8. **Variable naming:** `$snake_case`.
9. **Constants:** `SCREAMING_SNAKE_CASE`. Class constants prefixed contextually; top-level constants prefixed `UMP_`. Magic numbers must be extracted as named class constants.
10. **Array keys:** lowercase, single-quoted strings — `'auto_activate'`, `'plugin_file'`.
11. **Visibility:** Explicit `public` or `private` on every class method. No `protected`. No omissions.
12. **Return types:** Declare `: void`, `: bool`, `: array`, `: string` on all class methods and top-level plugin functions.
    - **Exception — PHP 7.0 union types:** `UMP_Installer::validate_zip()` returns `array|WP_Error`. Union return types require PHP 8.0. Until the minimum is raised, use PHPDoc `@return` only and omit the signature type.
13. **Parameter types:** Declare where unambiguous.
    - **Exception — Settings API sanitize:** `UMP_Settings::sanitize()` receives raw `$_POST` input. Omit the type hint for WP Settings API sanitize callbacks only.
14. **Static methods:** Only when the method has zero shared mutable state. `UMP_Installer` is the reference.
15. **Method ordering in classes:** Constructor first → public methods in call order → private helpers last.
16. **Error propagation:** `WP_Error` for all recoverable failures. Codes must be `snake_case` strings. Never throw PHP exceptions.
17. **AJAX handler order:** nonce (`check_ajax_referer`) → capability (`current_user_can`) → input validation → delegate → `wp_send_json_success/error`. Never skip or reorder.
18. **Hook registration (instance methods):** `[ $this, 'method_name' ]`. No string callbacks for class methods.
19. **Hook registration (standalone functions):** String `'function_name'`.
20. **Nonce naming:** Nonce name must match the AJAX action name exactly.
21. **Settings access:** Always via `UMP_Settings::get()`. Never call `get_option()` directly outside `UMP_Settings`. `get()` uses `static $cache` — one DB read per request.
22. **AJAX responses:** `wp_send_json_success( $data )` / `wp_send_json_error( $data )` exclusively. Never `echo` raw JSON.
23. **Permission denied:** `wp_die()` on capability failure inside settings page callbacks.
24. **Uninstall cleanup:** `delete_option()` in `uninstall.php`. No class instantiation in that file.
25. **Comments:** PHPDoc on all public class methods. Section dividers (`// ---`) between method groups inside a class. Inline comments for non-obvious WHY only — never describe what the next line obviously does.

### WP_Error codes in use

| Code | Condition |
|---|---|
| `no_ziparchive` | PHP ZipArchive extension missing |
| `invalid_zip` | ZipArchive cannot open the file |
| `traversal` | ZIP entry path contains `..`, `./`, `:\`, or leading `/` |
| `empty_zip` | ZIP contains no entries |
| `multiple_roots` | ZIP contains more than one root directory |
| `no_plugin_header` | No PHP file with `Plugin Name:` header found at root depth |

---

## JavaScript

1. **Module:** All code inside `( function ( $ ) { 'use strict'; ... } )( jQuery );` IIFE. No actual globals.
2. **Variables:** `var` only. No `let` or `const`.
3. **Functions:** Regular `function` declarations. No arrow functions.
4. **Quotes:** Single quotes `'...'` everywhere. No double quotes, no template literals.
5. **String building:** `+` concatenation only. No template literals.
6. **Semicolons:** Required on all statements.
7. **jQuery refs:** Cache all jQuery-wrapped objects into `$camelCase` variables at `document.ready`. Plain DOM/data variables are unprefixed `camelCase`. Never re-query the DOM inside event handlers.
8. **jQuery AJAX:** `$.ajax()` with explicit `type`, `processData`, `contentType`. No `$.post()` shorthand for file uploads.
9. **File top:** Must begin with `/* global umpData, jQuery */`.
10. **State variables:** Declared at top of IIFE, before `document.ready`.
11. **Error handling:** Early return on invalid state. No `try/catch`. Surface errors via `updateFileItem()` or `addStatusMessage()`.
12. **Section headers:** Between logical function groups:
    ```js
    // -----------------------------------
    // Section Name
    // -----------------------------------
    ```

---

## CSS

1. **Indentation:** Hard tabs.
2. **Braces:** Opening brace on the same line as the selector. Closing brace on its own line.
3. **Selectors:** Class-only. No ID selectors inside component styles.
4. **Prefix:** All custom classes use `ump-` prefix.
5. **BEM modifiers:** State variants use double-dash — `.ump-file-item--{state}`.
6. **Property ordering:** position → display/flex → sizing → background/border → typography → effects/animation.
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
        font-size: 14px;
        animation: umpFadeIn 0.15s ease;
    }
    ```
7. **Section headers:**
    ```css
    /* ----------------------------- Section Name ------------------------------ */
    ```
8. **Colors:** Use only the established palette. No ad-hoc values.

    | Role | Hex |
    |---|---|
    | Primary blue | `#0073aa` |
    | Success green | `#00a32a` |
    | Warning amber | `#dba617` |
    | Error red | `#d63638` |
    | Body text | `#1d2327` |
    | Muted text | `#646970` |
    | Border | `#c3c4c7` |

9. **Animations:** Name with `ump` prefix — `umpFadeIn`, `umpBounce`, `umpSlideIn`.
10. **RTL overrides:** Grouped in a single RTL section at file bottom.
11. **Responsive sizing:** Use `min()` and `calc()` inline. No media queries for component sizing.

---

## Naming Reference

| Target | Convention | Example |
|---|---|---|
| PHP class | `UMP_PascalCase` | `UMP_Admin`, `UMP_Installer` |
| PHP method | `snake_case` | `ajax_install()`, `init_filesystem()` |
| PHP constant | `SCREAMING_SNAKE_CASE` | `UMP_VERSION`, `PLUGIN_HEADER_READ_BYTES` |
| PHP variable | `$snake_case` | `$zip_path`, `$plugin_folder` |
| PHP array key | lowercase, single-quoted | `'auto_activate'`, `'success'` |
| WP option | `ump_` + `snake_case` | `'ump_settings'` |
| WP AJAX action | `ump_` + `snake_case` | `'ump_install'` |
| WP nonce | matches AJAX action | `'ump_install'` |
| WP error code | `snake_case` | `'no_plugin_header'` |
| Menu/page slug | `kebab-case` | `'upload-multiple-plugins'` |
| Text domain | `kebab-case` | `'upload-multiple-plugins'` |
| JS variable | `camelCase` | `queue`, `dragCounter` |
| JS jQuery ref | `$camelCase` | `$modal`, `$dropZone` |
| JS function | `camelCase` | `handleFiles()`, `uploadFile()` |
| JS localized object | `camelCase` | `umpData` |
| CSS class | `ump-` + `kebab-case` | `.ump-modal`, `.ump-drop-zone` |
| CSS BEM modifier | `--state` | `.ump-file-item--error` |
| HTML element ID | `ump-` + `kebab-case` | `#ump-modal`, `#ump-file-input` |
| CSS animation name | `ump` + `PascalCase` | `umpFadeIn`, `umpBounce` |

---

## Architecture

1. **Layer rule — never mix:**
    - `UMP_Admin` — hook registration + I/O (enqueue, render, AJAX entry).
    - `UMP_Installer` — install logic only. All methods static.
    - `UMP_Settings` — config persistence + settings page UI.
2. **Constructors:** Hook registration only. No logic.
3. **Dependency direction:** `UMP_Admin` → `UMP_Installer` and `UMP_Settings`. `UMP_Installer` → `UMP_Settings`. `UMP_Settings` → nothing. No circular deps.
4. **New PHP classes:** `includes/class-ump-{name}.php`. One class per file. Add `require_once` in entry point.
5. **New frontend files:** `assets/{css|js}/ump-{context}.{ext}`. Enqueue in `UMP_Admin::enqueue()`.
6. **No external dependencies.** WP core APIs + ZipArchive only. No Composer, no npm.

---

## File Organization

```
{plugin-slug}/
├── {plugin-slug}.php           # Constants + require_once chain + plugins_loaded hook
├── uninstall.php               # delete_option() only — no class instantiation
├── includes/
│   └── class-ump-{name}.php   # One class per file
└── assets/
    ├── css/ump-{context}.css
    └── js/ump-{context}.js
```

---

## Git

1. Imperative verb, sentence case, ≤ 72 characters, no trailing period.
2. No conventional commits prefix (`feat:`, `fix:`, `docs:`).
3. Commit body optional — use only for non-obvious rationale.

```
Add UMP_Installer: ZIP validation and WP filesystem extraction
Fix ajax_install: reject non-ZIP MIME types before unzip
Update ump-admin.js: fix dragCounter underflow on fast drags
```
