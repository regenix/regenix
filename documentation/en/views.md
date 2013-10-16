**Contents**

+ [A simple view](#a-simple-view)
+ [Basic syntax features](#basic-syntax-features)
+ [Conditions, If](#conditions-if)
+ [Loops](#loops)
+ [Filters](#filters)
  + [Create a new filter](#create-a-new-filter)
+ [Tags](#tags)
  + [Create a new tag via php](#create-a-new-tag-via-php)
  + [Create a new tag via html](#create-a-new-tag-via-html)
+ [Template inheritance](#template-inheritance)


---

# Views

Views are important part of any MVC framework. Some of them use
PHP as template engine for views, but regenix has own implementation
of this with simple syntax.

---

## Introduction

Usually, in MVC, controllers are responsible for rendering views. 
To output a view or template you call a special method in 
a controller that is responsible for this. In Regenix, there is a few
of such methods: `render()` and `renderTemplate()`.

The all views are located at the `/apps/<project>/src/views/` directory and
have `html` extension by default.

---

## A simple view

Next, We consider a simple example of views. We will use only one controller and
one template for this. Before, you need to read about how controllers work
in our framework (see: [controllers](controller.md)).

For example, we create the `Users` controller and `Users/detail.html` view:

    controllers/Users.php
    
    <?php namespace controller;
    
    use regenix\mvc\Controller;
    
    
    class Users extends Controller {
    
        public function detail($id){
            $this->notFoundIfEmpty($id);
            
            $user = ... // get a user from a database, for example it will be an array
            
            // add variable to view
            $this->put('user', $user);
            
            // here will be output the `src/views/Users/detail.html` file
            // because name of the controller is `Users` and name of the method is `detail`
            $this->render();
        }
    }
    

In our view:

```
views/Users/detail.html

<html>
    <head>
        <title>Users: {$user[name]}</title>
    </head>
<body>
    <h1>Hello, {$user[name]}. Your id is "{$user[id]}"</h1>
    {if $user[is_admin]}
        [ <a href="/admin/">Admin panel</a> ]
    {/if}
</body>
</html>
```

---

## Basic syntax features

Now, we consider common syntax of rules of our template engine. 
Any expression should be enclosed to brackets `{...}`. To render a variable
you can easily use php expressions `{$variable}`. 

> **IMPORTANT**: By default, any expression is filtered by `htmlspecialchars` function before
rendering. It is made for greater security.

Also, you can use any php operators or functions: `{ $variable . ":" . max($a, $b) }`.

---

## Conditions, if

Syntax of this feature is very similiar to php syntax, you need only to replace `<? ?>` on
`{ }`. This looks like shown below:


    {if ... any condition ... }
        
        anything...
        
    {/if}
    
Also, you can use `else` and `elseif` syntax:

    {if $user}
        Hello, {$user[name]}
    {elseif $guest}
        Hello, guest.
    {else}
        Please, login.
    {/if}

---

## Loops

Our template engine has several types of loops: `foreach`, `while`, `for`.
This is same as in PHP.

    {foreach $list as $key => $value}
        ...
    {/foreach}
    
    {for $i = 0; $i < 10; $i++}
        ...
    {/for}
    
    {while $i < 10}
        ...
    {/while}

---

## Filters

A filter is a special function that modifies end result of expression. Syntax of it
looks like: `{$variable | filter_name}`. There are several built-in filters:

1. `raw` - it allows to output raw of value
2. `format($format)` - allows to render data in needed format.
3. `lowerCase` - allows to lowercase a value
4. `upperCase` - allows to uppercase a value
5. `trim` - trims a value
6. `substring($from, $to = null)` - returns substring
7. `replace($what, $replace)` - replaces a piece of a value 
8. `nl2br` - replace new-line symbols to the `<br>` tag.

Examples:

    {$variable | raw}
    
    {$user[date_created] | format("Y.m.d")}
    
    {$user[about] | nl2br}
    

Also, you can use several filters together, for example:

    {$variable | raw, lowerCase}
    
    {$variable | trim, substring(0, 100)}
    
    {$variable | substring(0, 1000), nl2br}
    
For this you need to use the comma symbol: `{expression | filter1, filter2, filter3 ...}`.

---

## Create a new filter via PHP

To create a new template filter, use the special class `regenix\libs\RegenixTemplateFilter`. 
Create a new class inherited by `RegenixTemplateFilter` anywhere inside the directory 
of your application. Next, you should define two methods: `getName()` and `call( ... )`.
Consider them example:

```
use regenix\libs\RegenixTemplate;
use regenix\libs\RegenixTemplateFilter;

class RegenixMyFilter extends RegenixTemplateFilter {
    
    public function getName(){
      return "my";
    }
    
    public function call($value, array $args, RegenixTemplate $ctx){
      return $value . "!!!";
    }
}


and next, we can use "my" filter in a template:

{"Alarm" | my} // will output "Alarm!!!"
```


---

## Tags

In regenix template engine, a tag is a special insert that looks like: `{tagName ...args...}`. 
Usually, a tag have several named arguments and one unnamed (default) argument, see example:

```
{tag $default, key1: $value1, $key2: $value2}
```

Our template engine already has built-in tags: `set`, `get`, `render`, `include`, `path`,
`asset`, `public`, `image.crop`, `image.resize`, `image.captcha`, `deps.assets`, `deps.asset`,
`html.asset`, `debug.info`, `extends`, `content`.

We will consider the most important ones:

+ **set**

This tag set a lazy value that can be output in future, it can be used for printing a title
of a page or keywords. A key feature is that you can change a value below outputting. For
rendering a value, see the next tag.

```
{set title: 'My Site'}
```

+ **get**

This tag outputs a lazy value that was set by using the `set` tag. In order to understand how it works,
see the next example:

```
main.html

<html>
    <title>{get 'title'}</title>
    {content}
</htm>
```

Somewhere in another template:

```
{extends 'main.html'}
{set title: 'My Site'}
```

Here, we have used `extends` and `content` tags. This is a basic feature of our template engine that
called **Template inheritance**. About this, read below.


+ **render** and **include**

`{render "path/to/other.html"}`: It inserts an external file into your template, 
but it makes an isolated environment for variables. For sharing variables, you need 
to use `include` tag: `{include "path/to/other.html"}`.

Also, you can pass named parameters for inserting a file and the parameters will be available inside, 
see the example:

```
{render "path/to/other.html", user: "My User"}

// path/to/other.html, 
// Now, you can get a value of the user argument by using the $user variable.
<b>{$user}</b>
```


+ **path**

`{path "Controller.method", arg1: value1, arg2: value2 ...}`. This tag converts the action name to
URL by using route rules and inserts it. (read about routing: [routing](routing.md)).

---

## Create a new tag via PHP

Regenix supports custom tags for templates. There are two types of custom tags - php and html.
You can create them yourself. To create a new php tag, use the special asbtract class 
`regenix\libs\RegenixTPL\RegenixTemplateTag`. Follow the instruction:

1. Create file in your application directory, for example `src/tags/MyTag.php`.
2. Create class inherited by `RegenixTemplateTag`.
3. Override methods `getName()` and `call($args, RegenixTemplate $ctx)`.

That's all. Next, we will consider a simple example of creating custom php tag.

```
<?php namespace tags;

use regenix\libs\RegenixTPL\RegenixTemplateTag;
use regenix\libs\RegenixTPL\RegenixTemplate;

class MyTag extends RegenixTemplateTag {

  // name of our tag, in view it will look like: {my.tag ...}
  public function getName(){
    return 'my.tag';
  }
  
  // here, we need to return an html result of execution of our tag
  public function call($args, RegenixTemplate $ctx){
    return "html_code";
  }
}
```

In the call method, you can implement anything, also, you will have access to
passed arguments `$args` and a current context `$ctx`. The `$args` is an array that
contains named arguments. Above, we explain that there is a default argument in tags,
to get it, use the `_arg` element of `$args`

```
...

public function call($args, RegenixTemplate $ctx){
  // return a default argument
  return $args['_arg'];
}
```

> **IMPORTANT**: You do not need to register your php tags manually!
> Our framework itself includes all classes of php tags via the [class scanner](#class_scanner.md)
> when needed.

---

## Create a new tag via html

In regenix, there is a special way for inserting html tags. To insert a tag created via a html file,
use the `tag.` prefix, this looks like `{tag.name ...}`, `{tag.another.name ...}`.
If you insert `{tag.user.auth}` for example, it will include the `views/.tags/user/auth.html` file.
You might notice that all html tags are located in `views/.tags/`. In general, the behaviour of 
html tags is similar to the built-in tag `render` (see above).

Eventually, you can also insert a html tag via the render tag, for example: `{render ".tags/user/auth.html"}`.
Consider the following example (`views/.tags/user/auth.html`):

```
Hello, <b>{$user}</b>
```

and now we can use it:

```
{tag.user.auth user: 'Name'}
```

You can pass any named values into a html tag. To get default value, use the `$_arg` variable.

```
Hello, <b>{$_arg}</b>

{tag.user.auth 'Name'}
```

> **NOTICE**: A html tag makes an isolated enviroment for variables and therefore external variables
> are not available inside.

---

## Template inheritance

One of important features of any template engine is _Template inheritance_. Regenix supports this
feature in views. There are two special tags for template inheritance - `{extends $name}` and `{content}`.
Using the `extends` tag, you can easily include a template for a page. The `{content}` tag is used for
output content of template. For to understand this we consider the next example:

We need to create two files: `main.html` and `Application/index.html`. The `main.html` will be the template for
the `Application/index.html` page. All files should be located at `/src/views/`. Also, we need to
create a controller `Application` with `index` method:

```  
src/controllers/Application.php

<?php namespace controllers;

use regenix\mvc\Controller;

class Application extends Controller {

  public function index(){
    $this->render(); // render "Application/index.html" template
  }
}
```

The main template `src/views/main.html`:

```
<html>
  <head>
    <title> ... </title>
  </head>
<body>
  <h1>My Site</h1>
  <div>
    {content}
  </div>
</body>
</html>
```

The view `src/views/Application/index.html`.

```
{extends 'main.html'}

<b> My Content </b>
```

In the last file, we have included the main template and have inserted our content.
The `{content}` in our main template will be replaced to our content from `index.html`.
As a result, the page will be created like shown below:

```
<html>
  <head>
    <title> ... </title>
  </head>
<body>
  <h1>My Site</h1>
  <div>
    
    <b> My Content </b>
  </div>
</body>
</html>
```

Template inheritance is a powerful mechanism. You can easily include one template into another template
and there is no limit for nesting of templates.

