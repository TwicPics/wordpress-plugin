<?php

defined( 'ABSPATH' ) || die( 'ERROR !' );

/**
 * The class for TwicPics admin settings
 */
class TwicPics_Admin {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'options_page' ) );
		add_action( 'admin_print_scripts-twicpics', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add the settings's menu under the upload section
	 */
	public function options_page() {
		add_menu_page( __( 'TwicPics Options', 'twicpics' ), 'TwicPics', 'manage_options', 'twicpics', array( $this, 'render_config_page' ), '', null );
		add_submenu_page( 'upload.php', __( 'TwicPics Options', 'twicpics' ), 'TwicPics', 'manage_options', 'twicpics', array( $this, 'render_config_page' ) );
	}

	/**
	 * Add scripts in config page header
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * The callback for the settings page
	 */
	public function render_config_page() {
		/* Checks user capabilities. */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Adds error/update messages.
		// Checks if the user has submitted the settings.
		// WordPress will add the "settings-updated" $_GET parameter to the URL.
		if ( isset( $_GET['settings-updated'] ) ) {
			/* Adds settings saved message with the class of "updated". */
			add_settings_error( 'twicpics_messages', 'twicpics_message', __( 'Settings Saved', 'twicpics' ), 'updated' );
		}

		/* Shows error/update messages. */
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

		add_settings_section( 'twicpics_section_account_settings', __( 'Account Settings', 'twicpics' ), array( $this, 'section_account_settings' ), 'twicpics' );

		add_settings_field(
			'twicpics_field_user_domain',
			__( 'TwicPics domain', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'   => 'user_domain',
				'description' => esc_html( __( 'Fill in your TwicPics domain in this field.', 'twicpics' ) ),
			)
		);

		add_settings_field(
			'twicpics_field_max_width',
			__( 'Max width of images', 'twicpics' ),
			array( $this, 'field_textinput' ),
			'twicpics',
			'twicpics_section_account_settings',
			array(
				'label_for'   => 'max_width',
				'description' => esc_html( __( 'The max width you want your images to be displayed' ) ),
			)
		);
	}

	/**
	 * Callback for the lazyload section
	 *
	 * @param     array $args Displays arguments.
	 */
	public function section_account_settings( $args ) {
		?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>">
		<?php
		echo sprintf(
			__( '<p> Set your <strong>TwicPics domain</strong> here to begin with your images optimization.<br />You can get your domain by going to your <a href="%1$s" target="_blank">TwicPics account</a>.<p><em>For more information about TwicPics domain, please refer to the <a href="%2$s" target="_blank">documentation</a>.</em></p>', 'twicpics' ),
			'https://account.twicpics.com/login',
			'https://www.twicpics.com/documentation/subdomain/'
		);
		?>
	</p>
		<?php
	}

	/**
	 * Callback for the text input type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_textinput( $args ) {
		$options = get_option( 'twicpics_options' );
		echo '<input type="text" id="', esc_attr( $args['label_for'] ) ,'" name="twicpics_options[', esc_attr( $args['label_for'] ), ']" value="', ( esc_attr( $options[ $args['label_for'] ] ) ? esc_attr( $options[ $args['label_for'] ] ) : '' ),'" class="regular-text" />';
		?>
	<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Callback for the radios type fields
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_radios( $args ) {
		$options = get_option( 'twicpics_options' );
		$types   = $args['values'];
		foreach ( $types as $label => $value ) :
			echo '<label>
        		<input type="radio" id="', esc_attr( $args['label_for'] ) ,'" name="twicpics_options[', esc_attr( $args['label_for'] ), ']" value="', esc_attr( $value ),'" ',( checked( $options[ $args['label_for'] ] ? $options[ $args['label_for'] ] : 'placeholder', $value, false ) ),'>',
				esc_html( $label ),
				'</label><br/><br/>';
		endforeach;
		?>
	<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Callback for blank field (treated elsewhere)
	 *
	 * @param     array $args Displays values arguments.
	 */
	public function field_blank( $args ) {}
}
