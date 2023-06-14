<?php
/**
 * TwicPics plugin admin-specific functionality.
 */
new class {
    /**
     * Set required actions
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function admin_init() {
        register_setting( 'twicpics', 'twicpics_options' );
    }

    public function admin_menu() {
        add_menu_page(
            __( 'TwicPics Options', 'twicpics' ),
            'TwicPics',
            'manage_options',
            'twicpics',
            array( $this, 'render' ),
            '',
            null
        );
    }

    public function render() {
        /* Checks user capabilities. */
        if ( !current_user_can( 'manage_options' ) ) {
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
        <div class="wrap" id="twicpics-options-admin-wrapper"></div>
        <script>
            var TWICPICS = <?php echo wp_json_encode( ( object ) array(
                "hiddenFields" => wp_nonce_field( "twicpics-options", "_wpnonce", true, false ),
                "options" => \TwicPics\Options::get_object(),
            ) ); ?>;
            <?php include 'admin.js'; ?>
        </script>
        <style>
            <?php include 'admin.css'; ?>
        </style>
        <?php
    }
};
