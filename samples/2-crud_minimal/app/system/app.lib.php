<?php
abstract class StrCase {
  const None  = 0;
  const Upper = 1;
  const Lower = -1;
}

function get($obj_array, $key, $val=null) {
  if (is_array($obj_array) && isset($obj_array[$key]))
    $val = $obj_array[$key];
  else if (is_object($obj_array) && property_exists($obj_array, $key))
    $val = $obj_array->$key;
  else if (! is_numeric($val) && is_string($val))
    $val = trim($val);
  return $val;
}

/**Copia propriedades públicas */
function Assign($dst, $src) {
  if (! $src || ! $dst)
    return;
  foreach (get_object_vars($src) as $key => $val)
    $dst->$key = $val;
}

function EndsWith($s, $ends) {
  $strlen  = strlen($s);
  $endslen = strlen($ends);
  if ($endslen > $strlen)
    return false;
  if ($endslen === 1)
    return (substr($s, -1) === $ends);
  return substr_compare($s, $ends, $strlen - $endslen, $endslen) === 0;
}

function EscapeSql($s, $keep_html_tags=false) {
  if (is_null($s))
    return null;
  if (is_object($s))
    return new Obj(EscapeSql(get_object_vars($s)));
  if (is_array($s))
    return array_map(__METHOD__, $s);
  if (is_int($s))
    return (int)$s;
  if (is_double($s))
    return (double)$s;
  if (empty($s) || !is_string($s))
    return $s;
  if ($keep_html_tags)
    return str_replace(["'"], ['"'], $s);
  return str_replace (
    ['\\',   "\0",  "\n",  "\r",  "'",   '"',   "\x1a"],
    ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
    strip_tags($s)
  );
}

/** Timestamp p/ 2015-10-21 00:00:00 */
function DbDateTime($dt=null) {
  return DbDate($dt, true);
}
/** Timestamp p/ 2015-10-21 */
function DbDate($dt=null, $time=false) {
  //já está no formato certo?
  if (is_string($dt) && strlen($dt) > 9 && $dt[4] == '-' && $dt[7] == '-')
    return $dt;
  $time = ($time? " H:i:s" : "");
  return date("Y-m-d{$time}", $dt ?: time());
}

/** 2015-10-21 ou 21/10/2015 para Timestamp */
function ToDate($s) {
  if (! $s || $s == '00/00/0000' || $s == '0000-00-00' || $s == '0000-00-00 00:00:00')
    return false;
  if (! is_string($s))
  {
    $s = (int)$s;
    if ($s > 0 && $s === (string)$s && ($s <= PHP_INT_MAX)); //($s >= ~PHP_INT_MAX)
      return $s;
    return false;
  }
  //21/10/2015
  if (preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/", $s, $matches)) {
    $d = $matches[1];
    $m = $matches[2];
    $y = $matches[3];
    if (! checkdate($m, $d, $y))
      return false;
    $s = "$y-$m-$d";
  }
  return max(strtotime($s), 0);
}

/**2015-10-21 => 21/10/2015 */
function FmtDate($d) {
  $d = ToDate($d);
  if (! $d)
    return $d;
  return date('d/m/Y', $d);
}

/**1999.440000 => 1.999,44 */
function Money($v, $symbol=false) {
  if (! $v)
    return "";
  return ($symbol? "R$ " : "") . number_format((double)$v, 2, ',', '.');
}

function StrToCase($s, $case) {
  return ($case === StrCase::Lower? strtolower($s) : ($case === StrCase::Upper? strtoupper($s): $s));
}

function StrHash($s, $salt=null) {
  $hash = new Obj();
  $hash->Salt = $salt ?: substr(md5(uniqid(rand(), true)), 5, 10);
  $hash->Hash = md5($hash->Salt . md5($s));
  return $hash;
}

function EncodeToken($array, $validadeDias=null, $validadeHoras=null) {
  $s2 = str_replace("/", "r", bin2hex(openssl_random_pseudo_bytes(5)));
  //validade token
  $t_ini = 1538362800; //"2018-10-01" >> 1538362800
  $val  = (int)($validadeDias * 24 + $validadeHoras) ?: 8784; //validade padrão: 366 dias
  $val = dechex(ceil( (strtotime("+{$val} hour") - $t_ini) / 600 )); //até 2038; precisão 10 minutos
  $n = strlen($val);
  if ($n < 5)
    $val .= 'x' . ($n > 3? "" : substr($s2, 0, 4-$n));

  $token = "";
  foreach ($array as $s) {
    if (strpos($s, '/')) throw new Exception("Caractere inválido: /");
    $token .= "{$s}/";
  }
  $token = substr($token, 0, -1) . substr($s2, -2);
  $s1 = substr(crc32($token), -3);
  $token = StrXor($token, $s1);

  $s = "";
  for ($i=0; $i<3; $i++)
    $s .= $val[$i] . $s1[$i];
  $s .= substr($val, 3);

  $token = str_replace(['6','8','b','c','e','f'], ['h','n','p','r','w','t'], $s) . EncodeParam($token);
  return strrev($token);
}

