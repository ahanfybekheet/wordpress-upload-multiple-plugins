<?php

define( 'ABSPATH', __DIR__ . '/' );

function __( string $message, string $domain ): string {
	return $message;
}

class WP_Error {
	private $code;
	private $message;

	public function __construct( string $code, string $message ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-ump-installer.php';

function ump_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

$parsed = UMP_Installer::parse_plugin_list(
	"elementor -a\nakismet\nwoocommerce@9.8.1 --activate\nwordpress-seo@25.4 -n",
	true
);

ump_assert_same(
	[
		[ 'slug' => 'elementor', 'version' => '', 'activate' => true ],
		[ 'slug' => 'akismet', 'version' => '', 'activate' => true ],
		[ 'slug' => 'woocommerce', 'version' => '9.8.1', 'activate' => true ],
		[ 'slug' => 'wordpress-seo', 'version' => '25.4', 'activate' => false ],
	],
	$parsed,
	'Valid plugin list was not parsed correctly.'
);

$parsed = UMP_Installer::parse_plugin_list( "akismet\n\nelementor", false );
ump_assert_same( false, $parsed[0]['activate'], 'Default activation should be configurable.' );
ump_assert_same( false, $parsed[1]['activate'], 'Default activation should apply to every unflagged entry.' );

$invalid_slug = UMP_Installer::parse_plugin_list( 'Bad Slug -a', false );
ump_assert_same( 'invalid_plugin_spec', $invalid_slug->get_error_code(), 'Invalid slugs should be rejected.' );

$invalid_version = UMP_Installer::parse_plugin_list( 'akismet@latest!', false );
ump_assert_same( 'invalid_plugin_spec', $invalid_version->get_error_code(), 'Invalid versions should be rejected.' );

$invalid_flag = UMP_Installer::parse_plugin_list( 'akismet --maybe', false );
ump_assert_same( 'invalid_plugin_spec', $invalid_flag->get_error_code(), 'Unknown flags should be rejected.' );

echo "Plugin list parser tests passed.\n";
