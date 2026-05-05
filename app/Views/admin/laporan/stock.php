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
                <a href="<?= base_url('admin/pesanan/pending-payments') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-credit-card me-2"></i>Verifikasi Pembayaran
                </a>
                <a href="<?= base_url('admin/laporan') ?>" class="list-group-item list-group-item-action active">
                    <i class="bi bi-graph-up me-2"></i>Laporan
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/laporan') ?>">Laporan</a></li>
                    <li class="breadcrumb-item active">Stok</li>
                </ol>
            </nav>

            <h2 class="fw-bold mb-4"><i class="bi bi-box-seam me-2 text-primary"></i>Laporan Stok</h2>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Nilai Stok</h6>
                                    <h3 class="fw-bold mb-0">Rp <?= number_format($total_stock_value, 0, ',', '.') ?></h3>
                                </div>
                                <i class="bi bi-safe fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Stok Rendah</h6>
                                    <h3 class="fw-bold mb-0"><?= $low_stock_count ?></h3>
                                    <small class="opacity-75">Produk dengan stok &le; 10</small>
                                </div>
                                <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Stok Habis</h6>
                                    <h3 class="fw-bold mb-0"><?= $out_of_stock_count ?></h3>
                                    <small class="opacity-75">Produk dengan stok 0</small>
                                </div>
                                <i class="bi bi-x-octagon fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Stock Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Detail Stok Produk</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($products)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-center">Stok Tersedia</th>
                                        <th class="text-center">Stok Dibooking</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th class="text-end">Nilai Stok</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $i => $product): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td class="fw-semibold"><?= esc($product['nama_ayam']) ?></td>
                                            <td><span class="badge bg-light text-dark"><?= esc($product['nama_kategori']) ?></span></td>
                                            <td class="text-center">
                                                <?php if ($product['stok_tersedia'] == 0): ?>
                                                    <span class="badge bg-danger"><?= $product['stok_tersedia'] ?></span>
                                                <?php elseif ($product['stok_tersedia'] <= 10): ?>
                                                    <span class="badge bg-warning text-dark"><?= $product['stok_tersedia'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?= $product['stok_tersedia'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($product['stok_dibooking'] > 0): ?>
                                                    <span class="badge bg-info"><?= $product['stok_dibooking'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">Rp <?= number_format($product['harga'], 0, ',', '.') ?></td>
                                            <td class="text-end fw-semibold">Rp <?= number_format($product['total_value'], 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <?php if (!$product['is_active']): ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                <?php elseif ($product['stok_tersedia'] == 0): ?>
                                                    <span class="badge bg-danger">Habis</span>
                                                <?php elseif ($product['stok_tersedia'] <= 10): ?>
                                                    <span class="badge bg-warning text-dark">Stok Rendah</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Tersedia</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="6" class="text-end fw-bold">Total Nilai Stok:</td>
                                        <td class="text-end fw-bold text-primary">Rp <?= number_format($total_stock_value, 0, ',', '.') ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Belum ada data produk.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
