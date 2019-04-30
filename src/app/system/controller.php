<?php
/**
Controller base, do qual todos os demais devem descender :: sempre terminar o nome c/ "Ctrl", ex.: LoginCtrl
  Passar dados para View: $this->nome = "xxx"; //na View acessa: $this->nome
  $this->Model->xxx(); //Model: automático

  return $this->Ok("Ok!");
  return $this->Forbidden();
  return $this->BadRequest("Mau sapão!");
  return $this->Result(403, "cai fora");
  return $this->View("login");
*/
abstract class Controller extends Obj {

  public function __construct() {
    $this->anonymous = $this->AnonymousMethods();
    if ($this->anonymous)
      $this->anonymous = ",$this->anonymous,";
    else
      $this->RequireAuth();

    $this->getModel();
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
      $this->Reg = $this->GetPostObj();
    $this->IsPopup = (bool)get($_GET, "popup");
    //TODO:
    if (get_class($this) !== 'CondominioDoctosCtrl') {
      unset($_SESSION['CondominioId']);
      unset($_SESSION['Condominio']);
    }
  }

  /** Métodos que podem ser chamados sem autenticação; ex.: return "Listar,Consultar"; */
  protected function AnonymousMethods() { return null; }

  /** ação padrão, chamada quando nenhuma ação especificada; sobrepor nos descendentes */
  public function Index() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
      return $this->OnPost();
    return $this->OnGet();
    // header("Content-type: text/html; charset=utf-8");
    // die("<center> <h1> Bem-vindo! </h1> </center>");
  }

  protected function OnGet() { return null; }
  protected function OnPost() { return null; }

  protected function GetPagination() {
    $pag = $this->Model->GetPagination();
    $pag->Order = get($_GET, "Order");
    return $pag;
  }

  protected function GetFilters($filter_names=null) {
    $filter_names = ",Page,Order," . ($filter_names ?: "") . ",";
    $o           = EscapeSql(new Obj($_GET, $filter_names));
    $o->Empty    = (empty((array)$o));
    $o->Required = false;
    $o->Page     = (int)get($o, "Page", 1);
    $o->Order    = get($o, "Order");
    return $o;
  }

  protected function View($view_name, $no_header=false) {
    if (! App::IsSite() || get($_SERVER, "CONTENT_TYPE") === "application/json") {
      $res = $this->Lista ?: $this->Reg;
      return $this->Json($res);
    }

    ob_start();
    include_once _APP_DIR_ . '/system/view.php';

    $view_name = strtolower($view_name);

    $hf = (! App::IsAjax() && !$no_header && ! $this->IsPopup && $view_name != 'header' && $view_name != 'footer');
    if ($hf)
      $this->View('header');

    include _APP_DIR_ . "/view/$view_name.php";

    if ($hf)
      $this->View('footer');

    ob_end_flush();
    return true;
  }

  protected function Json($obj) {
    if (! $obj)
      return $this->Ok("Sem registros para exibir");
    return $this->Ok($obj);
  }

  protected function Redirect($url, $msg=null) {
    return App::Redirect($url, $msg);
  }

  protected function RequireAuth() {
    if (! App::IsAuth())
    {
      if (defined('_IMB_') && _IMB_ >= 0)
        return $this->Redirect('Login');
      App::Redirect(App::getUrl());
      die('Acesso negado!');
    }
  }

  protected function Ok($content=null, $url=null) {
    return $this->Result(200, $content, $url);
  }

  protected function Pdf($content) {
    if (! $content)
      return $this->Forbidden();
    return $this->Mime($content, "application/pdf");
  }
  protected function File($path, $responseFileName=null) {
    if (! $path)
      return $this->Forbidden();
    $responseFileName = $responseFileName ?: basename($path);
    header("Content-Type: application/octet-stream");
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"$responseFileName\"");
    readfile($path);
  }
  protected function Mime($content, $mime, $title=null) {
    if (! $content)
      return $this->Forbidden();
    header("Content-type:$mime");
    if ($title)
      header("Content-disposition: inline; filename=\"$title\"");
    return $this->Ok($content);
  }

  /** Retorna Ok ou a mensagem de erro */
  protected function Response($s=null) {
    return ($s? $this->Error($s) : $this->Ok());
  }

  protected function Result($codigo, $content, $url=null) {
    if (! App::IsSite() || get($_SERVER, "CONTENT_TYPE") === "application/json")
    {
      if (is_string($content))
        $content = $content; //json_encode(new Obj(["Codigo"=>$codigo,"Mensagem"=>$content]));
      else if (is_object($content))
        $content = json_encode($content);
      else if (is_array($content)) {
        $o = new Obj();
        $o->Lista = $content;
        $content = json_encode($o);
      }
    }

    if (strlen($url) < 4)
      $url = null;
    if ($url)
      $content .= "|$url";
    switch ($codigo) {
      case 400: header("HTTP/1.0 $codigo Bad Request"); break;
      case 403: header("HTTP/1.0 $codigo Forbidden"); break;
      case 404: header("HTTP/1.0 $codigo Not Found"); break;
      case 500: header("HTTP/1.0 $codigo Error"); break;
      default:
        header('HTTP/1.0 200 OK'); break;
    }
    die((string)$content);
  }

  protected function Forbidden($msg = 'Acesso negado!', $url=null) {
    return $this->Result(403, $msg, $url);
  }

  protected function BadRequest($msg = 'Requisição inválida!', $url=null) {
    return $this->Result(400, $msg, $url);
  }

  protected function Error($msg = 'Não foi possível completar a operação!', $url=null) {
    return $this->Result(500, (string)$msg, $url);
  }

  protected function GetPostObj() {
    $reg = new Obj();
    foreach ($_POST as $key => $val) {
      if ($key === 'Id' || is_int($val)) {
        $reg->$key = (int)$val;
        continue;
      }
      $val = str_replace(Db::NoEscapeTag, "", $val); //if (strpos($val, Db::NoEscapeTag) === 0)
      $reg->$key = (is_string($val)? trim($val) : $val);
    }
    foreach ($_FILES as $key => $val) {
      if ($val['error'] === UPLOAD_ERR_NO_FILE)
        continue;
      if ($val['error'] !== UPLOAD_ERR_OK)
        throw new FileUploadException($val['error']);
      $reg->$key = $val;
    }
    return $reg;
  }

  private function getModel()
  {
    if (! $this->Model) {
      $cls = substr(get_class($this), 0, -4) . "Model"; //4 = strlen('Ctrl');
      if (class_exists($cls))
        $this->Model = new $cls();
    }
    return $this->Model;
  }

  public function Call($action, $params=null) {
    //se não existe a ação, executa ação padrão
    if (! Method_exists($this, $action)) {
      $params = [$action];
      $action = 'Index'; //App::$Params = PushFront(App::$Params, App::$Acao);
    }
    if (! App::IsAuth() && $this->anonymous != ',*,') {
      if (! $this->anonymous || false === strpos($this->anonymous, ",{$action},"))
        $this->RequireAuth();
    }

    if (! $params || empty($params))
      $this->{$action}();
    else
      call_user_func_array([$this, $action], $params);
  }

  /** @var Model $Model */
  protected $Model;
  private $anonymous;
}

