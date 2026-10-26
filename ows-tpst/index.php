<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

// Total data
$total = $conn->query("SELECT COUNT(*) as total FROM ows_data")->fetch_assoc()['total'];
$totalTonase = $conn->query("SELECT SUM(tonase) as total FROM ows_data")->fetch_assoc()['total'] ?? 0;
$rataTonase = $total > 0 ? $totalTonase / $total : 0;

/**
 * Fungsi untuk mengekstrak kode pintu dari NOMOR PINTU SAJA
 * 
 * ATURAN:
 * - UPK-BA / UPK di mana pun posisinya -> Badan Air
 * - PP di AKHIR string -> Kepulauan Seribu
 * - H / HH di AKHIR -> Dinas Kehutanan
 * - Kode wilayah S, U, B, T, P di mana pun posisinya (biasanya di akhir)
 *   Tanda titik (.) di akhir string diabaikan
 * - Selain format di atas -> B2B (Mobil Swasta)
 * 
 * Contoh yang VALID:
 * - "0997 S"      -> S   (Jakarta Selatan)
 * - "0649 S."     -> S   (Jakarta Selatan) - abaikan titik
 * - "0447 S."     -> S   (Jakarta Selatan) - abaikan titik
 * - "0225 U"      -> U   (Jakarta Utara)
 * - "0225 U."     -> U   (Jakarta Utara) - abaikan titik
 * - "0012 B"      -> B   (Jakarta Barat)
 * - "0012 T"      -> T   (Jakarta Timur)
 * - "0012 P"      -> P   (Jakarta Pusat)
 * - "0012 H"      -> HH  (Dinas Kehutanan)
 * - "0012 HH"     -> HH  (Dinas Kehutanan)
 * - "0012 PP"     -> PP  (Kepulauan Seribu)
 * - "UPK-BA 004"  -> UPK (Badan Air)
 * - "003 UPK-BA"  -> UPK (Badan Air)
 * 
 * Contoh yang TIDAK VALID (masuk B2B):
 * - "SHI 021"     -> tidak ada kode di akhir
 * - "09 OSI"      -> tidak ada kode di akhir
 * - "AMP 06"      -> tidak ada kode
 * - "BTJ 032"     -> tidak ada kode
 * - "AKU 001"     -> tidak ada kode
 */
function getPintuCode($nomor_pintu) {
    $nomor = strtoupper(trim($nomor_pintu));
    
    if (empty($nomor)) return 'B2B';
    
    // ===== PRIORITAS 1: Cek UPK (Badan Air) di mana pun posisinya =====
    if (strpos($nomor, 'UPK') !== false) return 'UPK';
    
    // ===== PRIORITAS 2: Bersihkan tanda titik di akhir =====
    $nomor_bersih = rtrim($nomor, '.');
    
    // ===== PRIORITAS 3: Cek PP (Kepulauan Seribu) di AKHIR =====
    // Format: "0012 PP"
    if (preg_match('/\sPP$/i', $nomor_bersih)) return 'PP';
    
    // ===== PRIORITAS 4: Cek H atau HH (Dinas Kehutanan) di AKHIR =====
    if (preg_match('/\sH{1,2}$/i', $nomor_bersih)) return 'HH';
    
    // ===== PRIORITAS 5: Cek kode S, U, B, T, P di AKHIR =====
    if (preg_match('/\s([SUTBP])$/i', $nomor_bersih, $matches)) {
        return strtoupper($matches[1]);
    }
    
    // ===== PRIORITAS 6: Cek kode di AWAL string =====
    if (preg_match('/^([SUTBP])\s/i', $nomor_bersih, $matches)) {
        return strtoupper($matches[1]);
    }
    
    // ===== FALLBACK: B2B (Mobil Swasta) =====
    return 'B2B';
}

// Mapping kode pintu ke wilayah
$pintuWilayah = [
    'S' => 'Jakarta Selatan',
    'U' => 'Jakarta Utara',
    'B' => 'Jakarta Barat',
    'T' => 'Jakarta Timur',
    'P' => 'Jakarta Pusat',
    'HH' => 'Dinas Kehutanan',
    'PP' => 'Kepulauan Seribu',
    'UPK' => 'Badan Air',
    'B2B' => 'Mobil Swasta / Kawasan Mandiri'
];

// Ambil semua data lengkap untuk detail
$allDataFull = $conn->query("SELECT * FROM ows_data ORDER BY tanggal_jam DESC");

// Inisialisasi counter dan detail per wilayah
$pintuCount = ['S' => 0, 'U' => 0, 'B' => 0, 'T' => 0, 'P' => 0, 'HH' => 0, 'PP' => 0, 'UPK' => 0, 'B2B' => 0];
$wilayahCount = [
    'Jakarta Selatan' => 0, 'Jakarta Utara' => 0, 'Jakarta Barat' => 0,
    'Jakarta Timur' => 0, 'Jakarta Pusat' => 0, 'Dinas Kehutanan' => 0,
    'Kepulauan Seribu' => 0, 'Badan Air' => 0, 'Mobil Swasta / Kawasan Mandiri' => 0
];

