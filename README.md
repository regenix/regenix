Welcome to Regenix framework
============================

Regenix easy to use and learn MVC framework.

Supports
--------
* MVC Architecture
* Route for url
* Multiple projects on a single core 
* Dependency injection
* Validation based on tests
* Twig, PHP or Smarty templates
* Lazy class loading
* Session, Flash, etc.
* More utils classes


Getting started
---------------

### Installation

Copy all source from git, create the directory project in `src/<project_name>/`. 
Project `project1` already exists in regenix source. 

### Project structure

* conf/ - configuration directory
** conf/application.conf - general config
* public/ - directory for static content, images, javascript, css, etc.
* app/controllers/ - controller directory
* app/models/ - models directory, ORM
* app/views/ - directory for search templates

### First controller

1. Create `Application.php` in `app/controllers/`
2. Write `Application` class in `controllers` namespace, inherited from `framework\mvc\Controller` class
3. Define controller public method `index`

`
namespace controllers

use framework\mvc\Controller;

class Application extends Controller {

    public function index(){
          
         // add named variable to template
         $this->put('var', 'Hello world');

         // Render template views/Application/index.{ext} and exit
         $this->render();

	 // after code no work ...
         // ...
    }
}
`

