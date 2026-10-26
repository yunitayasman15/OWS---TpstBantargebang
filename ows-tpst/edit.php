<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data master kendaraan untuk autocomplete
$masterData = [];
$masterQuery = $conn->query("SELECT no_pintu, no_plat FROM master_kendaraan");
while($row = $masterQuery->fetch_assoc()) {
    $masterData[$row['no_pintu']] = $row['no_plat'];
}

$stmt = $conn->prepare("SELECT * FROM ows_data WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header('Location: data.php');
    exit();
}
$data = $result->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_jam = $_POST['tanggal_jam'];
    $tonase = floatval(str_replace(',', '.', $_POST['tonase']));
    $keterangan = trim($_POST['keterangan']);
    
    // ===== LOGIKA: Operator tidak bisa ubah nomor pintu, plat, dan grup =====
    if ($_SESSION['role'] == 'operator') {
        // Operator: gunakan data lama (tidak berubah)
        $nomor_pintu = $data['nomor_pintu'];
        $nomor_plat = $data['nomor_plat'];
        $grup = $_SESSION['grup']; // Gunakan grup dari session
    } else {
        // Admin: bisa ubah nomor pintu & grup, tapi nomor plat tetap dari data lama
        // (karena nomor plat terisi otomatis dari master kendaraan via nomor pintu)
        $nomor_pintu = strtoupper(trim($_POST['nomor_pintu']));
        $nomor_plat = $data['nomor_plat']; // Nomor plat tidak berubah
        $grup = $_POST['grup'];
    }
    // ======================================================================
    
    $update = $conn->prepare("UPDATE ows_data SET tanggal_jam=?, nomor_pintu=?, grup=?, nomor_plat=?, tonase=?, keterangan=? WHERE id=?");
    $update->bind_param("ssssdsi", $tanggal_jam, $nomor_pintu, $grup, $nomor_plat, $tonase, $keterangan, $id);
    
    if($update->execute()) {
        echo "<script>alert('Data berhasil diupdate!'); window.location.href='data.php';</script>";
    } else {
        echo "<script>alert('Gagal mengupdate data!');</script>";
    }
}

$page_title = 'Edit Data - OWS TPST Bantargebang';
$page_heading = 'Edit Data Timbangan';
$page_subheading = 'Perbaiki data yang sudah diinput';

include 'includes/header.php';
?>

