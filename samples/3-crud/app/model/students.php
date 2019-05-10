<?php
class StudentsModel extends Model {

  public function __construct() {
    $this->tbl_or_select = self::select;
    $this->id_prop = "StudentId";
    //we won't need these: $insert=null, $update=null, $delete=null
  }

  public function save($rec) {
    Db::save($rec, "ContactInfo", "ContactInfoId", "ContactInfoId,Email,Phone");
    Db::save($rec, "Student", "StudentId", "StudentId,ContactInfoId,Name");
  }

  public function delete($id_or_obj) {
    Db::deleteFromSql("ContactInfo", "Select ContactInfoId From Student Where StudentId=" . $this->getId($id_or_obj), "ContactInfoId");
    Db::deleteId($this->getId($id_or_obj), "Student", "StudentId");
  }


const select = <<<END
  Select
    std.*,
    cti.Email, cti.Phone
  From
    Student std
    Join ContactInfo cti on cti.ContactInfoId=std.ContactInfoId
Order by Name
END;

}