<?php
session_start();
include 'config/database.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // Ambil role dari form
    
    // Validasi
    if (empty($username) || empty($fullname) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } 
    elseif ($password !== $confirm_password) {
        $error = 'Password dan Konfirmasi Password tidak sama!';
    }
    elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter!';
    }
    else {
        // Cek apakah username sudah ada
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = 'Username sudah digunakan! Silakan pilih username lain.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru dengan role yang dipilih
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, role, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $username, $hashed_password, $fullname, $role);
            
            if ($stmt->execute()) {
                $role_text = ($role == 'admin') ? 'Administrator' : 'Operator';
                $success = "Akun berhasil dibuat! Role: $role_text. Silakan login.";
                // Clear form
                $username = $fullname = '';
            } else {
                $error = 'Gagal membuat akun: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - OWS TPST Bantargebang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50, #1a1a2e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .register-card h3 {
            color: #2c3e50;
            font-weight: 600;
        }
        .btn-register {
            background: #2c3e50;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            background: #1a252f;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .input-icon input, .input-icon select {
            padding-left: 40px;
        }
        .password-strength {
            height: 4px;
            margin-top: 5px;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .role-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .role-card:hover {
            transform: translateY(-2px);
        }
        .role-card.selected {
            border-color: #2c3e50;
            background: #e8f4f8;
        }
        .role-card input {
            display: none;
        }
        .role-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .role-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .role-desc {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-person-plus" style="font-size: 48px; color: #2c3e50;"></i>
            </div>
            <h3>🏭 OWS TPST Bantargebang</h3>
            <p class="text-muted">Buat Akun Baru</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-person"></i> Username
                </label>
                <div class="input-icon">
                    <i class="bi bi-person-circle"></i>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Masukkan username" 
                           value="<?= isset($username) ? htmlspecialchars($username) : '' ?>"
                           required autofocus>
                </div>
                <small class="text-muted">Username akan digunakan untuk login</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-card-text"></i> Nama Lengkap
                </label>
                <div class="input-icon">
                    <i class="bi bi-person-badge"></i>
                    <input type="text" name="fullname" class="form-control" 
                           placeholder="Masukkan nama lengkap"
                           value="<?= isset($fullname) ? htmlspecialchars($fullname) : '' ?>"
                           required>
                </div>
            </div>
            
            <!-- Pilihan Role -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-shield-lock"></i> Pilih Role / Hak Akses
                </label>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="role-card text-center" id="roleOperatorCard" onclick="selectRole('operator')">
                            <div class="role-icon">
                                <i class="bi bi-person-badge" style="color: #0d6efd;"></i>
                            </div>
                            <div class="role-title">Operator</div>
                            <div class="role-desc">
                                <i class="bi bi-check-circle-fill text-success small"></i> Input & Edit data<br>
                                <i class="bi bi-x-circle-fill text-danger small"></i> Tidak bisa hapus<br>
                                <i class="bi bi-x-circle-fill text-danger small"></i> Tidak bisa export
                            </div>
                            <input type="radio" name="role" value="operator" id="roleOperator" checked>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="role-card text-center" id="roleAdminCard" onclick="selectRole('admin')">
                            <div class="role-icon">
                                <i class="bi bi-shield-fill-check" style="color: #dc3545;"></i>
                            </div>
                            <div class="role-title">Administrator</div>
                            <div class="role-desc">
                                <i class="bi bi-check-circle-fill text-success small"></i> Semua akses<br>
                                <i class="bi bi-check-circle-fill text-success small"></i> Kelola user<br>
                                <i class="bi bi-check-circle-fill text-success small"></i> Export data<br>
                                <i class="bi bi-check-circle-fill text-success small"></i> Hapus data
                            </div>
                            <input type="radio" name="role" value="admin" id="roleAdmin">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-key"></i> Password
                </label>
                <div class="input-icon">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Minimal 4 karakter" required>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <small class="text-muted">Minimal 4 karakter</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    <i class="bi bi-key-fill"></i> Konfirmasi Password
                </label>
                <div class="input-icon">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           placeholder="Ulangi password" required>
                </div>
                <div id="passwordMatch" class="small mt-1"></div>
            </div>
            
            <button type="submit" class="btn btn-register btn-primary w-100" id="submitBtn">
                <i class="bi bi-person-plus"></i> Daftar Akun
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="text-center">
            <p class="mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none">Login di sini</a></p>
        </div>
        
        <div class="text-center text-muted small mt-3">
            <p class="mb-0">* Setelah registrasi, akun akan langsung aktif</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pilihan role
        function selectRole(role) {
            const operatorCard = document.getElementById('roleOperatorCard');
            const adminCard = document.getElementById('roleAdminCard');
            const operatorRadio = document.getElementById('roleOperator');
            const adminRadio = document.getElementById('roleAdmin');
            
            if (role === 'operator') {
                operatorCard.classList.add('selected');
                adminCard.classList.remove('selected');
                operatorRadio.checked = true;
            } else {
                adminCard.classList.add('selected');
                operatorCard.classList.remove('selected');
                adminRadio.checked = true;
            }
        }
        
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthDiv = document.getElementById('passwordStrength');
        const matchDiv = document.getElementById('passwordMatch');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let color = '';
            
            if (password.length >= 4) strength = 25;
            if (password.length >= 6) strength = 50;
            if (password.length >= 8 && /[A-Z]/.test(password)) strength = 75;
            if (password.length >= 10 && /[A-Z]/.test(password) && /[0-9]/.test(password)) strength = 100;
            
            if (strength <= 25) color = '#dc3545';
            else if (strength <= 50) color = '#ffc107';
            else if (strength <= 75) color = '#17a2b8';
            else color = '#28a745';
            
            strengthDiv.style.width = strength + '%';
            strengthDiv.style.backgroundColor = color;
            
            checkPasswordMatch();
        });
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchDiv.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Password cocok!';
                    matchDiv.style.color = '#28a745';
                } else {
                    matchDiv.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i> Password tidak cocok!';
                    matchDiv.style.color = '#dc3545';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }
        
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        // Set default role operator terpilih
        document.getElementById('roleOperatorCard').classList.add('selected');
    </script>
</body>
</html>