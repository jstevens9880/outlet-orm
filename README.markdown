OutletORM
=========

Outlet is an open source object-to-relational mapping tool for PHP.

It differs from other orm solutions for php in that it provides transparent, unobtrusive persistence. It does not require your entity objects to implement any interfaces or extend some sort of base class. It is also very lightweight, only a handful of classes and tools.
It uses an approach similar to hibernate in java, using proxy objects that save the data behind the scenes.

Installation
------------

To install OutletORM on your project, create a new empty project and clone the repository.

On your project add OutletORM's folder to your include path:

    $outletRootDir = 'Your path to Outlet`s main dir';
    set_include_path(get_include_path() . PATH_SEPARATOR . $outletRootDir);

On your project include the OutletORM's autoloader and register it with SPL:

    require 'application/org.outlet-orm/autoloader/OutletAutoloader.php';
    OutletAutoloader::register();
    
Usage
-----

Create the configuration file, following the XSD schema (for XML config files) or www.outlet-orm.org instructions for array config.
Initialize Outlet engine with the XML string, path to XML file or your config array:

    Outlet::Init('/var/www/my-project/config/entities.xml'); 