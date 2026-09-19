<?php

$ump_test_root = sys_get_temp_dir() . '/ump-slug-installer-' . uniqid();
mkdir( $ump_test_root . '/wordpress/wp-admin/includes', 0777, true );
mkdir( $ump_test_root . '/plugins', 0777, true );
touch( $ump_test_root . '/wordpress/wp-admin/includes/file.php' );
touch( $ump_test_root . '/wordpress/wp-admin/includes/plugin.php' );
touch( $ump_test_root . '/wordpress/wp-admin/includes/plugin-install.php' );

define( 'ABSPATH', $ump_test_root . '/wordpress/' );
define( 'WP_PLUGIN_DIR', $ump_test_root . '/plugins' );

$ump_requested_urls = [];
$ump_activations    = [];
$ump_deleted_files  = [];
$ump_package_path   = $ump_test_root . '/package.zip';

$zip = new ZipArchive();
$zip->open( $ump_package_path, ZipArchive::CREATE );
$zip->addFromString( 'sample-plugin/sample-plugin.php', "<?php\n/* Plugin Name: Sample Plugin */\n" );
$zip->close();

function __( string $message, string $domain ): string {
	return $message;
}

class WP_Error {
	private $message;

	public function __construct( string $code, string $message ) {
		$this->message = $message;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

class UMP_Settings {
	public static function get(): array {
		return [
			'auto_activate'     => true,
			'preserve_existing' => false,
		];
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function plugins_api( string $action, array $args ) {
	return (object) [
		'version'       => '2.0',
		'download_link' => 'https://downloads.wordpress.org/plugin/sample-plugin.2.0.zip',
		'versions'      => [
			'1.0' => 'https://downloads.wordpress.org/plugin/sample-plugin.1.0.zip',
		],
	];
}

function download_url( string $url ) {
	global $ump_requested_urls, $ump_package_path, $ump_test_root;
	$ump_requested_urls[] = $url;
	$temporary_file       = tempnam( $ump_test_root, 'download-' );
	copy( $ump_package_path, $temporary_file );
	return $temporary_file;
}

function wp_delete_file( string $path ): void {
	global $ump_deleted_files;
	$ump_deleted_files[] = $path;
	unlink( $path );
}

function get_filesystem_method( array $args, string $context ): string {
	return 'direct';
}

function request_filesystem_credentials( string $url, string $method, bool $error, string $context, array $fields, bool $allow_relaxed ): bool {
	return true;
}

function WP_Filesystem( $credentials ): bool {
	return true;
}

function unzip_file( string $zip_path, string $destination ) {
	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path ) ) {
		return new WP_Error( 'unzip_failed', 'Could not open ZIP.' );
	}
	$zip->extractTo( $destination );
	$zip->close();
	return true;
}

function activate_plugin( string $plugin_file ) {
	global $ump_activations;
	$ump_activations[] = $plugin_file;
	return null;
}

function ump_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function ump_remove_test_tree( string $path ): void {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $iterator as $item ) {
		$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}
	rmdir( $path );
}

require_once dirname( __DIR__ ) . '/includes/class-ump-installer.php';

$latest = UMP_Installer::install_from_slug( 'sample-plugin', '', true );
ump_assert_same( true, $latest['success'], 'Latest plugin installation should succeed.' );
ump_assert_same( true, $latest['activated'], 'Latest plugin should use its activation choice.' );
ump_assert_same( 'https://downloads.wordpress.org/plugin/sample-plugin.2.0.zip', $ump_requested_urls[0], 'An omitted version should use the latest package.' );

$versioned = UMP_Installer::install_from_slug( 'sample-plugin', '1.0', false );
ump_assert_same( true, $versioned['success'], 'Versioned plugin installation should succeed.' );
ump_assert_same( false, $versioned['activated'], 'Versioned plugin should respect disabled activation.' );
ump_assert_same( 'https://downloads.wordpress.org/plugin/sample-plugin.1.0.zip', $ump_requested_urls[1], 'A requested version should use its matching package.' );
ump_assert_same( [ 'sample-plugin/sample-plugin.php' ], $ump_activations, 'Only the activated entry should invoke activate_plugin().' );
ump_assert_same( 2, count( $ump_deleted_files ), 'Every downloaded temporary file should be deleted.' );

$missing = UMP_Installer::install_from_slug( 'sample-plugin', '3.0', true );
ump_assert_same( false, $missing['success'], 'An unavailable plugin version should fail.' );
ump_assert_same( 2, count( $ump_requested_urls ), 'An unavailable version should not start a download.' );

ump_remove_test_tree( $ump_test_root );
echo "Slug installer tests passed.\n";
