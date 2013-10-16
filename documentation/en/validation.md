**Contents**

+ [Introduction](#introduction)
+ [Create a simple validator](#create-a-simple-validator)
+ [Multiple validate methods](#multiple-validate-methods)
+ [Entity Validator](#entity-validator)
+ [Validation in controllers](#validation-in-controllers)

---

# Validation

Regenix has a non-standard way for checking incoming data. Validation in Regenix 
similars to unit tests. You write a new class of validation that looks like an
unit test. In this way, the logic of validation wil be separeted. All sources
of validation are in a separate class. Thus, your controllers will not have
a code that checks incoming data.

---

## Introduction

There are two abstract classes `regenix\validation\Validator` and 
`regenix\validation\EntityValidator` in Regenix for writting validators. 
The `EntityValidator` is inherited by `Validator` and has a number of 
additional methods for checking attributes of an entity.

To create a validator you need to do the following, e.g.:

1. Create a class `validators\MyValidator` inherited by `Validator`.
2. Define a protected method, it will be a checking procedure.
3. In defined method, you need to write something like this:

```
protected function main(){
  $this->requires($value)->message('It is required');
  ...
}
```

The `requires` method is for checking data - empty or not. However, it
is not one method that is in the Validator class for checking, 
you can also use: `minLength`, `maxLength`, `match`, `isEmpty`, etc.

--- 

We consider a simple example of a validator. For example, you need to check
login and password fields. So, for this we write a new validator 
that will verify an array of fields. 

```
class LoginPasswordValidator extends Validator {

  private $fields;

  public function __construct(array $fields){
    $this->fields = $fields;
  }
  
  public function main(){
    $this->minSize($this->fields['login'], 3)->message('Minimal size for login field is 3 letters');
    if ($this->isLastOk()){
      $this->match($this->fields['login'], '/([a-z0-9\.]+)/i')
           ->message('Login must contain latin letters, numbers ant point symbol');
    }
    
    $this->minSize($this->fields['password'], 6)->message('Minimal size for password is 6 letters');
  }
}
```

To use our validator we need to create a new instance and to call the `validate` method.

```

$validator = new LoginPasswordValidator($postData);
if ($validator->validate()){
  // no errors
} else {
  $errors = $validator->getErrors();
}

```

---

## Multiple validate methods

A validator's class may contain several methods for checking. All this methods must be
protected and they are invoked in the `validate` procedure. A Validator automaticly 
finds all protected methods defined in your validator's class and calls them. The calls
occur in the order they are declared. However, you can always validate with only one method.

```
class MyValidator extends Validator {
    protected function one(){
      ...
    }
    
    protected function two(){
      ....
    }
}

  $validator = new MyValidator();
  $validator->validate(); // validates by using all protected methods are defined in a class ("one" and "two").
  $validator->validate('one'); // validates by using only one method "one"
```

---

## Entity Validator

There is another type of validators in Regenix - `EntityValidator`. This class adds a number
of methods for checking objects and arrays by using names of properties instead of values.
It has the protected property `$entity` and constructor with the `$entity` parameter, also,
has special methods: `validateAttribute` and check methods `requiresAttr`, `maxSizeAttr`, etc.

For example: 

```
class UserValidator extends EntityValidator {

   protected function register(){
      $this->requiresAttr('login');
      $this->requiresAttr('email');
      $this->requiresAttr('password');
   }
}

$user = new User();
$user->login = " ... ";
$user->email = " ... @ ...";
$user->password = "123456";

$validator = new UserValidator($user);
$validator->validate();
```

In previous example, we have checked values of fields, but we have only used names of 
properties. This is key feature of the entity validator - you don't need to get values
of entity's properties manually and it is very convenient for validating your models, 
for example.

> Usually, in another MVC framework, a validator is embedded into a model. 
> In our opinion it is not very convenient because one and the same model must
> be checked in different ways, also, mixing models and validators is wrong.

---

## Validation in controllers

Controllers have a few methods for validation, also, they support several validators
for one check. Next, we consider a simple example:

```
class MyController extends Controller {

   public function index(){
      $data = $this->body->asQuery();
      
      $this->validate(new UserValidator($data));
      if ($this->hasErrors()){
        // ... an error occured in validation
      } else {
        // ok...
      }
   }
}
```

The base controller class has the `validate(Validator $validator, $method = null)` method.
You can invoke this method repeatedly and it will works. Let's look at an example:

```
public function index(){
  $data = $this->body->asQuery();
  
  $this->validate(new UserValidator($data));
  $this->validate(new CaptchaValidator());
  if ($this->hasErrors()){
    // ... an error occured in validation
  } else {
    // ok...
  }
}
```

Here, we have used two validators for checking user's data and captcha's word. 
If you want to check by using only one method of validator, use the second argument 
of the validate method:

```
$this->validate(new UserValidator($data), 'one'); // the validator should have the "one" method.
```
