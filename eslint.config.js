import js from '@eslint/js';
import globals from 'globals';
import prettierRecommended from 'eslint-plugin-prettier/recommended';

export default [
    { ignores: ['assets/vendor/**', 'node_modules/**', 'vendor/**'] },
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                $: 'readonly',
                jQuery: 'readonly',
                WaveSurfer: 'readonly',
            },
        },
        rules: {
            // Was 'warn' while legacy code had pre-existing dead/duplicate variables
            // (Phase 1 style pass didn't block on cleanup belonging to the page-by-page
            // pass, Phase 5). That cleanup is done (2026-08-25): back to 'error' so a
            // regression fails lint instead of blending into the warning noise.
            'no-unused-vars': 'error',
            'no-redeclare': 'error',
            'no-unassigned-vars': 'error',
        },
    },
    prettierRecommended,
];
