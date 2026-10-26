<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$error = '';

// Fungsi untuk menentukan shift berdasarkan jam
function getShift($jam) {
    $hour = (int)date('H', strtotime($jam));
    
    if ($hour >= 0 && $hour < 8) {
        return 1;
    } elseif ($hour >= 8 && $hour < 16) {
        return 2;
    } else {
        return 3;
    }
}

// Fungsi untuk mendapatkan range jam shift
function getShiftRange($shift) {
    $ranges = [
        1 => '00:00 - 08:00',
        2 => '08:00 - 16:00',
        3 => '16:00 - 00:00'
    ];
    return $ranges[$shift];
}

// Fungsi untuk mengekstrak kode pintu dan wilayah
function getPintuCodeExport($nomor_pintu) {
    $nomor = strtoupper(trim($nomor_pintu));
    
    if (preg_match('/\b(UPK)\b/i', $nomor)) return 'UPK';
    if (preg_match('/\b(HH)\b/i', $nomor)) return 'HH';
    if (preg_match('/\b(PP)\b/i', $nomor)) return 'PP';
    
    if (preg_match('/[0-9]+\s*([A-Z])$/', $nomor, $matches)) {
        $huruf = $matches[1];
        if (in_array($huruf, ['S', 'U', 'B', 'T', 'P'])) {
            return $huruf;
        }
    }
    
    if (preg_match('/^([A-Z])\s+[0-9]/', $nomor, $matches)) {
        $huruf = $matches[1];
        if (in_array($huruf, ['S', 'U', 'B', 'T', 'P'])) {
            return $huruf;
        }
    }
    
    return 'B2B';
}

// Mapping kode pintu ke wilayah
$pintuWilayahExport = [
    'S' => 'Jakarta Selatan',
    'U' => 'Jakarta Utara',
    'B' => 'Jakarta Barat',
    'T' => 'Jakarta Timur',
    'P' => 'Jakarta Pusat',
    'PP' => 'Dinas Kehutanan',
    'HH' => 'Kepulauan Seribu',
    'UPK' => 'Badan Air',
    'B2B' => 'Mobil Swasta'
];

$wilayahList = [
    'Jakarta Selatan',
    'Jakarta Utara',
    'Jakarta Barat',
    'Jakarta Timur',
    'Jakarta Pusat',
    'Dinas Kehutanan',
    'Kepulauan Seribu',
    'Badan Air',
    'Mobil Swasta'
];

