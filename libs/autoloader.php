<?php
//Autoloader start
if(!function_exists("Autoloader")){

    function Autoloader($class)
    {
        $basedir = realpath(__DIR__ . '/..') . '/';
        if (preg_match("/controller$/i", $class))
        {
            // Resolving Controller classes
            $filepath = sprintf('controllers/%s.controller.php', strtolower(preg_replace("/controller$/i", "", $class)));

            if (file_exists($basedir . strtolower($filepath)))
            {
                require $basedir. strtolower($filepath);
                return true;
            }
        }

        // Check if the class is namespaced
        if (strpos($class, '\\') !== false)
        {
            // Use namespace as file name
            $class = substr($class, 0, strpos($class, '\\'));
        }

        // Path to class file
        $filepath = 'classes/' . $class . '.class.php';

        // If the file doesn't exist it might be a child class
        if (!file_exists($basedir . strtolower($filepath)))
        {
            // Use first part of camelcased class name as file name
            preg_match_all('/[A-Z]+[^A-Z]*/', $class, $parts);

            if (isset($parts[0][0]))
            {
                $class = $parts[0][0];
                $filepath = 'classes/' . $class .'.class.php';
            }
        }

        if (file_exists($basedir . strtolower($filepath)))
        {
            require $basedir. strtolower($filepath);
            return true;
        }

		 // Path to libs class file
        $libpath = 'libs/' . $class . '.class.php';
		// If the file doesn't exist it might be a child class
        if (!file_exists($basedir . strtolower($filepath)))
        {
            // Use first part of camelcased class name as file name
            preg_match_all('/[A-Z]+[^A-Z]*/', $class, $parts);

            if (isset($parts[0][0]))
            {
                $class = $parts[0][0];
                $filepath = 'libs/' . $class .'.class.php';
            }
        }
		
        if (file_exists($basedir . strtolower($filepath)))
        {
            require $basedir. strtolower($filepath);
            return true;
        }
        return false;
    }
}
spl_autoload_register('Autoloader');

//require_once __DIR__ . '/../vendor/autoload.php';
//Autoloader end
