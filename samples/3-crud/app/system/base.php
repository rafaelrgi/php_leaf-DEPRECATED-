<?php
class Obj //extends stdClass
{
  /** new Obj($obj_or_array, "Property_1,Property_2") */
  public function __construct($obj_or_array=null, $props_to_copy=null)
  {
    if ($obj_or_array)
    {
      if (is_object($obj_or_array))
        $obj_or_array = get_object_vars($obj_or_array);
      if ($props_to_copy && ! is_array($props_to_copy))
        $props_to_copy = array_map("trim", explode(',', $props_to_copy));
      foreach ($obj_or_array as $key => $val)
      {
        if ($props_to_copy && ! is_string($props_to_copy) && ! in_array($key, $props_to_copy))
          continue;
        $this->$key = $val;
      }
    }
  }

  public function __get($key)
  {
    return null; //get($this->data, $key);
  }

  //public function __set($key, $value) { $this->data[$key] = $value; }
  //private $data = array();
}