<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$success = '';
$error = '';

// Ambil data master kendaraan untuk autocomplete
$masterData = [];
$masterQuery = $conn->query("SELECT no_pintu, no_plat FROM master_kendaraan ORDER BY no_pintu ASC");
while($row = $masterQuery->fetch_assoc()) {
    $masterData[$row['no_pintu']] = $row['no_plat'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_jam = $_POST['tanggal_jam'];
    $nomor_pintu = strtoupper(trim($_POST['nomor_pintu']));
    $nomor_plat = strtoupper(trim($_POST['nomor_plat']));
    $tonase = floatval($_POST['tonase']);
    $keterangan = trim($_POST['keterangan']); // Keterangan dari input user (bisa diisi siapa saja)
    
    // ===== LOGIKA GRUP =====
    if ($_SESSION['role'] == 'operator') {
        // Operator: grup diambil dari session (tidak bisa diubah)
        $grup = $_SESSION['grup'];
    } else {
        // Admin: grup diambil dari POST (bisa dipilih bebas)
        $grup = $_POST['grup'];
    }
    // =======================
    
    if (empty($nomor_pintu)) {
        $error = 'Nomor pintu harus diisi!';
    } elseif (empty($grup)) {
        $error = 'Grup harus dipilih!';
    } else {
        $stmt = $conn->prepare("INSERT INTO ows_data (tanggal_jam, nomor_pintu, grup, nomor_plat, tonase, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdsi", $tanggal_jam, $nomor_pintu, $grup, $nomor_plat, $tonase, $keterangan, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Data berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan data: ' . $conn->error;
        }
    }
}

$recentData = $conn->query("SELECT * FROM ows_data ORDER BY tanggal_jam DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input OWS - TPST Bantargebang</title>
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
        
        /* Animations */
        .main-content { animation: fadeInPage 0.5s ease-out; }
        @keyframes fadeInPage { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card { transition: all 0.3s ease; animation: fadeInUp 0.4s ease-out; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .alert { animation: slideInRight 0.3s ease-out; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        
        .btn-save { transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        
        .form-control, .form-select { transition: all 0.3s ease; }
        .form-control:focus, .form-select:focus { transform: scale(1.01); border-color: #2c3e50; box-shadow: 0 0 0 3px rgba(44,62,80,0.1); }
        
        /* Autocomplete */
        .autocomplete-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .autocomplete-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            transition: all 0.2s;
        }
        .autocomplete-suggestion:hover { background: #e8f4fd; }
        .autocomplete-suggestion.active { background: #94aed5; color: white; }
        .autocomplete-suggestion strong { color: #0d6efd; }
        .position-relative { position: relative; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar-header { padding: 24px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo { width: 60px; height: 60px; margin: 0 auto 15px auto; display: block; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); border-radius: 50%; padding: 3px; background: white; }
        .sidebar-header h4 { margin: 0; font-weight: 600; font-size: 16px; }
        .user-info { padding: 15px 20px; background: rgba(255,255,255,0.08); margin: 15px 15px; border-radius: 12px; }
        .user-info .badge { background: #28a745; font-size: 10px; }
        .nav-link { color: #e0e0e0; padding: 12px 20px; display: flex; align-items: center; gap: 12px; text-decoration: none; margin: 4px 12px; border-radius: 10px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: #0d6efd; color: white; transform: translateX(5px); }
        .nav-link i { width: 24px; font-size: 1.2rem; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 10px 20px; }
        .nav-section { padding: 10px 20px; font-size: 10px; color: #888; text-transform: uppercase; }
        
        .main-content { margin-left: 280px; padding: 24px; min-height: 100vh; }
        .top-bar { background: white; border-radius: 16px; padding: 12px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .menu-toggle { display: none; background: none; border: none; font-size: 24px; cursor: pointer; }
        .menu-toggle:hover { background: #0d6efd; transform: scale(1.05); }
        
        .card { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 24px; background: white; }
        .card-header { background: white; border-bottom: 1px solid #f0f0f0; padding: 18px 24px; font-weight: 600; font-size: 16px; }
        .card-header i { margin-right: 8px; color: #2c3e50; }
        
        .form-group { margin-bottom: 24px; }
        .form-label { font-weight: 500; margin-bottom: 8px; color: #333; font-size: 14px; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; padding: 12px 16px; font-size: 14px; width: 100%; }
        .btn-save { background: linear-gradient(135deg, #2c3e50, #1a252f); border: none; padding: 14px; font-weight: 600; border-radius: 12px; width: 100%; font-size: 15px; color: white; }
        .btn-save:hover { background: linear-gradient(135deg, #1a252f, #0f1a22); cursor: pointer; }
        
        /* ========== TABEL STYLES (SAMA DENGAN DATA OWS) ========== */
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f8fafc;
            padding: 10px 8px;
            font-size: 12px;
            font-weight: 600;
            color: #1a1a2e;
            border-bottom: 2px solid #eef2f6;
            text-align: left;
        }
        
        .data-table td {
            padding: 8px 8px;
            font-size: 13px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: middle;
        }
        
        .data-table tr:hover td {
            background: #fafbfd;
        }
        
        .badge-pintu {
            background: #e8f4fd;
            color: #0d6efd;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-grup {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-grup-1 { background: #dc3545; color: white; }
        .badge-grup-2 { background: #fd7e14; color: white; }
        .badge-grup-3 { background: #ffc107; color: #333; }
        .badge-grup-4 { background: #198754; color: white; }
        
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
        }
        .btn-edit:hover { background: #e0a800; color: #333; }
        
        .tonase-value { font-weight: 700; color: #2c3e50; }
        .empty-state { text-align: center; padding: 48px 24px; color: #999; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .alert { border-radius: 12px; border: none; padding: 12px 16px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; min-width: 200px; }
        
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #0d6efd;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        @media (max-width: 992px) {
            .sidebar { left: -280px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .form-row { flex-direction: column; gap: 0; }
        }
        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .data-table th, .data-table td { padding: 10px 12px; font-size: 12px; }
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
        <a href="input.php" class="nav-link active">
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
<a href="export_excel.php" class="nav-link">
    <i class="bi bi-download"></i> Export Excel
</a>
<a href="import_kendaraan.php" class="nav-link">
    <i class="bi bi-truck"></i> Import Kendaraan
</a>
<!-- TAMBAHKAN BARIS INI -->
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
    <div class="top-bar">
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>
        <span class="fw-bold">Operational Weighbridge System</span>
        <div class="d-flex gap-3 text-muted small">
            <span><i class="bi bi-search"></i> Mencari</span>
            <span><i class="bi bi-globe"></i> ENG</span>
            <span><i class="bi bi-clock"></i> <span id="currentTime"></span></span>
            <span><i class="bi bi-calendar"></i> <span id="currentDate"></span></span>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="bi bi-pencil-square"></i> Form Input Timbangan
        </div>
        <div class="card-body" style="padding: 24px;">
            <?php if($success != ''): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if($error != ''): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($_SESSION['role'] == 'operator'): ?>
            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i> Anda login sebagai <strong>Operator Grup <?php echo $_SESSION['grup']; ?></strong>. 
                Nomor Plat dan Grup sudah terkunci sesuai akun Anda.
            </div>
            <?php endif; ?>
            
            <form method="POST" id="inputForm">
                <!-- BARIS 1: NOMOR PINTU - NOMOR PLAT -->
                <div class="form-row">
                    <div class="form-group position-relative">
                        <label class="form-label">
                            <i class="bi bi-door-open"></i> Nomor Pintu
                        </label>
                        <input type="text" name="nomor_pintu" id="nomorPintu" class="form-control" 
                               placeholder="Ketik nomor pintu..." autocomplete="off" required>
                        <div id="pintuSuggestions" class="autocomplete-suggestions" style="display: none;"></div>
                        <small class="text-muted">Ketik nomor pintu, gunakan panah ↓ ↑ untuk navigasi, Enter untuk pilih</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-truck"></i> Nomor Plat
                        </label>
                        <input type="text" name="nomor_plat" id="nomorPlat" class="form-control" 
                               placeholder="Akan terisi otomatis" readonly style="background-color: #f5f5f5;">
                    </div>
                </div>
                
                <!-- BARIS 2: BERAT MASUK - KETERANGAN -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-weight-scale"></i> Berat Masuk
                        </label>
                        <input type="number" step="0.01" name="tonase" class="form-control" 
                               placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-chat-text"></i> Keterangan
                        </label>
                        <!-- Keterangan bisa diisi bebas oleh siapa saja (admin & operator) -->
                        <input type="text" name="keterangan" class="form-control" 
                               placeholder="Opsional - catatan tambahan (contoh: e-spj, RFID ERROR)">
                    </div>
                </div>
                
                <!-- BARIS 3: PILIH GRUP - TANGGAL & JAM -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-grid-3x3-gap-fill"></i> Pilih Grup
                        </label>
                        <?php if($_SESSION['role'] == 'operator'): ?>
                            <!-- Operator: grup terkunci (readonly) -->
                            <input type="text" class="form-control" 
                                   value="Grup <?php echo $_SESSION['grup']; ?>" readonly 
                                   style="background-color: #f5f5f5;">
                            <input type="hidden" name="grup" value="<?php echo $_SESSION['grup']; ?>">
                        <?php else: ?>
                            <!-- Admin: grup bisa dipilih -->
                            <select name="grup" class="form-select" required>
                                <option value="">-- Pilih Grup --</option>
                                <option value="1">Grup 1</option>
                                <option value="2">Grup 2</option>
                                <option value="3">Grup 3</option>
                                <option value="4">Grup 4</option>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-calendar-event"></i> Tanggal & Jam
                        </label>
                        <input type="datetime-local" name="tanggal_jam" id="tanggalJam" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="bi bi-save"></i> Simpan Data
                </button>
            </form>
        </div>
    </div>
    
    <div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history"></i> Data Terbaru
    </div>
    <div class="card-body p-0">
        <?php if($recentData && $recentData->num_rows > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 20%">Tanggal & Jam</th>
                        <th style="width: 20%">Nomor Pintu</th>
                        <th style="width: 10%">Grup</th>
                        <th style="width: 20%">Nomor Plat</th>
                        <th style="width: 10%">Berat Masuk</th>
                        <th style="width: 10%">Keterangan</th>
                        <th style="width: 5%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while($row = $recentData->fetch_assoc()): 
                        $grupClass = '';
                        if($row['grup'] == 1) $grupClass = 'badge-grup-1';
                        elseif($row['grup'] == 2) $grupClass = 'badge-grup-2';
                        elseif($row['grup'] == 3) $grupClass = 'badge-grup-3';
                        elseif($row['grup'] == 4) $grupClass = 'badge-grup-4';
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($row['tanggal_jam'])); ?></strong>
                            <br><small class="text-muted"><?php echo date('H:i:s', strtotime($row['tanggal_jam'])); ?></small>
                        </td>
                        <td>
                            <span class="badge-pintu">
                                <i class="bi bi-door-closed"></i> <?php echo htmlspecialchars($row['nomor_pintu']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-grup <?php echo $grupClass; ?>">
                                <i class="bi bi-grid"></i> Grup <?php echo htmlspecialchars($row['grup']); ?>
                            </span>
                        </td>
                        <td>
                            <i class="bi bi-truck text-muted me-1"></i>
                            <?php echo htmlspecialchars($row['nomor_plat']); ?>
                        </td>
                        <td>
                            <strong><?php echo number_format($row['tonase'], 0, ',', '.'); ?></strong>
                            <small class="text-muted">ton</small>
                        </td>
                        <td>
                            <?php echo !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : '-'; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Belum ada data</p>
            <small class="text-muted">Silakan input data timbangan pertama Anda</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Data master dari PHP
    const masterData = <?php echo json_encode($masterData); ?>;
    
    // Set tanggal & jam sekarang (real-time)
    function updateDateTimeNow() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const datetimeLocal = `${year}-${month}-${day}T${hours}:${minutes}`;
        const tanggalJamInput = document.getElementById('tanggalJam');
        if(tanggalJamInput) tanggalJamInput.value = datetimeLocal;
    }
    
    updateDateTimeNow();
    setInterval(updateDateTimeNow, 1000);
    
    // Autocomplete dengan navigasi keyboard
    const pintuInput = document.getElementById('nomorPintu');
    const platInput = document.getElementById('nomorPlat');
    const suggestionsDiv = document.getElementById('pintuSuggestions');
    
    let selectedIndex = -1;
    let debounceTimer;
    
    function hideSuggestions() {
        suggestionsDiv.style.display = 'none';
        selectedIndex = -1;
    }
    
    function showSuggestions(matches) {
        if (matches.length === 0) {
            hideSuggestions();
            return;
        }
        
        suggestionsDiv.innerHTML = matches.map((pintu, index) => 
            `<div class="autocomplete-suggestion" data-index="${index}" data-pintu="${escapeHtml(pintu)}" data-plat="${escapeHtml(masterData[pintu])}">
                <i class="bi bi-door-closed"></i> <strong>${escapeHtml(pintu)}</strong> → ${escapeHtml(masterData[pintu])}
            </div>`
        ).join('');
        suggestionsDiv.style.display = 'block';
        selectedIndex = -1;
        
        document.querySelectorAll('.autocomplete-suggestion').forEach(el => {
            el.addEventListener('click', function() {
                selectSuggestion(this.getAttribute('data-pintu'), this.getAttribute('data-plat'));
            });
        });
    }
    
    function selectSuggestion(pintu, plat) {
        pintuInput.value = pintu;
        platInput.value = plat;
        hideSuggestions();
        pintuInput.focus();
    }
    
    function updateSelectedSuggestion() {
        const items = document.querySelectorAll('.autocomplete-suggestion');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
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
    
    pintuInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = this.value.toUpperCase().trim();
            
            if (query.length === 0) {
                hideSuggestions();
                platInput.value = '';
                return;
            }
            
            const matches = Object.keys(masterData).filter(pintu => 
                pintu.toUpperCase().includes(query)
            ).slice(0, 15);
            
            showSuggestions(matches);
            
            if (matches.length === 0) {
                platInput.value = '';
            }
        }, 200);
    });
    
    pintuInput.addEventListener('keydown', function(e) {
        const items = document.querySelectorAll('.autocomplete-suggestion');
        const itemsCount = items.length;
        
        if (itemsCount === 0) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % itemsCount;
            updateSelectedSuggestion();
            if (items[selectedIndex]) items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
        else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + itemsCount) % itemsCount;
            updateSelectedSuggestion();
            if (items[selectedIndex]) items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
        else if (e.key === 'Enter') {
            if (selectedIndex >= 0 && selectedIndex < itemsCount) {
                e.preventDefault();
                const selectedItem = items[selectedIndex];
                selectSuggestion(selectedItem.getAttribute('data-pintu'), selectedItem.getAttribute('data-plat'));
            }
        }
        else if (e.key === 'Escape') {
            hideSuggestions();
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!pintuInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
    
    function updateCurrentTime() {
        const now = new Date();
        const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const date = now.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
        
        const timeElement = document.getElementById('currentTime');
        const dateElement = document.getElementById('currentDate');
        if(timeElement) timeElement.textContent = time;
        if(dateElement) dateElement.textContent = date;
    }
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const menuBtn = document.getElementById('menuToggle');
        if(window.innerWidth <= 992 && sidebar.classList.contains('show')) {
            if(!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    window.addEventListener('load', function() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>