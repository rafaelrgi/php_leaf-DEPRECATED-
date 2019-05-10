<?php
class HomeCtrl extends BaseCtrl {

  public function index() {
    $this->Redirect("Students/Listing");
  }

}