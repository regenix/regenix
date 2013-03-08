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
* Lazy loading of classes
* Session, Flash, etc.
* More utils classes


Getting started
---------------

### Installation

Copy all source from git, create the directory project in 'src/<project_name>/'. 
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





