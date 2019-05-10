<?php
abstract class Model extends Obj {

  /**When $tbl_or_select contains an select, fill $insert, $update and $delete */
  protected $id_prop="Id", $tbl_or_select=null, $insert=null, $update=null, $delete=null;

  public function create() {
    return Db::createRecord($this->tbl_or_select);
  }

  public function getId($obj) {
    if (is_numeric($obj))
      return (int)$obj;
    return (int)$obj->{$id_prop};
  }

  public function findAll() {
    return Db::load($this->tbl_or_select);
  }

  public function find($params) {
    return Db::load($this->tbl_or_select, $params);
  }

  public function findById($id) {
    return Db::loadRecord($this->tbl_or_select, [$this->id_prop=>$id]);
  }

  public function save($rec) {
    return Db::save($rec, $this->tbl_or_select, $this->id_prop, $cols, $this->insert, $this->update);
  }

  public function delete($id_or_obj) {
    if (! $this->delete)
      return Db::deleteId($this->getId($id_or_obj), $this->tbl_or_select, $this->id_prop);
    return Db::exec($this->delete, $id_or_obj);
  }

  public function getPagination() {
    //TODO: return $this->Mapper->GetPagination();
  }

}