<?php
defined( 'ABSPATH' ) || exit;

class UMP_Settings {

	const OPTION_KEY = 'ump_settings';

	public static function defaults(): array {
		return [
			'auto_activate'     => true,
			'preserve_existing' => false,
		];
	}

	public static function get(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		return array_merge( self::defaults(), (array) $saved );
	}

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_menu(): void {
		add_plugins_page(
			__( 'Upload Multiple Plugins – Settings', 'upload-multiple-plugins' ),
			__( 'Upload Multiple', 'upload-multiple-plugins' ),
			'activate_plugins',
			'upload-multiple-plugins',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			'ump_settings_group',
			self::OPTION_KEY,
			[
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => self::defaults(),
			]
		);

		add_settings_section(
			'ump_main',
			__( 'Installation Behavior', 'upload-multiple-plugins' ),
			'__return_false',
			'upload-multiple-plugins'
		);

		add_settings_field(
			'auto_activate',
			__( 'Auto-Activate', 'upload-multiple-plugins' ),
			[ $this, 'field_auto_activate' ],
			'upload-multiple-plugins',
			'ump_main'
		);

		add_settings_field(
			'preserve_existing',
			__( 'Preserve Existing', 'upload-multiple-plugins' ),
			[ $this, 'field_preserve_existing' ],
			'upload-multiple-plugins',
			'ump_main'
		);
	}

	public function sanitize( $input ): array {
		return [
			'auto_activate'     => ! empty( $input['auto_activate'] ),
			'preserve_existing' => ! empty( $input['preserve_existing'] ),
		];
	}

	public function field_auto_activate(): void {
		$settings = self::get();
		?>
		<label>
			<input type="checkbox" name="ump_settings[auto_activate]" value="1"
				<?php checked( $settings['auto_activate'] ); ?> />
			<?php esc_html_e( 'Automatically activate plugins after installation', 'upload-multiple-plugins' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, each plugin is activated immediately after it is installed.', 'upload-multiple-plugins' ); ?>
		</p>
		<?php
	}

	public function field_preserve_existing(): void {
		$settings = self::get();
		?>
		<label>
			<input type="checkbox" name="ump_settings[preserve_existing]" value="1"
				<?php checked( $settings['preserve_existing'] ); ?> />
			<?php esc_html_e( 'Skip installation if plugin already exists (preserve current version)', 'upload-multiple-plugins' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When disabled (default), uploading a plugin will overwrite any existing installation.', 'upload-multiple-plugins' ); ?>
		</p>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'upload-multiple-plugins' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Upload Multiple Plugins – Settings', 'upload-multiple-plugins' ); ?></h1>

			<?php settings_errors( 'ump_settings_group' ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'ump_settings_group' );
				do_settings_sections( 'upload-multiple-plugins' );
				submit_button();
				?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'How to Use', 'upload-multiple-plugins' ); ?></h2>
			<ul style="list-style:disc;padding-left:1.5em;">
				<li><?php esc_html_e( 'Drag and drop one or more plugin ZIP files anywhere on the WordPress admin.', 'upload-multiple-plugins' ); ?></li>
				<li><?php esc_html_e( 'On media upload pages, use the "Upload Plugins" button in the admin bar instead.', 'upload-multiple-plugins' ); ?></li>
				<li><?php esc_html_e( 'Each plugin is processed and reported individually.', 'upload-multiple-plugins' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
