# Configuration

Regenix is easy to configure framework. There are a few typical files to configure your application:

  1. **General configuration** - contains main options of your app, named as `application.conf`
  2. **Dependencies configuration** - contains all asset and module dependencies of your app, named as `deps.json`
  3. **Route configuration** - contains routing rules for urls of your app, named as `route`
  4. **Subroute configurations** - additional configurations to include into a main configuration of router.

All configurations are located at the directory `/apps/<your_app>/conf/`.

---

## General configuration

The general configuration is located at `conf/application.conf` and 
looks like an ini file without sections. It can also contain comments. 
For example:

    # comment line
    app.mode = dev
    
    # ...
    app.rules = /sub/
    
    # etc.
    dev.app.rules = /
  
Options are several types:

  1. _string_ - a typical string
  2. _boolean_ - a value may be true or false, true is `on` or `1`, false - `off`, `0`.
  3. _number_ - integer values
  4. _double_ - float values

Also, you can use a multiple value as array for options.

**How get a option in code?**

For this, see following example:

    /** @var $config Configuration **/
    $config = Regenix::app()->config;
    
    $value = $config->get("option.name", "default.value");
    
    // or you can use typed methods
    
    $value = $config->getNumber("number.option", 123456);
    $value = $config->getBoolean("bool.option", false);
    $value = $config->getArray("multiple.option", array("a", "b"));


**How divide options for dev and prod modes?**

For this you need to use a prefix for names of options,
it's very easy:

    dev.my.option = 123
    prod.my.option = 321
  
As shown above, we have one option, but two values - for dev and prod modes.
To get a value you should use a name without prefix:

    // if an app in dev mode that the value will be equal `123`, in prod mode - `321`.
    $value = $config->get("my.option");
    

Next, we consider some framework options of a general config file.

---

#### Application rules

By default, your application may be opened by `http://<host>/` address.
There is an option which can change the address of your app - `app.rules`.

    app.rules = /
    
The value `/` means that your site will be available at the `http://<any_host>/` address. 
If you change the value to `/sub/` (for example), your site will be available at `http://<any_host>/sub/`.
This option can be used as a prefix of all urls of an application. 
However, the option can contain a host, domain or port:

    app.rules = site.com/
    
This allows you to make your site will only be available in the `site.com` domain.
Also, this option supports multiple values, for example:

    app.rules = site1.com/, site2.com/
    
To set several rules, the value should be separated with commas. If you have done this, 
your site can be opened with multiple addresses. 

> **NOTICE**: Your site always uses default server port, but you can redefine it.
> To do this, specify your port in the address after the domain: `site.com:port/`.

---

#### Application Mode

Regenix supports two modes for applications - `prod` and `dev`.

  1. **Production** (prod) - production mode, it is uses all optiomizations and caching. 
      It has high performance. Do not use this mode during development.

  2. **Development** (dev) - development mode, it is used for development stage, it not uses
      caching and most of optiomizations.

Read more about this in the _development_ chapter.

---

#### Strict Mode

The strict mode allows you do not make a lot of mistakes. This mode forces to pay attention
to many things in your code. PHP is not a strict language therefore we added this option 
for safer coding. In the configuration, the option looks like below:

    app.mode.strict = on
  
By default it is enabled. The mode is only applicable to sources of applications,
and not applicable to sources of modules, vendors and the framework.

> NOTICE: We strongly recommend to enable this option for any case.

--- 

#### Secrect code

Every application should have its own secrect code. It is used in a few
of places in the framework for security. The code should be a string of 
any random symbols, for example:

    app.secret = nlkJALJLJS309jl6876876kajllakjLLAKJkljalkjlaj

> This option is required.

---

## Logger

#### Logger enable

This option enables/disables the logger. By default it is enabled.

    logger.enable = on/off
    
---

#### Logger division

This option allows to divide output of log messages to several files.
This uses the log level of a message to select a needed file for writing.

    logger.division = on/off
    
This may be convenient for searching errors.

---

#### Logger level

This is the typical option for a lot of loggers. The level of the logger
allows to log only some messages considering their level.

    logger.level = debug/info/warn/error/fatal
    
Read more about this feature in the `logger` chapter. 
    
---

#### Logger fatal enabled

Fatal errors are an espacial type of errors therefore they log to 
a separate file named as `fatal.log`. The errors may be discovered 
if you make a mistake in syntax (for example). In general, 
fatal errors are not runtime errors.

    logger.fatal.enable = on/off
