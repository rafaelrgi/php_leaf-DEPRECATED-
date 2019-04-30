<?php
abstract class Model extends Obj {

  /** @var DataMapper $Mapper */
  protected $Mapper;

  public function Create() {
    //TODO: return $this->Mapper->CreateRecord();
  }

  public function GetId($obj) {
    //TODO: return $this->Mapper->GetId($obj);
  }

  public function FindAll() {
    //TODO: return $this->Mapper->FindAll();
  }

  public function Find($params) {
    //TODO: return $this->Mapper->Find($params);
  }

  public function FindById($id) {
    //TODO: return $this->Mapper->FindById($id);
  }

  public function Save($rec) {
    //TODO: return $this->Mapper->Save($rec); //return (string)$this->Mapper->GetId($rec);
  }

  public function Delete($id_or_obj) {
    //TODO: return $this->Mapper->Delete($id_or_obj);
  }

  public function GetPagination() {
    //TODO: return $this->Mapper->GetPagination();
  }

}