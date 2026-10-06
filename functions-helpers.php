<?php
/**
* Helpers for building theme-specific functionality
*
* @package memoria
* @since 1.0.0
*/

// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

// Function for setting up variable object
function memoria_config(): object {
	// Variables for config object
	$theme = get_template_directory_uri();
	$vite = 'https://localhost:3006';

	// Set up default config
	$config = (object) [
		'dev' => (object) [
			'ddev' => 'ddev.site',
			'client' => $vite . '/@vite/client',
			'admin' => $vite . '/admin.ts'
		],
		'paths' => (object) [
			'css' => $theme . '/assets/css',
			'js' => $theme . '/assets/js'
		],
	];
	
	return $config;
}

// Check if on dev / local
function memoria_is_dev(): bool {
	$config = memoria_config();
	$domain = $_SERVER['HTTP_HOST'];
	return str_contains($domain, $config->dev->ddev);
}