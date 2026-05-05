<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="eyebrow">
                    <i class="bi bi-star-fill"></i> Peternakan Terpercaya di Bali
                </span>
                <h1 class="display-4 fw-bold mb-3">
                    Peternakan Ayam <span class="text-brand">Berkualitas Tinggi</span>
                </h1>
                <p class="lead mb-4">
                    DIX Game Farm menyediakan telur, DOC (Day Old Chick), dan ayam siap jual dengan kualitas terbaik
                    langsung dari peternakan kami di Bali.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= base_url('katalog') ?>" class="btn btn-crimson btn-lg btn-pill px-4">
                        <i class="bi bi-grid"></i> Lihat Katalog
                    </a>
                    <a href="<?= base_url('kontak') ?>" class="btn btn-outline-crimson btn-lg btn-pill px-4">
                        <i class="bi bi-telephone"></i> Hubungi Kami
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="<?= base_url('img/logo.png') ?>" alt="DIX Game Farm" class="img-fluid hero-logo">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section-dark py-5">
    <div class="container">
        <h2 class="text-center section-title mb-5">Mengapa Memilih DIX Game Farm?</h2>
        <div class="row g-4 mt-3">
            <div class="col-md-4">
                <div class="card dark-card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon mx-auto">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Kualitas Terjamin</h4>
                        <p class="text-muted">
                            Ayam kampung super berkualitas tinggi dengan perawatan terbaik, pakan bergizi, dan vaksinasi
                            lengkap.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card dark-card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon mx-auto">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Pengiriman Aman</h4>
                        <p class="text-muted">
                            Sistem pengiriman yang aman dan terpercaya, atau bisa ambil langsung di lokasi peternakan
                            kami.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card dark-card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon mx-auto">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Harga Bersaing</h4>
                        <p class="text-muted">
                            Harga kompetitif langsung dari peternak tanpa perantara. Hemat dan tetap berkualitas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Siap Berbelanja?</h2>
        <p class="lead mb-4">
            Daftar sekarang dan dapatkan akses ke semua produk berkualitas kami dengan sistem pemesanan yang mudah.
        </p>
        <?php if (!session()->get('isLoggedIn')): ?>
            <a href="<?= base_url('auth/register') ?>" class="btn btn-crimson btn-lg px-5">
                <i class="bi bi-person-plus"></i> Daftar Sekarang
            </a>
        <?php else: ?>
            <a href="<?= base_url('katalog') ?>" class="btn btn-crimson btn-lg px-5">
                <i class="bi bi-grid"></i> Mulai Belanja
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Stats Section -->
<section class="section-dark py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <h2>1000+</h2>
                    <p>Ayam Terjual</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <h2>500+</h2>
                    <p>Pelanggan Puas</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <h2>4</h2>
                    <p>Kategori Produk</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <h2>5<i class="bi bi-star-fill" style="font-size: 0.7em;"></i></h2>
                    <p>Rating Pelanggan</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
