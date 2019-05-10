<base href='<?=Config::Path?>'>

<style>
input[type='text'], input[type='password'] {
  min-width: 340px;
}
</style>

<div id='page' name='User'></div>
<h2> User <br><small><?=$this->record->Name?></small></h2>

<form action='Users/Save' method='POST'>
  <input type='hidden' name='Id' value='<?=$this->record->Id?>'>

  <label for='Descricao'>Name*</label><br>
  <input type='text' name='Name' value='<?=$this->record->Name?>' required autofocus>
  <br><br>

  <label for='Descricao'>Email</label><br>
  <input type='text' name='Email' value='<?=$this->record->Email?>'>
  <br><br>

  <h4>Password will be requested on first access.</h4>

  <br><br>
  <input type='submit' value='Save'> &emsp; <button type='reset' onclick='window.history.back();'>Cancel</button>
</form>
