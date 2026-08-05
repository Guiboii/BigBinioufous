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
            // legacy code has pre-existing dead/duplicate variables; downgraded to warn
            // so the style pass (Phase 1) doesn't block on cleanup that belongs to the
            // page-by-page pass (Phase 5) — fixing these means touching actual logic
            'no-unused-vars': 'warn',
            'no-redeclare': 'warn',
            'no-unassigned-vars': 'warn',
        },
    },
    prettierRecommended,
];
