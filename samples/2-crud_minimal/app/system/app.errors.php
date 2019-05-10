<?php
if (! defined('Halt_On_Errors'))
  define('Halt_On_Errors', true);

ini_set('error_log', dirname(__DIR__) . '/logs/php.log');
ini_set('log_errors', 1);
ini_set('display_startup_errors', (Config::Debug? 1 : 0));
ini_set('display_errors', (Config::Debug? 1 : 0));

if (Config::Debug)
  error_reporting(E_ALL | E_STRICT);
else
  error_reporting(E_ERROR | E_PARSE);

set_error_handler('log_error');
set_exception_handler('log_exception');
register_shutdown_function('log_shutdown');

function log_exception($ex, $fatal=false, $op=null)
{
  if (class_exists('Db', false))
    Db::Rollback();
  DoLog($ex->getMessage(), $ex->getFile(), $ex->getLine(), 'Exception', $fatal, $op, $ex->getTrace());
}
function log_error($severity, $msg, $file, $line)
{
  switch ($severity)
  {
    case E_NOTICE:
    case E_USER_NOTICE:
      if (! Config::Debug) return;
      $tipo = 'Notice';
      break;
    case E_WARNING:
    case E_USER_WARNING:
    case E_STRICT:
      if (! Config::Debug) return;
      $tipo = 'Warning';
      break;
    case E_ERROR:
    case E_PARSE:
    case E_RECOVERABLE_ERROR:
      $tipo = 'Error';
      break;
    case E_USER_ERROR:
      $tipo = 'Exception';
      break;
    default:
      $tipo = 'Unknown Error';
      break;
  }
  $fatal = (Halt_On_Errors && in_array($tipo, ['Error', 'Exception', 'Unknown Error']));
  DoLog($msg, $file, $line, $tipo, $fatal);
}
function log_shutdown()
{
  $error = error_get_last();
  if ($error["type"] === E_ERROR || $error["type"] === E_COMPILE_ERROR || $error["type"] === E_USER_ERROR || $error["type"] === E_RECOVERABLE_ERROR)
  {
    DoLog($error["message"], $error["file"], $error["line"], 'Error', true);
    if (! Config::Debug)
      echo("<br><b>Não foi possível completar a operação<br><br>Tente novamente mais tarde.</b><br>");
  }
}

function DoLog($msg, $file=null, $line=null, $type=null, $fatal=false, $op=null, $stack=null)
{
  try
  {
    $pilha = "";
    //apenas log
    if (! $file)
    {
      $stack = debug_backtrace()[0];
      $msg .= "    Local: " . $stack["file"] . "[" . $stack["line"] . "]";
    }
    else
    //exceção/erro
    {
      $stack = $stack ?: debug_backtrace();
      //stack to str
      if (is_array($stack) && ! empty($stack))
      {
        $pilha = "";
        foreach ($stack as $e)
        {
          $e_file = get($e, "file");
          if ($e_file === __FILE__) //
            continue;
          $e_class = get($e, "class");
          if ($e_class == "App")
            continue;
          $fnc = get($e, "function");
          if ($fnc === "DoLog" || $fnc === "log_error" || $fnc === "log_exception" ||
              ($fnc === "Call" && $e_class == "Controller") ||
              $fnc === "call_user_func_array" || $fnc === "log_shutdown")
            continue;
          $args = "";
          if (! empty($e['args']))
          {
            if (! isset($e['line'])) $e['line'] = " ";
            foreach ($e["args"] as $k => $v)
            {
              if (is_object($v))
                $v = "{" . get_class($v) . "}";
              else if (is_array($v))
                $v = "{Array}"; //$v = ' [' . implode(',', $v) . '] ';
              else
                $v = ($v===null? "null" : (is_string($v)? "\"$v\"" : str_replace(["\n", "\r"], ["§", " "], $v)));
              $v = str_replace("\r\n", " ", $v); do { $v = str_replace("  ", " ", $v); } while (strpos($v, "  "));
              $args .= ', ' . (is_numeric($k)? '' : "{$k}:") . $v;
            }
            $args = substr($args, 2);
          }
          $pilha .= "\r\n   {$e_class} " . get($e, "type") . $fnc .
                    "($args)  " .
                    substr($e_file, strpos($e_file, "app")) . '[' . $e['line'] .']';
        }
      }
    }

    $msg = strip_tags($msg);
    $i = strpos($msg, "Stack trace:");
    if ($i) $msg = substr($msg, 0, $i-1);

    $type = ($type===null? '' : "$type:");
    $line = ($line===null? '' : "[$line]");
    $file = ($file===null? '' : "\r\n  Local: " .  substr($file, strpos($file, "app")));
    $op   = ($op===null?   '' : "\r\n  Ação: $op");
    $app_msg = '';
    if (class_exists('App', false))
    {
      $imb  = (defined('_IMB_')? "Imb: " . str_pad(_IMB_, 3, '0', STR_PAD_LEFT) . "  " : "");
      $app_msg = "{$imb}Usr: " . (App::$User? App::$User->Login :  '') . '  ';
    }
    $imb  = (defined('_IMB_')? str_pad(_IMB_, 3, '0', STR_PAD_LEFT) : "000");

    $msg = Date('d/m/Y H:i:s ') .  " $app_msg $type $msg $op $file $line " . ($pilha? "\r\n  Stack trace:$pilha \r\n" : "\r\n") . "\r\n";
    $arq = dirname(__DIR__) . '/../logs/app_' . Date('Y-m-d') . "_{$imb}.log";
    if (is_file($arq)) chmod($arq, 0777);
    file_put_contents($arq, $msg, FILE_APPEND);

    if ($fatal)
    {
      if (Config::Debug)
      {
        if (! headers_sent())
          header('Content-Type: text/html; charset=utf-8');
        echo('<br><br><pre>' . str_replace("|", "\r\n", $msg) . '</pre><br><br>');
      }
      if (! headers_sent())
        header('HTTP/1.0 500 Nao foi possivel completar a operacao');
      die("Não foi possível completar a operação");
    }
  }
  finally
  {
    return false;
  }
}

class FileUploadException extends Exception
{
  public function __construct($code)
  {
    $msg = 'Erro desconhecido';
    switch ($code)
    {
      case UPLOAD_ERR_OK:
        $msg = 'Status: OK - erro não identificado';
        break;
      case UPLOAD_ERR_INI_SIZE:
        $msg = 'UPLOAD_ERR_INI_SIZE: O arquivo enviado excede o limite definido na diretiva upload_max_filesize do php.ini';
        break;
      case UPLOAD_ERR_FORM_SIZE:
        $msg = 'UPLOAD_ERR_FORM_SIZE: O arquivo excede o limite definido em MAX_FILE_SIZE no formulário HTML';
        break;
      case UPLOAD_ERR_PARTIAL:
        $msg = 'UPLOAD_ERR_PARTIAL: O upload do arquivo foi feito parcialmente.';
        break;
      case UPLOAD_ERR_NO_FILE:
        $msg = 'UPLOAD_ERR_NO_FILE: Nenhum arquivo foi enviado.';
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $msg = 'UPLOAD_ERR_NO_TMP_DIR: Pasta temporária ausente.';
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $msg = 'UPLOAD_ERR_CANT_WRITE: Falha em escrever o arquivo em disco.';
        break;
      case UPLOAD_ERR_EXTENSION:
        $msg = 'UPLOAD_ERR_EXTENSION: Uma extensão do PHP interrompeu o upload do arquivo (Dica: verifique as extensões c/ phpinfo )';
        break;
    }
    parent::__construct($msg, $code);
  }
}