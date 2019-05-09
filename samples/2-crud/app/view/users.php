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


<div id='page' name='Users'></div>
<br>
<h2 style='text-align:center'> Users </h2>

<table>
  <tr>
    <th width='20%'><a href='Users/Add'>New</a></th>
    <th width='40%'>Name</th>
    <th width='40%'>Email</th>
  </tr>
<?php foreach ($this->list as $rec): ?>
  <tr>
    <td><a href='Users/Edit/<?=$rec->Id?>'>Edit</a> &nbsp; <a href='Users/Delete/<?=$rec->Id?>'>Delete</a></th>
    <td><?=$rec->Name?></th>
    <td><?=$rec->Email?></td>
  </tr>
<?php endforeach; ?>

</table>
<br><br>