<h2>Admin Dashboard</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
</tr>

<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= sanitize($u['username']) ?></td>
    <td><?= sanitize($u['email']) ?></td>
    <td><?= sanitize($u['role']) ?></td>
</tr>
<?php endforeach; ?>

</table>
