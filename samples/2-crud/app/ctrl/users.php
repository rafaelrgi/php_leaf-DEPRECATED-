<?php
class UsersCtrl extends BaseCtrl {

  public function Listing() { //"List" is a reserved word!
    $this->list = $this->Model->findAll();
    return $this->view("Users");
  }

  public function Add() {
    $this->record = $this->Model->create();
    return $this->view("User");
  }

  public function Edit($id) {
    $this->record = $this->Model->findById($id);
    return $this->view("User");
  }

  public function Save() {
    $this->Model->save($this->record);
    return $this->redirect("Users/Listing");
  }

  public function Delete($id) {
    $this->Model->delete($id);
    return $this->redirect("Users/Listing");
  }


  /** @var UsersModel $Model */
  protected $Model;
}