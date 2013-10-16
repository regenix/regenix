# Performance

In this article we will consider how improve performance of the framework and
your applications. We will also describe some features of the framework which
can affect performance.

---

## Opcode caching (APC, XCache, etc.)

The first recommendation for improving the performance is to install and enable opcode caching
extensions such as APC, XCache, eAccelerator. Regenix contains a lot php files which follow PSR-0
standard and therefore all classes are located in separated files (only one class can be in a file).

Every including php file affects the performance and any file operation (reading, checking modification, etc)
reduces performance. An opcache extension caches byte-codes and helps to reduce file operations.

Usually, opcache extensions for PHP have a special boolean option which named like `stat`,
in APC it is `apc.stat`, XCache - `xcache.stat`, eAccelerator - `eaccelerator.check_mtime`. By default,
this option is disabled and located in `php.ini`. When you enable the option any changes of your
code will not affect your applications. It occurs because an opcache extension does not check modification
time of source files for reducing amount of file operations.

Use the stat option in production only! For the changes to take effect (if the option is enabled)
you need to restart your web server or remove all cache data.

---

## Building Framework

Regenix has special functionality for joining all its source files to one file. This can helps to
improve performance in some cases. To do this, use CLI and the `framework-build` command.

```
regenix framework-build
```

This command builds the join file and put it into your temp directory. Next, you can include the build file
by using `Regenix::requireBuild()` before initialization of framework. You can do it in `index.php`:

```
<?php
use regenix\core\Regenix;

    // require main file
    require __DIR__ . '/framework/include.php';

    // If you generate regenix build file via `regenix framework-build`
    Regenix::requireBuild();

    // Init apps
    Regenix::initWeb(__DIR__);
```

> The build file can increase memory usage (about 30-40%), but reduce time of execution.

---

## Don't include php files manually!

You do not need to include php files because Regenix has smart class loader.
It is called `lazy loading`. Regenix include php files only used classes in a request.