function DecodeToken($token) {
  if (! $token)
    return null;
  //valida token
  $t_ini = 1538362800; //"2018-10-01" >> 1538362800
  $token = strrev($token);

  $s1 = "";
  $s2 = "";
  $s = str_replace(['h','n','p','r','w','t'], ['6','8','b','c','e','f'], substr($token, 0, 8));
  for ($i=0; $i<6; $i+=2) {
    $s1 .= $s[$i + 1];
    $s2 .= $s[$i];
  }
  $s2 .= substr($s, 6, 2);

  $token = StrXor(DecodeParam(substr($token, 8)), $s1);
  if ($s1 !== substr(crc32($token), -3))
    return null;
  $s2 = substr($s2, 0, strpos($s2, 'x') ?: 5);
  $val = hexdec($s2) * 600 + $t_ini;
  if ($val < time())
    return null;

  return explode('/', substr($token, 0, -2));
}


/**codigopessoa >> CodigoPessoa:
 *  PropsCase($obj_or_objs, StrCase::Lower, ["CodigoPessoa"]);
 *  @* @param $obj 1 item ou array
*/
function PropsCase($obj, $case, $props) {
  if (! is_array($props))
    $props = explode(',', $props);

  if (is_array($obj)) {
    foreach ($obj as $row)
      PropsCase($row, $case, $props);
    return;
  }

  $res = new Obj();
  foreach ($props as $prop) { //get_object_vars($obj) as $key => $val
    $prop_case = StrToCase($prop, $case);
    if (! property_exists($obj, $prop_case)) {
      //if ($case !== StrCase::None) continue;
      $prop_case = strtolower($prop);
      if (! property_exists($obj, $prop_case)) {
        $prop_case = strtoupper($prop);
        if (! property_exists($obj, $prop_case))
          continue;
      }
    }
    $res->{$prop} = $obj->{$prop_case};
  }
  return $res;
}



function PushFront($array, $itm) {
  if (! is_array($itm))
    $itm = [0=>$itm];
  $x = $itm;
  foreach ($array as $key => $val)
    $x[$key] = $val;
  return $x;
}

/** Locate($array, ["Id"=>$id]): localiza um item no array, retornando a chave do item ou false */
function Locate($array, $params, $caseSens=true) {
  //compara um valor (parâmetro_da_busca == obj->prop)
  if ($caseSens)
    $cmp_val = function($v1, $v2) { return ($v1 == $v2); };
  else
    $cmp_val = function($v1, $v2) { return (0 === strcasecmp($v1, $v2)); };

  //compara um item do array com o(s) parâmetro_da_busca
  if (sizeof($params) === 1) { //1 propriedade
    $prop = array_keys($params)[0];
    $val  = $params[$prop];
    $cmp_reg = function($obj) use ($prop, $val, $cmp_val)
    {
      return (property_exists($obj, $prop) && $cmp_val($obj->{$prop}, $val));
    };
  }
  else {
    $props = array_keys($params);
    $vals  = array_values($params);
    $max = sizeof($props) - 1;
    $cmp_reg = function($obj) use ($max, $props, $vals, $cmp_val) {
      for($i=$max; $i>=0; $i--) {
        if (! property_exists($obj, $props[$i]))
          return false;
        if (! $cmp_val($obj->{$props[$i]}, $vals[$i]))
          return false;
      }
      return true;
    };
  }

  //envia cada item do array para comparação
  foreach ($array as $key => $obj) {
    if ($cmp_reg($obj))
      return $key;
  }
  return false;

//--------------------------------------------------
/*
//      TESTE:
  $array = [];
  $array[6] = new Obj(["Id"=>2,  "Nome"=>"Dois"]);
  $array[9] = new Obj(["Id"=>1,  "Nome"=>"Um"]);
  $array[7] = new Obj(["Id"=>9,  "Nome"=>"Nove"]);
  $array[8] = new Obj(["Id"=>5,  "Nome"=>"Cinco"]);
  $array[5] = new Obj(["Id"=>10, "Nome"=>"Um"]);
  $array[4] = new Obj(["Id"=>7,  "Nome"=>"Sete"]);

  //$x = Locate($array, ["Nome"=>"Um"]);
  //$x = Locate($array, ["NomeX"=>"Um"]);
  //$x = Locate($array, ["Nome"=>"um"], false);
  //$x = Locate($array, ["Id"=>10, "Nome"=>"Um"]);
  //$x = Locate($array, ["Ids"=>10, "Nome"=>"Um"]);
  //$x = Locate($array, ["Id"=>10, "Nome"=>"Um"], false);
  $x = Locate($array, ["Id"=>10, "Nome"=>"uM"], false);
  var_dump($array[$x]); die(">>>{$x}");
*/
}