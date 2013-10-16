**Contents**

+ [Introduction](#introduction)
+ [Query Parameters](#query-parameters)
+ [Router Parameters](#router-parameters)
+ [POST, PUT and Body parameters](#post-put-and-body-parameters)
+ [Request information](#information-about-a-request)
+ [Rendering data](#rendering-data)
  + [Simple formats](#simple-formats)
  + [JSON format](#json-format)
  + [Templates](#templates)
  + [Sending files](#sending-files)
  + [Custom rendering](#custom-rendering)
  + [Redirects](#redirects)
+ [Controller lifecycle callbacks](#controller-lifecycle-callbacks)
  + [onBefore, onAfter, onFinally](#onbefore-onafter-onfinally)
  + [onException, onHttpException](#onexception-onhttpexception)
  + [onReturn](#onreturn)
  + [onBindParams](#onbindparams)
+ [REST Controller](#rest-controller)
+ [How to return 404 and other http errors?](#how-to-return-404-and-other-http-errors)

---

# Controllers

> Regenix is an MVC framework. Controllers are the important part of our framework.

In Regenix, a controller is a class inherited by the `regenix\mvc\Controller` 
abstract class. The public methods of a controller are the actions for proccessing 
http requests.

---

## Introduction

Usually, all controllers are located at the `<app>/src/controllers/` directory and 
belong to the `controllers` namespace.

Our recommends:

  - The name of a class controller should match its file name.
  - The namespace of controller should start with `controllers`.
  - In one file should be only one controller class.

Next, the example:

    src/controllers/Clients.php
    
    <?php
    namespace controllers;
    
    use regenix\mvc\Controller;
    
    class Clients extends Controller {
        // the action
        public function index(){
          // ... something
        }
    }
    
> **NOTICE**: In regenix, URLs do not depend on names of actions as in another MVC frameworks.
> There are another way for routing in our framework. Read more about this in the 
> `routing` chapter.

### Modifiers of methods

There are tree modifer types in PHP - `private`, `protected`, `public`. 
Only public methods of a controller can be actions for http requests,
except methods are defined in abstract classes.


### Helpful controller properties

The basic controller class has a number of properties which you can use for
something:

1. `Response $response` - an instance of response class that will be sent to browser.
2. `Request $requet` - a request instance that contains information about a browser request - host, path, uri and more.
3. `Session $session` - an object that allows to manage user data in a session.
4. `Flash $flash` - an object that allows to manage flash messages, it uses a current session object for saving messages.
5. `Cookie $cookie` - an object for managing cookies.
6. `Query $query` - an object that allows to get arguments from a query string of URL.
7. `RequestBody $body` - a special object that allows to get data such as POST arguments from body of a request.
8. `array $routeArgs` - an array of router arguments
9. `string actionMethod` - a current method name which is invoked by a request.
10. `ReflectionMethod actionMethodReflection` - a reflection object of a current method which is invoked by a request.
11. `array $renderArgs` - arguments which will be passed to a template view.

---

## Query Parameters

Usually, in PHP you use the `$_GET` supper global variable for getting query parameters.
Our framework supports another way for this. You can get query parameters from
method arguments, for example: 

    public function index($page){
      // $page will be equal to $_GET[page]
    }
    
    
Also, you can use default values:

    public function index($page = 1){
      // $page will only be equal to $_GET[page] if it exists, 
      // in other cases the value will be equal to 1.
    }
    
    
The alternative way is usage of the `query` property:

    public function index(){
      $page = $this->query->get('page');
    }
    
The query property is an instance of the `ArrayTyped` class. Read more about this class
in other chapter.

---

## Router parameters

Router's parameters are also passed to controllers. To get these you can use
method arguments like shown above:

    public function index($id){
        // if there is the `id` param in the router, $id will be equal to the router's id param.
    }
    
Method arguments can be used for getting route and query arguments. The priority of route arguments is higher than query arguments.

The alternative way is usage of the `routeArgs` property:

    public function index(){
        $id = $this->routeArgs['id'];
    }

The routeArgs is a typical array.

--- 

## POST, PUT and body parameters

We do not recommend to use the `$_POST` variable for getting body parameters. There are much better ways for this in Regenix:

1. the `$body` property is an instance of the `regenix\mvc\RequestBody` class.
2. overriding the special method `onBindParams`. 

```
public function index(){
    // parse body as json
    $form = $this->body->asJson();
    
    // parse body as query (key=value&...)
    $form = $this->body->asQuery();
    
    // get body as single string
    $form = $this->body->asString();
    
    // getting a file
    $file = $this->body->getFile('image');
    
    // getting a file group 
    $file = $this->body->getFiles('images');
    
    // getting all files
    $file = $this->body->getAllFiles();
}
```

> Read about overriding `onBindParams` below.
    
---

## Information about a request

The basic controller class has a special property `$request` for getting information about a current request. This property is an instance of the `regenix\mvc\Request` class and it has the next methods:

* `getHash()`: returns a sha1 hash of all request properties (host, port, etc.), returns a string
* `getUri()`: returns a current uri, returns a string
* `getHeader(string $name, $def = null)`: returns a parameter from headers by name, returns a string. The name is not case sensitive. If a requested parameter is not exists, it will returns default value (null or you can pass any value to $def).
* `hasHeader(string $name)`: returns true if header argument is exists, returns a boolean value. The name argument is not case sensitive.
* `getQuery()`: returns a query string from a current URL, it is a string after the `?` excluding this symbol.
* `getPath()`: returns a path string from a current URL, it is a string after the host, always starts with the `/` symbol, for example: `/user/news/11`
* `getHost()`: returns a current host without a port and `http://`, returns a string.
* `getUserAgent()`: returns a useragent string of current request from headers.
* `getReferer()`: returns a referer string (usually URL) from headers.
* `getMethod()`: returns an http method of current request, it is a string, for example: `GET`, `POST`, `PUT`, etc.
* `isMethod(string $method)`: returns true if a current http method is equal to a value of the `$method` paramater that is not case sensitive.
* `isMethods(array $methods)`: returns true if one of values is equal to a current http method.
* `getLanguages()`: returns an array of languages that a user supports from headers, it uses the `accept-language` header, returned values looks like: `["en", "ru", "ua"]`.
* `isAjax()`: returns true if a current request is sent with Ajax, it uses the `x-requested-with` header for check.

---

## Rendering data

For rendering any data, our framework has several methods: `render()`, `renderText()`, `renderHtml()`,
`renderJson()`, etc. Calls of these methods interrupt the execution of a controller's method via
throwing exceptions therefore you don't need to use `return` to exit the method. Frequently, you 
need to exit after rendering data and therefore this behaviour is convenient. 

```
public function action(){
  $this->render("OK");
  // exit...
  // anything below will not work ...
}
```

### Simple formats

For rendering a clear text or html, you need to use `renderText(string $text)` and 
`renderHtml(string $html)` methods. The both methods output a text by changing
the header's value `Content-type`, for simple text is `text/plain`, for html - `text/html`.

```
public function action(){
  $this->renderText("Simple text");
  $this->renderHtml("<b>Html text</b>");
}
```

Also, you can use the universal method `render(mixed $object, array $args = null)` for
rendering a simple text:

```
public function action(){
  $this->render("Simple text");
}
```

### JSON Format

For rendering JSON, use the `renderJson(mixed $object)` method. It uses
the `json_encode` php function and set up header value `Content-type` to `application/json`.

```
public function action(){
  $this->renderJson( array('status' => 'ok') );
}
```

### Templates

For rendering templates, use the `render(mixed $data, array $args = null)` or 
`renderTemplate(string $template, array $args = null)` methods. To learn more about this,
read the **Views** chapter. However, we yet consider a few examples:

```
public function action(){
  $this->renderTemplate("index.html", array("var" => "value"));
  // or
  $this->render("index.html", array("var" => "value"));
}
```

Also, there are several helpful methods for templates:

1. `boolean templateExists(string $name)` - returns true if a template exists.
2. `string template()` - returns the name of a current template by using special pattern (read more: [views](views.md)).


### Sending files

For sending a file into browser, use the `renderFile(mixed $file, bool $attach = true)` method. 
It allows to send file by two ways as attached files and rendering files in a browser (for example: pictures).
You can change this behaviour via the `$attach` argument that is true by default.
The `$file` argument can be two types: string or `regenix\lang\File` and it should contain full path to a file.

Examples:

```
public function action(){
  $this->renderFile("/path/to/file.ext");
  // or
  $file = new File("/path/to/file.ext");
  $this->renderFile($file);
}
```

Rendering images:

```
public function image(){
  $this->renderFile("/path/to/file.jpg", false);
}
```


### Redirects

For redirecting, use methods: 

1. `redirectUrl(string $url, bool $permanent = false)` - redirect by using a url
2. `redirect(string $action, array $args = [], bool $permanent = false)` - redirect by using an action string, 
  action string is a controller name + method name, for example: `MyController.index`. Also, you can pass only
  a name of method, in this case, it will uses a current controller. 
3. `refresh(array $args = [], bool $permanent = false)` - redirect to current URL.

Examples:

```
  // redirect to external URL.
  $this->redirectUrl('http://php.net/');

  // it will redirect to URL of the Personal.auth action
  $this->redirect('Personal.auth', array('ref' => 'index'));
  
  // in a Personal controller
  $this->redirect('auth'); // it will redirect to URL of the Personal.auth action.
  
  // refresh a current page
  $this->refresh();
  // or with args
  $this->refresh(array('reloaded' => 1, ...)); // to <current_url>?reloaded=1
}
```

---

## Controller lifecycle callbacks

There is a number of callbacks in our framework to handle some
events of controller life-cycle:

1. `onBefore` - invoked before proccessing request in a controller method.
2. `onAfter` - invoked after processing request, but before sending data to the browser.
3. `onFinally` - invoked after processing request and sending data to the browser.
4. `onException(Exception $e)` - invoked if throws any exception.
5. `onHttpException(HttpException $e)` - invoked if throws only exception inherited by the `regenix\exceptions\HttpException`.
6. `onReturn($return)` - invoked if returns any value from an action except for null values.
7. `onBindParams(&$params)` - invoked after trying to bind http parameters.


### onBefore, onAfter, onFinally

These callbacks are important methods for control the lifecycle of controllers in Regenix.
The onBefore is needed to provide some features:

1. In the before method you can put some template variable for all actions of controller.
2. There you can check something like as account access.
3. In this method you can use all controller featuares such as rendering methods, sending cookies, aborting.

In general, the before method is most important callback and usually it is being used often time.
The onAfter method you can use for such things:

1. Logging something
2. For modification of response data (yes, in the after callback you can use http data and modify it).

Next, the onFinally method is not used often time, but it can be helpful. You cannot change data of http
response inside of this callback because it is invoked after sending reponse. Although, you can use
it for logging or something else.

> **IMPORTANT**: When you override these callbacks, you will need to insert a parent call like:
> `parent::onBefore()`. This rule applies to other callbacks in our framework.


### onException, onHttpException

These callbacks are used to handle exceptions that occur in yours controllers. We separated
the exception callback on two methods - for http exceptions and another exceptions. Http exceptions
are inherited by the `HttpException` class and they have an http status property. This is convenient
for catching only http exceptions to output 404 errors or similar.

The basic controller class has a number of methods for sending http errors: `notFound()`, `forbiddien()`.
These methods throw an `HttpException` with 404 and 403 statuses. You can always use HtppException instead of
these methods and it is a good idea. Next, we consider an example that uses the onHttpException callback.

    class MainCtrl extends Controller {

        public function onHttpException(HttpException $e){
            if ($e->getStatus() === 404){
                // 404 error occurs
            }
        }
    }


### onReturn

This callback is used to implement a specific behavior in a controller. By default, the basic controller
class have no implementation of this callback. If you return a value from your controller method, nothing
will happen. So, what this callback? You can implement a special controller class inside which it will
be possible to use return statement to send a response. This was implemented in the RESTController class,
where the return value is always output as JSON.

This callback is as follows:

    public function onReturn($value){
        $this->render($value); // or do something else ...
    }


### onBindParams

This callback is responsible for binding method arguments in controllers. By default, Regenix ensures that
http get and router arguments will be passed to any controller method, but you can redefine this
behaviour via the onBindParams callback, this is as follows:

    public function onBindParams(&$params){
        // in $params array will be http-get and router arguments
        // but you can change this array.
    }

---

## REST Controller

Regenix has an espacial controller class for creating REST services, it is
the `RESTController` class. This class allows to design typical
controllers for REST services. There are a few of differences from the 
basic class of controller:

1. You can use return statements instead of render methods.
2. REST Controller passes JSON values into an action method as arguments.
3. Uses the JSend the unofficial standard for responses (see: <http://labs.omniti.com/labs/jsend>).

Examples:

    class ClientApi extends RESTController {
        
        public function get($id, $full = false){
            // ... something
            return 'OK'; 
        }
    }
    
    if action linked to the `/api/clients/get` address ...
    
    GET   /api/clients/get    {id: 123, full: true}
    return JSON: {status: "success", data: "OK"}

---

## How to return 404 and other http errors?

To do that we recommend to use the `onHttpException` callback and http exceptions.
You can override the callback like shown below: 

    class Clients extends Controller {
    
        public function onHttpException(HttpException $e){
            $status = $e->getStatus(); // a http status - 404, 403, 500, etc.
            // ... to do something
            
            $this->render("errors/" . $status . ".html");
        }
    }

By default, a controller already renders a suitable view for http errors. It 
tries to render an http error by using the status code, for 404 errors it tries to
render the `.errors/404.html` template. If this template does not exist, it will render
the `.errors/500.html` template.

