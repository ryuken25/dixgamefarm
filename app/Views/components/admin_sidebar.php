<?php
$uri = uri_string();
$pendingCount = 0; // will be overridden if passed via $pendingPayments
if (!empty($pendingPayments)) $pendingCount = count($pendingPayments);
if (!empty($stats['pending_payments'])) $pendingCount = (int)$stats['pending_payments'];

$navItems = [
    ['admin/dashboard',                'bi-speedometer2',  'Dashboard',            false],
    ['admin/kategori',                 'bi-tags',          'Kategori',             false],
    ['admin/produk',                   'bi-box-seam',      'Produk',               false],
    ['admin/pesanan',                  'bi-cart-check',    'Pesanan',              false],
    ['admin/pesanan/pending-payments', 'bi-credit-card',   'Verifikasi Bayar',     true],
    ['admin/laporan',                  'bi-graph-up',      'Laporan',              false],
];
?>
<div class="panel p-0 overflow-hidden sticky-top" style="top:80px;">
    <div class="px-3 py-3" style="background:var(--brand-primary);">
        <span class="text-white fw-bold small text-uppercase" style="letter-spacing:.08em;">Admin Panel</span>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($navItems as [$path, $icon, $label, $showBadge]): ?>
            <a href="<?= base_url($path) ?>"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3
                      <?= str_starts_with($uri, trim($path, '/')) ? 'active' : '' ?>">
                <i class="bi <?= $icon ?> fs-6"></i>
                <span class="flex-grow-1"><?= $label ?></span>
                <?php if ($showBadge && $pendingCount > 0): ?>
                    <span class="badge bg-danger"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
