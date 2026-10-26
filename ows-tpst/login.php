<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'config/database.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Password mentah dari input
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // --- Perbandingan langsung (plain text) ---
        if ($password == $user['password']) { 
        
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            // ===== TAMBAHAN: Simpan grup ke session =====
            $_SESSION['grup'] = $user['grup'];
            // ============================================
            header('Location: index.php');
            exit();
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OWS TPST Bantargebang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
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
        
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px auto;
            display: block;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #1a1a2e;
            padding: 5px;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .login-card h3 {
            color: #1a1a2e;
            font-weight: 700;
            font-size: 22px;
            margin-top: 10px;
        }
        
        .login-card p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 12px;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #16213e, #0f121f);
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: #1a1a2e;
            box-shadow: 0 0 0 3px rgba(26,26,46,0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
        }
        
        .alert {
            border-radius: 12px;
        }
        
        hr {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center">
            <img src="assets/logoupst.jpeg" alt="Logo TPST" class="login-logo" 
                 onerror="this.src='https://via.placeholder.com/80?text=TPST'">
            <h3>TPST Bantargebang</h3>
            <p>Operational Weighbridge System</p>
        </div>
        
        <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-login btn-primary w-100">Login</button>
        </form>
        
        <div class="text-center text-muted small mt-4">
            <p class="mb-0">Hubungi Administrator untuk mendapatkan akun</p>
        </div>
    </div>
</body>
</html>