/* Packages */
import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import importPlugin from 'eslint-plugin-import';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default tseslint.config(
	{
		ignores: ['**/*.js'],
	},
	{
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: {
				...globals.browser,
				...globals.node,
				...globals.es2022,
			},
			parserOptions: {
				project: './tsconfig.json',
				tsconfigRootDir: import.meta.dirname,
			},
		},
		settings: {
			'import/parsers': {
				'@typescript-eslint/parser': ['.ts'],
			},
			'import/resolver': {
				typescript: { alwaysTryTypes: true },
			},
		},
	},
	js.configs.recommended,
	tseslint.configs.recommendedTypeChecked,
	importPlugin.flatConfigs.errors,
	eslintConfigPrettier,
	{
		rules: {
			'prefer-const': 'error',
			'@typescript-eslint/consistent-type-imports': 'error',
			'@typescript-eslint/no-empty-function': 'off',
			'@typescript-eslint/no-require-await': 'off',
			'@typescript-eslint/no-explicit-any': 'error',
			'@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
			'@typescript-eslint/require-await': 'off',
			'import/no-unresolved': 'off',
		},
	},
);
