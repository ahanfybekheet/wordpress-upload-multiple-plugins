# Upload Multiple Plugins

Fast drag-and-drop installation and activation of multiple WordPress plugins. Optimized for development and testing environments.

## Installation

1. Download `upload-multiple-plugins.zip`
2. Extract it to your WordPress `/wp-content/plugins/` directory
3. Go to **Plugins** in your WordPress admin and click **Activate**

## Usage

### Drag & Drop (Primary)
- Drag one or more plugin ZIP files anywhere in the WordPress admin dashboard
- A blue overlay appears as you drag—drop to install
- Files are processed sequentially; results appear in a modal

### Admin Bar Button (Fallback)
- Click the **Upload Plugins** button in the admin bar
- Opens a modal where you can drag-and-drop or click to browse

### Settings
Navigate to **Plugins > Upload Multiple** to configure:
- **Auto-Activate**: Enable/disable automatic activation after install (default: **ON**)
- **Preserve Existing**: Skip installation if plugin folder already exists (default: **OFF**)

## Requirements

- WordPress 5.0+
- PHP 7.0+
- `ZipArchive` extension
- Direct filesystem access (`FS_METHOD` = `'direct'`)

## Security

✓ Nonce-protected AJAX  
✓ Capability: `activate_plugins`  
✓ ZIP validation: prevents directory traversal, validates plugin structure  
✓ MIME type checking  
✓ Requires valid `Plugin Name:` header in main PHP file  

## How It Works

1. **Validation**: ZIP is scanned for safe structure before extraction
2. **Installation**: Contents extracted to `/wp-content/plugins/`
3. **Activation** (optional): Plugin activated if configured
4. **Feedback**: Per-file results shown with success/error status

---

**Note**: This plugin is designed for **development/testing**. In production, consider using proper plugin deployment tools like WP-CLI or automated CI/CD pipelines.
