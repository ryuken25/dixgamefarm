<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$badges = [
    'MENUNGGU_BAYAR' => 'warning',
    'MENUNGGU_VERIFIKASI' => 'warning',
    'DIPROSES' => 'primary',
    'PESANAN_SIAP' => 'primary',
    'DIKIRIM' => 'info',
    'SELESAI' => 'success',
    'BATAL' => 'danger',
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
?>

<div class="container-fluid py-4">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="panel p-0 overflow-hidden sticky-top" style="top:80px;">
                <div class="px-3 py-3" style="background:var(--brand-primary);">
                    <span class="text-white fw-bold small text-uppercase ls-wide">Admin Panel</span>
                </div>
                <?php
                $navItems = [
                    ['admin/dashboard', 'bi-speedometer2', 'Dashboard'],
                    ['admin/kategori', 'bi-tags', 'Kategori'],
                    ['admin/produk', 'bi-box-seam', 'Produk'],
                    ['admin/pesanan', 'bi-cart-check', 'Pesanan'],
                    ['admin/pesanan/pending-payments', 'bi-credit-card', 'Verifikasi Bayar'],
                    ['admin/laporan', 'bi-graph-up', 'Laporan'],
                ];
                $uri = uri_string();
                ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($navItems as [$path, $icon, $label]): ?>
                        <a href="<?= base_url($path) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3
                                   <?= str_starts_with($uri, trim($path, '/')) ? 'active' : '' ?>">
                            <i class="bi <?= $icon ?> fs-6"></i>
                            <span><?= $label ?></span>
                            <?php if ($path === 'admin/pesanan/pending-payments' && ($stats['pending_payments'] ?? 0) > 0): ?>
                                <span class="badge bg-danger ms-auto"><?= $stats['pending_payments'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">

            <div class="mb-4">
                <span class="eyebrow">Panel Admin</span>
                <h2 class="fw-bold mb-0" style="color:var(--brand-ink);">Dashboard</h2>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-5">
                <div class="col-6 col-xl-3">
                    <div class="panel p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Pesanan Hari Ini</span>
                            <i class="bi bi-cart-check fs-4" style="color:var(--brand-primary);"></i>
                        </div>
                        <h2 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $stats['total_orders_today'] ?>
                        </h2>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Pending Bayar</span>
                            <i class="bi bi-hourglass-split fs-4" style="color:var(--warning);"></i>
                        </div>
                        <h2 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $stats['pending_payments'] ?></h2>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Stok Rendah</span>
                            <i class="bi bi-exclamation-triangle fs-4" style="color:var(--danger);"></i>
                        </div>
                        <h2 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $stats['low_stock_products'] ?>
                        </h2>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase">Total Pendapatan</span>
                            <i class="bi bi-cash-stack fs-4" style="color:var(--success);"></i>
                        </div>
                        <h5 class="fw-bold mb-0" style="color:var(--brand-ink);">
                            Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?>
                        </h5>
                    </div>
                </div>
            </div>

            <!-- Pending Payments Alert -->
            <?php if (!empty($pending_payments)): ?>
                <div class="panel p-4 mb-4 d-flex align-items-center gap-3"
                    style="border-left:4px solid var(--brand-primary);">
                    <i class="bi bi-bell-fill fs-4" style="color:var(--brand-primary);"></i>
                    <div class="flex-grow-1">
                        <strong style="color:var(--brand-ink);">
                            <?= count($pending_payments) ?> pembayaran menunggu verifikasi
                        </strong>
                        <div class="text-muted small">Periksa dan konfirmasi bukti transfer pelanggan.</div>
                    </div>
                    <a href="<?= base_url('admin/pesanan/pending-payments') ?>"
                        class="btn btn-crimson btn-pill btn-sm flex-shrink-0">
                        Verifikasi <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Recent Orders -->
            <div class="panel p-0 overflow-hidden mb-4">
                <div class="px-4 py-3 d-flex align-items-center gap-2"
                    style="border-bottom:1px solid var(--brand-border);">
                    <i class="bi bi-clock-history" style="color:var(--brand-primary);"></i>
                    <h5 class="panel__title mb-0">Pesanan Terbaru</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background:var(--bg-surface);">
                            <tr>
                                <th class="ps-4 py-3">Invoice</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order):
                                $key = (string) ($order['status_pesanan'] ?? '');
                                ?>
                                <tr>
                                    <td class="ps-4 fw-semibold" style="color:var(--brand-ink);">
                                        <?= esc($order['nomor_invoice']) ?>
                                    </td>
                                    <td><?= esc($order['nama_lengkap']) ?></td>
                                    <td class="text-muted small">
                                        <?= date('d/m/Y H:i', strtotime($order['tanggal_pesanan'])) ?>
                                    </td>
                                    <td class="fw-bold" style="color:var(--brand-primary);">
                                        Rp <?= number_format($order['grand_total'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $badges[$key] ?? 'secondary' ?>">
                                            <?= $statusLabels[$key] ?? str_replace('_', ' ', $key) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4">
                                        <a href="<?= base_url("admin/pesanan/detail/{$order['id']}") ?>"
                                            class="btn btn-sm btn-outline-crimson btn-pill">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Low Stock Products -->
            <?php if (!empty($low_stock_products)): ?>
                <div class="panel p-0 overflow-hidden">
                    <div class="px-4 py-3 d-flex align-items-center gap-2"
                        style="border-bottom:1px solid var(--brand-border); background:rgba(192,57,43,.05);">
                        <i class="bi bi-exclamation-triangle" style="color:var(--danger);"></i>
                        <h5 class="panel__title mb-0" style="color:var(--danger);">Produk Stok Rendah</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead style="background:var(--bg-surface);">
                                <tr>
                                    <th class="ps-4 py-3">Produk</th>
                                    <th>Kategori</th>
                                    <th>Tersedia</th>
                                    <th>Dibooking</th>
                                    <th class="pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold" style="color:var(--brand-ink);">
                                            <?= esc($product['nama_ayam']) ?>
                                        </td>
                                        <td class="text-muted"><?= esc($product['nama_kategori']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $product['stok_tersedia'] == 0 ? 'danger' : 'warning' ?>">
                                                <?= $product['stok_tersedia'] ?>
                                            </span>
                                        </td>
                                        <td><?= $product['stok_dibooking'] ?></td>
                                        <td class="pe-4">
                                            <a href="<?= base_url('admin/produk') ?>"
                                                class="btn btn-sm btn-outline-crimson btn-pill">
                                                <i class="bi bi-pencil me-1"></i> Update
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?= $this->endSection() ?>