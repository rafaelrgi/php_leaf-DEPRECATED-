<?php
class StudentsCtrl extends BaseCtrl {

  public function Listing() { //"List" is a reserved word!
    $this->list = $this->Model->findAll();
    return $this->view("Students");
  }

  public function Add() {
    $this->record = $this->Model->create();
    return $this->view("Student");
  }

  public function Edit($id) {
    $this->record = $this->Model->findById($id);
    return $this->view("Student");
  }

  public function Save() {
    $this->Model->save($this->record);
    return $this->redirect("Students/Listing");
  }

  public function Delete($id) {
    $this->Model->delete($id);
    return $this->redirect("Students/Listing");
  }


  /** @var StudentsModel $Model */
  protected $Model;
}