<?php
/**
 * Real Time Responsive Images Plugin for WordPress by TwicPics
 *
 * @package          TwicPics
 * @link             https://www.twicpics.com/
 * @version          0.3.0
 * @license          gpl-2.0
 *
 * @wordpress-plugin
 * Plugin Name:      TwicPics
 * Plugin URI:       https://www.twicpics.com/documentation/
 * Description:      Delivers pixel perfect images on the fly.
 * Version:          0.3.0
 * License:          GPL-2.0+
 * Licence URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 * Author:           TwicPics
 * Author URI:       https://www.twicpics.com/
 * Text Domain:      twicpics
 * Domain Path:      /languages
 */

defined( 'ABSPATH' ) || die( 'ERROR !' );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/' . ( is_admin() ? 'twicpics-admin.php' : 'twicpics-plugin.php' );