<style>
    /* Autocomplete styling */
    .autocomplete-suggestions {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        width: calc(100% - 2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .autocomplete-suggestion {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    
    .autocomplete-suggestion:hover {
        background: #e8f4fd;
    }
    
    .position-relative {
        position: relative;
    }
    
    .readonly-field {
        background-color: #f5f5f5 !important;
        cursor: not-allowed;
    }
    
    .info-box {
        background: #e8f4fd;
        border-left: 4px solid #0d6efd;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
    }
</style>

<div class="card">
    <div class="card-header">
        <i class="bi bi-pencil-square"></i> Edit Data Timbangan
    </div>
    <div class="card-body">
    
        <?php if($_SESSION['role'] == 'operator'): ?>
        <div class="info-box">
            <i class="bi bi-info-circle-fill"></i> Anda login sebagai <strong>Operator Grup <?php echo $_SESSION['grup']; ?></strong>. 
            Nomor Pintu, Nomor Plat, dan Grup tidak dapat diubah. Yang bisa diubah hanya Tanggal, Berat Masuk, dan Keterangan.
        </div>
        <?php else: ?>
        <div class="info-box">
            <i class="bi bi-info-circle-fill"></i> Anda login sebagai <strong>Admin</strong>. 
            Nomor Plat terkunci (terisi otomatis dari nomor pintu). Nomor Pintu, Grup, Tanggal, Berat Masuk, dan Keterangan dapat diubah.
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group mb-3">
                <label class="form-label">
                    <i class="bi bi-calendar-event"></i> Tanggal & Jam
                </label>
                <input type="datetime-local" name="tanggal_jam" class="form-control" 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($data['tanggal_jam'])); ?>" required>
            </div>
            
            <div class="form-group mb-3 position-relative">
                <label class="form-label">
                    <i class="bi bi-door-open"></i> Nomor Pintu
                </label>
                <?php if($_SESSION['role'] == 'operator'): ?>
                    <!-- Operator: readonly -->
                    <input type="text" class="form-control readonly-field" 
                           value="<?php echo htmlspecialchars($data['nomor_pintu']); ?>" readonly>
                <?php else: ?>
                    <!-- Admin: bisa edit -->
                    <input type="text" name="nomor_pintu" id="nomorPintu" class="form-control" 
                           value="<?php echo htmlspecialchars($data['nomor_pintu']); ?>"
                           placeholder="Ketik nomor pintu..." autocomplete="off" required>
                    <div id="pintuSuggestions" class="autocomplete-suggestions" style="display: none;"></div>
                    <small class="text-muted">Format: 0023 B (Jakarta Timur) atau UPK-BA 002 (Badan Air)</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">
                    <i class="bi bi-truck"></i> Nomor Plat
                </label>
                <!-- Nomor Plat: SELALU readonly untuk admin & operator -->
                <input type="text" class="form-control readonly-field" 
                       value="<?php echo htmlspecialchars($data['nomor_plat']); ?>" readonly>
                <small class="text-muted">Nomor plat terisi otomatis dari master kendaraan berdasarkan nomor pintu.</small>
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Pilih Grup
                </label>
                <?php if($_SESSION['role'] == 'operator'): ?>
                    <!-- Operator: readonly, tampilkan grup dari session -->
                    <input type="text" class="form-control readonly-field" 
                           value="Grup <?php echo $_SESSION['grup']; ?>" readonly>
                <?php else: ?>
                    <!-- Admin: bisa pilih -->
                    <select name="grup" class="form-select" required>
                        <option value="">-- Pilih Grup --</option>
                        <option value="1" <?php echo $data['grup'] == 1 ? 'selected' : ''; ?>>Grup 1</option>
                        <option value="2" <?php echo $data['grup'] == 2 ? 'selected' : ''; ?>>Grup 2</option>
                        <option value="3" <?php echo $data['grup'] == 3 ? 'selected' : ''; ?>>Grup 3</option>
                        <option value="4" <?php echo $data['grup'] == 4 ? 'selected' : ''; ?>>Grup 4</option>
                    </select>
                <?php endif; ?>
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">
                    <i class="bi bi-weight-scale"></i> Berat Masuk (Ton)
                </label>
                <!-- Berat Masuk: hilangkan ",00" jika bulat -->
                <input type="number" step="0.01" name="tonase" class="form-control" 
                       value="<?php echo (floor($data['tonase']) == $data['tonase']) ? (int)$data['tonase'] : $data['tonase']; ?>" required>
            </div>
            
            <div class="form-group mb-4">
                <label class="form-label">
                    <i class="bi bi-chat-text"></i> Keterangan
                </label>
                <input type="text" name="keterangan" class="form-control" 
                       value="<?php echo htmlspecialchars($data['keterangan'] ?? ''); ?>"
                       placeholder="Opsional - catatan tambahan">
            </div>
            
            <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: none; padding: 12px; border-radius: 10px;">
                <i class="bi bi-save"></i> Update Data
            </button>
        </form>
    </div>
</div>

<script>
    // Data master dari PHP
    const masterData = <?php echo json_encode($masterData); ?>;
    
    // Autocomplete hanya untuk admin (nomor pintu)
    const pintuInput = document.getElementById('nomorPintu');
    const suggestionsDiv = document.getElementById('pintuSuggestions');
    
    // Cek apakah elemen ada (hanya admin yang punya)
    if (pintuInput && suggestionsDiv) {
        let currentSuggestions = [];
        
        pintuInput.addEventListener('input', function() {
            const query = this.value.toUpperCase().trim();
            
            if (query.length === 0) {
                suggestionsDiv.style.display = 'none';
                return;
            }
            
            const matches = Object.keys(masterData).filter(pintu => 
                pintu.toUpperCase().includes(query)
            ).slice(0, 10);
            
            if (matches.length > 0) {
                currentSuggestions = matches;
                suggestionsDiv.innerHTML = matches.map(pintu => 
                    `<div class="autocomplete-suggestion" data-pintu="${pintu}" data-plat="${masterData[pintu]}">
                        <strong>${pintu}</strong> - ${masterData[pintu]}
                    </div>`
                ).join('');
                suggestionsDiv.style.display = 'block';
                
                document.querySelectorAll('.autocomplete-suggestion').forEach(el => {
                    el.addEventListener('click', function() {
                        const pintu = this.getAttribute('data-pintu');
                        pintuInput.value = pintu;
                        suggestionsDiv.style.display = 'none';
                        // Nomor plat akan otomatis terisi di database saat submit
                    });
                });
            } else {
                suggestionsDiv.style.display = 'none';
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!pintuInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>