<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero__inner text-center">
            <span class="eyebrow"><i class="bi bi-patch-check-fill"></i> Tentang Kami</span>
            <h1 class="page-hero__title">Tentang DIX Game Farm</h1>
            <p class="page-hero__lead">Peternakan ayam kampung super berkualitas tinggi di Bali — mulai dari bibit, telur, hingga ayam siap jual.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5 mb-5">
            <div class="col-lg-6 text-center">
                <img src="<?= base_url('img/logo.png') ?>" alt="DIX Game Farm" class="about-logo">
            </div>
            <div class="col-lg-6">
                <span class="eyebrow mb-2"><i class="bi bi-book"></i> Sejarah Kami</span>
                <h2 class="fw-bold mb-3">Dibangun dari Passion, Dirawat dengan Standar</h2>
                <p>
                    DIX Game Farm didirikan dengan visi menyediakan produk peternakan ayam berkualitas tinggi untuk masyarakat Bali
                    dan sekitarnya. Berlokasi strategis di Jl. Umadiwang, Perean, Baturiti, Tabanan, kami telah melayani ribuan pelanggan dengan produk terbaik.
                </p>
                <p class="mb-0">
                    Dengan pengalaman bertahun-tahun dalam budidaya ayam kampung super, kami berkomitmen memberikan yang terbaik mulai dari telur,
                    anak ayam (DOC), hingga ayam siap jual.
                </p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon mx-auto"><i class="bi bi-award"></i></div>
                        <h4 class="fw-bold">Kualitas Terjamin</h4>
                        <p class="mb-0">Semua produk kami melalui kontrol kualitas ketat untuk memastikan kesehatan dan mutu terbaik.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon mx-auto"><i class="bi bi-heart-pulse"></i></div>
                        <h4 class="fw-bold">Perawatan Terbaik</h4>
                        <p class="mb-0">Ayam-ayam kami dirawat dengan penuh kasih dan diberi pakan berkualitas tinggi setiap hari.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon mx-auto"><i class="bi bi-shield-check"></i></div>
                        <h4 class="fw-bold">Bebas Penyakit</h4>
                        <p class="mb-0">Protokol biosecurity dan vaksinasi rutin memastikan ayam kami bebas dari penyakit.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel p-0 overflow-hidden">
            <div class="row g-0">
                <div class="col-md-6 p-4 p-lg-5" style="background: linear-gradient(135deg, #E67E22, #D35400); color: #fff;">
                    <span class="eyebrow eyebrow--light" style="background: rgba(255,255,255,.18); color: #fff !important; border-color: rgba(255,255,255,.3);">
                        <i class="bi bi-compass"></i> Visi
                    </span>
                    <h3 class="fw-bold mt-3 mb-2" style="color: #fff !important;">Visi</h3>
                    <p class="mb-0" style="color: rgba(255,255,255,.92) !important;">
                        Menjadi peternakan ayam kampung super terdepan di Bali yang menghasilkan produk berkualitas tinggi dan berkelanjutan.
                    </p>
                </div>
                <div class="col-md-6 p-4 p-lg-5">
                    <span class="eyebrow"><i class="bi bi-list-check"></i> Misi</span>
                    <h3 class="fw-bold mt-3 mb-3">Misi</h3>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-check2-circle text-brand me-2"></i>Menyediakan produk ayam berkualitas tinggi.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-brand me-2"></i>Menerapkan teknologi modern dalam peternakan.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-brand me-2"></i>Memberikan pelayanan terbaik kepada pelanggan.</li>
                        <li class="mb-0"><i class="bi bi-check2-circle text-brand me-2"></i>Mendukung ekonomi lokal Bali.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
