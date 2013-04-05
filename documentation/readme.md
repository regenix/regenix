## Documentation

Welcome to the **Regenix framework** Documentation.

#### Overview

Regenix framework is classical web MVC framework for PHP. It focuses on
developer productivity and targets RESTful architectures.

#### PHP framework is lightweight and powerful

Regenix is lightweight and yet powerful framework. Complex history of the
PHP language affected all mvc frameworks that exist in PHP. MVC
frameworks often suffer congestion, complex configuration and installation. some
frameworks pulled my old code is not the best quality, some are not too
functional.

#### Simple MVC architecture

Regenix provides a very clear and simple architecture of MVC. Models, views and controllers.
Architecture web applications from year to year is improving and becoming more complex.
Frameworks should provide a clear and simple system for modern
Web-based applications. This applies to simple web applications, ajax, to applications using
Web sockets. Regenix enables the development of such complex applications without hesitation that
hidden behind these technologies, the developer writes the same code for ajax-and for the rest.

#### HTTP and call scripts

In PHP, views, and controllers make people laugh with each other, and the rules are based on the url
file structure of the project. All frameworks correct this deficiency in all senses.
Each framework does it in his own way. In Regenix configuration file is used for routing, which are very
easy to read.

Example configuration of Regenix for routing:

    GET    /cliens/{id}/       Clients.show()

It is clear that at the request GET address `/ clients/32 /` method is called class show and he Clients
will be transferred to the argument `id = 32`. At the same time, the controller will be given all the necessary
and additional information - Request, Response, Session, Headers, etc.

#### Checkers, simple and convenient

On each request Regenix tries to instantiate the controller based on the routing rules
and calling the appropriate method. With this large number of developers available auxiliary
information assistants to manage HTTP cycle of the application. Let's see an example of the controller:

    namespace controllers;

    use framework\mvc\Controller;

    class Clients extends Controller {

        public function show($id){
            // show client with $id
            $this->put("client", /* findById($id) */);
            $this->render(); // render Clients/show.html
        }
    }

From the controller developer can obtain and manipulate information from classes and
helpers: *Request* - incoming request, *Response* - response to the browser, *Headers* - come
headers, *Session* - session management, *Flash* - Control live one request flash messages
*Query* - array GET request parameters and other information.

A developer can write hooks for controllers - *onBefore*, *onAfter*, *onFinally*, thus driving
Lifecycle Controller. It can also override the output Exception, if it occurred
in a controller method. In this case, all supported by inheritance.

#### The effective template engine

In the world of PHP, there are several approaches to create patterns - how to use PHP
template or use a third party template. The first option is not very
convenient, but very productive, the second option - comfortable, but performance suffers.
In PHP there is a known template engine Smarty and Twig, Regenix optional support
these engines, but the framework has its own engine, it is very productive and is built
php for templates, it simplifies the expression syntax.

For example, templates and PHP Regenix:

    Your name `<?php echo htmlspecialchars($var)?>`, <a href="<?php echo $linkLogout->render()?>">logout</a>.
    Now <?php date($time, 'd.m.Y')?>

and regenix:

    Your name `${var}`, <a href="${linkLogout->render()}">logout</a>.
    Now ${time->format('d.m.Y')}

Regenix template syntax in a very simple and intuitive, by default all output variables is escaped, but
always possible to deduce unescaped variable. And it is always possible to use
another engine - Smarty or Twig, or pure PHP.

#### The system models, ORM and ODM

Regenix not implement some ORM, it just provides the interface for implementing ORM, ActiveRecord, etc.
Based on these interfaces and classes are very easy to build additional modules that
implement ORM architecture.

In Regenix logic of the models is separated from the models themselves, it is in services (Services).
Each model has its own service to work with it, by default it is at all, and is standard.
But you can override and adds its services for each model separately.
Services engaged saving, deleting, adding and finding patterns in the database, they must also
perform more complex operations. Model has only the same with your data without knowing of the existence of
services.

In the framework, the system has its own annotations, comments that uses php. Based on these annotations
is a description of models. This versatile system annotation model is built into the core Regenix.
By default, the module is in Regenix ORM to work with MySQL, SQLite, Postgres and ODM module for
with MongoDB.

An example of working with models and services:

Descriptions:

    /**
     * @collection posts
     */
    class Post extends Document {

        const type = __CLASS__;

        /**
         * @id
         * @var \MongoId
         */
        public $_id;

        /**
         * @length 255
         * @var string
         */
        public $name;

        /**
         * @length 10000
         * @var string
         */
        public $desc;

        /**
         * @indexed
         * @var string
         */
        public $author;

        /**
         * @var int
         */
        public $skip;

        /**
         * @var int[]
         */
        public $groups = array();
    }

Working with the model:

    $postService = Post::getService();

    $post = new Post();
    $post->name = 'Article name';
    $post->autor = 'Mike';
    $post->dateCreated = time();

    // save or insert if not exists
    $postService->save($post);
    // $post->getId() - return saved id


#### Search bugs during development

PHP language is type-safety, it is not statically typed, and provides
too much freedom. It threatens to bad consequences. Regenix
partially corrects these defects, forcing the developer to pay for many
errors in its code, not trying to ignore the error and somehow independently
correct them.

Error Output in Regenix is very convenient, it shows where the error occurred, displays
of the code in which the error occurred in the browser. This allows you to define
cause.

#### Control of the data types

In php little control over data types. Therefore Regenix implements in many places
control or automatic type conversion yourself, it allows
avoid many of the errors at the design stage.

It is important to control and convert to the desired types of data incoming data
from users in POST, GET, etc. Regenix provides methods for converting
incoming data into the correct types and objects (binginds). It also provides
typing on the level of routing.

For example, in the route:

    GET /cliens/{id:int}/           Clients.show()
    POST /switch/{check:bool}       Switch.check()

In these cases, the parameters `id` and `check` to the controller has received in these types,
it saves the developer from manual conversion types in the method of the controller. here
You can also specify and classes for which implemented Binding (binging).

Or try to implement a class with the implementation binding'a, for this, it should inherit
the interface `RequestBindValue` and implement the method `onBindValue`. A simple example
implement a class `Integer` just as an example:

    namespace types;
    use framework\mvc\RequestBindValue;

    class Integer implements RequestBindValue {
        public $value;

        public function onBindValue($value){
            $this->value = (int)$value;
        }

        public function __toString(){
            return (string)$this->value;
        }
    }

In routing and more can now use the class Integer, to convert
incoming data to the correct format. In this instance of `Integer` will be created
automatically.

    GET /cliens/{id:types.Integer}/     Clients.show()

For separation namespace is used in routing `.` Instead of `\`.