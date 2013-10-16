# Quality Inspection

Regenix has powerful system of Quality Inspection for sources. It is
unusual for typical PHP web framework and maybe you don't understand
that we mean. What is Quality Inspection in Regenix Framework?

1. Checking syntax (parse errors)
2. Checking coding standard (PSR-0)
3. Finding errors which PHP cannot find (example: checking classes in use statements)
4. Disable some unsafe features of PHP (disable goto, globals, some functions, etc.)

And more...

*Note* that Regenix analyzes all sources of an application even if some of them is not
included within a request. Yes, Regenix does it when you are opening your application in a browser,
but you can run analyze in CLI manually.

---

## Configuration

The configuration of the analyzer located at `conf/analyzer.conf` in the directory of your application.
See the next example of this file:

```
##### Disable Analyzer
# Disable some PHP features: globals, goto, functions
disable.features = globals, goto, functions

# Disable usage of some global vars
# $_GET, $_POST, $_REQUEST, $_SESSION, $_COOKIE, $_FILES, $_ENV, $_SERVER, $GLOBALS
disable.globals = $_GET, $_POST, $_REQUEST, $_SESSION, $_COOKIE, $_FILES

# Disable usage of some php functions, you can also use pattern <prefix>_*
# examples: mysql_*, exec, etc.
disable.functions = exec, shell_exec, mysql_*

##### PSR0 Analyzer settings...
# enable psr0 analyzer, default off
psr0.enable = on

# exclude some namespaces ...
# psr0.exclude = controllers, models
```