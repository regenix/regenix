Welcome to Regenix framework
============================

Regenix is easy-to-learn and powerful MVC framework. Our framework is similar to the [Play! Framework](http://playframework.com/),
Ruby on Rails and Django frameworks, but has a lot of unique ideas.

[![Build Status](https://travis-ci.org/dim-s/regenix.png?branch=dev)](https://travis-ci.org/dim-s/regenix)

![regenix](http://develstudio.ru/upload/medialibrary/cf8/cf88db498096a1eba21c75f7910a4ef4.png)

Features
--------
* The clear MVC.
* The easy and powerful routing (sub-routing, inserts, etc).
* Easy debugging, displaying errors in the detailed form.
* The Dependency Manager for assets and modules (git, local repos).
* Multiple applications within one core.
* REST and other special types of controllers.
* Dependency Injection Container.
* Convenient validators like tests.
* The fast template engine with a simple syntax.
* Lazy loading of classes.
* The smart scanner for searching classes.
* HTTP util classes: Session, Flash, Headers, Query, Body, etc.
* The smart logger, logging of any errors (even fatal and parse).
* The CLI for managing of applications.
* I18n features.
* Unit and Functional Tests (own implementation)


Requires
--------

* PHP 5.3 or greater
* Apache, Nginx or another server
* Mod_rewrite enabled (for apache)


Getting started
---------------

### Installation

Clone all the sources from our git repo. Next, create a directory in the location `/apps/` of your copy of the framework.
This directory will be the directory of a project. For example, you can name it like `myApp`. Then the full path of your 
app will be `<framework_path>/apps/myApp/`. 

The directory `/apps/` contains all applications and that allows to use one copy of the framework for
several projects. You do not need something like symlinks in Linux to support a few applications. 

The next step, you need to know the typical sturtcure of an application.


### Simple structure of an application

* `conf/` - configurations
 * `conf/application.conf` - the general config
 * `conf/deps.json` - the configuration of asset and module dependencies
 * `conf/route` - the url routing config
 * `conf/routes/` - directory of sub-routes
* `src/` - php sources of your application
 * `src/controllers/`
 * `src/models/`
 * `src/views/`
 * `src/notifiers/` - notifiers for mail sending messages
 * `src/*` - other packages of sources
* `tests/` - sources of unit and functional tests
* `assets/` - local asset directory of your app
