Welcome to Regenix framework
============================

Regenix is easy-to-learn and powerful MVC framework. Our framework is similar to [Play! Framework](http://playframework.com/),
Ruby on Rails and Django frameworks, but has a lot of unique ideas.

[![Build Status](https://travis-ci.org/dim-s/regenix.png?branch=dev)](https://travis-ci.org/dim-s/regenix)

![regenix](http://develstudio.ru/upload/medialibrary/cf8/cf88db498096a1eba21c75f7910a4ef4.png)

Features
--------
* Clear MVC architecture.
* Easy and powerful routing (sub-routing, inserts, etc).
* Easy debugging, displaying errors in the detailed form.
* Dependency Manager for assets and modules (git, local repos).
* Multiple applications within one core.
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


Getting started
---------------

### Installation

Clone all the sources from our git repo. Next, create a directory in the location `/apps/` of your copy of the framework.
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
* `src/` - php sources of your application
 * `src/controllers/`
 * `src/models/`
 * `src/views/`
 * `src/notifiers/` - notifiers for mail sending messages
 * `src/*` - other packages of sources
* `tests/` - sources of unit and functional tests
* `assets/` - local asset directory of your app

---

#### Hello World

Create a new controller inhereted by `regenix\mvc\Controller` class in `controllers` namespace
and define a public non-static method inside like this:

    namespace controllers;

    use regenix\mvc\Controller;

    class MainCtrl extends Controller {
    
      public function index(){
        // and here output "hello world"
        $this->renderText("Hello World!");
      }
    }
    
Next, define a new pattern in the routing of your project `conf/route`. Add a new line there:

    GET   /helloworld     MainCtrl.index
    
That is it. Now, you can open your browser, go to the url `http://localhost/helloworld` and you 
can see the output of "Hello World".


#### Using templates

If you want to output some formatted text, you need to write views (or templates, this is the same).
In Regenix templates has own its syntax. Create a new template in the path `/src/views/index.html`

> *Notice*: All the views have the html extension.

For example:

    <html>
        <head>
            <title>{get 'title'}</title>
            {html.asset 'mystyle.css'}
            {html.asset 'myscript.js'}
            {deps.asset 'jquery'}
        </head>
    <body>
        Hello, {$user}!
    </body>
    </html>
    
Use the "hello world" example to create a controller and route pattern.
Write the next code in the controller method `index`:

    public function index(){
        $this->put("user", "Mike");
        $this->render("index.html");
    }
    
At this example, we use the `render` method to output a template. We also put a user variable and
we can use this variable in the template. You can also notice that we have used some expressions:
`{html.asset ...}, {get ....}` etc. These expressions are tags. `{html.asset ...}` includes 
an asset from the app directory. 

In general, Regenix templates have many kinds of tags. 
