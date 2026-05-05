<footer class="premium-footer">
    <div class="container">
        <!-- Main Footer Content -->
        <div class="row g-4 py-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="footer-brand-wrapper">
                    <a href="<?= base_url('/') ?>" class="footer-brand-link">
                        <img src="<?= base_url('img/logo.png') ?>" alt="DIX Game Farm"
                            class="footer-logo rounded-circle" height="32" width="32">
                        <span class="footer-brand-text">DIX Game Farm</span>
                    </a>
                    <p class="footer-description">Peternakan ayam berkualitas di Bali. Menyediakan telur, DOC, dan ayam
                        siap jual dengan kualitas terbaik langsung dari peternakan.</p>
                    <div class="footer-social">
                        <a href="https://www.instagram.com/dixgamefarmbali" target="_blank" class="social-link"
                            aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://www.tiktok.com/@DixGameFarm" target="_blank" class="social-link"
                            aria-label="TikTok">
                            <i class="bi bi-tiktok"></i>
                        </a>
                        <a href="https://wa.me/6285847937050" target="_blank" class="social-link" aria-label="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6 mb-4 mb-lg-0">
                <h6 class="footer-heading">Navigasi</h6>
                <ul class="footer-links">
                    <li><a href="<?= base_url('/') ?>">Beranda</a></li>
                    <li><a href="<?= base_url('katalog') ?>">Katalog</a></li>
                    <li><a href="<?= base_url('tentang') ?>">Tentang</a></li>
                    <li><a href="<?= base_url('kontak') ?>">Kontak</a></li>
                </ul>
            </div>

            <!-- Products -->
            <div class="col-lg-2 col-md-6 col-6 mb-4 mb-lg-0">
                <h6 class="footer-heading">Produk</h6>
                <ul class="footer-links">
                    <li><a href="<?= base_url('katalog') ?>">Ayam Kampung</a></li>
                    <li><a href="<?= base_url('katalog') ?>">Telur</a></li>
                    <li><a href="<?= base_url('katalog') ?>">DOC</a></li>
                    <li><a href="<?= base_url('katalog') ?>">Pakan</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-4 col-md-6">
                <h6 class="footer-heading">Hubungi Kami</h6>
                <ul class="footer-contact">
                    <li>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Jl. Umadiwang, Perean, Baturiti, Tabanan, Bali</span>
                    </li>
                    <li>
                        <i class="bi bi-telephone-fill"></i>
                        <a href="tel:+6285847937050">+62 858-4793-7050</a>
                    </li>
                    <li>
                        <i class="bi bi-clock-fill"></i>
                        <span>07:00 - 16:00 WITA</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Divider -->
        <div class="footer-divider"></div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="footer-copyright">&copy; <?= date('Y') ?> DIX Game Farm. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="footer-credit">Developed for Bachelor Thesis — ITB STIKOM Bali</p>
                </div>
            </div>
        </div>
    </div>
</footer>