<?php
/**
 * Plugin Name: Dendrila Accessibility Assistant
 * Description: Diagnostics éditoriaux d'accessibilité dans WordPress, Elementor et Divi, avec corrections guidées et sans modification automatique.
 * Version: 0.1.0-alpha
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Le Potager du Web
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: dendrila-accessibility-assistant
 * Update URI: https://github.com/jasmin-abernathy/Dendrila-accessibility-assistant
 */
defined('ABSPATH') || exit;

define('DAA_VERSION', '0.1.0-alpha');
define('DAA_FILE', __FILE__);
define('DAA_DIR', plugin_dir_path(__FILE__));

require_once DAA_DIR . 'includes/class-daa-analyzer.php';
require_once DAA_DIR . 'includes/class-daa-builders.php';
require_once DAA_DIR . 'includes/class-daa-scanner.php';
require_once DAA_DIR . 'includes/class-daa-media.php';
require_once DAA_DIR . 'includes/class-daa-admin.php';

add_action('admin_menu', array('DAA_Admin', 'menu'));
add_action('admin_post_daa_scan', array('DAA_Admin', 'scan'));
add_action('add_meta_boxes', array('DAA_Admin', 'meta_boxes'));
add_action('admin_enqueue_scripts', array('DAA_Admin', 'assets'));
DAA_Media::boot();
