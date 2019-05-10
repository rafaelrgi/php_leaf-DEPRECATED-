<?php
//db.php: centraliza o código de acesso ao bd
if (! defined('_Db_'))
{
  define('_Db_', true);

  class Db {
    const NoEscapeTag = "<::keep_html_tags::/>";
    private static $conn = null;

    private static function open() {
      if (Db::$conn) return;
      try {
        $conn_str = "mysql:host=". Config::Db_Server .";dbname=" . Config::Db_Name . ";charset=utf8";
        Db::$conn = new PDO($conn_str, Config::Db_User, Config::Db_Pass);
        Db::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Db::$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
        //Db::$conn->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, false);
        //Db::$conn->exec('SET CHARACTER SET utf8');
        Db::$conn->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, true);
      }
      catch (PDOException $ex) {
        log_exception($ex);
        die("Não foi possível conectar ao banco de dados");
      }
    }

    public static function close() {
      if (! Db::$conn)
      return;
      //Db::$conn->close();
      Db::$conn = null;
    }

    /** Marca string para evitar remoção de tags, etc */
    public static function noEscape($s) {
      return Db::NoEscapeTag . $s;
    }

    public static function inTransaction() {
      self::open();
      return  (bool)(Db::$conn->inTransaction());
    }
    public static function beginTran() {
      self::open();
      Db::rollback();
      Db::$conn->beginTransaction();//if (! Db::$conn->inTransaction())
    }
    public static function commit() { if (Db::$conn && Db::$conn->inTransaction()) Db::$conn->commit(); }
    public static function rollback() { if (Db::$conn && Db::$conn->inTransaction()) Db::$conn->rollback(); }

    public static function prepare($sql, $params = null, $tblNames = false) {
      Db::open();
      Db::$conn->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, $tblNames);
      try {
        return Db::$conn->prepare($sql);
      }
      catch (PDOException $ex) {
        throw new Exception($ex->getMessage());
      }
      return $qry;
    }

    public static function lastInsertId() {
      return (Db::$conn? Db::$conn->lastInsertId() : false);
    }

    /**executa ação e retorna nº linhas afetadas */
    public static function exec($sql, $params=null) {
      try {
        if (! get($params, "_checked_params_"))
          $params = Db::checkParams($sql, $params);
        unset($params["_checked_params_"]);
        $qry = Db::prepare($sql);
        $qry->execute($params);
        return $qry->rowCount();
      }
      catch(Exception $ex) {
        throw new Exception($ex->getMessage());
      }
    }

    /**executa a consulta e retorna o iterador */
    public static function query($sql, $params=null) {
      try {
        if (! get($params, "_checked_params_"))
          $params = Db::checkParams($sql, $params);
        unset($params["_checked_params_"]);
        $qry = Db::prepare($sql);
        $qry->execute($params);
        return $qry;
      }
      catch(Exception $ex) {
        throw new Exception($ex->getMessage());
      }
    }

    ///executa a consulta e retorna um único registro
    // public static function Query1($sql, $params = null)
    // {
    //   $qry = Db::query($sql, $params);
    //   $row = $qry->fetch(PDO::FETCH_ASSOC);
    //   Db::Format($format_row_func, $row);
    //   return $row;
    // }

    public static function load($tbl_or_select=null, $params=null, $cols=null, $orderBy=null, $group=null, $arr_key_prop=null) {
      $cols = trim($cols ?: '*');
      static::tableOrSelect($tbl_or_select, $cols);
      $tbl_or_select = static::buildSelect($tbl_or_select, null, $orderBy);
      $qry = static::query($tbl_or_select, $params);
      $res = $qry->fetchAll(PDO::FETCH_CLASS, "Obj");

      // if ($cols && $cols!='*')
      //   $res = propsCase($res, strCase::Lower, $cols);
      if ($group)
        $res = static::GroupBy($res, $group);
      if (! $arr_key_prop)
        return $res;
      $x = [];
      foreach ($res as $rec)
        $x[$rec->{$arr_key_prop}] = $rec;
      return $x;
    }

    public static function loadRecord($tbl_or_select=null, $params=null, $cols=null, $group=null) {
      if ($group)
        return static::load($params, $tbl_or_select, $cols, null, null, $group);

      $cols = $cols ?: '*';
      static::tableOrSelect($tbl_or_select, $cols);
      $qry = static::query($tbl_or_select, $params);
      $qry->setFetchMode(PDO::FETCH_CLASS, "Obj");
      $res = $qry->fetch();
      // if ($cols && $cols!='*')
      //   $res = propsCase($res, strCase::Lower, $cols);
      return $res;
    }

    /**executa a consulta e retorna um único valor (valor simples) */
    public static function loadVal($tbl_or_select=null, $params=null, $col=null) {
      if (! $tbl_or_select)
        throw new Exception("Parâmetro inválido!");
      if ($col && strpos($tbl_or_select, " ") === false && strpos($tbl_or_select, "\n") === false)
        $tbl_or_select = "Select {$col} From {$tbl_or_select}";
      $row = static::query($tbl_or_select, $params)->fetch(PDO::FETCH_NUM);
      return ((is_array($row) && sizeof($row) > 0)? $row[0] : $row);
    }

    /** fetch("EmailAgendado", null, null, ["EmailGrupo"=>"Left,GrupoId=Id"]):
     *
     * Join: Tabela_ligada=>(opcional: Left,) on(cláusula de ligação)
     * Cuidado com Left e nomes de colunas repetidos sem alias! */
    public static function fetch($table, $params=null, $cols=null, $joins=null, $orderBy=null, $pag=null, $group=null, $arr_key_prop=null) {
      $cols = trim($cols ?: '*');

      $join = null;
      foreach ($joins as $k=>$v) { //"EmailGrupo"=>"GrupoId=Id"   "EmailGrupo"=>"Left,GrupoId=Id"
        $tbl = $k;
        $a = explode('=', $v);
        if (strpos($a[0], ',')) {
          $ar = explode(',', $a[0]);
          $a[0] = $ar[1];
          $join .= $ar[0] . ' ';
        }
        $on = "{$table}." . trim($a[0]) . '=' . "{$tbl}." . get($a, 1, $a[0]);
        $join .= "Join {$tbl} on {$on} ";
      }

      $sql = "Select {$cols} From {$table} {$join}";
      return static::load($sql, $params, null, $orderBy, $pag, $group, $arr_key_prop);
    }

    /**Salva o registro (inserção retorna o Id)
     * $idProp=true: usa a 1ª propriedade de $rec
    */
    public static function save($rec, $tbl=null, $idProp="Id", $cols=null, $insert=null, $update=null, $force_insert=false) {
      if (! $rec)
        return;

      if ($idProp === true)
        $idProp = array_keys(get_object_vars($rec))[0];
      else if (! $idProp && property_exists($rec, "Id"))
          $idProp = "Id";
      $idPrpOri= $idProp;

      if ($cols) {
        $id = static::save(new Obj($rec, "$idProp,$cols"), $tbl, $idProp, null, $insert, $update, $force_insert);
        $rec->{$idPrpOri} = $id;
        return $id;
      }

      if (is_array($rec)) {
        foreach ($rec as $k => $x)
          static::save($x, $tbl, $idProp, $insert, $update, $force_insert);
        return;
      }

      //$id = (int)($force_insert? 0 : get($rec, $idProp, get($rec, "Id", 0)));
      $id = (int)(get($rec, $idProp, get($rec, "Id", 0)));

      if (! $tbl && ($update || $insert)) {
        foreach (get_object_vars($rec) as $key => $val) {
          if (strtoupper($key) === "ID" || $key === $idProp || strtoupper($key) === "FILES")
            continue;
          if (stripos($key, "Dt") === 0 && ! toDate($val))
            $rec->$key = null;
          // if ($val === "0000-00-00 00:00:00") //($val === "0000-00-00" || $val === "0000-00-00 00:00:00")
          //   $rec->$key = null;
        }

        if ($id > 0 && ! $force_insert)
          $sql = $update;
        else
          $sql = $insert;
        $params = get_object_vars($rec);
      }
      else {
        $cols    = "";
        $vals    = "";
        $params  = [];
        $idProp  = $idProp;
        $props   = get_object_vars($rec);
        foreach ($props as $key => $val) {
          if (strtoupper($key) === "FILES" || ((strtoupper($key) === "ID" || $key === $idProp) && !$force_insert))
            continue;
          if (stripos($key, "Dt") === 0 && ! toDate($val))
            $val = null;
          if ($val === "0000-00-00" || $val === "0000-00-00 00:00:00")
            $val = null;
          array_push($params, $val);
          if ($id && ! $force_insert)
            $cols .= "$key=?,";
          else {
            $cols .= "$key,";
            $vals .= "?,";
          }
        }

        $cols = substr($cols, 0, -1);
        $vals = substr($vals, 0, -1);
        if ($id && ! $force_insert) {
          $sql = "Update $tbl Set $cols Where $idProp=?";
          array_push($params, $id);
        }
        else
          $sql = "Insert Into $tbl ($cols) Values ($vals)";
      }

      static::exec($sql, $params);
      if (! $id) {
        $id = static::lastInsertId();
        $rec->{$idPrpOri} = $id;
      }
      return $id;
    }

    /**Exclui um ou mais registros */
    public static function deleteId($ids, $tbl, $idProp="Id") {
      $id = (is_array($ids)? implode(',', $ids) : $ids);
      $sql = "Delete From $tbl Where $idProp in ($id)";
      return static::exec($sql);
    }

    public static function delete($tbl, $params, $idProp="Id") {
      $rows = static::load($tbl, $params, $idProp);
      if (! $rows || empty((array)$rows))
        return 0;
      $ids = "";
      foreach ($rows as $row) {
        $ids .= $row->{$idProp} . ',';
      }
      $ids = substr($ids, 0, -1);
      return static::exec("Delete from {$tbl} where {$idProp} in ($ids)");
    }

    /**Exclui registros baseados em uma consulta; a consulta deve retornar os ids: Select Id from... */
    public static function deleteFromSql($tbl, $select, $idProp="Id") {
      $rows = static::load($select);
      if (! $rows || empty((array)$rows))
        return 0;
      $ids = "";
      foreach ($rows as $row) {
        $ids .= $row->{$idProp} . ',';
      }
      $ids = substr($ids, 0, -1);
      return static::exec("Delete from {$tbl} where {$idProp} in ($ids)");
    }


    public static function createRecord($tbl_or_select) {
      return new Obj();
      /*
      UNDONE: remove Order by
      static::tableOrSelect($tbl_or_select);
      $qry = static::query(static::buildSelect($tbl_or_select, "1=0"));
      $max = $qry->columnCount();
      for ($i=0; $i<$max; $i++) {
        $s = $qry->getColumnMeta($i)['name'];
        $x = strpos($s, '.');
        if ($x !== false)
          $s = substr($s, $x+1);
        $obj->{$s} = null;
      }
      return $obj;
      */
    }


    public static function buildSelect($select, $where=null, $orderBy=null, $limit=null) {
      $select = "$select   ";
      if (! $where && ! $orderBy && ! $limit)
        return $select;
      $where = str_replace(" Where ", "", $where);
      $orderBy = str_replace(" Order by ", "", $orderBy);
      $limit = str_replace(" Limit ", "", $limit);

      if ($where) {
        $i = stripos($select, " :{Where} ") ?: stripos($select, " Order by ") ?: stripos($select, " :{Order} ") ?:
            stripos($select, " Limit ") ?: stripos($select, " :{Limit} ") ?:
            PHP_INT_MAX;
        $select = substr_replace($select, (stripos($select, " Where ")? " and $where " : " Where $where "), $i, 0);
      }

      if ($orderBy) {
        $i = stripos($select, " Order by ") ?: stripos($select, " :{Order} ") ?:
            stripos($select, " Limit ") ?: stripos($select, " :{Limit} ") ?:
            PHP_INT_MAX;
        $select = substr_replace($select, (stripos($select, " Order by ")? " $orderBy " : " Order by $orderBy "), $i, 0);
      }

      if ($limit) {
        $i = stripos($select, " Limit ") ?: stripos($select, " :{Limit} ") ?:
            PHP_INT_MAX;
        $select = substr_replace($select, (stripos($select, " Limit ")? " $limit " : " Limit $limit "), $i, 0);
      }

      return $select;
    }

    public static function normalize($sql) {
      $s = ' ' . str_replace(["\r\n", "\r", "\n"], [" ", " ", " "], $sql) . ' ';
      while (strpos($s, "  ") !== false) {
        $s = str_replace("  ", " ", $s);
      }
      return trim($s);
    }

    public static function checkParams(&$sql, $params, $dbCase=null) {
      if (! isset($params["_checked_params_"]))
        $sql = Db::normalize($sql);
      $where = "";
      $is_select = (stripos($sql, "Select") !== false && stripos($sql, "Update") === false && stripos($sql, "Insert") === false);
      if ($params && ! isset($params["_checked_params_"])) {
        if ($params && ! is_array($params))
          $params = array($params);
        foreach ($params as $key => $val) {
          //if (is_array($val)) unset($params[$key]);

          if ($val === null || is_array($val)) continue; //if ($val === null || is_array($val)) continue;
          $keepTags = (strpos($val, self::NoEscapeTag) === 0);
          $val = str_replace(self::NoEscapeTag, "", EscapeSql($val, $keepTags));
          $params[$key] = $val;

          //"macros"
          if (strpos($key, "{")===false || strpos($key, "}")===false)
            continue;
          $sql = str_replace(":{$key}", $val, $sql);
          unset($params[$key]);
        }

        //----positional params?
        if (strpos($sql, '?')) {
          if (! $params)
            $params = [];
          for($i=substr_count($sql, '?') - sizeof($params); $i>0; $i--)
            array_push($params, null);
          //IN
          if (preg_match_all("/\sin\s*\(\s*\?\s*\)/i", $sql, $tags, PREG_OFFSET_CAPTURE)) {
            //lista clausulas in
            $ins = [];
            foreach ($tags as $tag)
              array_push($ins, strpos($sql, '?', $tag[0][1]));
            //substitui in's
            $i = 0;  $p = 0;
            while(true)
            {
              $p = strpos($sql, '?', $p+1);
              if ($p === false)
                break;
              if (in_array($p, $ins))
              {
                $sql = substr_replace($sql, $params[$i], $p, 1);
                unset($params[$i]);
              }
              $i++;
            }
          }
        }
        else {
          //----named params
          //:param => :param_1, :param_2,...
          foreach($params as $key => $val)
          {
            $val = (is_string($val)? trim($val) : $val);
            preg_match_all("/:".$key."\b/", $sql, $matches);
            $n = (isset($matches[0])) ? count($matches[0]) : 0;
            //parâmetro não usado
            if ($n === 0)
            {
              if (! $is_select)
                unset($params[$key]);
              else
              {
                if (! $dbCase || $dbCase === strCase::None)
                  $col = $key;
                else
                  $col = ($dbCase === strCase::Lower? strtolower($key) : strtoupper($key));

                if (strncasecmp("Cast(", $key, 5)===0) //"Cast({$col} as Date)"
                {
                  $s = $key;
                  $key = trim(substr($key, 5, strpos($key, " ")-5), " ()");
                  $params[$key] = $params[$col];
                  unset($params[$s]);
                  $val = str_replace("\'", "'", $val);
                }

                if (is_array($val)) {
                  $s = "{$col} in (" . implode(',', $val) . ")";
                  unset($params[$key]);
                }
                else if ($val === null)
                {
                  $s = "{$col} is null";
                  unset($params[$key]);
                }
                else if (strCasecmp("not null", $val)===0)
                {
                  $s = "{$col} is not null";
                  unset($params[$key]);
                }
                else if (strpos($val, '%')!==false)
                  $s = "{$col} Like :{$key}";
                else if (strncasecmp("Between", $val, 7)===0)
                {
                  $s = "{$col} {$val}";
                  unset($params[$key]);
                }
                else
                  $s = "{$col}=:{$key}";
                $where .= ($where? " and " : " ") . "({$s}) ";
              }
            }
            else if($n > 1) {
              //params
              for($i=1; $i<=$n; $i++)
              {
                $params["{$key}_{$i}"] = $val;
              }
              unset($params[$key]);
              //sql
              $i = 0;
              $sql = preg_replace_callback('(:'.$key.'\b)',
              function($paMatches) use (&$i) { return sprintf("%s_%d", $paMatches[0], ++$i); } , $sql, $limit = -1, $i);
            }
          }
          //IN
          if (preg_match_all("/\sin\s*\(\s*:\w+\s*\)/i", $sql, $tags)) {
            if (isset($tags[0]) && is_array($tags[0]))
              $tags = $tags[0];
            foreach ($tags as $key) {
              $key = str_replace([')', ' '], ["", ""], substr($key, strpos($key, ':')));
              if (! $key)
                continue;
              $val = get($params, str_replace(':', '', $key), 0) ?: 0;
              $sql = str_replace($key, $val, $sql);
              unset($params[str_replace(':', '', $key)]);
            }
          }
        }
        $params["_checked_params_"] = true;
      }

      $sql = str_replace([":{Where}",":{Order}",":{Limit}"], "", Db::buildSelect($sql, $where));
      return $params;
    }


    public static function paginate($sql, $pag, $per_page) {
      $res = new stdClass();

      $res->Total = PHP_INT_MAX;
      if ($per_page) {
        try {
          $res->Total = Db::loadVal("SELECT COUNT(1) FROM ($sql) tmp");
        }
        catch(Exception $e) { }
      }

      $res->Page    = ($pag? $pag : 1);
      $res->PerPage = ($per_page? $per_page : PHP_INT_MAX);
      $res->Pages   = (int)ceil($res->Total / $res->PerPage);
      $res->Offset  = max(0, ($res->Page - 1) * $res->PerPage);
      $res->Script  = ($per_page? " Limit {$res->PerPage} Offset {$res->Offset} " : "");
      return $res;
    }


//-------- PRIVATE --------
    private static function tableOrSelect(&$tbl_or_select, $cols='*') {
      if ($tbl_or_select && strpos($tbl_or_select, " ") === false && strpos($tbl_or_select, "\n") === false)
        $tbl_or_select = "Select $cols From {$tbl_or_select}";
    }

//----------------
  }

}