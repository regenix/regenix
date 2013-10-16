## Documentaion

> Welcome to Regenix Framework Documentation

---

#### Overview
Regenix is a classic MVC web-framework for PHP. The general goals of our project are
fast and effective development with the REST architecture.


#### The PHP Framework - Fast and Easy.
Regenix is an easy-to-learn and powerful framework. PHP has a complex history of
evolution that has had an impact at all php frameworks. Many of them are
too heavy and uncomfortable, they are not easy to install and configure. Some
of them include old sources are not best quality.

#### The Simple MVC Architecture
Regenix has a very simple and clear MVC architecture. Models, Controllers, Views.
The architecture of web applications is becoming more difficult from year to year.
Frameworks should have a simple and clear system to build modern applications.
This applies to small applications, Ajax, applications using web-sockets.

Regenix helps to create complex applications and you do not need to think 
about what is hidden behind technologies.

#### HTTP and running scripts
In PHP, views and controllers are mixed and patterns of urls are set via the file structure of a project.
All frameworks fix this problem. Every of them has own implementation for this. A system of binding urls 
and scripts is called "routing". Regenix uses config files for routing that are very easy to read.

The example from a route configuration: 

    GET   /clients/{id}/    Clients.show()
  
It is clear that when you make a request at the address `/clients/32/`, method `show` of 
the `Clients` class will be called and the `id` param will equal to `32`. At the same time, 
the controller will be given all the necessary information - request, response, session, headers, etc.

---


#### Controllers are easy and convenient.
Every request Regenix tries to create an instance of a controller class using rules of routing
and tries to call a fit method. At this a developer has a lot of useful information, 
helpers to control the HTTP cycle. See the next example:


    namespace controllers;

    use regenix\mvc\Controller;

    class Clients extends Controller {

        public function show($id){
            // show client with $id
            $this->put("client", /* findById($id) */);
            $this->render(); // render Clients/show.html
        }
    }


In the controller, you can take and manipulate information from classes and helpers such as: 
Request - an incoming request, Response - an answer to the browser, Headers - http headers, 
Session - a session of a user, Flash - to manage flash messages that living in one request, 
Query - the array of the HTTP GET parameters, other information.

You can hook some kinds of the events - onBefore, onEception, onAfter, onFinally,
onHttpException, onReturn, onBindParams. To do that you can manage the lifecycle 
of a controller.

---

#### Convenient template engine
There are several ways for creating templates in the field of PHP language.
You can use PHP as a template engine or use a third party engine. 
The first way is not quite convenient but it ensures hige performance. 
The second way is useful but performance sufferы. There are famous php 
template engines - Smarty and Twig. Regenix does not use these engines.
Our framework has its own template engine that ensures high performance. 
It only simplifies the syntax of php inserts:

For example, the Regenix and PHP inserts:

    Your name is `<?php echo htmlspecialchars($var)?>`, <a href="<?php echo $linkLogout->render()?>">logout</a>.
    Now <?php date($time, 'd.m.Y')?>

and in regenix:

    Your name is `{$var}`, <a href="{$linkLogout->render()}">logout</a>.
    Now {$time | format('d.m.Y')}
    
The syntax of the regenix templates is very easy and clear. 
By default any output of expressions is escaped by the `htmlspecialchars` 
function, but you can always output the raw of a expression, for example:

	Now {$var | raw}
	
At this example, we have used the special modifier - `raw`. 

---

#### The class scanner instead of a loader!
Usually, frameworks use a class loader to include source files. They use the name of a class to
find its location. There are namespaces in php 5.3, and it is allows to find a location of a class. 

Regenix uses another way to load classes. There is the class scanner that to scan 
all the class paths. It is allows to find the location of a class without usage of its name.
The scanner caches information of locations for higher performance.

The name and namespace of classes do not require special typing rules, but we recommend you to use
the PSR-0 standard for names and namespaces. Regenix will always find locations of classes even
if you do not abide with this standard. 

---

#### Handling errors
Regenix has the good system of handling errors. It always shows where a error was. However, Regenix shows 
more types and information than PHP. Regenix has the strict mode option. If this option is enabled that 
the framework will show you some notices, for example usage an undefined variable or constant. 
By default this option is enabled in the main configuration `/conf/application.conf`.

	
	app.mode.strict = on
	# you can change the value to "on" or "off". 





  
