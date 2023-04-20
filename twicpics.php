<?php
/**
 * Real Time Responsive Images Plugin for WordPress by TwicPics
 *
 * @package          TwicPics
 * @link             https://www.twicpics.com/
 * @version          0.2.4
 * @license          gpl-2.0
 *
 * @wordpress-plugin
 * Plugin Name:      TwicPics
 * Plugin URI:       https://www.twicpics.com/documentation/
 * Description:      Delivers pixel perfect images on the fly.
 * Version:          0.2.4
 * License:          GPL-2.0+
 * Licence URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 * Author:           TwicPics
 * Author URI:       https://www.twicpics.com/
 * Text Domain:      twicpics
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

add_action( 'wp_loaded', 'twicpics_init', PHP_INT_MAX );
