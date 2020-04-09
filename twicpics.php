<?php
/**
 * Plugin Name: Twicpics - Responsive Images as a Service
 * Plugin URI: https://www.twicpics.com/
 * Description: The best responsive image service plugin.
 * Version: 1.0
 * Author: Studio Cassette
 * Author URI: https://www.studiocassette.com/
 * Text Domain: twicpics
 * Domain Path: languages
 * */

defined( 'ABSPATH' ) || die( 'ERROR !' );

function twicpics_init(){
  include 'front_class.php';
  $twicpics = new TwicPics();
  
  include 'back_class.php';
  $twicpics_admin = new TwicPics_admin();
}
add_action('wp_loaded','twicpics_init');
