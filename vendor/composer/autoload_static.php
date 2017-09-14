<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit81d36764bc127327e2e1afcb1dd2c2c6
{
    public static $files = array (
        'ef6802c8a38664a4b1e8712ed25377fb' => __DIR__ . '/..' . '/shuber/curl/curl.php',
        '35e59de4710b0d6ef1e7e82248a7a88e' => __DIR__ . '/../..' . '/src/autoload.php',
    );

    public static $classMap = array (
        'MySQLDump' => __DIR__ . '/..' . '/dg/mysql-dump/src/MySQLDump.php',
        'MySQLImport' => __DIR__ . '/..' . '/dg/mysql-dump/src/MySQLImport.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit81d36764bc127327e2e1afcb1dd2c2c6::$classMap;

        }, null, ClassLoader::class);
    }
}
