<?php
class HomeCtrl extends BaseCtrl {

  public function index() {
    $this->Info = $this->Model->getInfo();
    return $this->View("home");
  }

  /** @var HomeModel $Model */
  protected $Model;
}