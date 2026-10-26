<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$page_title = 'History Log - TPST Bantargebang';
$page_heading = 'History Log';
$page_subheading = 'Catatan aktivitas semua user';

$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalData = $conn->query("SELECT COUNT(*) as total FROM activity_logs")->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);

$logs = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT $offset, $limit");

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history"></i> Semua Aktivitas User
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Username</th>
                        <th>Aksi</th>
                        <th>Detail</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($log = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><?php echo $log['username']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $log['action']; ?></span></td>
                        <td><?php echo $log['details']; ?></td>
                        <td><?php echo $log['ip_address']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($logs->num_rows == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada aktivitas</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($totalPages > 1): ?>
        <nav class="p-3">
            <ul class="pagination justify-content-center">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>