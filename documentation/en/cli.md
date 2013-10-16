# Console

> Regenix has the console interface to manage the framework and applications.
> Regenix CLI has a lot of util commands.

---

### Setup

To use CLI you need to register the path of root of your webserver in the OS evnviroment configuration.
After this, you can use the new CLI command `regenix`. If you do not want to do this, you can easily
enter to the root directory of your webserver via the `cd` command, after this, the `regenix` command
will be available in your console.f


### Basic commands

Open your system console and enter following command: 

    > regenix about

This will help you to know that Regenix CLI is working and you will see the basic information
about applications.

---


#### Loading App
Regenix needs the operation of loading applications because Regenix is the multiple-app framework.
If you have only one application in the `apps` directory, you don't need to use it. By default the
framework loads the first found application in the directory.

    > regenix load <name_of_your_project>


After this command, any following command will be applies to the current loaded app. 

---

#### Getting Information
This command allows you to see some information of a loaded app.

    > regenix info

---

#### Dependencies
This command shows what there are dependencies in your loaded app. The command
supports some subcommands to manage asset and module dependencies.

    > regenix deps

> See the chapter of dependencies for getting more information about the subcommands.

---

#### Testing
Regenix supports two way for running tests of your applications. 
The first way is running tests in the console, the second way is running 
tests in a browser (only dev mode).

This command can run your unit and functional tests in CLI. 
It is may be convenient to use for continuous integration. 
CLI always return 0 if all tests are successful, and return 1 
when some of tests is not.

    > regenix test
    
Also, you can run tests of your module. For this there is the `module` param, 
you need only to specify the name and version of a module. 

To run tests of a module:

    > regenix test -module=<name~version>
    
---

#### Help
Regenix also has the help command. It shows information about all 
supported commands by Regenix CLI.

    > regenix help
