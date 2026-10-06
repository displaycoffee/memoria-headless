/* Packages */
import { cpSync, readdirSync } from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig } from 'vite';
import basicSsl from '@vitejs/plugin-basic-ssl';

/* Paths */
const themePath = fileURLToPath(new URL('.', import.meta.url));
const srcPath = fileURLToPath(new URL('./src', import.meta.url));
const distPath = fileURLToPath(new URL('./dist', import.meta.url));

/* Theme files WordPress needs at runtime, copied into dist so it's a complete theme to upload (root PHP files are added automatically) */
const themeFiles = ['admin', 'assets', 'screenshot.png', 'style.css', 'theme.json'];

/* Vite plugin that fully reloads the page when a theme PHP file changes */
/* Note: the PHP lives outside of root (src), so the theme folder has to be added to the watcher (chokidar doesn't take globs) */
const phpReload = () => {
	return {
		name: 'php-reload',
		apply: 'serve',
		configureServer(server) {
			server.watcher.add(themePath);
			server.watcher.on('change', (file) => {
				if (file.endsWith('.php')) server.ws.send({ type: 'full-reload', path: '*' });
			});
		},
	};
};

/* Vite plugin that copies the theme files into dist after a build */
const themeCopy = () => {
	return {
		name: 'theme-copy',
		apply: 'build',
		closeBundle() {
			const phpFiles = readdirSync(themePath).filter((file) => file.endsWith('.php'));
			for (const file of [...phpFiles, ...themeFiles]) {
				cpSync(path.join(themePath, file), path.join(distPath, file), { recursive: true });
			}
		},
	};
};

export default defineConfig({
	root: 'src',
	publicDir: false, // Static theme files (PHP, fonts, style.css) live at the theme root, where WordPress expects them
	envDir: '../',
	plugins: [basicSsl(), phpReload(), themeCopy()],
	server: {
		host: 'localhost',
		port: 3006,
		strictPort: true, // functions-helpers.php loads the dev scripts from this port
		origin: 'https://localhost:3006',
		cors: {
			origin: /^https:\/\/([a-z0-9-]+\.)*ddev\.site$/,
		},
	},
	resolve: {
		tsconfigPaths: true, // Resolve the @/ alias (maps to src) from tsconfig.json paths
	},
	css: {
		preprocessorOptions: {
			scss: {
				loadPaths: [srcPath], // Lets Sass @use files from src without relative paths, e.g. @use '_core/styles/_theme'
			},
		},
	},
	build: {
		outDir: '../dist',
		emptyOutDir: true,
		modulePreload: {
			polyfill: false,
		},
		rollupOptions: {
			input: {
				admin: `${srcPath}/admin.ts`,
			},
			output: {
				// Fixed names (no hashes) so functions.php can enqueue assets/js/admin.js and assets/css/admin.css directly
				assetFileNames: 'assets/[ext]/[name][extname]',
				chunkFileNames: 'assets/js/[name].js',
				entryFileNames: 'assets/js/[name].js',
			},
		},
	},
});
