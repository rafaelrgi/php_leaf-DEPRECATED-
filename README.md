# php_leaf
###### Welcome to the Leaf!
  
**Leaf** is a Light, Easy, Agile/Adaptable, Fast PHP MVC framework, aiming to provide a very thin (but helpfull) foundation for PHP web sites and web applications.
  
To use it, just take the `/src/` folder as a starting point for your code, keeping the folder `/src/app/systems/` untouched.
  
  
  
   
## Automatic Routes
You don't need to set routes, **Leaf** will automatically route all the requests to an Action in a Controller.
  - `http://domain.com/Login` will be routed to the method `Index` (the default action) in the controller `Login`
  - `http://domain.com/Users/List` will be routed to the method `List` in the controller `Users`
  - `http://domain.com/Users/Edit/42` will be routed to the method `Edit` in the controller `Users`, passing 42 as a parameter:
    - `public function Edit($id) { ... }`
  
  
## Automatic Models
Every controller will automatically receive an Model instance (if the corresponding model exists), accessible through the property "Model":
  ```php
  <?php
  $this->Model->...
  ```
   
  
## Automatic "POST record object"
On Every POST request the Controller will automatically create an object with the contents of the POST data (`$_POST`), accessible through the property "Record":
  ```php  <?php
  $this->record->...
  ```
