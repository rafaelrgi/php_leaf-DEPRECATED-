<?php

if (function_exists('opcache_reset') ) {
  opcache_reset();
  echo("Clear cache! <br>");
}

echo phpinfo();

?>