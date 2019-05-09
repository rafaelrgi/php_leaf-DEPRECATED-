<?php
function FixStr($s, $len, $html_spaces=false, $left=false)
{
  $len += strlen($s) - mb_strlen($s, mb_detect_encoding($s));
  $s = substr(trim($s), 0, $len);
  $s = str_pad($s, $len, '^', ($left? STR_PAD_LEFT : STR_PAD_RIGHT));
  return str_replace('^', ($html_spaces? '&nbsp;' : ' '), substr(str_pad(trim($s), $len, '^', $left), 0, $len));
}

function DaysBetween($dt1, $dt2 = null)
{
  if (is_string($dt1)) // dd/mm/yyyy OU yyyy-mm-dd
  {
    if (strlen($dt1) < 9)
      return -1;
    if (strpos($dt1, '/'))
      $dt1 = substr($dt1, 6, 4) . '-' . substr($dt1, 3, 2) . '-'. substr($dt1, 0, strlen($dt1)===10? 2 : 1); //2010-01-01
    $dt1 = strtotime($dt1);
  }
  if (! $dt2) // data atual
    $dt2 = time();
  return (int)floor(($dt2 - $dt1) / 86400); //86400 = 60 * 60 * 24
}

function EncodeParam($s)
{
  // Se for um Array codifica com JSON
  $s = (is_array($s)) ? json_encode($s) : $s;
  // Codifica com Base64 e após com o XOR
  $s = base64_encode(StrXor($s));
  // String segura para URLs
  $s = str_replace(array('+', '/', '='), array('-', '_', ''), $s);
  $s = rawurlencode($s);
  return $s;
}

function DecodeParam($s)
{
  // Reverte a segunrança para URLs
  $s = rawurldecode($s);
  $s = str_replace(array('-', '_'), array('+', '/'), $s);
  $s = (strlen($s)%4) ? $s.substr('====', strlen($s)%4) : $s;
  // Decodifica com o XOR e após com Base64
  $s = StrXor(base64_decode($s));
  // Se o objeto original for um Array decodifica a String JSON
  $s = (is_array(json_decode($s, true))) ? json_decode($s, true) : $s;
  return $s;
}

function StrXor($s, $key=null)
{
  if (! $s)
    return null;
  $x    = '';
  $key    = $key ?: chr(155).chr(165);
  $lenKey = strlen($key);
  $lenStr = strlen($s) - 1;
  do
  {
    $x .= ($s{$lenStr} ^ $key{$lenStr % $lenKey});
  } while ($lenStr--);
  return strrev($x);
}

function EmptyDir($dir)
{
  if (! is_dir($dir))
    return;
  //arquivos ocultos (iniciando por ponto): $files = glob('path/to/temp/{,.}*', GLOB_BRACE);
  $dir = (endsWith($dir, '/')? "{$dir}*" : "{$dir}/*");
  $arqs = glob($dir);
  foreach($arqs as $arq)
  {
    if (is_dir($arq))
    {
      EmptyDir($arq);
      rmdir($arq);
      continue;
    }
    if(is_file($arq))
      unlink($arq);
  }
}

function RemoveDir($dir)
{
  if (! is_dir($dir))
    return;
  EmptyDir($dir);
  rmdir($dir);
}

function NumericOnly($s)
{
  return preg_replace('/\D/', '', $s);
}

function Mask($val, $mask)
{
  $res = "";
  $k = 0;
  $max = strlen($mask) - 1;
  for($i=0; $i<=$max; $i++)
  {
    if($mask[$i] == '#')
    {
      if(isset($val[$k]))
        $res .= $val[$k++];
    }
    else
    {
      if(isset($mask[$i]))
        $res .= $mask[$i];
    }
  }
  return $res;
}

function modulo_11($num, $base=9, $r=0) {
  $soma = 0;
  $fator = 2;

  // Separacao dos numeros
  for ($i = strlen($num); $i > 0; $i--) {
    $numeros[$i] = substr($num,$i-1,1);
    $parcial[$i] = $numeros[$i] * $fator;
    $soma += $parcial[$i];
    if ($fator == $base)
      $fator = 1;
    $fator++;
  }

  // Calculo do modulo 11 */
  if ($r == 0) {
    $soma *= 10;
    $digito = $soma % 11;
    if ($digito == 10) {
      $digito = 0;
    }
    return $digito;
  }
  else if ($r == 1) {
    $resto = $soma % 11;
    return $resto;
  }
}


// function removeAcentos($s, $remove_espacos=false)
// {
//   return preg_replace(array("/(". ($remove_espacos? " " : "") ."|-)/","/(ç)/","/(Ç)/","/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"), explode(" ", ($remove_espacos? "_" : "") . " c C a A e E i I o O u U n N"), $s);
// }

// function SoLetrasEspacos_($s)
// {
//   return preg_replace("/[^A-Za-z0-9_]/", '_', removeAcentos($s));
// }

// function Erro($codigo, $msg)
// {
//   switch ($codigo)
//   {
//     case 400: header('HTTP/1.0 404 Bad Request'); break;
//     case 403: header('HTTP/1.0 403 Forbidden'); break;
//     case 404: header('HTTP/1.0 404 Not Found'); break;
//   }
//   Mensagem($msg, true);
// }

// function Mensagem($mensagem, $error=false)
// {
//   include('view/mensagem.php');
//   die();
// }

// function uniqueFileName($dir='tmp', $pre='tmp', $ext='tmp')
// {
//   if ($dir === 'tmp' || ! is_dir($dir))
//     $dir = sys_get_temp_dir();
//   $ext = strtolower( (strlen($ext)>1 && $ext[0]!='.')? $ext = '.' . $ext : $ext);
//   $dir = str_replace(['\\', '//'], ['/', '/'], $dir . '/');
//   $s = strtolower($dir . uniqid($pre));
//   $i = '';
//   while (true)
//   {
//     $x = $s . $i .$ext;
//     if (! file_exists($x))
//       return $x;
//     $i = ((int)$i) + 1;
//   }
// }

