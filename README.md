# php_leaf
###### Welcome to the Leaf!
  
**Leaf** is a Light, Easy, Agile/Adaptable, Fast PHP MVC framework, aiming to provide a very thin foundation for PHP web sites and web applications.
  
To use it, just take the `/src/` folder as a starting point for your code, keeping the folder `/src/app/systems/` untouched.
  
  
**TODO:**
- [ ] Features
- [ ] Samples
- [ ] How-tos
  
  
  
## Routes
You don't need to set routes, **Leaf** will automatically route all the requests to an Action in a Controller.
  - `http://domain.com/Login` will be routed to the method `Index` (the default action) in the controller `Login`
  - `http://domain.com/Users/List` will be routed to the method `List` in the controller `Users`
  - `http://domain.com/Users/Edit/42` will be routed to the method `Edit` in the controller `Users`, passing 42 as a parameter:
    - `public function Edit($id) { ... }`
  
  
## Models  
  
