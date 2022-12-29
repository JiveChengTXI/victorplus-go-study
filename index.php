<?php
/**
 *
 * Go-Study
 *
 * @link              https://txipartners.com
 * @since             1.0.0
 * @package           ODS
 *
 * @wordpress-plugin
 * Plugin Name:       GoStudy
 * Plugin URI:        https://txipartners.com
 * Version:           1.0.0
 * Author:            Jive Cheng
 * Description:       Victor Plus 客制化修改 Plugin
 * Author URI:        https://txipartners.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gostudy
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GOSTUDY_PLUGIN_FILE' ) ) {
	define( 'GOSTUDY_PLUGIN_FILE', __FILE__ );
}



if ( ! defined( 'GOSTUDY_ABSPATH' ) ) {
	define( 'GOSTUDY_ABSPATH', dirname( GOSTUDY_PLUGIN_FILE ) . '/' );
}

if ( ! class_exists( 'GOSTUDY', false ) ) {
	include_once dirname( GOSTUDY_PLUGIN_FILE ) . '/inc/class-gostudy.php';
}

/**
 * GOSTUDY
 *
 * @return GOSTUDY
 */
function gostudy() {
	$instance = GOSTUDY::instance();
	return $instance;
}

$gostudy = gostudy();

$GLOBALS['gostudy'] = $gostudy;
