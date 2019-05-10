<?php
/* ---------------------------------------------------------------------------------------------------------------------
Central application class
Route the Requests to the right Controller and Request.
Using the format: /Controller/Action/[Param1/Param2/etc...]
  Ex.:localhost/app.php/Login/ResetPassword
      localhost/app.php/User/Editar/1

(htacess):
  localhost/Login/RecuperarSenha
--------------------------------------------------------------------------------------------------------------------- */

if (!isset($_SESSION)) {
  if (defined("Config::PathSession") && Config::PathSession) {
    //$s = (Config::PathSession===true? sys_get_temp_dir() : Config::PathSession);
    $s = null;
    if (Config::PathSession===true)
      $s = str_replace("public_html", "tmp", $_SERVER["CONTEXT_DOCUMENT_ROOT"]);
    else
      if (Config::PathSession=="TMP") $s = sys_get_temp_dir();
    else
      $s = Config::PathSession;
    if (is_dir($s)) {
      chmod($s, 0777);
      session_save_path($s);
    }
  }
  session_set_cookie_params(0);
  session_start();
}

define('Halt_On_Errors', true);

require __DIR__ . '/../../config.php';
require __DIR__ . '/app.errors.php';
require __DIR__ . '/app.lib.php';
require __DIR__ . '/../lib/lib.php';
require __DIR__ . '/base.php';
require __DIR__ . '/http_response.php';


class AppBase {//static!!!!

  public static $User; //const

