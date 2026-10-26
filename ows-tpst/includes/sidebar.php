<?php
// File ini digunakan untuk sidebar di semua halaman
// Pastikan session sudah dimulai di file yang memanggil sidebar
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="assets/logoupst.jpeg" alt="Logo TPST" class="sidebar-logo" 
             onerror="this.src='https://via.placeholder.com/80?text=TPST'">
        <h4>TPST Bantargebang</h4>
    </div>
    
    <div class="user-info">
        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['fullname']; ?>
        <span class="badge float-end"><?php echo $_SESSION['role'] == 'admin' ? 'Admin' : 'Operator'; ?></span>
    </div>
    
    <nav>
        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="input.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'input.php' ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle"></i> Input OWS
        </a>
        <a href="data.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'data.php' ? 'active' : ''; ?>">
            <i class="bi bi-table"></i> Data OWS
        </a>
        
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <div class="nav-divider"></div>
        <div class="nav-section">ADMINISTRATOR</div>
        <a href="kelola_user.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_user.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Kelola User
        </a>
        <a href="history_log.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'history_log.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> History Log
        </a>
        <a href="export_excel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'export_excel.php' ? 'active' : ''; ?>">
            <i class="bi bi-download"></i> Export Excel
        </a>
        <a href="import_kendaraan.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'import_kendaraan.php' ? 'active' : ''; ?>">
            <i class="bi bi-truck"></i> Import Kendaraan
        </a>
        <!-- MENU IMPORT OWS -->
        <a href="import_ows.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'import_ows.php' ? 'active' : ''; ?>">
            <i class="bi bi-archive"></i> Import OWS
        </a>
        <?php endif; ?>
        
        <div class="nav-divider"></div>
        <a href="logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </nav>
</div>