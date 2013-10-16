# Class Scanner

Regenix does not use a classic way for loading classes, does not use classes' names
for searching their locations in a file sturcture. Usually, in PHP, frameworks
have some class loader that loads classes using their names. 

Namespaces have been implemented in PHP 5.3 and they are now used for improved
searching classes via their names. 

We have choosen an another way...


## Architecture

In Regenix, there is a scanner for searching locations of classes, which does not use 
their names. It scans all classpath directories and parse found files to get 
information about classes inside, than the scanner stores this information to cache. 
Next, class scanner can easilly find a location of a class using cache. It works 
very well and has high performance. 

In Regenix, the scanner is a static class with a number of static methods. It uses
tokinizer extension for parsing files and internal cache system. 

The class has a number of important methods:

    -> addClassPath(string $path, bool $scan = true)
    -> scan(bool $cached = true)
    -> find(string $className)

1. **addClassPath** - adds a new classpath directory for scanning classes.
2. **scan** -  scans all classpath directories that have been registered before.
3. **find** - finds information about a class.

> **IMPORTANT**: After every classpath adding, you should to call the scan method.
> By default, in the `addClassPath`, the scan method will be called after, but you
> can manually do that. 


## How does the scan method work?

By default, this method uses cache to do not scan directories so often. Before starting, 
the scan method checks if something has been changed in classpath directories or not. 
In case some file has been changed, it will invoke full rescan of all files in classpath 
directories. Usually, it takes a little time about 100 - 1000 mlsec, but you do not need to
worry about it because this procedure is invoked only once time when cache is empty. 
