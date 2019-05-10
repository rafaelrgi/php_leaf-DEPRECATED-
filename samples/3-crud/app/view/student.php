<base href='<?=Config::Path?>'>

<style>
input[type='text'], input[type='password'] {
  min-width: 340px;
}
</style>

<div id='page' name='Student'></div>
<h2> Student <br><small><?=$this->record->Name?></small></h2>

<form action='Students/Save' method='POST'>
  <input type='hidden' name='StudentId' value='<?=$this->record->StudentId?>'>
  <input type='hidden' name='ContactInfoId' value='<?=$this->record->ContactInfoId?>'>

  <label for='Name'>Name*</label><br>
  <input type='text' name='Name' value='<?=$this->record->Name?>' required autofocus>
  <br><br>

  <label for='Email'>E-mail</label><br>
  <input type='text' name='Email' value='<?=$this->record->Email?>'>
  <br><br>

  <label for='Phone'>Phone</label><br>
  <input type='text' name='Phone' value='<?=$this->record->Phone?>'>
  <br><br>


  <br><br>
  <input type='submit' value='Save'> &emsp; <button type='reset' onclick='window.history.back();'>Cancel</button>
</form>
