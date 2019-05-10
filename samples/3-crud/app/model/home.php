<?php
class HomeModel extends Model {

  public function getInfo() {
    ob_start();
    phpinfo();
    $res = ob_get_contents();
    ob_clean();
    return $res;
  }

}