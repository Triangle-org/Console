<?php

return [
    'enable' => true,

    'build_dir' => BASE_PATH . DIRECTORY_SEPARATOR . 'build',

    'phar_filename' => 'triangle.phar',

    'bin_filename' => 'triangle.bin',

    'signature_algorithm' => Phar::SHA256, // Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, Phar::OPENSSL.

    'private_key_file' => '', // Для Phar::OPENSSL

    'exclude_pattern' => '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/))(.*)$#',

    'exclude_files' => [
        '.env', 'LICENSE', 'composer.json', 'composer.lock', 'triangle.phar', 'triangle.bin'
    ],

    'custom_ini' => '
    memory_limit = 256M
    ',
];