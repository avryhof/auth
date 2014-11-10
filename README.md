Auth
====

An Authentication Class similar to Pear Auth ( http://pear.php.net/package/Auth/ )

This class was created primarily because when I try to use pear/auth, composer fires a bunch of dependency issues I don't want to resolve. It is also designed to be lighter, and have less requirements than pear/auth, while maintaining somcompatible APIs.

Example
-------
```php
  require_once("auth.php");
  or
  require("vendor/autoload.php")

  $auth_options = array(
    "dsn" => "mysqli://".$dsn,
    "table" => "users",
    "usernamecol" => "email",
    "passwordcol" => "password",
    "db_fields" => array("id","name")
  );
  $auth = new Auth("Database", $auth_options, "", false);

  $auth->start();

  if (!$auth->checkAuth()) {
      header("Location:".$baseurl."/login.php");
  }

Also related
=============
- https://github.com/avryhof/database
- http://pear.php.net/package/Auth/docs