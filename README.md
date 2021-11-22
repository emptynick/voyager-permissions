# spatie/permission plugin for Voyager II

This plugin for Voyager II implements spaties [laravel-permission](https://github.com/spatie/laravel-permission) to authorize actions and users.

## Getting started

### Require the package

First, require the plugin: `composer require emptynick/voyager-permissions`.

### Run the installer

Run the installer by calling `php artisan permissions:install`.

#### Publishing migrations and config file
  
First the installer will publish a configuration file and migrations.  
After that you will be asked if you want to migrate now.  
If you want to change the published migrations select `No` and run the command again after you finished editing.

#### Add the HasRole Trait to your user model

The the installer will ask you to open your user model and add the trait `Emptynick\Permissions\Traits\HasRoles`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Emptynick\Permissions\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // ...
}
```

#### Add necessary permissions

In order to be allowed to open the permission manager, the installer will add some required permissions to your user.  
Enter your user model (normally `\App\Models\User`) hit enter and enter the ID of your user.

Thats it! Now you are able to access the `Permission` page from the menu.