</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> money-Q — Website Budgeting Pribadi</p>
        <p class="note">Data disimpan lokal di perangkat ini. Tidak ada server eksternal.</p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil semua alert
        const alerts = document.querySelectorAll('.alert');

        alerts.forEach(alert => {
            // Tambahkan delay 3 detik sebelum fade-out
            const timeout = setTimeout(() => {
                alert.classList.add('fade-out');
                setTimeout(() => {
                    if (alert.parentNode) alert.parentNode.removeChild(alert);
                }, 300); // sesuaikan dengan durasi animasi CSS
            }, 3000);

            // Jika user hover, batalkan timeout
            alert.addEventListener('mouseenter', () => {
                clearTimeout(timeout);
                alert.style.animationPlayState = 'paused';
            });

            // Lanjutkan saat mouse leave
            alert.addEventListener('mouseleave', () => {
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        if (alert.parentNode) alert.parentNode.removeChild(alert);
                    }, 300);
                }, 2000); // beri jeda 2 detik lagi setelah mouse pergi
            });
        });
    });
</script>
</body>

</html>