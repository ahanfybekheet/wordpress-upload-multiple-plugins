<?php
defined( 'ABSPATH' ) || exit;

class UMP_Installer {

	/** Bytes read from a candidate PHP file to detect the "Plugin Name:" header. */
	const PLUGIN_HEADER_READ_BYTES = 8192;

	/**
	 * Validate a ZIP file for safe plugin structure.
	 *
	 * @param string $zip_path Absolute path to the uploaded ZIP.
	 * @return array{folder:string,plugin_file:string}|WP_Error
	 */
	public static function validate_zip( string $zip_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'no_ziparchive', __( 'ZipArchive extension is not available on this server.', 'upload-multiple-plugins' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return new WP_Error( 'invalid_zip', __( 'The uploaded file is not a valid ZIP archive.', 'upload-multiple-plugins' ) );
		}

		$root_dirs    = [];
		$plugin_files = [];

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex( $i );
			if ( false === $stat ) {
				continue;
			}
			$name = $stat['name'];

			// Prevent directory traversal and absolute paths.
			if (
				false !== strpos( $name, '..' ) ||
				false !== strpos( $name, './' ) ||
				false !== strpos( $name, ':\\' ) ||
				0 === strpos( $name, '/' )
			) {
				$zip->close();
				return new WP_Error( 'traversal', __( 'ZIP contains potentially unsafe file paths.', 'upload-multiple-plugins' ) );
			}

			// Skip Mac metadata.
			if ( 0 === strpos( $name, '__MACOSX' ) || '.DS_Store' === basename( $name ) ) {
				continue;
			}

			$parts = array_values( array_filter( explode( '/', $name ) ) );
			if ( empty( $parts[0] ) ) {
				continue;
			}

			$root_dirs[ $parts[0] ] = true;

			// Main plugin file: sits directly inside the root folder (depth == 2).
			if ( count( $parts ) === 2 && 'php' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
				// Read leading bytes to check for Plugin Name header.
				$content = $zip->getFromIndex( $i, self::PLUGIN_HEADER_READ_BYTES );
				if ( $content && false !== strpos( $content, 'Plugin Name:' ) ) {
					$plugin_files[] = $name;
				}
			}
		}

		$zip->close();

		if ( empty( $root_dirs ) ) {
			return new WP_Error( 'empty_zip', __( 'ZIP file appears to be empty.', 'upload-multiple-plugins' ) );
		}

		if ( count( $root_dirs ) > 1 ) {
			return new WP_Error(
				'multiple_roots',
				__( 'ZIP contains multiple root directories. Please package one plugin per ZIP.', 'upload-multiple-plugins' )
			);
		}

		if ( empty( $plugin_files ) ) {
			return new WP_Error(
				'no_plugin_header',
				__( 'No valid WordPress plugin file found. The main PHP file must contain a "Plugin Name:" header.', 'upload-multiple-plugins' )
			);
		}

		return [
			'folder'      => array_key_first( $root_dirs ),
			'plugin_file' => $plugin_files[0],
		];
	}

	/**
	 * Install (and optionally activate) a plugin from a validated ZIP path.
	 *
	 * @param string $zip_path     Absolute path to uploaded ZIP.
	 * @param string $original_name Original filename for error messages.
	 * @return array{success:bool,message:string,installed:bool,activated:bool,skipped:bool}
	 */
	public static function install( string $zip_path, string $original_name ): array {
		$result = [
			'success'   => false,
			'message'   => '',
			'installed' => false,
			'activated' => false,
			'skipped'   => false,
		];

		// Validate ZIP contents first.
		$validation = self::validate_zip( $zip_path );
		if ( is_wp_error( $validation ) ) {
			$result['message'] = $validation->get_error_message();
			return $result;
		}

		$plugin_folder = $validation['folder'];
		$plugin_file   = $validation['plugin_file']; // e.g. "my-plugin/my-plugin.php"

		$settings         = UMP_Settings::get();
		$auto_activate    = $settings['auto_activate'];
		$preserve         = $settings['preserve_existing'];
		$destination_dir  = WP_PLUGIN_DIR . '/' . $plugin_folder;

		// Honour preserve_existing setting.
		if ( $preserve && is_dir( $destination_dir ) ) {
			$result['success'] = true;
			$result['skipped'] = true;
			$result['message'] = sprintf(
				/* translators: %s plugin folder name */
				__( '"%s" already installed — skipped (preserve existing is enabled).', 'upload-multiple-plugins' ),
				$plugin_folder
			);
			return $result;
		}

		// Initialise WP_Filesystem (requires direct access in non-interactive context).
		if ( ! self::init_filesystem() ) {
			$result['message'] = __( 'Cannot initialise WordPress filesystem. Ensure direct filesystem access is available.', 'upload-multiple-plugins' );
			return $result;
		}

		// Extract ZIP to plugins directory.
		$unzip = unzip_file( $zip_path, WP_PLUGIN_DIR );
		if ( is_wp_error( $unzip ) ) {
			$result['message'] = sprintf(
				/* translators: %s WP_Error message */
				__( 'Extraction failed: %s', 'upload-multiple-plugins' ),
				$unzip->get_error_message()
			);
			return $result;
		}

		$result['installed'] = true;

		// Activate if configured.
		if ( $auto_activate ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$activate = activate_plugin( $plugin_file );
			if ( is_wp_error( $activate ) ) {
				$result['success'] = true;
				$result['message'] = sprintf(
					/* translators: %s WP_Error message */
					__( 'Installed but activation failed: %s', 'upload-multiple-plugins' ),
					$activate->get_error_message()
				);
				return $result;
			}
			$result['activated'] = true;
		}

		$result['success'] = true;
		$result['message'] = $auto_activate
			? __( 'Installed and activated.', 'upload-multiple-plugins' )
			: __( 'Installed (not activated).', 'upload-multiple-plugins' );

		return $result;
	}

	// -------------------------------------------------------------------------

	private static function init_filesystem(): bool {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$method = get_filesystem_method( [], WP_PLUGIN_DIR );
		if ( 'direct' !== $method ) {
			return false;
		}

		$creds = request_filesystem_credentials( '', $method, false, WP_PLUGIN_DIR, [], false );
		return (bool) WP_Filesystem( $creds );
	}
}
