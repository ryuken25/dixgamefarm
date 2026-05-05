<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$menunggu = 0;
$diproses = 0;
$selesai = 0;
foreach ($orders as $o) {
    if (in_array($o['status_pesanan'], ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI']))
        $menunggu++;
    elseif (in_array($o['status_pesanan'], ['DIPROSES', 'PESANAN_SIAP', 'DIKIRIM']))
        $diproses++;
    elseif ($o['status_pesanan'] === 'SELESAI')
        $selesai++;
}
$statusClass = [
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
// Payment-aware override labels (set per row using latest_payment_status)
$paymentStatusOverride = [
    'PENDING' => ['label' => 'Menunggu Validasi', 'class' => 'info'],
    'INVALID' => ['label' => 'Bukti Ditolak', 'class' => 'danger'],
];
?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero__inner text-center">
            <span class="eyebrow">Akun Pelanggan</span>
            <h1 class="page-hero__title">Selamat datang, <?= esc(session()->get('nama_lengkap')) ?>!</h1>
            <p class="page-hero__lead">Pantau semua pesanan dan riwayat transaksi Anda di sini.</p>
        </div>
    </div>
</section>

<div class="container py-5">

    <!-- Stats row -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="panel p-4 text-center">
                <div class="mb-2">
                    <i class="bi bi-bag-check fs-2" style="color:var(--brand-primary);"></i>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= count($orders) ?></h3>
                <p class="text-muted small mb-0">Total Pesanan</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="panel p-4 text-center">
                <div class="mb-2">
                    <i class="bi bi-hourglass-split fs-2" style="color:var(--warning);"></i>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $menunggu ?></h3>
                <p class="text-muted small mb-0">Menunggu Bayar</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="panel p-4 text-center">
                <div class="mb-2">
                    <i class="bi bi-truck fs-2" style="color:var(--info);"></i>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $diproses ?></h3>
                <p class="text-muted small mb-0">Diproses / Dikirim</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="panel p-4 text-center">
                <div class="mb-2">
                    <i class="bi bi-check-circle fs-2" style="color:var(--success);"></i>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $selesai ?></h3>
                <p class="text-muted small mb-0">Selesai</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-4">
        <a href="<?= base_url('customer/profile') ?>" class="btn btn-outline-crimson btn-pill btn-sm">
            <i class="bi bi-person-gear me-1"></i> Edit Profil
        </a>
        <a href="<?= base_url('katalog') ?>" class="btn btn-crimson btn-pill btn-sm">
            <i class="bi bi-grid me-1"></i> Lihat Katalog
        </a>
    </div>

    <!-- Orders Table -->
    <div class="panel p-0 overflow-hidden">
        <div class="px-4 py-3 d-flex align-items-center gap-2" style="border-bottom:1px solid var(--brand-border);">
            <i class="bi bi-list-ul" style="color:var(--brand-primary);"></i>
            <h5 class="panel__title mb-0">Daftar Pesanan</h5>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5 px-3">
                <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                <h5 style="color:var(--brand-ink);">Belum Ada Pesanan</h5>
                <p class="text-muted">Mulai berbelanja dan temukan ayam berkualitas.</p>
                <a href="<?= base_url('katalog') ?>" class="btn btn-crimson btn-pill px-4 mt-1">
                    <i class="bi bi-grid me-1"></i> Lihat Katalog
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:var(--bg-surface);">
                        <tr>
                            <th class="ps-4 py-3">No. Invoice</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $key = (string) ($order['status_pesanan'] ?? '');
                            $latestPmt = (string) ($order['latest_payment_status'] ?? '');
                            // Override badge for MENUNGGU_BAYAR when payment exists
                            if ($key === 'MENUNGGU_BAYAR' && isset($paymentStatusOverride[$latestPmt])) {
                                $badgeClass = $paymentStatusOverride[$latestPmt]['class'];
                                $badgeLabel = $paymentStatusOverride[$latestPmt]['label'];
                            } else {
                                $badgeClass = $statusClass[$key] ?? 'secondary';
                                $badgeLabel = $statusLabels[$key] ?? str_replace('_', ' ', $key);
                            }
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold" style="color:var(--brand-ink);">
                                    <span class="invoice-code"><?= esc($order['nomor_invoice']) ?></span>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y H:i', strtotime($order['tanggal_pesanan'])) ?>
                                </td>
                                <td class="fw-bold" style="color:var(--brand-primary);">
                                    Rp <?= number_format($order['grand_total'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <?= esc($badgeLabel) ?>
                                    </span>
                                </td>
                                <td class="pe-4">
                                    <a href="<?= base_url("customer/order/{$order['id']}") ?>"
                                        class="btn btn-sm btn-outline-crimson btn-pill">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?= $this->endSection() ?>