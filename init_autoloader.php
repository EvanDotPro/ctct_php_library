<?php
/**
 * Trying to keep things PHP 5.1.3+
 */
class CtctAutoloader
{
    public static function autoloadClass($classname)
    {
        $filename = dirname(__FILE__) . '/src/' . str_replace('_', '/', $classname) . '.php';
        if (is_readable($filename)) {
            require $filename;
        }
    }
}
spl_autoload_register('CtctAutoloader::autoloadClass');