  public static function isAuth() { return isset($_SESSION['auth']); }
  public static function isSite() { return static::$IsSite; }
  public static function isAjax() { return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'); }
  public static function getUrl() { return static::$Url; }
  public static function getCtrl() { return substr(static::$Ctrl, 0, -4); }
  public static function getAction() { return static::$Action; }
  public static function getVersion() { $v = explode('.', static::Versao); unset($v[3]); return implode('.', $v); }

  protected static function getCtrlFile() {
    return __DIR__ . '/../ctrl/' . str_replace('ctrl.php', '.php', strtolower(static::$Ctrl . '.php'));
  }

  public static function logExtraInfo() { return ""; }
  public static function logSuffix() { return ""; }

  public static function run($customViews=false) {
    define("_APP_DIR_", dirname(__DIR__) . DIRECTORY_SEPARATOR);
    static::$CustomViews = $customViews;

    spl_autoload_register(['App', 'AutoLoad']);

    static::$User = null;
    static::$Url = static::getProtocol() . '://' . $_SERVER['HTTP_HOST'] . Config::Path;

    //Aplicativo
    if (isset($_GET["AppId"])) {
      $_GET["appid"] = $_GET["AppId"];
      unset($_GET["AppId"]);
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      if (isset($_GET["appid"]) || strpos(get($_SERVER, "CONTENT_TYPE"), "application/json") !== false)
        $_POST = json_decode(file_get_contents("php://input"), true) ?: [];
    }

    static::extractRoute();
    static::dispatch();
  }

  public static function viewPath($view_name) {
    $view_name = strtolower(removeAcentos(str_replace(' ', '_', basename($view_name, ".php")))) . ".php";
    if (static::$CustomViews) {
      $vw = dirname(_APP_DIR_) . "/custom/$view_name";
      if (is_file($vw))
        return $vw;
    }
    return _APP_DIR_ . "view/$view_name";
  }

  public static function autoLoad($cls) {
    $path = '';
      if ($cls === 'Controller' || $cls === 'CrudController' || $cls === 'Model' || $cls === 'Db' || $cls === 'DataMapper')
        $path = 'system';
      else if (endsWith($cls, 'Ctrl')) {
        $cls = substr($cls, 0, -4); //4='Ctrl'
        $path = 'ctrl';
      }
      else if (endsWith($cls, 'Model')) {
        $cls = substr($cls, 0, -5);
        $path = 'model';
      }
      else if (endsWith($cls, 'Mapper')) {
        $cls = substr($cls, 0, -6);
        $path = 'model/db';
      }
      else if ($cls === 'Consultas' || is_file(_APP_DIR_ . "lib/" . strtolower($cls) . '.php')) {
        $path = 'lib';
      }
      // else if ($cls !== 'Config')
      // {
      //   throw new Exception("Classe não encontrada: $cls");
      //   return false;
      // }

      $path = _APP_DIR_ . "$path/" . strtolower($cls) . '.php';
      if (! is_file($path))
        return false;
      require $path;
      return true;
  }

  public static function redirect($url, $msg=null) {
    if (strpos($url, static::getUrl()) === false)
      $url = static::getUrl() . $url;
    //    die("<br><br><a id='redir' href='{$url}' title='{$msg}'></a><script src='" . static::getUrl() . "app/view/js/redir.min.js'></script>");
    header("Location: {$url}"); die();
    return;
  }

  protected static function getProtocol() {
    if ($_SERVER['REQUEST_SCHEME'])
      return $_SERVER['REQUEST_SCHEME'];
    if ('443' == $_SERVER['SERVER_PORT'] || (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'))
      return 'https';
    return 'http';
  }



  protected static function dispatch() {
    //se não existe o controller, ação padrão
    if (! endsWith(static::$Ctrl, 'Ctrl'))
      static::$Ctrl .= 'Ctrl';
    if (! file_exists(static::getCtrlFile())) {
      static::$Ctrl = 'HomeCtrl';
      static::$Action = 'Index';
    }

    //instancia o controller
    if (! class_exists(static::$Ctrl))
      require (static::getCtrlFile());
    $ctrl = new static::$Ctrl();
    //chama a ação no controller
    try {
      $ctrl->Call(static::$Action, static::$Params);
      if (! static::isSite()) {
        session_unset();
        //session_destroy()
      }
    }
    catch(Exception $ex) {
      log_exception($ex, true, static::$Ctrl . "::" . static::$Action);
    }
  }

  protected static function extractRoute() {
    static::$IsSite    = true;
    static::$AuthToken = null;
    static::$Ctrl      = null;
    static::$Action    = null;
    static::$Params    = [];

    //Aplicativo
    if (isset($_GET["appid"])) {
      $appId = $_GET["appid"];
      if ($appId == 1)
        $appId = str_replace("Basic ", "", get(array_change_key_case(getallheaders()), "authorization"));

      static::$IsSite = false;
      if ($appId)
        static::$AuthToken = $appId;
      unset($_GET["appid"]);

      // if (static::$User && $imb) {
      //   $_SESSION['auth'] = true;
      //   $_SESSION['user'] = serialize(static::$User);
      // }
      // else
      //   session_unset();
    }
    if (isset($_GET["XDEBUG_SESSION_START"])) unset($_GET["XDEBUG_SESSION_START"]);

    $route = str_replace($_SERVER['HTTP_HOST'], '', $_SERVER['REQUEST_URI']);
    if (Config::Path != '/')
      $route = str_replace(Config::Path, '', $route);

    if (! strlen($route))
      return;

    //processa GET params
    $i = strpos($route, '?');
    if ($i !== false) {
      $route = substr($route, 0, $i);
      foreach($_GET as $key=>$val) {
        if (is_array($val))
          $val = implode($val);
        //if ($key=="cli" && is_numeric($val)) static::$ClienteId = max((int)$val, 0);
        $route .= "/{$val}";
      }
    }

    // [0] => controller [1] => ação [2,...] => params
    $routes = array_values(array_filter(explode('/', $route), function($v, $k) { return strlen(trim($v)) > 0; }, ARRAY_FILTER_USE_BOTH)); //array_values(array_filter(explode('/', $route)));
    if (sizeof($routes) === 0 && endsWith($_SERVER['REQUEST_URI'], Config::Path . '0'))
      array_push($routes, 0);

    $max = sizeof($routes);
    static::$Ctrl = ($max > 0 ? trim($routes[0]) : '');
    static::$Action = ($max > 1 ? trim($routes[1]) : '');
    if (sizeof($routes) > 2) {
      for ($i = 2; $i < $max; $i++)
        array_push(static::$Params, urldecode($routes[$i]));
    }
  }

  protected static function sair($break=true) {
    //session_unset();
    (new LoginCtrl())->Sair($break);
    // static::$Ctrl = 'LoginCtrl';
    // static::$Action = 'Sair';
  }

  protected static $AuthToken;
  protected static $IsSite;
  protected static $Ctrl;
  protected static $Action;
  protected static $Params;
  protected static $Url;
  protected static $CustomViews;
}
