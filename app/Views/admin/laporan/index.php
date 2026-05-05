<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 bg-light admin-sidebar">
            <div class="list-group list-group-flush">
                <a href="<?= base_url('admin/dashboard') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="<?= base_url('admin/kategori') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i>Kategori
                </a>
                <a href="<?= base_url('admin/produk') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-box-seam me-2"></i>Produk
                </a>
                <a href="<?= base_url('admin/pesanan') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-cart-check me-2"></i>Pesanan
                </a>
                <a href="<?= base_url('admin/pesanan/pending-payments') ?>"
                    class="list-group-item list-group-item-action">
                    <i class="bi bi-credit-card me-2"></i>Verifikasi Pembayaran
                </a>
                <a href="<?= base_url('admin/laporan') ?>" class="list-group-item list-group-item-action active">
                    <i class="bi bi-graph-up me-2"></i>Laporan
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <h2 class="fw-bold mb-4"><i class="bi bi-graph-up me-2"></i>Pusat Laporan</h2>
            <p class="text-muted mb-4">Pilih jenis laporan yang ingin Anda lihat.</p>

            <div class="row g-4">
                <!-- Sales Report Card -->
                <div class="col-md-6 col-lg-3">
                    <a href="<?= base_url('admin/laporan/sales') ?>" class="text-decoration-none">
                        <div class="card shadow-sm h-100 border-0 report-card">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-cash-stack text-success" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark">Laporan Penjualan</h5>
                                <p class="card-text text-muted small">
                                    Lihat ringkasan penjualan, pendapatan, dan produk terlaris berdasarkan periode
                                    waktu.
                                </p>
                            </div>
                            <div class="card-footer bg-success bg-opacity-10 text-center border-0">
                                <span class="text-success fw-semibold">Lihat Laporan <i
                                        class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Stock Report Card -->
                <div class="col-md-6 col-lg-3">
                    <a href="<?= base_url('admin/laporan/stock') ?>" class="text-decoration-none">
                        <div class="card shadow-sm h-100 border-0 report-card">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-box-seam text-crimson" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="card-title fw-bold">Laporan Stok</h5>
                                <p class="card-text text-muted small">
                                    Pantau ketersediaan stok, nilai inventaris, dan produk yang perlu restok.
                                </p>
                            </div>
                            <div class="card-footer text-center border-0"
                                style="background-color: rgba(200,16,46,0.12);">
                                <span class="text-crimson fw-semibold">Lihat Laporan <i
                                        class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Customer Report Card -->
                <div class="col-md-6 col-lg-3">
                    <a href="<?= base_url('admin/laporan/customers') ?>" class="text-decoration-none">
                        <div class="card shadow-sm h-100 border-0 report-card">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-people text-info" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="card-title fw-bold">Laporan Pelanggan</h5>
                                <p class="card-text text-muted small">
                                    Data pelanggan, riwayat pesanan, dan statistik pembelian pelanggan.
                                </p>
                            </div>
                            <div class="card-footer bg-info bg-opacity-10 text-center border-0">
                                <span class="text-info fw-semibold">Lihat Laporan <i
                                        class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    .report-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
<?= $this->endSection() ?>