# Routing (URLs)

In Web MVC pattern, controller actions are always called by using URLs. Binding actions
and URLs is named `Routing`. In Regenix there is only one way for this. The framework
has a special type of configuration for routing, it is located at `conf/route` file.

---

## Introducation

A typical http request contains information about:

+ `protocol` - http or https
+ `host` - or doman, example: `example.com`
+ `port` - http port, default http port - 80
+ `path` - a string after host and port, exmaple `/news/list`.
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

```
<METHOD>       <PATH>       <ACTION_NAME>
```

There:

+ `METHOD` may be POST, GET, PUT, DELETE, PATCH, OPTIONS, HEAD or `*` for any method.
+ `PATH` - absolute path of url, example: `/news/123`
+ `ACTION_NAME` - a string contains the name of controller class and the name of its method.

To understand this format, you need to look the following example:

```
*     /             Application.index

GET   /news/        News.index
GET   /news/{id}    News.detail
```

Here are three rules: two ones for static URLs and other one for a dynamic URL with `id` argument.
`Application.index`, `News.index` and `News.detail` are the names of actions, the first part of the name
is the name of a controller class, the last part is a method name of the controller class.

> To understand it you should read about controllers in Regenix.

The controller `Application` will look like shown below:

```
<?php
namespace controllers;

use regenix\mvc\Controller;

class Application extends Controller {
  public function index(){
    // action method
  }
}
```

When you open your application in browser, it will create an instance of the Application class 
and call the `index` method. 



