</div> <!-- close .main-content -->

<script>
    // Toggle sidebar dengan transisi
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                // Animasi tambahan untuk konten
                if (mainContent) {
                    mainContent.style.opacity = '0.7';
                    setTimeout(() => {
                        mainContent.style.opacity = '1';
                    }, 300);
                }
            }
        });
    }
    
    // Klik di luar sidebar untuk mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    // Efek loading selesai - animasi fade in untuk semua elemen
    window.addEventListener('load', function() {
        // Animasi untuk stat cards jika ada
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
        });
    });
    
    // Efek hover untuk tabel
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = (index * 0.05) + 's';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>