<?php
/**
 * iThemes Exchange - Madmimi Add-on.
 *
 * @package   TGM_Exchange_Madmimi
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @link      http://thomasgriffinmedia.com/
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 *
 * @wordpress-plugin
 * Plugin Name:  iThemes Exchange - Madmimi Add-on
 * Plugin URI:   http://ithemes.com/exchange/madmimi/
 * Description:  Integrates Madmimi into the iThemes Exchange plugin.
 * Version:      1.0.0
 * Author:       Thomas Griffin
 * Author URI:   http://thomasgriffinmedia.com/
 * Text Domain:  tgm-exchange-madmimi
 * Contributors: griffinjt
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:  /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

// Define constants.
define( 'TGM_EXCHANGE_MADMIMI_FILE', __FILE__ );

// Register the addon with the Exchange engine.
add_action( 'it_exchange_register_addons', 'tgm_exchange_madmimi_register' );
/**
 * Registers the Madmimi addon with the Exchange addons engine.
 *
 * @since 1.0.0
 */
function tgm_exchange_madmimi_register() {

    $versions         = get_option( 'it-exchange-versions', false );
	$current_version  = empty( $versions['current'] ) ? false : $versions['current'];

	if ( $current_version && version_compare( $current_version, '1.0.3', '>' ) ) {
		$options = array(
			'name'              => __( 'Madmimi', 'tgm-exchange-madmimi' ),
			'description'       => __( 'Adds a Madmimi optin checkbox to the user registration form.', 'tgm-exchange-madmimi' ),
			'author'            => 'Thomas Griffin',
			'author_url'        => 'http://thomasgriffinmedia.com/',
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

    TGM_Exchange_Madmimi::get_instance()->settings();

}

/**
 * Callback function for displaying upgrade nag.
 *
 * @since 1.0.0
 */
function tgm_exchange_madmimi_nag() {

    TGM_Exchange_Madmimi::get_instance()->nag();

}