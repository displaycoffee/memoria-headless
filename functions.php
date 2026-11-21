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

// Enqueue memoria's block styles in the editor canvas
// Note: enqueue_block_assets is the hook that reaches inside the editor's iframe (admin_enqueue_scripts doesn't), and it also runs on the frontend, hence is_admin()
// Note: in dev, styles are served by Vite but aren't hot reloaded inside the iframe, so refresh the editor to see changes
// Note: load after wp-block-library, since memoria's block styles have the same specificity as its styles in the editor (see editor.scss), so ties go to memoria
function memoria_enqueue_editor_styles(): void {
	if (!is_admin()) {
		return;
	}

	$config = memoria_config();
	$is_dev = memoria_is_dev();
	$version = wp_get_theme()->get('Version');

	if ($is_dev) {
		wp_enqueue_style('memoria-editor-styles', $config->dev->editor, ['wp-block-library'], null);
	} else {
		wp_enqueue_style('memoria-editor-styles', $config->paths->css . '/editor.css', ['wp-block-library'], $version);
	}

	// Keep the canvas in light mode (memoria's dark mode follows the OS unless data-theme is set, same as its theme toggle)
	wp_register_script('memoria-editor-theme', false, [], null);
	wp_enqueue_script('memoria-editor-theme');
	wp_add_inline_script('memoria-editor-theme', "document.documentElement.setAttribute('data-theme', 'light');");
}
add_action('enqueue_block_assets', 'memoria_enqueue_editor_styles');

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
		$allowed = ['http://localhost:4321', 'https://localhost:4321', 'http://localhost:4322'];
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

// Add the gallery's lightbox button type (icon, text, or both) to its markup for memoria's lightbox (Lightbox.tsx)
// Note: WordPress only passes navigationButtonType to the images as block context and puts it in its Interactivity API state, neither of which reaches renderedContent
function memoria_render_gallery_navigation_type(string $block_content, array $block): string {
	$type = $block['attrs']['navigationButtonType'] ?? 'icon';
	$processor = new WP_HTML_Tag_Processor($block_content);

	if ($processor->next_tag(array('class_name' => 'wp-block-gallery'))) {
		$processor->set_attribute('data-navigation-button-type', $type);
	}

	return $processor->get_updated_html();
}
add_filter('render_block_core/gallery', 'memoria_render_gallery_navigation_type', 10, 2);

