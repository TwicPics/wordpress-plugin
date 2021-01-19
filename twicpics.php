<?php
/**
 * Real Time Responsive Images Plugin for WordPress by TwicPics
 *
 * @package          TwicPics
 * @link             https://www.twicpics.com/
 * @version          0.1.0
 * @license          gpl-2.0
 *
 * @wordpress-plugin
 * Plugin Name:      TwicPics
 * Plugin URI:       https://www.twicpics.com/wordpress/
 * Description:      Delivers pixel perfect images on the fly.
 * Version:          0.1.0
 * Licence URI:      http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Author:           Studio Cassette
 * Author URI:       https://www.studiocassette.com/
 * Domain Path:      /languages
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * Plugin initialisation
 */
function twicpics_init() {
	include 'class-twicpics.php';
	$twicpics = new TwicPics();

	include 'class-twicpics-admin.php';
	$twicpics_admin = new TwicPics_Admin();
}

add_action( 'wp_loaded', 'twicpics_init' );
