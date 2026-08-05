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
    '@fortawesome/fontawesome-free' => [
        'version' => '7.3.1',
    ],
    '@fortawesome/fontawesome-free/css/fontawesome.min.css' => [
        'version' => '7.3.1',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-free/js/fontawesome.min.js' => [
        'version' => '7.3.1',
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
];
