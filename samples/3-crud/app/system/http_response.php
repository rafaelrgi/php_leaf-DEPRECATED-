<?php
/**HttpResponse */
class Response { // static

  public static function ok($content=null) {
    return static::result(200, $content);
  }

  public static function badRequest() {
    return static::result(400);
  }

  public static function unauthorized() {
    return static::result(401);
  }

  public static function forbidden() {
    return static::result(403);
  }

  public static function notFound() {
    return static::result(404);
  }

  public static function error($msg = 'Error') {
    return static::result(500, (string)$msg);
  }

  public static function result($cod, $content=null, $msg=null) {
    if ($cod == 200 && ! $content)
      $cod = 204;

    $msg = "";
    switch ($cod) {
      case 200: $msg = "OK"; break;
      case 204: $msg = "No Content"; break;
      case 400: $msg = "Bad Request"; break;
      case 401: $msg = "Unauthorized"; break;
      case 403: $msg = "Forbidden"; break;
      case 404: $msg = "Not Found"; break;
      case 500: $msg = $content ?: "Error"; break;
    }
    if (! headers_sent())
      header("HTTP/1.0 $cod $msg");

    if ($cod == 200 && strpos(get($_SERVER, "CONTENT_TYPE", "application/json"), "application/json")!==false) {
      if (is_string($content))
        $content = $content; //json_encode(new Obj(["Codigo"=>$cod,"Mensagem"=>$content]));
      else if (is_object($content))
        $content = json_encode($content);
      else if (is_array($content)) {
        $o = new Obj();
        $o->Items = $content;
        $content = json_encode($o);
      }
      die((string)$content);
    }

    //die("$cod $msg");
    die("$content");
  }

}