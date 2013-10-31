<?php
/**
 * iThemes Exchange - Madmimi Add-on class.
 *
 * @package   TGM_Exchange_Madmimi
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 */

/**
 * Main plugin class.
 *
 * @package TGM_Exchange_Madmimi
 */
class TGM_Exchange_Madmimi {

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
    public $plugin_name = 'iThemes Exchange - Madmimi Add-on';

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
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

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
     * Fired when the plugin is activated.
     *
     * @since 1.0.0
     *
     * @global int $wp_version The current version of WP on this install.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
     */
    public static function activate( $network_wide ) {

        global $wp_version;

        // If not WP 3.5 or greater, bail.
        if ( version_compare( $wp_version, '3.5.1', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( 'Sorry, but your version of WordPress, <strong>' . $wp_version . '</strong>, does not meet the required version of <strong>3.5.1</strong> to run this plugin properly. The plugin has been deactivated. <a href="' . admin_url() . '">Click here to return to the Dashboard</a>.' );
        }

        // If our option does not exist yet, add it now.
        $settings = get_option( 'tgm_exchange_madmimi' );
        if ( ! $settings )
            update_option( 'tgm_exchange_madmimi', TGM_Exchange_Madmimi::defaults() );

    }

    /**
     * Fired when the plugin is uninstalled.
     *
     * @since 1.0.0
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
     */
    public static function uninstall( $network_wide ) {

        // Remove any trace of our addon.
        delete_option( 'tgm_exchange_madmimi' );

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

        // Register the plugin updater.
        add_action( 'ithemes_updater_register', array( $this, 'updater' ) );

        // Load admin assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Utility actions.
        add_filter( 'plugin_action_links_' . plugin_basename( TGM_EXCHANGE_MADMIMI_FILE ), array( $this, 'plugin_links' ) );
        add_filter( 'it_exchange_theme_api_registration_password2', array( $this, 'output_optin' ) );
        add_action( 'it_exchange_register_user', array( $this, 'do_optin' ) );

    }

    /**
     * Initializes the plugin updater for the addon.
     *
     * @since 1.0.0
     */
    public function updater( $updater ) {

        // Return early if not in the admin.
        if ( ! is_admin() ) return;

        // Load the updater class.
        require_once dirname( __FILE__ ) . '/lib/updater/load.php';

        // Register the addon with the updater.
        $updater->register( 'exchange-addon-madmimi', TGM_EXCHANGE_MADMIMI_FILE );

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
            printf( __( 'To use the Madmimi add-on for iThemes Exchange, you must be using iThemes Exchange version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'tgm-exchange-madmimi' ), admin_url( 'update-core.php' ) );
            ?>
        </div>
        <?php

    }

    /**
     * Add Settings page to plugin action links in the Plugins table.
     *
     * @since 1.0.0
     *
     * @param array $links Default plugin action links.
     * @return array $links Amended plugin action links.
     */
    public function settings_link( $links ) {

        $setting_link = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'optin-monster' ), admin_url( 'admin.php' ) ), __( 'Settings', 'optin-monster' ) );
        array_unshift( $links, $setting_link );

        return $links;

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
            <h2><?php _e( 'Madmimi Settings', 'tgm-exchange-madmimi' ); ?></h2>

            <?php if ( ! empty( $this->errors ) ) : ?>
                <div id="message" class="error"><p><strong><?php echo implode( '<br>', $this->errors ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->saved ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your settings have been saved successfully!', 'tgm-exchange-madmimi' ); ?></strong></p></div>
            <?php endif; ?>

            <?php do_action( 'it_exchange_madmimi_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <div class="tgm-exchange-madmimi-settings">
                <p><?php _e( 'To setup Madmimi in Exchange, fill out the settings below.', 'tgm-exchange-madmimi' ); ?></p>
                <form class="tgm-exchange-madmimi-form" action="admin.php?page=it-exchange-addons&add-on-settings=madmimi" method="post">
                    <?php wp_nonce_field( 'tgm-exchange-madmimi-form' ); ?>
                    <input type="hidden" name="tgm-exchange-madmimi-form" value="1" />

                    <table class="form-table">
                        <tbody>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-username"><strong><?php _e( 'Madmimi Username', 'tgm-exchange-madmimi' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-username" type="text" name="_tgm_exchange_madmimi[madmimi-username]" value="<?php echo $this->get_setting( 'madmimi-username' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Madmimi username here.', 'tgm-exchange-madmimi' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-api-key"><strong><?php _e( 'Madmimi API Key', 'tgm-exchange-madmimi' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-api-key" type="text" name="_tgm_exchange_madmimi[madmimi-api-key]" value="<?php echo $this->get_setting( 'madmimi-api-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Madmimi API Key here.', 'tgm-exchange-madmimi' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-lists"><strong><?php _e( 'Madmimi List', 'tgm-exchange-madmimi' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-madmimi-list-output">
                                        <?php echo $this->get_madmimi_lists( $this->get_setting( 'madmimi-username' ), $this->get_setting( 'madmimi-api-key' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-madmimi-label"><strong><?php _e( 'Madmimi Label', 'tgm-exchange-madmimi' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-madmimi-label" type="text" name="_tgm_exchange_madmimi[madmimi-label]" value="<?php echo $this->get_setting( 'madmimi-label' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Madmimi username here.', 'tgm-exchange-madmimi' ); ?>" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Save Changes', 'tgm-exchange-madmimi' ), 'primary button-large', '_tgm_exchange_madmimi[save]' ); ?>
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
            $this->errors[] = __( 'Are you sure you want to do this? The form nonces do not match. Please try again.', 'tgm-exchange-madmimi' );
            return;
        }

        // Sanitize values before saving them to the database.
        $settings     = get_option( 'tgm_exchange_madmimi' );
        $new_settings = stripslashes_deep( $_POST['_tgm_exchange_madmimi'] );

        $settings['madmimi-username'] = trim( $new_settings['madmimi-username'] );
        $settings['madmimi-api-key']  = trim( $new_settings['madmimi-api-key'] );
        $settings['madmimi-list']     = esc_attr( $new_settings['madmimi-list'] );
        $settings['madmimi-label']    = esc_html( $new_settings['madmimi-label'] );

        // Save the settings and set saved flag to true.
        if ( update_option( 'tgm_exchange_madmimi', $settings ) )
            return $this->saved = true;
        else
            return $this->errors[] = __( 'There was an error saving your settings. Please try again.', 'tgm-exchange-madmimi' );

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

        if ( 'madmimi-label' == $setting )
            return isset( $settings[$setting] ) ? $settings[$setting] : __( 'Sign up to receive updates via email!', 'tgm-exchange-madmimi' );
        else
            return isset( $settings[$setting] ) ? $settings[$setting] : '';

    }

    /**
     * Helper function to retrieve all available Madmimi lists for the account.
     *
     * @since 1.0.0
     *
     * @param string $username The Madmimi username.
     * @param string $api_key The Madmimi API key.
     * @return string An HTML string with lists or empty dropdown.
     */
    public function get_madmimi_lists( $username = '', $api_key = '' ) {

        // Prepare the HTML holder variable.
        $html = '';

        // If there is no username or API key, send back an empty placeholder list.
        if ( '' === trim( $username ) || '' === trim( $api_key ) ) {
            $html .= '<select id="tgm-exchange-madmimi-lists" name="_tgm_exchange_madmimi[madmimi-list]" disabled="disabled">';
                $html .= '<option value="none">' . __( 'No lists to select from at this time.', 'tgm-exchange-madmimi' ) . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        } else {
            // Load the Madmimi necessary library components.
            if ( ! class_exists( 'MadMimi' ) )
                require_once plugin_dir_path( TGM_EXCHANGE_MADMIMI_FILE ) . 'lib/madmimi/MadMimi.class.php';

            // Load the Madmimi API.
            $madmimi = new MadMimi( $username, $api_key );

            // Attempt to load the lists from the API.
            libxml_use_internal_errors( true );
            $lists = simplexml_load_string( $madmimi->Lists() );

            // If XML is not returned, we need to send an error message.
            if ( ! $lists ) {
                $html .= '<select id="tgm-exchange-madmimi-lists" class="tgm-exchange-error" name="_tgm_exchange_madmimi[madmimi-list]" disabled="disabled">';
                    $html .= '<option value="none">' . __( 'Invalid credentials. Please try again.', 'tgm-exchange-madmimi' ) . '</option>';
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
     * Sets addon option defaults.
     *
     * @since 1.0.0
     *
     * @return array $defaults Default options.
     */
    public static function defaults() {

        $defaults                     = array();
        $defaults['madmimi-username'] = '';
        $defaults['madmimi-api-key']  = '';
        $defaults['madmimi-list']     = '';
        $defaults['madmimi-label']    = __( 'Sign up to receive updates via email!', 'tgm-exchange-madmimi' );

        return $defaults;

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

        $links['setup_addon'] = '<a href="' . get_admin_url( null, 'admin.php?page=it-exchange-addons&add-on-settings=madmimi' ) . '" title="' . esc_attr__( 'Setup Add-on', 'tgm-exchange-madmimi' ) . '">' . __( 'Setup Add-on', 'tgm-exchange-madmimi' ) . '</a>';
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
        $output  = '<div class="tgm-exchange-madmimi-signup">';
            $output .= '<label for="tgm-exchange-madmimi-signup-field">';
                $output .= '<input type="checkbox" id="tgm-exchange-madmimi-signup-field" name="tgm-exchange-madmimi-signup-field" value="" />' . $this->get_setting( 'madmimi-label' );
            $output .= '</label>';
        $output .= '</div>';
        $output  = apply_filters( 'tgm_exchange_madmimi_output', $output );

        // Append the optin output to the password2 field.
        return $res . $output;

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

        // Load the Madmimi API.
        if ( ! class_exists( 'MadMimi' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_MADMIMI_FILE ) . 'lib/madmimi/MadMimi.class.php';

        // Load the Madmimi API.
        $madmimi = new MadMimi( $this->get_setting( 'madmimi-username' ), $this->get_setting( 'madmimi-api-key' ) );

        // Prepare optin variables.
        $email      = trim( $_POST['email'] );
        $first_name = ! empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
        $last_name  = ! empty( $_POST['last_name'] )  ? trim( $_POST['last_name'] )  : '';

        // Process the optin.
        $madmimi->AddUser( array( 'add_list' => $this->get_setting( 'madmimi-list' ), 'email' => $email, 'firstName' => $first_name, 'lastName' => $last_name ) );

    }

}

// Register activation and uninstall hooks.
register_activation_hook( __FILE__, array( 'TGM_Exchange_Madmimi', 'activate' ) );
register_uninstall_hook(  __FILE__, array( 'TGM_Exchange_Madmimi', 'uninstall' ) );

// Initialize the plugin.
global $tgm_exchange_madmimi;
$tgm_exchange_madmimi = TGM_Exchange_Madmimi::get_instance();