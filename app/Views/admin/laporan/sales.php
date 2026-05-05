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
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/laporan') ?>">Laporan</a></li>
                    <li class="breadcrumb-item active">Penjualan</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="bi bi-cash-stack me-2 text-success"></i>Laporan Penjualan</h2>
                <a href="<?= base_url('admin/laporan/export-sales?start_date=' . esc($start_date) . '&end_date=' . esc($end_date)) ?>"
                    class="btn btn-outline-success">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>

            <!-- Date Range Filter -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" action="<?= base_url('admin/laporan/sales') ?>" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                value="<?= esc($start_date) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label fw-semibold">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                value="<?= esc($end_date) ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Pesanan</h6>
                                    <h2 class="fw-bold mb-0"><?= $total_orders ?></h2>
                                    <small class="opacity-75"><?= date('d/m/Y', strtotime($start_date)) ?> -
                                        <?= date('d/m/Y', strtotime($end_date)) ?></small>
                                </div>
                                <i class="bi bi-cart-check fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-success text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Pendapatan</h6>
                                    <h2 class="fw-bold mb-0">Rp <?= number_format($total_revenue, 0, ',', '.') ?></h2>
                                    <small class="opacity-75"><?= date('d/m/Y', strtotime($start_date)) ?> -
                                        <?= date('d/m/Y', strtotime($end_date)) ?></small>
                                </div>
                                <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php $salesOrders = $order_list ?? $orders ?? []; ?>
                    <?php if (!empty($salesOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Invoice</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salesOrders as $i => $order): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <a href="<?= base_url('admin/pesanan/detail/' . $order['id']) ?>"
                                                    class="fw-bold text-decoration-none">
                                                    <?= esc($order['nomor_invoice']) ?>
                                                </a>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($order['tanggal_pesanan'])) ?></td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'MENUNGGU_BAYAR' => 'warning',
                                                    'MENUNGGU_VERIFIKASI' => 'warning',
                                                    'DIPROSES' => 'primary',
                                                    'PESANAN_SIAP' => 'primary',
                                                    'DIKIRIM' => 'info',
                                                    'SELESAI' => 'success',
                                                    'BATAL' => 'danger'
                                                ];
                                                $statusLabels = [
                                                    'MENUNGGU_BAYAR' => 'Menunggu Bayar',
                                                    'MENUNGGU_VERIFIKASI' => 'Menunggu Bayar',
                                                    'DIPROSES' => 'Diproses',
                                                    'PESANAN_SIAP' => 'Pesanan Siap',
                                                    'DIKIRIM' => 'Dikirim',
                                                    'SELESAI' => 'Selesai',
                                                    'BATAL' => 'Batal',
                                                ];
                                                $badgeClass = $badges[$order['status_pesanan']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= $statusLabels[$order['status_pesanan']] ?? str_replace('_', ' ', $order['status_pesanan']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold text-success">Rp
                                                <?= number_format($order['grand_total'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Grand Total:</td>
                                        <td class="text-end fw-bold text-success">Rp
                                            <?= number_format($total_revenue, 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Tidak ada data pesanan pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Best Selling Products Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Produk Terlaris</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($best_selling)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-center">Jumlah Terjual</th>
                                        <th class="text-end">Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($best_selling as $i => $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($i < 3): ?>
                                                    <span
                                                        class="badge bg-<?= $i === 0 ? 'warning text-dark' : ($i === 1 ? 'secondary' : 'danger') ?> rounded-pill"><?= $i + 1 ?></span>
                                                <?php else: ?>
                                                    <?= $i + 1 ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-semibold"><?= esc($item['nama_ayam']) ?></td>
                                            <td><span class="badge bg-light text-dark"><?= esc($item['nama_kategori']) ?></span>
                                            </td>
                                            <td class="text-center"><?= number_format($item['total_terjual'], 0, ',', '.') ?>
                                            </td>
                                            <td class="text-end fw-semibold text-success">Rp
                                                <?= number_format($item['total_pendapatan'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-bar-chart text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Belum ada data produk terjual pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>