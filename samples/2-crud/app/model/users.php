<?php
class UsersModel extends Model {

  public function __construct() {
    $this->tbl_or_select = "User"; //just the table name; could be a complex select too
    //we won't need these: $insert=null, $update=null, $delete=null
  }

}