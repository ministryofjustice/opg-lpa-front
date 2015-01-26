<?php

// autoload_real.php @generated by Composer

<<<<<<< HEAD
class ComposerAutoloaderInit5a72f2277f73faa415972baeaf6f76c8
=======
class ComposerAutoloaderInit9ce00db23acd9821fb52758dcae46870
>>>>>>> 27cc746f1b154e64210534514e34c40c2cc99e62
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

<<<<<<< HEAD
        spl_autoload_register(array('ComposerAutoloaderInit5a72f2277f73faa415972baeaf6f76c8', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit5a72f2277f73faa415972baeaf6f76c8', 'loadClassLoader'));
=======
        spl_autoload_register(array('ComposerAutoloaderInit9ce00db23acd9821fb52758dcae46870', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit9ce00db23acd9821fb52758dcae46870', 'loadClassLoader'));
>>>>>>> 27cc746f1b154e64210534514e34c40c2cc99e62

        $includePaths = require __DIR__ . '/include_paths.php';
        array_push($includePaths, get_include_path());
        set_include_path(join(PATH_SEPARATOR, $includePaths));

        $map = require __DIR__ . '/autoload_namespaces.php';
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }

        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        $loader->register(true);

        $includeFiles = require __DIR__ . '/autoload_files.php';
        foreach ($includeFiles as $file) {
<<<<<<< HEAD
            composerRequire5a72f2277f73faa415972baeaf6f76c8($file);
=======
            composerRequire9ce00db23acd9821fb52758dcae46870($file);
>>>>>>> 27cc746f1b154e64210534514e34c40c2cc99e62
        }

        return $loader;
    }
}

<<<<<<< HEAD
function composerRequire5a72f2277f73faa415972baeaf6f76c8($file)
=======
function composerRequire9ce00db23acd9821fb52758dcae46870($file)
>>>>>>> 27cc746f1b154e64210534514e34c40c2cc99e62
{
    require $file;
}