// Make navigation blocks work with memoria's scripts: submenus become dropdowns (dropdown.ts) and the responsive container becomes a slideout dialog (overlay.ts, slideout.ts)
// Note: WordPress opens both with its Interactivity API (navigation/view.js), which never reaches renderedContent
function memoria_render_navigation_block(string $block_content): string {
	// Swap the responsive container's div for a dialog, so it opens as a modal with a backdrop, inert page, and focus handling
	// Note: the container is the nav's last child, so its closing tag is the last </div> before </nav>
	$block_content = preg_replace('#<div(?=\s[^>]*\bclass="wp-block-navigation__responsive-container[\s"])#', '<dialog', $block_content, 1, $count);
	if ($count) {
		$block_content = preg_replace('#</div>(\s*</nav>\s*)$#', '</dialog>$1', $block_content, 1);
	}

	$overlay_id = preg_match('#<dialog\s[^>]*\bid="([^"]+)"#', $block_content, $matches) ? $matches[1] : '';
	$processor = new WP_HTML_Tag_Processor($block_content);
	$label = '';
	$submenu_id = '';

	while ($processor->next_tag()) {
		$tag = $processor->get_tag();

		// Remove the Interactivity API directives, since WordPress processes them after this filter (e.g. data-wp-bind--aria-expanded drops the aria-expanded set below)
		foreach ($processor->get_attribute_names_with_prefix('data-wp-') ?? array() as $name) {
			$processor->remove_attribute($name);
		}

		if ($tag === 'NAV' && $processor->has_class('wp-block-navigation')) {
			$label = (string) $processor->get_attribute('aria-label');
		} elseif ($tag === 'LI' && $processor->has_class('has-child')) {
			// Submenus that are always open (open-always) stay as they are
			// Note: each item's toggle and submenu come before any nested item, so the id carries over to them
			$submenu_id = $processor->has_class('open-always') ? '' : wp_unique_id('navigation-submenu-');
			if ($submenu_id) {
				$processor->add_class('dropdown');
				$processor->add_class('dropdown-collapsed');
				$processor->set_attribute('data-close-on-click', 'true');
				$processor->remove_attribute('tabindex');
			}
		} elseif ($tag === 'BUTTON' && $processor->has_class('wp-block-navigation-submenu__toggle') && $submenu_id) {
			$processor->add_class('dropdown-button-toggle');
			$processor->set_attribute('aria-controls', $submenu_id);
			$processor->set_attribute('aria-expanded', 'false');
		} elseif ($tag === 'UL' && $processor->has_class('wp-block-navigation__submenu-container') && $submenu_id) {
			$processor->add_class('dropdown-content');
			$processor->set_attribute('id', $submenu_id);
		} elseif ($tag === 'BUTTON' && $processor->has_class('wp-block-navigation__responsive-container-open') && $overlay_id) {
			$processor->set_attribute('aria-controls', $overlay_id);
			$processor->set_attribute('aria-expanded', 'false');
			$processor->set_attribute('data-overlay-open', $overlay_id);
		} elseif ($tag === 'DIALOG') {
			// Overlay on mobile (not hidden-by-default) shows its content inline on desktop, so slideout.ts closes it when the window grows past the breakpoint
			foreach (array('overlay', 'overlay-dismissible', 'slideout', 'slideout-horizontal', 'slideout-left') as $class) {
				$processor->add_class($class);
			}
			$processor->set_attribute('aria-label', $label ?: 'Menu');
			$processor->set_attribute('data-close-on-backdrop', 'true');
			$processor->set_attribute('data-slideout-direction', 'left');
			if (!$processor->has_class('hidden-by-default')) {
				$processor->set_attribute('data-close-on-desktop', 'true');
			}
			$processor->remove_attribute('tabindex');
		} elseif ($tag === 'DIV' && $processor->has_class('wp-block-navigation__responsive-close')) {
			$processor->add_class('overlay-content');
			$processor->remove_attribute('tabindex');
		} elseif ($tag === 'DIV' && $processor->has_class('wp-block-navigation__responsive-container-content')) {
			// slideout.ts closes the overlay when a link inside .slideout-body is clicked
			$processor->add_class('slideout-body');
		} elseif ($tag === 'BUTTON' && $processor->has_class('wp-block-navigation__responsive-container-close')) {
			$processor->set_attribute('data-autofocus', true);
			$processor->set_attribute('data-overlay-close', true);
		}
	}

	return $processor->get_updated_html();
}
add_filter('render_block_core/navigation', 'memoria_render_navigation_block', 10, 1);

// Send login / logout blocks to the dashboard after logging in
// Note: the block renders during the GraphQL request, so WordPress's "current page" redirect points at the GraphQL endpoint, and WordPress's own frontend is blank (Astro is the frontend)
function memoria_render_loginout_block(string $block_content): string {
	$redirect = admin_url();
	$processor = new WP_HTML_Tag_Processor($block_content);

	while ($processor->next_tag()) {
		$tag = $processor->get_tag();

		if ($tag === 'A' && str_contains((string) $processor->get_attribute('href'), 'redirect_to=')) {
			$processor->set_attribute('href', wp_login_url($redirect));
		} elseif ($tag === 'INPUT' && $processor->get_attribute('name') === 'redirect_to') {
			$processor->set_attribute('value', $redirect);
		}
	}

	return $processor->get_updated_html();
}
add_filter('render_block_core/loginout', 'memoria_render_loginout_block', 10, 1);

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