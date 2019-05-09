<?php
//(HTTPS)
/*
if (! isset($_SERVER['HTTPS']) && isset($_SERVER['HTTP_HOST']) && !isset($_GET["AppId"]) && !isset($_GET["appid"])) {
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
  die();
}
*/

require(__DIR__ . '/app/app.php');  die();
