<?php
/**
* Memoria theme functions and definitions
* Note: This theme is not used on the frontend. It is only for connecting to the headless frontend.
*
* @link https://developer.wordpress.org/themes/basics/theme-functions/
*
* @package memoria
* @since 1.0.0
*/

// Include theme files
require_once get_template_directory() . '/functions-helpers.php';
require_once get_template_directory() . '/admin/options/options.php';
require_once get_template_directory() . '/admin/content/content.php';

// Theme support declarations
function memoria_theme_setup(): void {
	add_theme_support('menus');
	add_theme_support('post-thumbnails');
	add_theme_support('align-wide');
	add_theme_support('html5', ['gallery', 'caption']);
}
add_action('after_setup_theme', 'memoria_theme_setup');

// Enqueue media library scripts
function memoria_enqueue_scripts(): void {
	$config = memoria_config();
	$is_dev = memoria_is_dev();
	$version = wp_get_theme()->get('Version');

	// Enqueue scripts and styles
	wp_enqueue_media();
	
	if ($is_dev) {
		// Dev: load from Vite server (run `npm run dev -w memoria-headless`)
		wp_enqueue_script_module('memoria-vite-client', $config->dev->client, [], null);
		wp_enqueue_script_module('memoria-admin', $config->dev->admin, [], null);
	} else {
		// Production: use bundled scripts (run `npm run build -w memoria-headless`, then upload the contents of dist as the theme)
		wp_enqueue_script_module('memoria-admin', $config->paths->js . '/admin.js', [], $version);
		wp_enqueue_style('memoria-admin-styles', $config->paths->css . '/admin.css', [], $version);
	}
}
add_action('admin_enqueue_scripts', 'memoria_enqueue_scripts');

// Register nav menus so they appear in WP admin and are queryable
function memoria_init_menus(): void {
	register_nav_menus([
		'primary' => __('Primary Navigation'),
		'footer'  => __('Footer Navigation'),
	]);
}
add_action('init', 'memoria_init_menus');

// Allow Astro dev server to call the API without CORS errors
function memoria_init_dev_api(): void {
	remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

	add_filter('rest_pre_serve_request', function ($value) {
		$allowed = ['http://localhost:4321', 'http://localhost:4322'];
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

		if (in_array($origin, $allowed, true)) {
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Methods: GET, OPTIONS');
			header('Access-Control-Allow-Credentials: true');
		}

		return $value;
	});
}
add_action('rest_api_init', 'memoria_init_dev_api');

// Remove unused Appearance submenu items (priority 999 ensures WP has registered them first)
function memoria_admin_menu_removal(): void {
	global $submenu;

	// Customizer slug includes a dynamic ?return=... param so remove_submenu_page can't match it — scan directly
	if (isset($submenu['themes.php'])) {
		foreach ($submenu['themes.php'] as $key => $item) {
			if (isset($item[2]) && strpos($item[2], 'customize.php') !== false) {
				unset($submenu['themes.php'][$key]);
			}
		}
	}

	remove_submenu_page('themes.php', 'theme-editor.php'); // Theme File Editor
	remove_submenu_page('themes.php', 'site-editor.php'); // Design
	remove_submenu_page('themes.php', 'site-editor.php?p=/pattern'); // Patterns
	remove_submenu_page('themes.php', 'font-library.php'); // Fonts
}
add_action('admin_menu', 'memoria_admin_menu_removal', 999);