// Proses filter dan export
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export'])) {
    $tanggal_dari = $_POST['tanggal_dari'];
    $tanggal_sampai = $_POST['tanggal_sampai'];
    $wilayah = $_POST['wilayah'];
    
    if (empty($tanggal_dari) || empty($tanggal_sampai)) {
        $error = "Tanggal awal dan akhir harus diisi!";
    } else {
        $sql = "SELECT * FROM ows_data WHERE DATE(tanggal_jam) BETWEEN '$tanggal_dari' AND '$tanggal_sampai' ORDER BY tanggal_jam ASC";
        
        if ($wilayah != 'semua') {
            $query = $conn->query($sql);
            $filteredData = [];
            while($row = $query->fetch_assoc()) {
                $kode = getPintuCodeExport($row['nomor_pintu']);
                $wilayahData = $pintuWilayahExport[$kode];
                if ($wilayahData == $wilayah) {
                    $filteredData[] = $row;
                }
            }
            $results = $filteredData;
        } else {
            $result = $conn->query($sql);
            $results = [];
            while($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        
        if (count($results) > 0) {
            // Kelompokkan data berdasarkan shift
            $shiftData = [
                1 => [], // Shift 1 (00:00 - 08:00)
                2 => [], // Shift 2 (08:00 - 16:00)
                3 => []  // Shift 3 (16:00 - 00:00)
            ];
            
            foreach($results as $row) {
                $shift = getShift($row['tanggal_jam']);
                $shiftData[$shift][] = $row;
            }
            
            // Hitung rekap per shift
            $rekapShift = [];
            foreach([1,2,3] as $s) {
                $rekapShift[$s] = [
                    'jumlah' => count($shiftData[$s]),
                    'total_tonase' => array_sum(array_column($shiftData[$s], 'tonase'))
                ];
            }
            
            // Hitung rekap per wilayah
            $rekapWilayah = [];
            foreach($wilayahList as $wil) {
                $rekapWilayah[$wil] = [
                    'jumlah' => 0,
                    'total_tonase' => 0
                ];
            }
            
            foreach($results as $row) {
                $kode = getPintuCodeExport($row['nomor_pintu']);
                $wilayahData = $pintuWilayahExport[$kode];
                $rekapWilayah[$wilayahData]['jumlah']++;
                $rekapWilayah[$wilayahData]['total_tonase'] += $row['tonase'];
            }
            
            // Hitung total keseluruhan
            $totalJumlah = count($results);
            $totalTonaseSemua = array_sum(array_column($results, 'tonase'));
            
            // Header untuk download Excel
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=data_ows.xls");
            
            // ========== MULAI OUTPUT EXCEL DENGAN LAYOUT 2 KOLOM (KIRI & KANAN) ==========
            echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">';
            
            // --- BARIS 1: HEADER UTAMA ---
            echo '<tr style="background-color: #1a1a2e; color: white; font-weight: bold;">';
            echo '<td colspan="5" style="font-size: 14px; padding: 8px; text-align: center;">DETAIL DATA TIMBANGAN</td>';
            echo '<td colspan="5" style="font-size: 14px; padding: 8px; text-align: center;">REKAPITULASI</td>';
            echo '</tr>';
            
            // --- BARIS 2: HEADER KOLOM ---
            echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
            // Header Kiri
            echo '<td style="width: 140px;">Tanggal masuk</td>';
            echo '<td style="width: 100px;">No.Pintu</td>';
            echo '<td style="width: 100px;">Nopol</td>';
            echo '<td style="width: 80px;">Berat Masuk</td>';
            echo '<td style="width: 150px;">Keterangan</td>';
            // Header Kanan (Rekap Shift)
            echo '<td style="width: 120px;">Shift</td>';
            echo '<td style="width: 100px;">Jam Operasional</td>';
            echo '<td style="width: 80px;">Jumlah Ritasi</td>';
            echo '<td style="width: 80px;">Total Berat Masuk</td>';
            echo '<td style="width: 80px;">Rata-rata</td>';
            echo '</tr>';
            
            // Siapkan data untuk kolom kanan (Rekap Shift)
            $rekapShiftRows = [];
            foreach([1,2,3] as $s) {
                if($rekapShift[$s]['jumlah'] > 0) {
                    $rataShift = $rekapShift[$s]['total_tonase'] / $rekapShift[$s]['jumlah'];
                    $rekapShiftRows[] = [
                        'shift' => 'Shift ' . $s,
                        'jam' => getShiftRange($s),
                        'jumlah' => number_format($rekapShift[$s]['jumlah'], 0, ',', '.'),
                        'tonase' => number_format($rekapShift[$s]['total_tonase'], 0, ',', '.'),
                        'rata' => number_format($rataShift, 0, ',', '.')
                    ];
                }
            }
            
            // Gabungkan semua data detail dari shift 1, 2, 3 menjadi satu array linear
            // TAPI dengan tambahan baris header shift di setiap awal shift
            $allDetailRows = [];
            for($shift = 1; $shift <= 3; $shift++) {
                if(!empty($shiftData[$shift])) {
                    // Tambahkan baris HEADER SHIFT (Warna Biru Gelap)
                    $allDetailRows[] = [
                        'type' => 'header',
                        'shift_num' => $shift,
                        'text' => 'Shift ' . $shift . ' | ' . getShiftRange($shift) . ' | Total: ' . number_format(count($shiftData[$shift]), 0, ',', '.') . ' ritasi | Berat Masuk: ' . number_format(array_sum(array_column($shiftData[$shift], 'tonase')), 0, ',', '.')
                    ];
                    
                    // Tambahkan baris data detail
                    foreach($shiftData[$shift] as $row) {
                        $tonase = (int)$row['tonase'] == $row['tonase'] ? (int)$row['tonase'] : $row['tonase'];
                        $keterangan = !empty($row['keterangan']) ? $row['keterangan'] : '-';
                        
                        $allDetailRows[] = [
                            'type' => 'data',
                            'tanggal' => date('d/m/Y H:i', strtotime($row['tanggal_jam'])),
                            'pintu' => htmlspecialchars($row['nomor_pintu']),
                            'nopol' => htmlspecialchars($row['nomor_plat']),
                            'tonase' => number_format($tonase, 0, ',', '.'),
                            'keterangan' => htmlspecialchars($keterangan)
                        ];
                    }
                }
            }
            
            // Tentukan jumlah baris maksimal (antara detail kiri dan rekap shift kanan)
            $maxRows = max(count($allDetailRows), count($rekapShiftRows));
            
            // Loop untuk mencetak baris demi baris
            for($i = 0; $i < $maxRows; $i++) {
                echo '<tr>';
                
                // --- KOLOM KIRI (DETAIL) ---
                if(isset($allDetailRows[$i])) {
                    $detail = $allDetailRows[$i];
                    
                    if($detail['type'] == 'header') {
                        // Baris Header Shift (Warna Biru Gelap, Teks Putih)
                        echo '<td colspan="5" style="background-color: #1a1a2e; color: white; font-weight: bold; padding: 6px;">' . $detail['text'] . '</td>';
                    } else {
                        // Baris Data Biasa
                        echo '<td>' . $detail['tanggal'] . '</td>';
                        echo '<td>' . $detail['pintu'] . '</td>';
                        echo '<td>' . $detail['nopol'] . '</td>';
                        echo '<td style="text-align: right;">' . $detail['tonase'] . '</td>';
                        echo '<td>' . $detail['keterangan'] . '</td>';
                    }
                } else {
                    // Jika data detail sudah habis, kosongkan
                    echo '<td colspan="5"></td>';
                }
                
                // --- KOLOM KANAN (REKAP SHIFT) ---
                if(isset($rekapShiftRows[$i])) {
                    $rekap = $rekapShiftRows[$i];
                    echo '<td>' . $rekap['shift'] . '</td>';
                    echo '<td>' . $rekap['jam'] . '</td>';
                    echo '<td style="text-align: center;">' . $rekap['jumlah'] . '</td>';
                    echo '<td style="text-align: right;">' . $rekap['tonase'] . '</td>';
                    echo '<td style="text-align: right;">' . $rekap['rata'] . '</td>';
                } else {
                    // Jika data rekap shift sudah habis, kosongkan
                    echo '<td colspan="5"></td>';
                }
                
                echo '</tr>';
            }
            
            // --- BARIS SPACER SEBELUM REKAP WILAYAH ---
            echo '<tr><td colspan="10" style="height: 15px; background-color: #ffffff; border: none;"></td></tr>';
            
            // Header Rekap Wilayah (Di sebelah kanan)
            echo '<tr style="background-color: #1a1a2e; color: white; font-weight: bold;">';
            echo '<td colspan="5" style="text-align: center; padding: 8px;">TOTAL KESELURUHAN</td>';
            echo '<td colspan="5" style="text-align: center; padding: 8px;">REKAP RITASI PER WILAYAH</td>';
            echo '</tr>';
            
            // Baris Total Keseluruhan (Kiri) & Header Wilayah (Kanan)
            echo '<tr style="background-color: #e9ecef; font-weight: bold;">';
            // Kiri: Total
            echo '<td>Total Ritasi</td>';
            echo '<td style="text-align: center;">' . number_format($totalJumlah, 0, ',', '.') . '</td>';
            echo '<td>Total Berat Masuk</td>';
            echo '<td style="text-align: right;">' . number_format($totalTonaseSemua, 0, ',', '.') . '</td>';
            echo '<td>Rata-rata</td>';
            // Kanan: Header Wilayah
            echo '<td>Wilayah</td>';
            echo '<td>Jumlah Ritasi</td>';
            echo '<td>Total Berat Masuk</td>';
            echo '<td>Rata-rata</td>';
            echo '<td>Persentase</td>';
            echo '</tr>';
            
            // Siapkan data Rekap Wilayah
            $rekapWilayahRows = [];
            foreach($rekapWilayah as $wil => $data) {
                if($data['jumlah'] > 0) {
                    $rata = $data['total_tonase'] / $data['jumlah'];
                    $persen = ($data['jumlah'] / $totalJumlah) * 100;
                    $rekapWilayahRows[] = [
                        'wilayah' => $wil,
                        'jumlah' => number_format($data['jumlah'], 0, ',', '.'),
                        'tonase' => number_format($data['total_tonase'], 0, ',', '.'),
                        'rata' => number_format($rata, 0, ',', '.'),
                        'persen' => number_format($persen, 1, ',', '.') . '%'
                    ];
                }
            }
            
            $maxWilayahRows = count($rekapWilayahRows);
            
            for($i = 0; $i < $maxWilayahRows; $i++) {
                echo '<tr>';
                
                // --- KOLOM KIRI (TOTAL KESELURUHAN) ---
                if($i === 0) {
                    $rataKeseluruhan = $totalTonaseSemua / $totalJumlah;
                    echo '<td>TOTAL KESELURUHAN</td>';
                    echo '<td style="text-align: center;">' . number_format($totalJumlah, 0, ',', '.') . '</td>';
                    echo '<td style="text-align: right;">' . number_format($totalTonaseSemua, 0, ',', '.') . '</td>';
                    echo '<td style="text-align: right;">' . number_format($rataKeseluruhan, 0, ',', '.') . '</td>';
                    echo '<td style="text-align: center;">100%</td>';
                } else {
                    echo '<td colspan="5"></td>';
                }
                
                // --- KOLOM KANAN (REKAP WILAYAH) ---
                if(isset($rekapWilayahRows[$i])) {
                    $wil = $rekapWilayahRows[$i];
                    echo '<td>' . $wil['wilayah'] . '</td>';
                    echo '<td style="text-align: center;">' . $wil['jumlah'] . '</td>';
                    echo '<td style="text-align: right;">' . $wil['tonase'] . '</td>';
                    echo '<td style="text-align: right;">' . $wil['rata'] . '</td>';
                    echo '<td style="text-align: center;">' . $wil['persen'] . '</td>';
                } else {
                    echo '<td colspan="5"></td>';
                }
                
                echo '</tr>';
            }
            
            echo '</table>';
            exit();
        } else {
            $error = "Tidak ada data untuk periode dan wilayah yang dipilih!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Excel - OWS TPST Bantargebang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 16px;
        }
        
        .user-info {
            padding: 16px 20px;
            background: rgba(255,255,255,0.08);
            margin: 16px;
            border-radius: 12px;
        }
        
        .user-info .badge {
            background: #28a745;
            font-size: 10px;
            padding: 4px 10px;
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
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: #0d6efd;
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            font-size: 1.2rem;
        }
        
        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 12px 20px;
        }
        
        .nav-section {
            padding: 10px 20px;
            font-size: 11px;
            color: #8898aa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 24px 32px;
            min-height: 100vh;
        }
        
        .top-bar {
            margin-bottom: 28px;
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
        }
        
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
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            padding: 12px 16px;
            font-size: 14px;
            width: 100%;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
        }
        
        .btn-export {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            border-radius: 12px;
            width: 100%;
            font-size: 15px;
            color: white;
        }
        
        .btn-export:hover {
            background: linear-gradient(135deg, #16213e 0%, #0f121f 100%);
            cursor: pointer;
        }
        
        .alert {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .form-row .form-group {
            flex: 1;
            min-width: 200px;
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
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

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
        <a href="index.php" class="nav-link">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="input.php" class="nav-link">
            <i class="bi bi-plus-circle"></i> Input OWS
        </a>
        <a href="data.php" class="nav-link">
            <i class="bi bi-table"></i> Data OWS
        </a>
        
        <?php if($_SESSION['role'] == 'admin'): ?>
        <div class="nav-divider"></div>
        <div class="nav-section">ADMINISTRATOR</div>
        <a href="kelola_user.php" class="nav-link">
            <i class="bi bi-people"></i> Kelola User
        </a>
        <a href="history_log.php" class="nav-link">
            <i class="bi bi-clock-history"></i> History Log
        </a>
        <a href="export_excel.php" class="nav-link active">
            <i class="bi bi-download"></i> Export Excel
        </a>
        <a href="import_kendaraan.php" class="nav-link">
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

<div class="main-content">
    <button class="menu-toggle" id="menuToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <div class="top-bar">
        <h2>Export Data OWS</h2>
        <p>Download data timbangan dengan rekap per Shift dan per Wilayah</p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filter Data Export
        </div>
        <div class="card-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-calendar-start"></i> Tanggal Dari
                        </label>
                        <input type="date" name="tanggal_dari" class="form-control" 
                               value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-calendar-end"></i> Tanggal Sampai
                        </label>
                        <input type="date" name="tanggal_sampai" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-map"></i> Pilih Wilayah
                        </label>
                        <select name="wilayah" class="form-select">
                            <option value="semua">-- Semua Wilayah --</option>
                            <?php foreach($wilayahList as $wil): ?>
                            <option value="<?php echo $wil; ?>"><?php echo $wil; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-info-circle"></i> Informasi
                        </label>
                        <div class="form-control" style="background: #f8f9fa;">
                            <small>File akan di-download sebagai <strong>data_ows.xls</strong> dengan layout Rekap di sebelah kanan Detail Data.</small>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="export" class="btn-export">
                    <i class="bi bi-download"></i> Download Excel
                </button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="bi bi-question-circle"></i> Panduan Export
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li><strong>Filter Tanggal:</strong> Pilih rentang tanggal data yang ingin di-download</li>
                <li><strong>Filter Wilayah:</strong> Pilih wilayah spesifik atau "Semua Wilayah" untuk semua data</li>
                <li><strong>Hasil Export:</strong> File akan otomatis terdownload dengan nama <strong>data_ows.xls</strong></li>
                <li><strong>Layout Baru:</strong> Detail Data di kiri, Rekap Shift & Rekap Wilayah di kanan (berdampingan)</li>
                <li><strong>Header Shift:</strong> Setiap shift memiliki baris header berwarna biru gelap dengan keterangan shift</li>
            </ul>
        </div>
    </div>
</div>

<script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
    
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const menuBtn = document.getElementById('menuToggle');
        if(window.innerWidth <= 992 && sidebar.classList.contains('show')) {
            if(!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>