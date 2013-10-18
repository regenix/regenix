# Routing (URLs)

In Web MVC pattern, controller actions are always called by using URLs. Binding actions
and URLs is named `Routing`. In Regenix there is only one way for this. The framework
has a special type of configuration for routing, it is located at `conf/route` file.

---

## Introduction

A typical http request contains information about:

+ `protocol` - http or https
+ `host` - or domain, example: `example.com`
+ `port` - http port, default http port - 80
+ `path` - a string after host and port, example `/news/list`.
+ `query` - a string after path and `?`, example `id=2&open=true`
+ `method` - POST, GET, PUT, etc.

All this information is used to direct http requests to controller actions.

---

## REST Architecture

Representational State Transfer (REST) is one of architectures of web applications.
REST is based on the followings:

1. An application is divided into resources.
2. Access to every resource via URI
3. All resources use one interface for passing a state between a client and resources.

If an application follows the principles of REST the application is named RESTful. 
Regenix allows to create RESTful application easily. 

+ Routing in Regenix provides binding URLs and controller methods. Also, it supports regular expressions
  whereby you can set more flexible rules.
+ The framework don't keep a state. This means that you cannot save a state on a server between two requests.
+ Regenix allows to get all information of an http request.

---

## Configuration

There is a special configuration file in an application directory - `conf/route`. This file has the following
format:

    <METHOD>       <PATH>       <ACTION_NAME>

There:

+ `METHOD` may be POST, GET, PUT, DELETE, PATCH, OPTIONS, HEAD or `*` for any method.
+ `PATH` - absolute path of url, example: `/news/123`
+ `ACTION_NAME` - a string contains the name of controller class and the name of its method.

To understand this format, you need to look the following example:

    *     /             Application.index

    GET   /news/        News.index
    GET   /news/{id}    News.detail

Here are three rules: two ones for static URLs and other one for a dynamic URL with `id` argument.
`Application.index`, `News.index` and `News.detail` are the names of actions, the first part of the name
is the name of a controller class, the last part is a method name of the controller class.

> To understand it you should read about controllers in Regenix.

The controller `Application` will look like shown below:

    <?php
    namespace controllers;

    use regenix\mvc\Controller;

    class Application extends Controller {
      public function index(){
        // action method
      }
    }

When you open your application in browser, it will create an instance of the Application class 
and call the `index` method. 

---

## HTTP Methods

Regenix supports all http methods in route configuration: `GET`, `POST`, `PUT`, `DELETE`,
`PATCH`, `OPTIONS`, `HEAD`. However if you want to bind one action to all http methods,
you can use a special symbol - `*`. This symbol means that all http requests will be associated
with an action.

---

## The URI pattern

The URI pattern defines the route's request path. Some parts of the request path can be dynamic.
If you want a controller method is called by opening some URL, see the next example:

    GET    /news/       NewsController.index
     
Here we have defined `/news/` URL which is associated with the `controllers\NewsController` class and
its method `index`. This URL will only be available via `GET` method. This pattern has no dynamic parts.
Next, we will consider URI pattern with dynamic parts.

---

##### Dynamic parts

For example a news detail page URL:

    GET   /news/{id}    NewsController.detail

There is the `id` dynamic part. Dynamic parts are enclosed to braces `{...}`. By default
a dynamic part corresponds to the regex `[^/]+` pattern. To get value of dynamic parts, use
arguments of a controller method, for example:

    public function detail($id){
        // $id equals to the value of `{id}` from URI pattern:     GET   /news/{id}    NewsController.detail
    }
    
The names of method arguments corresponds to the names of route's dynamic parts. Look at a few examples:

    GET   /news/{category}/{id}     NewsController.detail 
    
    public function detail($category, $id){
        ...
        
        $this->render();
    }

You can use default values in arguments:

    public function detail($category = 'common', $id){
        // if the route pattern does not contain `category` dynamic part then `$category` will equal to `common`
    }

---

#### Regex URI patterns

Sometimes you need to use regex in URI patterns. Regenix supports this feature. For example:

    GET   /news/{id<[0-9]+>}      NewsController.detail

Here we have set `id` as a numeric value via the regex expression - `[0-9]+`. To set a regex expression
for dynamic parts use the next syntax: `{name<regex>}`.

---

#### Backslashes (/) at the end of URLs

Often times you need URLs are available with and without backslash at the end. This is easy to implement,
use a regex char `?` at the end of the URI pattern:

    GET   /news/?       NewsController.detail
    
The URL `/news/` will also be available at `/news` (without backslash).

---

#### Reverse routing

You can generate a URL by using the `Router::path()` method. This means that a URL can be generated with
an action name and parameters. Regenix tries to find a router's rule and reverse it to a URL.
Consider the next example:

    GET   /news/{id}    NewsController.detail

    $url = Router::path('NewsController.detail', array('id' => 123));
    // $url will be `/news/123`

The `path` method is quite flexible to resolve difficult rules.

**How generate URLs in templates?**

Regenix templates have a special tag for this - `{path ...}`. Learn the next example:

    <a href="{path 'NewsController.detail', id: 123}">detail link</a>

Read more about it in [`views` chapter](views.md).

---

## How add new router's rule dynamically?

You can add a new rule by not using the `conf/route` file. To do this, use Router's instance
and method `addRoute`:

    public function addRoute($method, $path, $action, $params = '')

To get Router's instance, use DI or the Application `router` property:

    $router = Regenix::app()->router;
    $router->addRoute('GET', '/news/{id}', 'NewsController.detail');

Or by using dependency injection:

    $router = DI::getInstance(Router::type);
    $router->addRoute('GET', '/news/{id}', 'NewsController.detail');

Read about DI in [this chapter](di.md).

---

## Routing priority

Many routes can match the same request. If there is a conflict, the first route (in declaration order) is used.