<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tambah User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">

  <div class="container">
    <h3 class="mb-4">Form Tambah User</h3>
    <form action="/api/register" method="POST">
      <div class="mb-3">
        <label for="user_name" class="form-label">Username</label>
        <input type="text" name="user_name" id="user_name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="user_pass" class="form-label">Password</label>
        <input type="password" name="user_pass" id="user_pass" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="user_email" class="form-label">Email</label>
        <input type="email" name="user_email" id="user_email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="user_phone" class="form-label">No HP</label>
        <input type="text" name="user_phone" id="user_phone" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="role_id" class="form-label">Role ID</label>
        <select name="role_id" id="role_id" class="form-select">
          <option value="1">Admin</option>
          <option value="2">User</option>
          <!-- tambahkan opsi lain sesuai kebutuhan -->
        </select>
      </div>

      <input type="hidden" name="user_sts" value="1">

      <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
  </div>

</body>
</html>
