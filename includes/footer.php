    <!-- Footer -->
    <footer class="site-footer mt-auto">
        <div class="container">
            <div class="row align-items-center py-4">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start">
                        <div class="brand-icon-sm">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <div>
                            <div class="fw-700 text-white">Muara Jaya Sport</div>
                            <div class="text-footer-sub">SPK Pemilihan Raket Bulu Tangkis</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-1 text-footer-sub">Metode: <strong class="text-accent">Simple Additive Weighting (SAW)</strong></p>
                    <p class="mb-0 text-footer-sub">&copy; <?= date('Y') ?> Sistem Pendukung Keputusan. Hak cipta dilindungi.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= $basePath ?? '' ?>assets/js/main.js"></script>

    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
