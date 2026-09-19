<?php
defined( 'ABSPATH' ) || exit;

class UMP_Admin {

	/** Body classes on pages that already handle file drag-and-drop natively. */
	const NATIVE_DND_BODY_CLASSES = [
		'upload-php',
		'upload-new-php',
		'async-upload-php',
	];

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_bar_menu',        [ $this, 'admin_bar_button' ], 999 );
		add_action( 'admin_footer',          [ $this, 'render_modal' ] );
		add_action( 'wp_ajax_ump_install',   [ $this, 'ajax_install' ] );
		add_action( 'wp_ajax_ump_install_slug', [ $this, 'ajax_install_slug' ] );
	}

	// -------------------------------------------------------------------------
	// Enqueue
	// -------------------------------------------------------------------------

	public function enqueue(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		wp_enqueue_style(
			'ump-admin',
			UMP_URL . 'assets/css/ump-admin.css',
			[],
			UMP_VERSION
		);

		wp_enqueue_script(
			'ump-admin',
			UMP_URL . 'assets/js/ump-admin.js',
			[ 'jquery' ],
			UMP_VERSION,
			true
		);

		$settings = UMP_Settings::get();

		wp_localize_script( 'ump-admin', 'umpData', [
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'ump_install' ),
			'slugNonce'      => wp_create_nonce( 'ump_install_slug' ),
			'nativeDndPage'  => $this->is_native_dnd_page(),
			'autoActivate'   => (bool) $settings['auto_activate'],
			'i18n'           => [
				'dropTitle'    => __( 'Drop plugin ZIPs here', 'upload-multiple-plugins' ),
				'dropSub'      => __( 'or click to browse', 'upload-multiple-plugins' ),
				'uploading'    => __( 'Uploading…', 'upload-multiple-plugins' ),
				'installing'   => __( 'Installing…', 'upload-multiple-plugins' ),
				'installed'    => __( 'Installed', 'upload-multiple-plugins' ),
				'activated'    => __( 'Installed & Activated', 'upload-multiple-plugins' ),
				'skipped'      => __( 'Skipped', 'upload-multiple-plugins' ),
				'error'        => __( 'Error', 'upload-multiple-plugins' ),
				'onlyZip'      => __( 'Only .zip files are accepted.', 'upload-multiple-plugins' ),
				'noFiles'      => __( 'No valid ZIP files detected.', 'upload-multiple-plugins' ),
				'noPlugins'    => __( 'Enter at least one plugin slug.', 'upload-multiple-plugins' ),
				'globalDrop'   => __( 'Drop plugin ZIPs to install', 'upload-multiple-plugins' ),
			],
		] );
	}

	// -------------------------------------------------------------------------
	// Admin bar button
	// -------------------------------------------------------------------------

	public function admin_bar_button( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! current_user_can( 'activate_plugins' ) || ! is_admin() ) {
			return;
		}

		$wp_admin_bar->add_node( [
			'id'    => 'ump-upload',
			'title' => '<span class="ab-icon dashicons dashicons-upload" aria-hidden="true"></span>'
				           . '<span class="ab-label">' . esc_html__( 'Install Plugins', 'upload-multiple-plugins' ) . '</span>',
			'href'  => '#',
			'meta'  => [
				'class' => 'ump-adminbar-btn',
				'title' => __( 'Install plugins from ZIP files or WordPress.org slugs', 'upload-multiple-plugins' ),
			],
		] );
	}

	// -------------------------------------------------------------------------
	// Modal HTML (injected once in admin_footer)
	// -------------------------------------------------------------------------

	public function render_modal(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div id="ump-modal" class="ump-modal" role="dialog" aria-modal="true"
		     aria-label="<?php esc_attr_e( 'Install Plugins', 'upload-multiple-plugins' ); ?>"
		     hidden>
			<div class="ump-modal-backdrop" tabindex="-1"></div>
			<div class="ump-modal-box">
				<button class="ump-modal-close" aria-label="<?php esc_attr_e( 'Close', 'upload-multiple-plugins' ); ?>">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</button>
				<h2 class="ump-modal-title">
					<span class="dashicons dashicons-upload" aria-hidden="true"></span>
					<?php esc_html_e( 'Install Plugins', 'upload-multiple-plugins' ); ?>
				</h2>

				<div class="ump-drop-zone" id="ump-drop-zone" tabindex="0"
				     role="button"
				     aria-label="<?php esc_attr_e( 'Drag and drop ZIP files here, or press Enter to browse', 'upload-multiple-plugins' ); ?>">
					<span class="dashicons dashicons-upload ump-drop-icon" aria-hidden="true"></span>
					<p class="ump-drop-title"><?php esc_html_e( 'Drop plugin ZIPs here', 'upload-multiple-plugins' ); ?></p>
					<p class="ump-drop-sub"><?php esc_html_e( 'or click to browse', 'upload-multiple-plugins' ); ?></p>
					<input type="file" id="ump-file-input" accept=".zip" multiple hidden>
				</div>

				<div class="ump-bulk-install">
					<h3 class="ump-bulk-title"><?php esc_html_e( 'Install from WordPress.org', 'upload-multiple-plugins' ); ?></h3>
					<p class="ump-bulk-help">
						<?php esc_html_e( 'Enter one plugin per line. Use slug@version for a specific release, -a to activate, or -n to keep inactive.', 'upload-multiple-plugins' ); ?>
					</p>
					<textarea id="ump-plugin-list" class="ump-plugin-list" rows="5"
						placeholder="elementor -a&#10;akismet -a&#10;woocommerce@9.8.1 -a&#10;wordpress-seo@25.4 -a"
						aria-label="<?php esc_attr_e( 'Plugin slugs, one per line', 'upload-multiple-plugins' ); ?>"></textarea>
					<div class="ump-bulk-actions">
						<label class="ump-activate-default">
							<input type="checkbox" id="ump-activate-default" <?php checked( UMP_Settings::get()['auto_activate'] ); ?>>
							<?php esc_html_e( 'Activate entries without a flag', 'upload-multiple-plugins' ); ?>
						</label>
						<button type="button" class="button button-primary" id="ump-install-list">
							<?php esc_html_e( 'Install Plugins', 'upload-multiple-plugins' ); ?>
						</button>
					</div>
				</div>

				<ul class="ump-file-list" id="ump-file-list" aria-live="polite" aria-label="<?php esc_attr_e( 'Installation results', 'upload-multiple-plugins' ); ?>"></ul>
			</div>
		</div>

		<div id="ump-global-overlay" class="ump-global-overlay" aria-hidden="true" hidden>
			<div class="ump-global-overlay-inner">
				<span class="dashicons dashicons-upload" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Drop plugin ZIPs to install', 'upload-multiple-plugins' ); ?></p>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// AJAX handler
	// -------------------------------------------------------------------------

	public function ajax_install(): void {
		check_ajax_referer( 'ump_install', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'upload-multiple-plugins' ) ] );
		}

		if ( empty( $_FILES['ump_plugin'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file received.', 'upload-multiple-plugins' ) ] );
		}

		$file = $_FILES['ump_plugin']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		// Validate upload.
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			wp_send_json_error( [ 'message' => $this->upload_error_message( (int) $file['error'] ) ] );
		}

		$original_name = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : 'plugin.zip';

		// Verify it's a ZIP by extension and MIME.
		$ext = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
		if ( 'zip' !== $ext ) {
			wp_send_json_error( [ 'message' => __( 'Only .zip files are accepted.', 'upload-multiple-plugins' ) ] );
		}

		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->file( $file['tmp_name'] );
		$allowed_mimes = [ 'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream' ];
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			wp_send_json_error( [ 'message' => __( 'File does not appear to be a valid ZIP.', 'upload-multiple-plugins' ) ] );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$result = UMP_Installer::install( $file['tmp_name'], $original_name );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Install one WordPress.org plugin specification from the bulk queue.
	 */
	public function ajax_install_slug(): void {
		check_ajax_referer( 'ump_install_slug', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'upload-multiple-plugins' ) ] );
		}

		if ( empty( $_POST['spec'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No plugin slug received.', 'upload-multiple-plugins' ) ] );
		}

		$spec             = sanitize_text_field( wp_unslash( $_POST['spec'] ) );
		$default_activate = ! empty( $_POST['default_activate'] );
		$plugins          = UMP_Installer::parse_plugin_list( $spec, $default_activate );

		if ( is_wp_error( $plugins ) ) {
			wp_send_json_error( [ 'message' => $plugins->get_error_message() ] );
		}

		$plugin = $plugins[0];
		$result = UMP_Installer::install_from_slug( $plugin['slug'], $plugin['version'], $plugin['activate'] );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function is_native_dnd_page(): bool {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		$body_class = $screen->id;
		foreach ( self::NATIVE_DND_BODY_CLASSES as $cls ) {
			// WordPress sets screen IDs like 'upload', 'async-upload', etc.
			if ( false !== strpos( $body_class, str_replace( '-php', '', $cls ) ) ) {
				return true;
			}
		}
		return false;
	}

	private function upload_error_message( int $code ): string {
		$messages = [
			UPLOAD_ERR_INI_SIZE   => __( 'File exceeds the server upload_max_filesize.', 'upload-multiple-plugins' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'File exceeds the form MAX_FILE_SIZE.', 'upload-multiple-plugins' ),
			UPLOAD_ERR_PARTIAL    => __( 'File was only partially uploaded.', 'upload-multiple-plugins' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'upload-multiple-plugins' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Temporary upload directory is missing.', 'upload-multiple-plugins' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'upload-multiple-plugins' ),
			UPLOAD_ERR_EXTENSION  => __( 'Upload was stopped by a PHP extension.', 'upload-multiple-plugins' ),
		];
		return $messages[ $code ] ?? __( 'Unknown upload error.', 'upload-multiple-plugins' );
	}
}
