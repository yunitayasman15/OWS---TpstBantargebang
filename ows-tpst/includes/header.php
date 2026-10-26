<?php
// File ini digunakan untuk semua halaman agar konsisten
if (!isset($_SESSION)) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'OWS TPST Bantargebang'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* ========== TRANSISI GLOBAL ========== */
        body {
            background: #a2b7c1;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background 0.3s ease;
        }
        
        /* Animasi fade in untuk semua halaman */
        .main-content {
            animation: fadeInPage 0.5s ease-out;
        }
        
        @keyframes fadeInPage {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Animasi untuk card */
        .card {
            transition: all 0.3s ease;
            animation: fadeInUp 0.4s ease-out;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Animasi untuk tombol */
        .btn, .btn-edit, .btn-delete, .btn-primary-custom, .btn-export {
            transition: all 0.3s ease !important;
        }
        
        .btn:hover, .btn-edit:hover, .btn-delete:hover, .btn-primary-custom:hover, .btn-export:hover {
            transform: translateY(-2px);
        }
        
        /* Animasi untuk tabel */
        .data-table tbody tr {
            transition: all 0.2s ease;
            animation: fadeInRow 0.3s ease-out;
            animation-fill-mode: both;
        }
        
        .data-table tbody tr:nth-child(1) { animation-delay: 0.05s; }
        .data-table tbody tr:nth-child(2) { animation-delay: 0.1s; }
        .data-table tbody tr:nth-child(3) { animation-delay: 0.15s; }
        .data-table tbody tr:nth-child(4) { animation-delay: 0.2s; }
        .data-table tbody tr:nth-child(5) { animation-delay: 0.25s; }
        .data-table tbody tr:nth-child(6) { animation-delay: 0.3s; }
        .data-table tbody tr:nth-child(7) { animation-delay: 0.35s; }
        .data-table tbody tr:nth-child(8) { animation-delay: 0.4s; }
        .data-table tbody tr:nth-child(9) { animation-delay: 0.45s; }
        .data-table tbody tr:nth-child(10) { animation-delay: 0.5s; }
        
        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .data-table tbody tr:hover {
            background: #f0f8ff;
            transform: scale(1.01);
        }
        
        /* Animasi untuk form input */
        .form-control, .form-select {
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            transform: scale(1.01);
        }
        
        /* Animasi untuk modal */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            transform: scale(0.9);
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        
        /* Animasi untuk alert */
        .alert {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        /* Sidebar collapsed state */
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar.collapsed .sidebar-header h4,
        .sidebar.collapsed .sidebar-header p,
        .sidebar.collapsed .user-info span:not(.badge),
        .sidebar.collapsed .nav-link span {
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.4rem;
        }
        
        .sidebar.collapsed .user-info {
            text-align: center;
        }
        
        .sidebar.collapsed .user-info .badge {
            float: none !important;
            display: inline-block;
            margin-top: 8px;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 80px;
        }
        
        /* Efek ripple pada menu */
        .nav-link {
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .nav-link:active::after {
            width: 200px;
            height: 200px;
        }
        
        .sidebar-header {
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        /* Style Logo */
        .sidebar-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px auto;
            display: block;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            padding: 3px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-logo {
            width: 45px;
            height: 45px;
            margin-bottom: 10px;
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .user-info {
            padding: 16px 20px;
            background: rgba(255,255,255,0.08);
            margin: 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .user-info .badge {
            background: #28a745;
            font-size: 10px;
            padding: 4px 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link {
            color: #e0e0e0;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            margin: 4px 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: #0d6efd;
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link i {
            width: 24px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 12px 20px;
            transition: all 0.3s ease;
        }
        
        .nav-section {
            padding: 10px 20px;
            font-size: 11px;
            color: #8898aa;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: 280px;
            padding: 24px 32px;
            min-height: 100vh;
            transition: all 0.3s ease-in-out;
        }
        
        .top-bar {
            margin-bottom: 28px;
            animation: fadeInDown 0.5s ease;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .top-bar h2 {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }
        
        .top-bar p {
            color: #6c757d;
            font-size: 14px;
            margin: 4px 0 0;
        }
        
        .menu-toggle {
            display: none;
            background: #1a1a2e;
            border: none;
            font-size: 24px;
            cursor: pointer;
            margin-right: 16px;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: #0d6efd;
            transform: scale(1.05);
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
            background: white;
        }
        
        .card-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border-bottom: none;
            padding: 16px 24px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .card-header i {
            margin-right: 8px;
            color: white;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f8fafc;
            padding: 14px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a2e;
            border-bottom: 2px solid #eef2f6;
            text-align: left;
        }
        
        .data-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: middle;
            background: white;
        }
        
        /* Button Styles */
        .btn-edit {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            color: #333;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #16213e 0%, #0f121f 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Badge */
        .badge-pintu {
            background: #e8f4fd;
            color: #0d6efd;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .badge-pintu:hover {
            background: #0d6efd;
            color: white;
            transform: scale(1.05);
        }
        
        /* Alert */
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 10px 14px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1a1a2e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26,26,46,0.1);
            transform: scale(1.01);
        }
        
        /* Pagination */
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        
        .pagination .page-link {
            color: #1a1a2e;
            border-radius: 8px;
            margin: 0 3px;
            transition: all 0.3s ease;
        }
        
        .pagination .page-link:hover {
            transform: translateY(-2px);
            background: #1a1a2e;
            color: white;
        }
        
        .pagination .active .page-link {
            background: #1a1a2e;
            border-color: #1a1a2e;
            color: white;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: #e8f4fd;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            background: #0d6efd;
        }
        
        .stat-card:hover .stat-icon i {
            color: white;
        }
        
        .stat-icon i {
            font-size: 24px;
            color: #0d6efd;
            transition: all 0.3s ease;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #6c757d;
            letter-spacing: 0.3px;
        }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
            animation-delay: 0.4s;
            animation-fill-mode: both;
        }
        
        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .chart-header {
            padding: 18px 24px;
            border-bottom: 1px solid #eef2f6;
        }
        
        .chart-header h5 {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }
        
        .chart-body {
            padding: 20px;
        }
        
        canvas {
            max-height: 280px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        /* Wilayah List */
        .wilayah-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .wilayah-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eef2f6;
            transition: all 0.3s ease;
        }
        
        .wilayah-item:hover {
            background: #f8f9fa;
            padding-left: 10px;
        }
        
        .wilayah-name {
            font-size: 14px;
            color: #5a6e8a;
        }
        
        .wilayah-count {
            background: #e8f4fd;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #0d6efd;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .wilayah-count:hover {
            background: #0d6efd;
            color: white;
            transform: scale(1.05);
        }
        
        .pintu-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #eef2f6;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .legend-item:hover {
            background: #e9ecef;
            transform: scale(1.02);
        }
        
        .legend-item.hidden {
            opacity: 0.4;
            text-decoration: line-through;
        }
        
        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                left: -280px;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
            .stat-value {
                font-size: 24px;
            }
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
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
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
        <a href="input.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'input.php' ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle"></i> <span>Input OWS</span>
        </a>
        <a href="data.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'data.php' ? 'active' : ''; ?>">
            <i class="bi bi-table"></i> <span>Data OWS</span>
        </a>
        
        <?php if($_SESSION['role'] == 'admin'): ?>
        <div class="nav-divider"></div>
        <div class="nav-section">ADMINISTRATOR</div>
        <a href="kelola_user.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_user.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> <span>Kelola User</span>
        </a>
        <a href="history_log.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'history_log.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> <span>History Log</span>
        </a>
        <a href="export_excel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'export_excel.php' ? 'active' : ''; ?>">
            <i class="bi bi-download"></i> <span>Export Excel</span>
        </a>
        <a href="import_kendaraan.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'import_kendaraan.php' ? 'active' : ''; ?>">
            <i class="bi bi-truck"></i> <span>Import Kendaraan</span>
        </a>
        <!-- MENU IMPORT OWS -->
        <a href="import_ows.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'import_ows.php' ? 'active' : ''; ?>">
            <i class="bi bi-archive"></i> <span>Import OWS</span>
        </a>
        <?php endif; ?>
        
        <div class="nav-divider"></div>
        <a href="logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> <span>Keluar</span>
        </a>
    </nav>
</div>

<!-- MAIN CONTENT -->
<div class="main-content" id="mainContent">
    <button class="menu-toggle" id="menuToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <div class="top-bar">
        <h2><?php echo $page_heading ?? 'Dashboard'; ?></h2>
        <p><?php echo $page_subheading ?? ''; ?></p>
    </div>