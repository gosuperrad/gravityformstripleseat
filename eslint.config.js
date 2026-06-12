const js = require('@eslint/js');
const globals = require('globals');

module.exports = [
	js.configs.recommended,
	{
		files: ['assets/js/**/*.js'],
		languageOptions: {
			ecmaVersion: 2021,
			sourceType: 'script',
			globals: {
				...globals.browser,
				...globals.es2021,
				// Gravity Forms' global filter/event bus, injected on the page.
				gform: 'readonly',
				jQuery: 'readonly',
			},
		},
		rules: {
			// Caught errors are intentionally swallowed in cookie parsing.
			'no-unused-vars': ['error', { caughtErrors: 'none' }],
		},
	},
];
