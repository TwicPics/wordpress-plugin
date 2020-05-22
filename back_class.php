<?php
defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The Class for TwicPics Admin Settings
 */
class TwicPics_admin {
  function __construct() {
    add_action( 'admin_init', array($this,'settings_init') );
    add_action( 'admin_menu', array($this,'options_page') );
    add_action( 'admin_print_scripts-twicpics', array($this, 'enqueue_admin_scripts'));

  }

  /**
   * Add the settings's menu under the upload section
   */
  public function options_page() {
   add_submenu_page( 'upload.php', __('TwicPics Options','twicpics'), 'TwicPics', 'manage_options', 'twicpics', array($this,'render_config_page'));
  }

  /**
   * Add scripts in config page header
   */
  public function enqueue_admin_scripts(){
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
  }

  /**
   * The callback for the settings page
   */
  public function render_config_page(){
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) return;

    // add error/update messages

    // check if the user have submitted the settings
    // wordpress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
      // add settings saved message with the class of "updated"
      add_settings_error( 'twicpics_messages', 'twicpics_message', __( 'Settings Saved', 'twicpics' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'twicpics_messages' );
  ?>
    <div class="wrap">
      <?php include 'assets/logo.svg.php'; ?>
      <form action="options.php" method="post">
        <?php
        settings_fields( 'twicpics' );
        do_settings_sections( 'twicpics' );
        submit_button( __( 'Save Settings', 'twicpics' ) );
      ?>
      </form>
    </div>
  <?php
  }

  /**
   * The settings configuration
   */
  public function settings_init() {
   register_setting( 'twicpics', 'twicpics_options' );

   add_settings_section( 'twicpics_section_identity', __( 'Identity', 'twicppics' ), array($this,'section_identity'), 'twicpics' );
    add_settings_field('twicpics_field_url', __('Custom Url','twicpics'), array($this,'field_textinput'), 'twicpics', 'twicpics_section_identity', array(
      'label_for' => 'url',
      'description'  => esc_html( __('Fill your twicpics url in this field', 'twicpics' ) )
    ));
  }

  /**
   * Callback for the lazyload section
   *
   * @param     array $args Display arguments.
   */
  public function section_identity($args){ ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php echo sprintf(
      __( 'In this section, you can set your registred TwicPics url. You can find it in your <a href="%1$s" target="_blank">account</a>', 'twicpics' ), "https://account.twicpics.com/login"
    ); ?></p>
  <?php
  }

  /**
   * Callback for the text input type fields
   *
   * @param     array $args Display/Values argurments
   */
  public function field_textinput($args){
    $options = get_option( 'twicpics_options' );
    echo '<input type="text" id="', esc_attr( $args['label_for'] ) ,'" name="twicpics_options[', esc_attr( $args['label_for'] ), ']" value="', ($options[ $args['label_for'] ]?:''),'" class="regular-text" />';
    ?>
    <p class="description"><?php echo $args['description']; ?></p>
    <?php
  }

  /**
   * Callback for the radios type fields
   *
   * @param     array $args Display/Values argurments
   */
  public function field_radios($args){
    $options = get_option( 'twicpics_options' );
    $types = $args['values'];
    foreach( $types as $label => $value ):
      echo '<label>
        <input type="radio" id="', esc_attr( $args['label_for'] ) ,'" name="twicpics_options[', esc_attr( $args['label_for'] ), ']" value="', $value,'" ',( checked( $options[ $args['label_for'] ]?:'placeholder', $value, false ) ),'>',
        $label,
      '</label><br/><br/>';
    endforeach;
    ?>
    <p class="description"><?php echo $args['description']; ?></p>
    <?php
  }

  /**
   * Callback for blank field (treated elsewhere)
   *
   * @param     array $args Display/Values argurments
   */
  public function field_blank($args){}
}