// Simpan detail data per wilayah
$wilayahDetails = [
    'Jakarta Selatan' => [], 'Jakarta Utara' => [], 'Jakarta Barat' => [],
    'Jakarta Timur' => [], 'Jakarta Pusat' => [], 'Dinas Kehutanan' => [],
    'Kepulauan Seribu' => [], 'Badan Air' => [], 'Mobil Swasta / Kawasan Mandiri' => []
];

while($row = $allDataFull->fetch_assoc()) {
    $kode = getPintuCode($row['nomor_pintu']);
    $wilayah = $pintuWilayah[$kode];
    $pintuCount[$kode]++;
    $wilayahCount[$wilayah]++;
    $wilayahDetails[$wilayah][] = $row;
}

// Data chart
$chartLabels = ['S', 'U', 'B', 'T', 'P', 'PP', 'H/HH/UPK', 'B2B'];
$chartData = [
    $pintuCount['S'], $pintuCount['U'], $pintuCount['B'],
    $pintuCount['T'], $pintuCount['P'],
    $pintuCount['PP'],
    $pintuCount['HH'] + $pintuCount['UPK'],
    $pintuCount['B2B']
];

// ========== KONFIGURASI WARNA ==========
$warnaS = '#003b30';
$warnaU = '#1f6748';
$warnaB = '#0c8614';
$warnaT = '#669005';
$warnaP = '#3c8e2a';
$warnaPP = '#2c5f8a';  // Warna baru untuk Kepulauan Seribu
$warnaInstansi = '#3f7d4c';
$warnaB2B = '#3a2412';

