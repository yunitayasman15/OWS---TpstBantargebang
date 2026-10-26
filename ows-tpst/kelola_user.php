<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$page_title = 'Kelola User - TPST Bantargebang';
$page_heading = 'Kelola User';
$page_subheading = 'Manajemen akun pengguna sistem';

// Tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    $password = $_POST['password']; // Plain text, tanpa hash
    
    // Ambil grup hanya jika role adalah operator
    $grup = ($role == 'operator' && !empty($_POST['grup'])) ? (int)$_POST['grup'] : NULL;
    
    $stmt = $conn->prepare("INSERT INTO users (username, fullname, role, grup, password, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssis", $username, $fullname, $role, $grup, $password);
    if ($stmt->execute()) {
        $success = "User berhasil ditambahkan!";
    } else {
        $error = "Username sudah ada!";
    }
}

// Reset password
if (isset($_GET['reset'])) {
    $id = (int)$_GET['reset'];
    $newPass = '123456'; // Plain text, tanpa hash
    $conn->query("UPDATE users SET password = '$newPass' WHERE id = $id");
    $success = "Password direset menjadi 123456";
}

// Hapus user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $id");
        $success = "User berhasil dihapus!";
    }
    header('Location: kelola_user.php');
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY role, username");

include 'includes/header.php';
?>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Form Tambah User -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-person-plus"></i> Tambah User Baru
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="add">
            <div class="col-md-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="fullname" class="form-control" placeholder="Nama Lengkap" required>
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select" id="roleSelect" required onchange="toggleGrupField()">
                    <option value="operator">Operator</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2" id="grupField">
                <select name="grup" class="form-select" id="grupSelect">
                    <option value="">-- Pilih Grup --</option>
                    <option value="1">Grup 1</option>
                    <option value="2">Grup 2</option>
                    <option value="3">Grup 3</option>
                    <option value="4">Grup 4</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn-primary-custom" style="padding: 10px 30px;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabel User -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-people"></i> Daftar User
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Grup</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['fullname']; ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($user['role'] == 'operator' && !empty($user['grup'])): ?>
                                <span class="badge bg-info">Grup <?php echo $user['grup']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?reset=<?php echo $user['id']; ?>" class="btn-edit" onclick="return confirm('Reset password jadi 123456?')">
                                <i class="bi bi-key"></i> Reset
                            </a>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Hapus user ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Toggle field grup: muncul hanya jika role = operator
    function toggleGrupField() {
        const role = document.getElementById('roleSelect').value;
        const grupField = document.getElementById('grupField');
        const grupSelect = document.getElementById('grupSelect');
        
        if (role === 'operator') {
            grupField.style.display = 'block';
            grupSelect.required = true;
        } else {
            grupField.style.display = 'none';
            grupSelect.required = false;
            grupSelect.value = '';
        }
    }
    
    // Jalankan saat halaman dimuat
    document.addEventListener('DOMContentLoaded', toggleGrupField);
</script>

<?php include 'includes/footer.php'; ?>