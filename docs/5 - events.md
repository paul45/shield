# Events

Shield fires off several events during the lifecycle of the application that your code can tap into. 

## Responding to Events

When you want to respond to an event that Shield publishes, you will need to add it to your `app/Config/Events.php` file. Each of the following events provides a sample for responding that uses a class and method name. Other methods are available. See the [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/extending/events.html) for more information. 

## Event List

#### didRegister

Triggered when a new user has registered in the system. It's only argument is the `User` entity itself.

```php
Events::trigger('didRegister', $user);

Events::on('didRegister', 'SomeLibrary::handleRegister');
```

#### didLogin

Fired immediately after a successful login. The only argument is the `User` entity.

```php
Events::trigger('didLogin', $user);

Events::on('didLogin', 'SomeLibrary::handleLogin');
```

#### failedLogin

Triggered when a login attempt fails. It provides an array containing the credentials the user attempted to 
sign in with, with the password removed from the array.

```php
// Original credentials array
$credentials = ['email' => 'foo@example.com', 'password' => 'secret123'];

Events::on('failedLogin', function($credentials) {
    dd($credentials);
});

// Outputs:

['email' => 'foo@example.com'];
```