$chartColors = [$warnaS, $warnaU, $warnaB, $warnaT, $warnaP, $warnaPP, $warnaInstansi, $warnaB2B];
$legendNames = ['S (Jaksel)', 'U (Jakut)', 'B (Jakbar)', 'T (Jaktim)', 'P (Jakpus)', 'PP (Kep. Seribu)', 'H/HH/UPK (Instansi)', 'B2B (Swasta)'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OWS TPST Bantargebang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #a2b7c1;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .main-content {
            margin-left: 280px;
            padding: 24px 32px;
            min-height: 100vh;
            transition: all 0.3s ease-in-out;
        }
        
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
        
        .sidebar-header {
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
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
            animation: fadeInUp 0.5s ease;
            animation-fill-mode: both;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        
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
            animation: fadeInUp 0.5s ease;
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
        
        .chart-header p {
            font-size: 12px;
            color: #6c757d;
            margin: 4px 0 0;
        }
        
        .chart-body {
            padding: 20px;
        }
        
        canvas {
            max-height: 280px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
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
        
        .wilayah-item:last-child {
            border-bottom: none;
        }
        
        .wilayah-name {
            font-size: 14px;
            color: #5a6e8a;
            transition: all 0.3s ease;
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
        
        .modal-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .modal-table th {
            background: #f8fafc;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a2e;
            border-bottom: 2px solid #eef2f6;
            text-align: left;
        }
        
        .modal-table td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #eef2f6;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-bar .form-group {
            flex: 1;
            min-width: 150px;
            margin-bottom: 0;
        }
        
        .filter-bar label {
            font-size: 12px;
            font-weight: 600;
            color: #555;
        }
        
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: scale(0.9);
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
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
            .filter-bar {
                flex-direction: column;
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
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="input.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'input.php' ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle"></i> Input OWS
        </a>
        <a href="data.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'data.php' ? 'active' : ''; ?>">
            <i class="bi bi-table"></i> Data OWS
        </a>
        
        <?php if($_SESSION['role'] == 'admin'): ?>
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


<!-- MAIN CONTENT -->
<div class="main-content" id="mainContent">
    <button class="menu-toggle" id="menuToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <div class="top-bar">
        <h2>Ringkasan Operasional</h2>
        <p>Update data otomatis berdasarkan input operator timbangan</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-file-text"></i></div>
            <div class="stat-value"><?php echo $total; ?> Kendaraan</div>
            <div class="stat-label">TOTAL KENDARAAN</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calculator-fill"></i></div>
            <div class="stat-value"><?php echo number_format($totalTonase, 2); ?> Ton</div>
            <div class="stat-label">TOTAL BERAT MASUK</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            <div class="stat-value"><?php echo number_format($rataTonase, 2); ?> Ton</div>
            <div class="stat-label">RATA-RATA BERAT MASUK</div>
        </div>
    </div>
    
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header">
                <h5>Distribusi per Pintu</h5>
            </div>
            <div class="chart-body">
                <canvas id="pintuChart"></canvas>
                <div class="pintu-legend" id="pintuLegend">
                    <?php for($i = 0; $i < count($legendNames); $i++): ?>
                    <div class="legend-item" data-index="<?php echo $i; ?>">
                        <span class="legend-color" style="background: <?php echo $chartColors[$i]; ?>"></span>
                        <span><?php echo $legendNames[$i]; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-header">
                <h5>Jumlah OWS per Wilayah</h5>
                <p>Klik pada angka kendaraan untuk melihat detail</p>
            </div>
            <div class="chart-body">
                <ul class="wilayah-list">
                    <?php foreach($wilayahCount as $wilayah => $jumlah): ?>
                    <li class="wilayah-item">
                        <span class="wilayah-name"><?php echo $wilayah; ?></span>
                        <button class="wilayah-count" onclick="showWilayahDetail('<?php echo addslashes($wilayah); ?>')">
                            <?php echo $jumlah; ?> Kendaraan
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Wilayah -->
<div class="modal fade" id="wilayahModal" tabindex="-1" aria-labelledby="wilayahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white;">
                <h5 class="modal-title" id="wilayahModalLabel">
                    <i class="bi bi-map"></i> Detail Kendaraan - <span id="modalWilayahName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="filter-bar">
                    <div class="form-group">
                        <label><i class="bi bi-search"></i> Cari (Plat/Pintu)</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari nomor plat atau pintu..." onkeyup="filterTable()">
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-calendar-start"></i> Tanggal Dari</label>
                        <input type="date" id="dateFrom" class="form-control" onchange="filterTable()">
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-calendar-end"></i> Tanggal Sampai</label>
                        <input type="date" id="dateTo" class="form-control" onchange="filterTable()">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-secondary w-100" onclick="resetFilters()">
                            <i class="bi bi-arrow-repeat"></i> Reset
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="modal-table" id="detailTable">
                        <thead>
                            <tr>
                                <th>Tanggal & Jam</th>
                                <th>Nomor Pintu</th>
                                <th>Nomor Plat</th>
                                <th>Berat Masuk</th>
                            </tr>
                        </thead>
                        <tbody id="detailTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    const wilayahDetails = <?php echo json_encode($wilayahDetails); ?>;
    let currentWilayah = '';
    let currentData = [];
    
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    menuToggle.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.style.opacity = '0.7';
            setTimeout(() => {
                mainContent.style.opacity = '1';
            }, 300);
        }
    });
    
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartData = <?php echo json_encode($chartData); ?>;
    const chartColors = <?php echo json_encode($chartColors); ?>;
    
    const ctx = document.getElementById('pintuChart').getContext('2d');
    let chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Jumlah Kendaraan',
                data: [...chartData],
                backgroundColor: [...chartColors],
                borderRadius: 8,
                barPercentage: 0.65
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1a2e',
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.raw + ' Kendaraan';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#eef2f6', drawBorder: false },
                    ticks: { stepSize: 1, precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
    
    const legendItems = document.querySelectorAll('.legend-item');
    const hiddenStatus = new Array(chartData.length).fill(false);
    
    legendItems.forEach((item, index) => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            
            hiddenStatus[index] = !hiddenStatus[index];
            
            const newData = chartData.map((originalValue, i) => {
                return hiddenStatus[i] ? 0 : originalValue;
            });
            
            chart.data.datasets[0].data = newData;
            chart.update();
            
            if (hiddenStatus[index]) {
                item.classList.add('hidden');
            } else {
                item.classList.remove('hidden');
            }
        });
    });
    
    function showWilayahDetail(wilayah) {
        currentWilayah = wilayah;
        currentData = wilayahDetails[wilayah] || [];
        
        document.getElementById('modalWilayahName').innerText = wilayah;
        document.getElementById('searchInput').value = '';
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        
        renderTable(currentData);
        
        const modal = new bootstrap.Modal(document.getElementById('wilayahModal'));
        modal.show();
    }
    
    function renderTable(data) {
        const tbody = document.getElementById('detailTableBody');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data</div>';
            return;
        }
        
        let html = '';
        data.forEach(row => {
            html += `
                <tr>
                    <td>${formatDate(row.tanggal_jam)}</div>
                    <td>${escapeHtml(row.nomor_pintu)}</div>
                    <td>${escapeHtml(row.nomor_plat)}</div>
                    <td>${parseFloat(row.tonase).toLocaleString('id-ID')} ton</div>
                `;
        });
        tbody.innerHTML = html;
    }
    
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        
        let filtered = [...currentData];
        
        if (search) {
            filtered = filtered.filter(row => 
                row.nomor_plat.toLowerCase().includes(search) || 
                row.nomor_pintu.toLowerCase().includes(search)
            );
        }
        
        if (dateFrom) {
            filtered = filtered.filter(row => row.tanggal_jam.split(' ')[0] >= dateFrom);
        }
        
        if (dateTo) {
            filtered = filtered.filter(row => row.tanggal_jam.split(' ')[0] <= dateTo);
        }
        
        renderTable(filtered);
    }
    
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        renderTable(currentData);
    }
    
    function formatDate(datetime) {
        const d = new Date(datetime);
        return d.toLocaleDateString('id-ID') + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    document.addEventListener('click', function(e) {
        if(window.innerWidth <= 992 && sidebar.classList.contains('show')) {
            if(!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    window.addEventListener('load', function() {
        document.querySelectorAll('.stat-card, .chart-card').forEach((el, index) => {
            el.style.animationDelay = (index * 0.1) + 's';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>