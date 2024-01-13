Simple script to limit authorisation attempts for WordPress. The script needs to be installed manually. It is triggered before Worpdress is loaded.

Usage
----

Copy the `wp_limit_login` folder to the folder where `wp-config.php` is located.

Add the following line to the beginning of the `wp-config.php` file:

```
require_once __DIR__ .'/wp_limit_login/zero.php';
```

