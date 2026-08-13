<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/main/app.js',
        'entrypoint' => true,
    ],
    'index' => [
        'path' => './assets/mascotte/mascotte.js',
        'entrypoint' => true,
    ],
    'music' => [
        'path' => './assets/music/music.js',
        'entrypoint' => true,
    ],
    'schedule' => [
        'path' => './assets/schedule/schedule.js',
        'entrypoint' => true,
    ],
    'story' => [
        'path' => './assets/story/story.js',
        'entrypoint' => true,
    ],
    'join' => [
        'path' => './assets/login/join.js',
        'entrypoint' => true,
    ],
    'story-admin' => [
        'path' => './assets/desk/story-admin.js',
        'entrypoint' => true,
    ],
    'avatar-preview' => [
        'path' => './assets/desk/avatar-preview.js',
        'entrypoint' => true,
    ],
    'note-admin' => [
        'path' => './assets/desk/note-admin.js',
        'entrypoint' => true,
    ],
    'jquery' => [
        'version' => '3.6.0',
    ],
    'bootstrap' => [
        'version' => '4.6.2',
    ],
    'popper.js' => [
        'version' => '1.16.1',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'three' => [
        'version' => '0.128.0',
    ],
    'three/examples/jsm/loaders/GLTFLoader.js' => [
        'version' => '0.128.0',
    ],
    'three/examples/jsm/controls/OrbitControls.js' => [
        'version' => '0.128.0',
    ],
    'remixicon/fonts/remixicon.css' => [
        'version' => '4.9.1',
        'type' => 'css',
    ],
    'easymde' => [
        'version' => '2.21.0',
    ],
    'codemirror' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/edit/continuelist.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/display/fullscreen.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/mode/markdown/markdown.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/mode/overlay.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/display/placeholder.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/display/autorefresh.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/selection/mark-selection.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/addon/search/searchcursor.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/mode/gfm/gfm.js' => [
        'version' => '5.65.21',
    ],
    'codemirror/mode/xml/xml.js' => [
        'version' => '5.65.21',
    ],
    'codemirror-spell-checker' => [
        'version' => '1.1.2',
    ],
    'marked' => [
        'version' => '4.3.0',
    ],
    'typo-js' => [
        'version' => '1.3.2',
    ],
    'codemirror/lib/codemirror.min.css' => [
        'version' => '5.65.21',
        'type' => 'css',
    ],
    'easymde/dist/easymde.min.css' => [
        'version' => '2.21.0',
        'type' => 'css',
    ],
];
