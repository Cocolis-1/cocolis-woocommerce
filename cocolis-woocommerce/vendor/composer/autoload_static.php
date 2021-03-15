<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticIniteabf9c29854eeedd4c9bce9a9cc61bcb
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PrestaShop\\Module\\Cocolis\\' => 26,
        ),
        'C' => 
        array (
            'Cocolis\\Api\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PrestaShop\\Module\\Cocolis\\' => 
        array (
            0 => __DIR__ . '/../../..' . '/src',
        ),
        'Cocolis\\Api\\' => 
        array (
            0 => __DIR__ . '/..' . '/cocolis/php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticIniteabf9c29854eeedd4c9bce9a9cc61bcb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticIniteabf9c29854eeedd4c9bce9a9cc61bcb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticIniteabf9c29854eeedd4c9bce9a9cc61bcb::$classMap;

        }, null, ClassLoader::class);
    }
}
