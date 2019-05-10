<base href='<?=Config::Path?>'>

<style>
table, th, td {
  border: 1px solid #ddd;
}
table {
  border-collapse: collapse;
}
th, td {
  padding: 4px 8px;
}
</style>


<div id='page' name='Students'></div>
<br>
<h2 style='text-align:center'> Students </h2>

<table>
  <tr>
    <th width='20%'><a href='Students/Add'>New</a></th>
    <th width='35%'>Name</th>
    <th width='25%'>E-mail</th>
    <th width='20%'>Phone</th>
  </tr>
<?php foreach ($this->list as $rec): ?>
  <tr>
    <td><a href='Students/Edit/<?=$rec->StudentId?>'>Edit</a> &nbsp; <a href='Students/Delete/<?=$rec->StudentId?>'>Delete</a></th>
    <td><?=$rec->Name?></th>
    <td><?=$rec->Email?></td>
    <td><?=$rec->Phone?></td>
  </tr>
<?php endforeach; ?>

</table>
<br><br>