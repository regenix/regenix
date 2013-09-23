Welcome to Regenix framework
============================

Regenix is easy-to-learn and powerful MVC framework. Our framework is similar to [Play! Framework](http://playframework.com/),
Ruby on Rails and Django frameworks, but has a lot of unique ideas.

[![Build Status](https://travis-ci.org/dim-s/regenix.png?branch=dev)](https://travis-ci.org/dim-s/regenix)

![regenix](http://develstudio.ru/upload/medialibrary/cf8/cf88db498096a1eba21c75f7910a4ef4.png)

Features
--------
* Clear MVC architecture.
* Multiple applications within one core.
* Easy and powerful routing (sub-routing, inserts, etc).
* Easy debugging, displaying errors in the detailed form.
* [Propel ORM](http://propelorm.org/) integration for models
* Dependency Manager for assets and modules (git, local repos).
* REST and other special types of controllers.
* Dependency Injection Container.
* Convenient validators like tests.
* Fast template engine with simple syntax.
* Lazy loading of classes.
* Smart scanner for searching classes.
* HTTP util classes: Session, Flash, Headers, Query, Body, etc.
* Smart logger, logging any errors (even fatal and parse).
* CLI for managing applications.
* I18n features.
* Unit and Functional Tests (own implementation)


Requires
--------

* PHP 5.3 or greater
* Apache, Nginx or another server
* Mod_rewrite enabled (for apache)

Documentation (in progress)
-------------

https://github.com/dim-s/regenix-documentation

+ [Installation](https://github.com/dim-s/regenix-documentation/blob/master/en/installation.md)
+ [Configuration](https://github.com/dim-s/regenix-documentation/blob/master/en/configuration.md)
+ [Bootstraps](https://github.com/dim-s/regenix-documentation/blob/master/en/bootstraps.md)
+ [Controllers](https://github.com/dim-s/regenix-documentation/blob/master/en/controllers.md)
+ [Views](https://github.com/dim-s/regenix-documentation/blob/master/en/views.md)
+ [Validation](https://github.com/dim-s/regenix-documentation/blob/master/en/validation.md)
+ [Localization (i18n)](https://github.com/dim-s/regenix-documentation/blob/master/en/i18n.md)
+ [Logging](https://github.com/dim-s/regenix-documentation/blob/master/en/logs.md)
+ [CLI for regenix](https://github.com/dim-s/regenix-documentation/blob/master/en/cli.md)
+ [Class Scanner](https://github.com/dim-s/regenix-documentation/blob/master/en/class_scanner.md)

Getting started
---------------

### Installation

Clone all the sources from our git repo. Our framework contains a few vendor libraries as git submodules 
therefore you need to run `git submodule init` and `git submodule update` after cloning repo.


Next, create a directory in the location `/apps/` of your copy of the framework.
This directory will be the directory of a project. For example, you can name it like `myApp`. Then the full path of your 
app will be `<framework_path>/apps/myApp/`. 

The directory `/apps/` contains all applications and that allows to use one copy of the framework for
several projects. You do not need something like symlinks in Linux to support a few applications. 

The next step, you need to know the typical sturtcure of an application.

* `conf/` - configurations
 * `conf/application.conf` - the general config
 * `conf/deps.json` - the configuration of asset and module dependencies
 * `conf/route` - the url routing config
 * `conf/routes/` - directory of sub-routes
 * `conf/orm/` - configurations for Propel ORM (schema.xml, built.properties, etc.)
* `src/` - php sources of your application
 * `src/controllers/`
 * `src/models/`
 * `src/views/`
 * `src/notifiers/` - notifiers for mail sending messages
 * `src/*` - other packages of sources
 * `Bootstrap.php` - a bootstrap file with a Bootstrap class (not required)
* `tests/` - sources of unit and functional tests
* `assets/` - local asset directory of your app

---

#### Create an application from template

Open your console and cd to a root directory of Regenix. There will be `regenix` and `regenix.bat` 
files therefore the command `regenix` will become available. Then use a special command `regenix new <name>` to
create a new application from template.

    cd /path/to/root_of_server_with_regenix/
    regenix new myApp
    
After this, a new directory `myApp` will appear in `<root>/apps`. To check it is working or not, open
your browser and navigate to `http://localhost/myApp`. This template application will help you to 
understand how create a typical application for Regenix.
