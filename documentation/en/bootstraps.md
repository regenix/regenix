# Bootstrap

Regenix supports bootstrap classes within application and global scope. Regenix is
a multiple-app framework and therefore bootstrap classes can be two types - application and global.

The global bootstrap handles global events, the application bootstrap - application events.
What is bootstrap in Regenix? The regenix bootstrap is a class inherited by an abstract 
bootstrap class which has a few overridden methods for handling events.

---

## Application bootstrap

To create an application bootstrap you need to create a new class inherited
by the `regenix\core\AbstractBootstrap` class. Inside the class, you can override some
methods such as `onStart`, `onEnvironment` and `onTest`.

    namespace {
  
        use regenix\AbstractBootstrap;
    
        class Bootstrap extends AbstractBootstrap {
    
            public function onStart(){
                // overridden method ...
            }
            
        }
    }

> **IMPORTANT**: A bootstrap class should be 
> located at `<app>/src/Bootstrap.php` and named `Bootstrap`.

Next, we consider all methods that can be overridden.

---

###### onStart ######

This method is invoked after loading of the application, but before
sending http data. 

    public function onStart(){
        // ... somethings, for example defining DI rules.
        // do not include some php files or libraries
    }
    
    
> **IMPORTANT**: Do not include any php files or libraries in this method
> because this can be cause of low performance. Let Regenix itself
> loads all php files and libraries when needed.


###### onEnvironment ######

This method is invoked when the framework tries to set the mode of your
application. The mode can be switched in the main configuration, but
sometimes you need to set the mode dynamically. For this, you can override
the `onEnvironment` method, it looks like this:

    public function onEnvironment(&$env){
        // ... here you can change $env dynamically
        if ( ... ){
            $env = 'dev';
        }
    }

In this method, for example, you can change the mode that depends on the host or 
something else.
    

###### onTest ######

This method is invoked when you start tests of a current application. 	
It makes no difference where you will run the tests - in a browser or CLI. 
This method will be invoked anyway. 

However, this method is needed for sorting tests before starting when needed.

    public function onTest(array &$tests){
        $tests = array(
            new tests\MyFirstTest(),
            new tests\MySecondTest(),
            ...
        );
    }

An array of instances of tests will be passed into the method. If you need to sort them 
you should manually create a new array as shown above. That's it.

---

## Global Bootstrap

Sometimes you need globally handle some processes and events of all your applications. 
To do this, there is the global bootstrap. It is a class inherited by 
`regenix\core\AbstractGlobalBootstrap` and located at `/apps/GlobalBootstrap.php`,
therefore this class should named as `GlobalBootstrap`. The next example helps you to understand:

    <?php
    namespace {

        use regenix\core\AbstractGlobalBootstrap;

        class GlobalBootstrap extends AbstractGlobalBootstrap {

            // override on exception globally, for all projects in apps directory
            public function onException(\Exception $e){
                $controller = Controller::current();
                $controller->render('error.html', array('e' => $e));
            }
        }
    }

The global bootstrap has several methods for overriding:

1. `onException(\Exception $e)` - occurs when throws any exceptions  
2. `onError(array $error)` - when php errors occur (not exception!).
3. `onBeforeRegisterApps(File &$pathToApps)` - before regenix finds apps in the `apps` path. 
4. `onAfterRegisterApps(&$apps)` - after regenix finds apps
5. `onBeforeRegisterCurrentApp(Application $app)` - before regenix registers a application at the current request
6. `onAfterRegisterCurrentApp(Application $app)` - after the registration of the current application
7. `onBeforeRequest(Request $request)` - before regenix tries to render a page.
8. `onAfterRequest(Request $request)` - after a request, but before to render a page
9. `onFinallyRequest(Request $request)` - after render a page


