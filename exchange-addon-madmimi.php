<?php
/**
 * ExchangeWP - Mad Mimi Add-on.
 *
 * @package   TGM_Exchange_MadMimi
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @link      http://thomasgriffinmedia.com/
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 *
 * @wordpress-plugin
 * Plugin Name:  ExchangeWP - MadMimi Add-on
 * Plugin URI:   https://exchangewp.com/downloads/madmimi
 * Description:  Integrates Mad Mimi into the ExchangeWP plugin.
 * Version:      1.1.0
 * Author:       ExchangeWP
 * Author URI:   https://exchangewp.com
 * Text Domain:  LION
 * Contributors: exchangewp, griffinjt
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:  /lang
 * ExchangeWP Package: exchange-addon-madmimi
 *
 * This add-on was originally developed by Thomas Griffin <http://thomasgriffinmedia.com/>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

// Define constants.
define( 'TGM_EXCHANGE_MADMIMI_FILE', __FILE__ );

// Register the plugin updater.
add_action( 'ithemes_updater_register', 'tgm_exchange_madmimi_updater' );
/**
 * Registers the iThemes updater with the addon.
 *
 * @since 1.0.0
 *
 * @param object $updater The iThemes updater object.
 */
function tgm_exchange_madmimi_updater( $updater ) {

    // Return early if not in the admin.
    if ( ! is_admin() ) return;

    // Load the updater class.
    // require_once dirname( __FILE__ ) . '/lib/updater/load.php';

    // Register the addon with the updater.
    $updater->register( 'exchange-addon-madmimi', __FILE__ );

}

if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) )  {
 	require_once 'EDD_SL_Plugin_Updater.php';
 }

 function exchange_madmimi_plugin_updater() {

 	// retrieve our license key from the DB
 	// this is going to have to be pulled from a seralized array to get the actual key.
 	// $license_key = trim( get_option( 'exchange_madmimi_license_key' ) );
 	$exchangewp_madmimi_options = get_option( 'it-storage-exchange_addon_madmimi' );
 	$license_key = $exchangewp_madmimi_options['madmimi_license'];

 	// setup the updater
 	$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
 			'version' 		=> '1.1.1', 				// current version number
 			'license' 		=> $license_key, 		// license key (used get_option above to retrieve from DB)
 			'item_name' 	=> 'madmimi', 	  // name of this plugin
 			'author' 	  	=> 'ExchangeWP',    // author of this plugin
 			'url'       	=> home_url(),
 			'wp_override' => true,
 			'beta'		  	=> false
 		)
 	);
 	// var_dump($edd_updater);
 	// die();

 }

 add_action( 'admin_init', 'exchange_madmimi_plugin_updater', 0 );

// Register the addon with the Exchange engine.
add_action( 'it_exchange_register_addons', 'tgm_exchange_madmimi_register' );
/**
 * Registers the Mad Mimi addon with the Exchange addons engine.
 *
 * @since 1.0.0
 */
function tgm_exchange_madmimi_register() {

    $versions         = get_option( 'it-exchange-versions', false );
    $current_version  = empty( $versions['current'] ) ? false : $versions['current'];

    if ( $current_version && version_compare( $current_version, '1.0.3', '>' ) ) {
        $options = array(
            'name'              => __( 'MadMimi', 'tgm-exchange-madmimi' ),
            'description'       => __( 'Adds a MadMimi optin checkbox to the user registration form.', 'tgm-exchange-madmimi' ),
            'author'            => 'ExchangeWP',
            'author_url'        => 'https://exchangewp.com/downloads/madmimi',
            'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/madmimi50px.png' ),
            'file'              => dirname( __FILE__ ) . '/class-exchange-addon-madmimi.php',
            'category'          => 'email',
            'settings-callback' => 'tgm_exchange_madmimi_settings'
        );
        it_exchange_register_addon( 'madmimi', $options );
    } else {
        add_action( 'admin_notices', 'tgm_exchange_madmimi_nag' );
    }

}

/**
 * Callback function for outputting the addon settings view.
 *
 * @since 1.0.0
 */
function tgm_exchange_madmimi_settings() {

    TGM_Exchange_MadMimi::get_instance()->settings();

}

/**
 * Callback function for displaying upgrade nag.
 *
 * @since 1.0.0
 */
function tgm_exchange_madmimi_nag() {

    TGM_Exchange_MadMimi::get_instance()->nag();

}

register_activation_hook( __FILE__, 'tgm_exchange_madmimi_activate' );
/**
 * Fired when the plugin is activated.
 *
 * @since 1.0.0
 *
 * @global int $wp_version The current version of WP on this install.
 *
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function tgm_exchange_madmimi_activate( $network_wide ) {

    global $wp_version;

    // If not WP 3.5 or greater, bail.
    if ( version_compare( $wp_version, '3.5.1', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'Sorry, but your version of WordPress, <strong>' . $wp_version . '</strong>, does not meet the required version of <strong>3.5.1</strong> to run this plugin properly. The plugin has been deactivated. <a href="' . admin_url() . '">Click here to return to the Dashboard</a>.' );
    }

    // If our option does not exist, add it now.
    if ( is_multisite() ) :
        global $wpdb;
        $site_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" ) );
        foreach ( (array) $site_list as $site ) :
            switch_to_blog( $site->blog_id );
            $settings = get_option( 'tgm_exchange_madmimi' );
            if ( ! $settings )
                update_option( 'tgm_exchange_madmimi', tgm_exchange_madmimi_defaults() );
            restore_current_blog();
        endforeach;
    else :
        $settings = get_option( 'tgm_exchange_madmimi' );
        if ( ! $settings )
            update_option( 'tgm_exchange_madmimi', tgm_exchange_madmimi_defaults() );
    endif;

}

register_uninstall_hook( __FILE__, 'tgm_exchange_madmimi_uninstall' );
/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 *
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function tgm_exchange_madmimi_uninstall( $network_wide ) {

    // Remove any trace of our addon.
    if ( is_multisite() ) :
        global $wpdb;
        $site_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" ) );
        foreach ( (array) $site_list as $site ) :
            switch_to_blog( $site->blog_id );
            delete_option( 'tgm_exchange_madmimi' );
            restore_current_blog();
        endforeach;
    else :
        delete_option( 'tgm_exchange_madmimi' );
    endif;

}

/**
 * Sets addon option defaults.
 *
 * @since 1.0.0
 *
 * @return array $defaults Default options.
 */
function tgm_exchange_madmimi_defaults() {

    $defaults                     = array();
    $defaults['madmimi-username'] = '';
    $defaults['madmimi-api-key']  = '';
    $defaults['madmimi-list']     = '';
    $defaults['madmimi-label']    = __( 'Sign up to receive updates via email!', 'tgm-exchange-madmimi' );
    $defaults['madmimi-checked']  = 1;

    return $defaults;

}
