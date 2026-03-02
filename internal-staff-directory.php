<?php
/**
 * Plugin Name:       Internal Staff Directory
 * Plugin URI:        https://github.com/edwinkrisnha/Internal-Staff-Directory-Wordpress-Plugin
 * Description:       Internal employee directory for company intranet. Searchable staff profiles stored as user meta.
 * Version:           1.20.0
 * Author:            Edwin Krisnha
 * Author URI:        https://github.com/edwinkrisnha
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       internal-staff-directory
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Tested up to:      6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EMPLOYEE_DIR_VERSION',     '1.20.0' );
define( 'EMPLOYEE_DIR_PLUGIN_FILE', __FILE__ );
define( 'EMPLOYEE_DIR_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'EMPLOYEE_DIR_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/profile.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/settings.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/profile-page.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/directory.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/admin.php';
require_once EMPLOYEE_DIR_PLUGIN_DIR . 'includes/hr-admin.php';

// Flush rewrite rules on activation/deactivation so the /staff/ rule takes effect.
register_activation_hook( EMPLOYEE_DIR_PLUGIN_FILE, 'flush_rewrite_rules' );
register_deactivation_hook( EMPLOYEE_DIR_PLUGIN_FILE, 'flush_rewrite_rules' );
