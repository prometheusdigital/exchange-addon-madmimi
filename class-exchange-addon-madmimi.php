<?php
/**
 * ExchangeWP - Mad Mimi Add-on class.
 *
 * @package   TGM_Exchange_MadMimi
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 */

/**
 * Main plugin class.
 *
 * @package TGM_Exchange_MadMimi
 */
class TGM_Exchange_MadMimi {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'ExchangeWP - Mad Mimi Add-on';

    /**
     * Unique plugin identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'exchange-addon-madmimi';

    /**
     * Plugin textdomain.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $domain = 'tgm-exchange-madmimi';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance = null;

    /**
     * Holds any error messages.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $errors = array();

    /**
     * Flag to determine if form was saved.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $saved = false;

    /**
     * Initialize the plugin class object.
     *
     * @since 1.0.0
     */
    private function __construct() {

        // Load plugin text domain.
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ) );

        // Load ajax hooks.
        add_action( 'wp_ajax_tgm_exchange_madmimi_update_lists', array( $this, 'lists' ) );

    }

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance )
            self::$instance = new self;

        return self::$instance;

    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->domain;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

    }

    /**
     * Loads the plugin.
     *
     * @since 1.0.0
     */
    public function init() {

        // Load admin assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Utility actions.
        add_filter( 'plugin_action_links_' . plugin_basename( TGM_EXCHANGE_MADMIMI_FILE ), array( $this, 'plugin_links' ) );
        add_filter( 'it_exchange_theme_api_registration_password2', array( $this, 'output_optin' ) );
        add_action( 'it_exchange_content_checkout_logged_in_checkout_requirement_guest_checkout_end_form', array( $this, 'output_optin_guest' ) );
        add_action( 'it_exchange_register_user', array( $this, 'do_optin' ) );
        add_action( 'it_exchange_init_guest_checkout', array( $this, 'do_optin_guest' ) );

    }

    /**
     * Outputs update nag if the currently installed version does not meet the addon requirements.
     *
     * @since 1.0.0
     */
    public function nag() {

        ?>
        <div id="tgm-exchange-madmimi-nag" class="it-exchange-nag">
            <?php
            printf( __( 'To use the Mad Mimi add-on for ExchangeWP, you must be using ExchangeWP version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'LION' ), admin_url( 'update-core.php' ) );
            ?>
        </div>
        <?php

    }

    /**
     * Register and enqueue admin-specific stylesheets.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_styles() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'lib/css/admin.css', __FILE__ ), array(), $this->version );

    }

    /**
     * Register and enqueue admin-specific JS.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_scripts() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'lib/js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since 1.0.0
     */
    public function settings() {

        // Save form settings if necessary.
        if ( isset( $_POST['tgm-exchange-madmimi-form'] ) && $_POST['tgm-exchange-madmimi-form'] )
            $this->save_form();

        ?>
        <div class="wrap tgm-exchange-madmimi">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'MadMimi Settings', 'LION' ); ?></h2>

            <?php if ( ! empty( $this->errors ) ) : ?>
                <div id="message" class="error"><p><strong><?php echo implode( '<br>', $this->errors ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->saved ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your settings have been saved successfully!', 'LION' ); ?></strong></p></div>
            <?php endif; ?>

            <?php do_action( 'it_exchange_madmimi_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <div class="tgm-exchange-madmimi-settings">
                <p><?php _e( 'To setup Mad Mimi in Exchange, fill out the settings below.', 'LION' ); ?></p>
                <form class="tgm-exchange-madmimi-form" action="admin.php?page=it-exchange-addons&add-on-settings=madmimi" method="post">
                    <?php wp_nonce_field( 'tgm-exchange-madmimi-form' ); ?>
                    <input type="hidden" name="tgm-exchange-madmimi-form" value="1" />
                    <?php
                       $exstatus = trim( get_option( 'exchange_madmimi_license_status' ) );
                    ?>
                    <table class="form-table">
                        <tbody>
                          <tr valign="middle">
                                <th scope="row">
                                    <label class="description" for="exchange_madmimi_license_key"><strong><?php _e('Enter your ExchangeWP Campaign Monitor license key'); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi_license" name="_tgm_exchange_madmimi[madmimi-license-key]" type="text" value="<?php echo $this->get_setting( 'madmimi-license-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your ExchangeWP License Key here.', 'LION' ); ?>" />
                                    <span>
                                        <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
                                            <span style="color:green;"><?php _e('active'); ?></span>
                          			            <?php wp_nonce_field( 'exchange_madmimi_nonce', 'exchange_madmimi_nonce' ); ?>
                          			            <input type="submit" class="button-secondary" name="exchange_madmimi_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
                                        <?php } else {
                                            wp_nonce_field( 'exchange_madmimi_nonce', 'exchange_madmimi_nonce' ); ?>
                                            <input type="submit" class="button-secondary" name="exchange_madmimi_license_activate" value="<?php _e('Activate License'); ?>"/>
                                        <?php } ?>
                                    </span>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-username"><strong><?php _e( 'Mad Mimi Username', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-username" type="text" name="_tgm_exchange_madmimi[madmimi-username]" value="<?php echo $this->get_setting( 'madmimi-username' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Mad Mimi username here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-api-key"><strong><?php _e( 'Mad Mimi API Key', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-api-key" type="password" name="_tgm_exchange_madmimi[madmimi-api-key]" value="<?php echo $this->get_setting( 'madmimi-api-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Mad Mimi API Key here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-lists"><strong><?php _e( 'Mad Mimi List', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-madmimi-list-output">
                                        <?php echo $this->get_madmimi_lists( $this->get_setting( 'madmimi-username' ), $this->get_setting( 'madmimi-api-key' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-label"><strong><?php _e( 'Mad Mimi Label', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-label" type="text" name="_tgm_exchange_madmimi[madmimi-label]" value="<?php echo $this->get_setting( 'madmimi-label' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Mad Mimi checkbox label here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-checked"><strong><?php _e( 'Check Mad Mimi box by default?', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-checked" type="checkbox" name="_tgm_exchange_madmimi[madmimi-checked]" value="<?php echo (bool) $this->get_setting( 'madmimi-checked' ); ?>" <?php checked( $this->get_setting( 'madmimi-checked' ), 1 ); ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Save Changes', 'LION' ), 'primary button-large', '_tgm_exchange_madmimi[save]' ); ?>
                </form>
            </div>

            <?php do_action( 'it_exchange_madmimi_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php

    }

    /**
     * Saves form field settings for the addon.
     *
     * @since 1.0.0
     */
    public function save_form() {

        // If the nonce is not correct, return an error.
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'tgm-exchange-madmimi-form' ) ) {
            $this->errors[] = __( 'Are you sure you want to do this? The form nonces do not match. Please try again.', 'LION' );
            return;
        }

        // Sanitize values before saving them to the database.
        $settings     = get_option( 'tgm_exchange_madmimi' );
        $new_settings = stripslashes_deep( $_POST['_tgm_exchange_madmimi'] );

        $settings['madmimi-license-key'] = isset( $new_settings['madmimi-license-key'] ) ? trim( $new_settings['madmimi-license-key'] ) : $settings['madmimi-license-key'];
        $settings['madmimi-username'] = trim( $new_settings['madmimi-username'] );
        $settings['madmimi-api-key']  = trim( $new_settings['madmimi-api-key'] );
        $settings['madmimi-list']     = esc_attr( $new_settings['madmimi-list'] );
        $settings['madmimi-label']    = esc_html( $new_settings['madmimi-label'] );
        $settings['madmimi-checked']  = isset( $new_settings['madmimi-checked'] ) ? 1 : 0;

        // Save the settings and set saved flag to true.
        update_option( 'tgm_exchange_madmimi', $settings );

        if( isset( $_POST['exchange_madmimi_license_activate'] ) ) {

		    // run a quick security check
		    if( ! check_admin_referer( 'exchange_madmimi_nonce', 'exchange_madmimi_nonce' ) )
			    return; // get out if we didn't click the Activate button

		    // retrieve the license from the database
		    // $license = trim( get_option( 'exchange_madmimi_license_key' ) );
		    $exchangewp_madmimi_options = get_option( 'tgm_exchange_madmimi' );
		    $license = trim( $exchangewp_madmimi_options['madmimi-license-key'] );

		    // data to send in our API request
		    $api_params = array(
			    'edd_action' => 'activate_license',
			    'license'    => $license,
			    'item_name'  => urlencode( 'madmimi' ), // the name of our product in EDD
			    'url'        => home_url()
		    );

		    // Call the custom API.
		    $response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		    // make sure the response came back okay
		    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			    if ( is_wp_error( $response ) ) {
				    $message = $response->get_error_message();
			    } else {
				    $message = __( 'An error occurred, please try again.' );
			    }

		    } else {

			    $license_data = json_decode( wp_remote_retrieve_body( $response ) );

			    if ( false === $license_data->success ) {

				    switch( $license_data->error ) {

					    case 'expired' :

						    $message = sprintf(
							    __( 'Your license key expired on %s.' ),
							    date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						    );
						    break;

					    case 'revoked' :

						    $message = __( 'Your license key has been disabled.' );
						    break;

					    case 'missing' :

						    $message = __( 'Invalid license.' );
						    break;

					    case 'invalid' :
					    case 'site_inactive' :

						    $message = __( 'Your license is not active for this URL.' );
						    break;

					    case 'item_name_mismatch' :

						    $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'madmimi' );
						    break;

					    case 'no_activations_left':

						    $message = __( 'Your license key has reached its activation limit.' );
						    break;

					    default :

						    $message = __( 'An error occurred, please try again.' );
						    break;
				    }

			    }

		    }

		    // Check if anything passed on a message constituting a failure
		    if ( ! empty( $message ) ) {
			    $base_url = admin_url( 'admin.php?page=' . 'it-exchange-addons&add-on-settings=madmimi-license' );
			    $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			    wp_redirect( $redirect );
			    exit();
		    }

		    //$license_data->license will be either "valid" or "invalid"
		    update_option( 'exchange_madmimi_license_status', $license_data->license );

	    }

	    // deactivate here
	    // listen for our activate button to be clicked
	    if( isset( $_POST['exchange_madmimi_license_deactivate'] ) ) {

		    // run a quick security check
		    if( ! check_admin_referer( 'exchange_madmimi_nonce', 'exchange_madmimi_nonce' ) )
			    return; // get out if we didn't click the Activate button

		    $exchangewp_madmimi_options = get_option( 'tgm_exchange_madmimi' );
		    $license = $exchangewp_madmimi_options['madmimi-license-key'];


		    // data to send in our API request
		    $api_params = array(
			    'edd_action' => 'deactivate_license',
			    'license'    => $license,
			    'item_name'  => urlencode( 'madmimi' ), // the name of our product in EDD
			    'url'        => home_url()
		    );
		    // Call the custom API.
		    $response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		    // make sure the response came back okay
		    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			    if ( is_wp_error( $response ) ) {
				    $message = $response->get_error_message();
			    } else {
				    $message = __( 'An error occurred, please try again.' );
			    }

		    }

		    // decode the license data
		    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
		    // $license_data->license will be either "deactivated" or "failed"
		    if( $license_data->license == 'deactivated' ) {
			    delete_option( 'exchange_madmimi_license_status' );
		    }

	    }

	    return $this->saved = true;

    }

    /**
     * Ajax callback to retrieve lists for the specific account.
     *
     * @since 1.0.0
     */
    public function lists() {

        // Prepare and sanitize variables.
        $username = stripslashes( $_POST['username'] );
        $api_key  = stripslashes( $_POST['api_key'] );

        // Retrieve the lists and die.
        die( $this->get_madmimi_lists( $username, $api_key ) );

    }

    /**
     * Helper flag function to determine if on the addon settings page.
     *
     * @since 1.0.0
     *
     * @return bool True if on the addon page, false otherwise.
     */
    public function is_settings_page() {

        return isset( $_GET['add-on-settings'] ) && 'madmimi' == $_GET['add-on-settings'];

    }

    /**
     * Helper function for retrieving addon settings.
     *
     * @since 1.0.0
     *
     * @param string $setting The setting to look for.
     * @return mixed Addon setting if set, empty string otherwise.
     */
    public function get_setting( $setting = '' ) {

        $settings = get_option( 'tgm_exchange_madmimi' );
        return isset( $settings[$setting] ) ? $settings[$setting] : '';

    }

    /**
     * Helper function to retrieve all available MadMimi lists for the account.
     *
     * @since 1.0.0
     *
     * @param string $username The MadMimi username.
     * @param string $api_key The MadMimi API key.
     * @return string An HTML string with lists or empty dropdown.
     */
    public function get_madmimi_lists( $username = '', $api_key = '' ) {

        // Prepare the HTML holder variable.
        $html = '';

        // If there is no username or API key, send back an empty placeholder list.
        if ( '' === trim( $username ) || '' === trim( $api_key ) ) {
            $html .= '<select id="tgm-exchange-madmimi-lists" name="_tgm_exchange_madmimi[madmimi-list]" disabled="disabled">';
                $html .= '<option value="none">' . __( 'No lists to select from at this time.', 'LION' ) . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        } else {
            // Load the MadMimi necessary library components.
            if ( ! class_exists( 'MadMimi' ) )
                require_once plugin_dir_path( TGM_EXCHANGE_MADMIMI_FILE ) . 'lib/madmimi/MadMimi.class.php';

            // Load the MadMimi API.
            $madmimi = new MadMimi( $username, $api_key );

            // Attempt to load the lists from the API.
            libxml_use_internal_errors( true );
            $lists = simplexml_load_string( $madmimi->Lists() );

            // If XML is not returned, we need to send an error message.
            if ( ! $lists ) {
                $html .= '<select id="tgm-exchange-madmimi-lists" class="tgm-exchange-error" name="_tgm_exchange_madmimi[madmimi-list]" disabled="disabled">';
                    $html .= '<option value="none">' . __( 'Invalid credentials. Please try again.', 'LION' ) . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            } else {
                $html .= '<select id="tgm-exchange-madmimi-lists" name="_tgm_exchange_madmimi[madmimi-list]">';
                    foreach ( $lists->list as $list )
                        $html .= '<option value="' . $list['name'] . '"' . selected( $list['name'], $this->get_setting( 'madmimi-list' ), false ) . '>' . $list['name'] . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            }
        }

        // Return the HTML string.
        return $html;

    }

    /**
     * Adds custom action links to the plugin page.
     *
     * @since 1.0.0
     *
     * @param array $links Default action links.
     * @return array $links Amended action links.
     */
    public function plugin_links( $links ) {

        $links['setup_addon'] = '<a href="' . get_admin_url( null, 'admin.php?page=it-exchange-addons&add-on-settings=madmimi' ) . '" title="' . esc_attr__( 'Setup Add-on', 'LION' ) . '">' . __( 'Setup Add-on', 'LION' ) . '</a>';
        return $links;

    }

    /**
     * Outputs the optin checkbox on the appropriate checkout screens.
     *
     * @since 1.0.0
     *
     * @param string $res The password2 field.
     * @return string $res Password2 field with optin code appended.
     */
    public function output_optin( $res ) {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'madmimi-username' ) ) || '' === trim( $this->get_setting( 'madmimi-api-key' ) ) || '' === trim( $this->get_setting( 'madmimi-list' ) ) )
            return $res;

        // Build the HTML output of the optin.
        $output = $this->get_optin_output();

        // Append the optin output to the password2 field.
        return $res . $output;

    }

    /**
     * Outputs the optin checkbox on the appropriate guest checkout screens.
     *
     * @since 1.0.0
     */
    public function output_optin_guest() {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'madmimi-username' ) ) || '' === trim( $this->get_setting( 'madmimi-api-key' ) ) || '' === trim( $this->get_setting( 'madmimi-list' ) ) )
            return;

        // Build and echo the HTML output of the optin.
        echo $this->get_optin_output();

    }

    /**
     * Processes the optin to the email service.
     *
     * @since 1.0.0
     */
    public function do_optin() {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'madmimi-username' ) ) || '' === trim( $this->get_setting( 'madmimi-api-key' ) ) || '' === trim( $this->get_setting( 'madmimi-list' ) ) )
            return;

        // Return early if our $_POST key is not set, no email address is set or the email address is not valid.
        if ( ! isset( $_POST['tgm-exchange-madmimi-signup-field'] ) || empty( $_POST['email'] ) || ! is_email( $_POST['email'] ) )
            return;

        // Load the MadMimi API.
        if ( ! class_exists( 'MadMimi' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_MADMIMI_FILE ) . 'lib/madmimi/MadMimi.class.php';

        // Load the MadMimi API.
        $madmimi = new MadMimi( $this->get_setting( 'madmimi-username' ), $this->get_setting( 'madmimi-api-key' ) );

        // Prepare optin variables.
        $email      = trim( $_POST['email'] );
        $first_name = ! empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
        $last_name  = ! empty( $_POST['last_name'] )  ? trim( $_POST['last_name'] )  : '';
        $data       = array( 'add_list' => $this->get_setting( 'madmimi-list' ), 'email' => $email, 'firstName' => $first_name, 'lastName' => $last_name );
        $data       = apply_filters( 'tgm_exchange_madmimi_optin_data', $data );

        // Process the optin.
        if ( $data )
            $madmimi->AddUser( $data );

    }

    /**
     * Processes the optin to the email service in a guest checkout.
     *
     * @since 1.0.0
     *
     * @param string $email The guest checkout email address.
     */
    public function do_optin_guest( $email ) {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'madmimi-username' ) ) || '' === trim( $this->get_setting( 'madmimi-api-key' ) ) || '' === trim( $this->get_setting( 'madmimi-list' ) ) )
            return;

        // Load the MadMimi API.
        if ( ! class_exists( 'MadMimi' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_MADMIMI_FILE ) . 'lib/madmimi/MadMimi.class.php';

        // Load the MadMimi API.
        $madmimi = new MadMimi( $this->get_setting( 'madmimi-username' ), $this->get_setting( 'madmimi-api-key' ) );

        // Prepare optin variables.
        $data = array( 'add_list' => $this->get_setting( 'madmimi-list' ), 'email' => $email );
        $data = apply_filters( 'tgm_exchange_madmimi_optin_data', $data );

        // Process the optin.
        if ( $data )
            $madmimi->AddUser( $data );

    }

    /**
     * Generates and returns the optin output.
     *
     * @since 1.0.0
     *
     * @return string $output HTML string of optin output.
     */
    public function get_optin_output() {

        $output  = '<div class="tgm-exchange-madmimi-signup" style="clear:both;">';
            $output .= '<label for="tgm-exchange-madmimi-signup-field">';
                $output .= '<input type="checkbox" id="tgm-exchange-madmimi-signup-field" name="tgm-exchange-madmimi-signup-field" value="' . $this->get_setting( 'madmimi-checked' ) . '"' . checked( $this->get_setting( 'madmimi-checked' ), 1, false ) . ' />' . $this->get_setting( 'madmimi-label' );
            $output .= '</label>';
        $output .= '</div>';
        $output  = apply_filters( 'tgm_exchange_madmimi_output', $output );

        return $output;

    }

}

// Initialize the plugin.
global $tgm_exchange_madmimi;
$tgm_exchange_madmimi = TGM_Exchange_MadMimi::get_instance();
