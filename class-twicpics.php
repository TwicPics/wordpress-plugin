<?php
/**
 * TwicPics plugin core class
 *
 * @package TwicPics
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The class for TwicPics plugin front-end
 */
class TwicPics {
	/**
	 * Set required image optimization mode
	 */
	public function __construct() {

		$options = get_option( 'twicpics_options' );

		if ( ! isset( $options['user_domain'] ) || empty( $options['user_domain'] ) ) {
			return;
		}
		if ( ! isset( $options['optimization_level'] ) ) {
			$options['optimization_level'] = 'script';
		}
		if ( ! isset( $options['max_width'] ) || empty( $options['max_width'] ) ) {
			$options['max_width'] = 2000;
		}
		if ( ! isset( $options['step'] ) || empty( $options['step'] ) ) {
			$options['step'] = 10;
		}
		if ( ! isset( $options['placeholder_type'] ) ) {
			$options['placeholder_type'] = 'blank';
		}

		if ( isset( $options['optimization_level'] ) ) {
			if ( 'api' === $options['optimization_level'] ) {
				include 'class-twicpics-api.php';
				$twicpics_api = new TwicPicsApi( $options );
			} else {
				include 'class-twicpics-script.php';
				$twicpics_script = new TwicPicsScript( $options );
			}
		}

		load_plugin_textdomain( 'twicpics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}
