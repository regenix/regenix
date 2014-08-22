## Installation

> Regenix is easy to install. You need only to copy the framework source to
> the root directory of a webserver. 


### Requirements

1. Web Server (nginx, apache or other). For apache, mod_rewrite must be installed.
2. PHP 5.4+ (also supports 5.5+, 5.6+ versions).
3. GD extension (for some features)
4. Memory Cache: APC or XCache (for performance)

> **WARNING**: Do not install eAccelerator extension, because it cuts 
> any comments in sources. It is used to emulate annotations in PHP. 
> Although you can install it, but you must to disable the option of optimization in 
> the eAccelerator configuration. 


### License

Regenix is lecensed under the Apache License 2.0. This means that you are free to modify,
distribute and republish the source code on the condition that the copyright notices are left intact. 
You are also free to incorporate Regenix into any commercial or closed source application.


### Getting Regenix

While there is only one way for getting Regenix - you can clone all sources of our framework and get to work.
Our source repository is located on Github: <https://github.com/dim-s/regenix>. To do this you
need to install git and run following commands in the git-bash: 

    cd <root_of_server>
    git clone https://github.com/dim-s/regenix.git .
    git submodule init
    git submodule update
    
That's it. There will be a directory for your future applications. By default this directory 
is located in root and named as `apps`. 


### Permissions

Regenix uses the `log` and `tmp` directories for some of different operations. These directories
must have access for writting and reading, other directories in root - only reading. 



### Setup

Regenix does not requires special actions to install. You need only to create the directory of your project
in the `apps/` and add some configuration files. That's it. 

---

## Apache Configuration

Next, we consider a number of examples to configure some web servers.
To configure a apache server you need to create the `.htaccess` file in root.
The typical configuration looks like below:

    Options +FollowSymlinks -Indexes

    php_flag display_errors on
    php_value error_reporting E_ALL
    
    AddDefaultCharset utf-8
    
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteBase /
    
        # MODULES
        RewriteRule ^assets/(.*)$ - [L]
    
        # MODULES
        RewriteRule ^modules/([a-z0-9-_A-Z\~\.]+)/assets/(.*)$ - [L]
    
        # PUBLIC
        RewriteRule ^public/([a-z0-9-_A-Z\~\.]+)/(.*)$ - [L]
    
        # ASSETS
        RewriteRule ^apps/([a-z0-9-_A-Z\~\.]+)/assets/(.*)$ - [L]
    
        # APPS
        RewriteRule ^(.*)$ index.php?/$1 [L]
    </IfModule>


## Ngnix + FastCGI configuration

The example to configure a nginx server:


    server {
        listen       80;
        server_name  127.0.0.1;
        charset utf8;

        root <root_of_your_web_server>;

        # static content
        location /public/ {
            autoindex off;
        }

        location /assets/ {
            autoindex off;
        }

        location ~ ^/apps/.*/assets/ {
            autoindex off;
        }

        location ~ ^/modules/.*/assets/ {
            autoindex off;
        }

        # dynamic content
        location / {
            fastcgi_pass  127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root/index.php;
            include       fastcgi_params;
        }
    }
    
> Also, you need to start cgi-server on _9000_ port for proccessing php scripts.

---

### Conclusion

In general, if you want to configure another web server, you need to provide the following points: 

- Redirect all dynamic requests to the `<root>/index.php` script.
- Redirect all static requests to the directires: `/public/`, `/assets/`, `/modules/.*/assets/` and `/apps/.*/assets`.

That's it.

---

## Non-standard configuring

Regenix has a few of directories in root:

- `framework` - the core directory
- `modules` - the module directory
- `apps` - the directory for applications
    
Also has some files:

- `index.php` - the main file for including framework and processing requests.
- `.htaccess` - the apache configuration (if you use an apache server)
- `regenix`, `regenix.bat` - the bash files for windows and unix.
    
However, you can change the location of core and apps directories. To do that, you 
need to change something in the `index.php` file. Next, we consider a typical index file:

    <?php 
    
    use regenix\Regenix;

    // require the framework
    require 'framework/include.php';
    
    // init and require applications
    Regenix::initWeb(__DIR__);
    

Here you can change the path of the framework include file at the line:

    require 'other/path/to/framework/include.php'

It will works very well! Also, you can change the root path at next line:

    Regenix::initWeb('other/path/of/root');
    
It also will works.
