<?php
if (! defined('_View_')) {

  define('_View_', true);

  function BtnOkCancel($okTxt=null, $okTitle=null, $cancelTxt=null, $cancelTitle=null, $cancelLink=null) {
    $okTxt = ($okTxt? $okTxt : 'Salvar');
    $okTitle = ($okTitle? $okTitle : 'Salvar alterações');
    $cancelTxt = ($cancelTxt? $cancelTxt : 'Cancelar');
    $cancelTitle = ($cancelTitle? $cancelTitle : 'Cancelar');
    $cancelLink = ($cancelLink? $cancelLink : ":back"); //App::getUrl()

    return <<< END
<br>
<center>
  <button type='submit' class='btn btn-primary ok' title='$okTitle'><i class='fa fa-check'></i> &nbsp; $okTxt</button>
  <div class='btn-spacer'></div>
  <a href='$cancelLink' class='btn btn-default confirma cancel' title='$cancelTitle'><i class='fa fa-remove'></i> &nbsp; $cancelTxt</a>
</center>
<br><br>
END;
  }

  function CheckBox($obj, $property, $label=null, $enabled=true, $val=null, $block=true, $collapseIn=null) {
    if ($val === null)
      $val = $obj->$property;
    $val        = ($val? 'checked': '');
    $label      = ($label? $label : $property);
    $enabled    = ($enabled? ''   : 'disabled');
    $block      = ($block? 'block': 'inline');
    $collapseIn = ($collapseIn? " collapse-in='#" . str_replace('#', '', $collapseIn) . "' " : "");
    return "<div class='$block checkbox'><input type='checkbox' $val name='$property' id='$property' value='1' $enabled $collapseIn><label for='$property'>$label</label></div>";
  }

  function RadioButton($obj, $property, $val, $label=null, $enabled=true, $block=true, $collapseIn=null) {
    $checked    = ($obj->$property == $val? 'checked': '');
    $label      = ($label? $label  : $property);
    $enabled    = ($enabled? ''    : ' disabled');
    $block      = ($block? "<br>" : "&emsp;&emsp;");
    $collapseIn = ($collapseIn? " collapse-in='#" . str_replace('#', '', $collapseIn) . "' " : "");
    return "<input type='radio' $checked name='$property' id='$property$val' value='$val' $enabled $collapseIn><label for='$property$val'>$label</label>$block";
  }

  ///chave: 1º char da opção (J => Jurídica, F => Física)
  function ComboBoxChar($obj, $property, $options, $allow_empty=false) {
    $keys_values = [];
    if (is_string($options))
      $options = explode(',', $options);
    foreach ($options as $s) {
      $s = trim($s);
      if ($s)
        $keys_values[$s[0]] = $s;
    }
    return ComboBox($obj, $property, $keys_values, $allow_empty);
  }
  ///chave: número (ordem da opção)
  function ComboBoxNum($obj, $property, $options, $allow_empty=false) {
    $i = 0;
    if (is_string($options))
      $options = explode(',', $options);
    return ComboBox($obj, $property, $options, $allow_empty);
  }
  ///ComboBox($this->Reg, "TipoPessoa", array('F' => 'Física', 'J' => 'Jurídica'));
  function ComboBox($obj_or_val, $property, $keys_values, $allow_empty=false, $required=false, $autofocus=false, $readonly=false, $propertyId=false) {
    $allow_empty = ($allow_empty? '<option></option>\r\n': ' ');
    $required    = ($required? 'required'                : '');
    $autofocus   = ($autofocus? 'autofocus'              : '');
    $readonly    = ($readonly? 'disabled'                : '');
    $s = "<select name='$property' id='$property' class='form-control' $required $autofocus $readonly>\r\n$allow_empty";
    foreach ($keys_values as $key => $val) {
      if (is_object($val)) {
        $key = (isset($val->Id)? $val->Id : $key);
        $val = get($val, "Descricao",  get($val, "Nome", get($val, $property, "")));
      }
      if (is_object($obj_or_val))
        $selected = ($obj_or_val && $obj_or_val->$property==$key? 'selected' : '');
      else
        $selected = ($obj_or_val && ($obj_or_val==$val || $obj_or_val==$key)? 'selected' : '');
      $s = $s . "<option value='$key' $selected>$val</option>\r\n";
    }
    $s .= "</select>\r\n";
    return $s;
  }

}
