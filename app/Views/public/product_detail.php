<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="row">
                        <div class="col-md-6">
                            <?php if ($product['foto']): ?>
                                <img src="<?= base_url($product['foto']) ?>" class="img-fluid rounded-xl shadow-soft" alt="<?= esc($product['nama_ayam']) ?>">
                            <?php else: ?>
                                <div class="rounded-xl d-flex align-items-center justify-content-center shadow-soft"
                                     style="height: 300px; background: linear-gradient(135deg, #FBF6F0, #F3EADD); color: #C99B6A;">
                                    <i class="bi bi-image display-1"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <span class="badge bg-success fs-6 mb-2"><?= esc($product['nama_kategori']) ?></span>
                                <h1 class="fw-bold"><?= esc($product['nama_ayam']) ?></h1>
                            </div>

                            <?php if ($product['usia_berat']): ?>
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Usia/Berat</h6>
                                    <p class="mb-0"><?= esc($product['usia_berat']) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Harga</h6>
                                <h2 class="text-crimson fw-bold">Rp <?= number_format($product['harga'], 0, ',', '.') ?></h2>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Ketersediaan Stok</h6>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <?php if (!empty($product['is_preorder'])): ?>
                                        <span class="badge bg-info fs-6">Pre-Order</span>
                                        <?php if (!empty($product['estimasi_pre_order'])): ?>
                                            <span class="text-muted">Estimasi siap: <?= esc($product['estimasi_pre_order']) ?></span>
                                        <?php endif; ?>
                                    <?php elseif ($product['stok_tersedia'] >= 5): ?>
                                        <span class="badge bg-success fs-6">Tersedia</span>
                                        <span class="text-muted">Stok: <?= $product['stok_tersedia'] ?></span>
                                    <?php elseif ($product['stok_tersedia'] > 0): ?>
                                        <span class="badge bg-warning fs-6">Terbatas</span>
                                        <span class="text-muted">Stok: <?= $product['stok_tersedia'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger fs-6">Habis</span>
                                        <span class="text-muted">Stok habis</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($product['is_preorder'])): ?>
                                    <div class="alert alert-info mt-3 mb-0 small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Produk pre-order dipesan dulu. Admin akan mengkonfirmasi ketersediaan & jadwal fulfillment setelah pembayaran terverifikasi.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php $canBuy = !empty($product['is_preorder']) || $product['stok_tersedia'] > 0; ?>
                            <?php if (session()->get('role') === 'PELANGGAN' && $canBuy): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Jumlah Pesanan</label>
                                        <div class="input-group input-group-lg">
                                            <button class="btn btn-outline-secondary" type="button" id="qty-minus">
                                                <i class="bi bi-dash-lg"></i>
                                            </button>
                                            <input type="number"
                                                   class="form-control text-center fw-bold fs-5"
                                                   id="quantity"
                                                   value="1"
                                                   min="1"
                                                   <?php if (empty($product['is_preorder'])): ?>max="<?= (int) $product['stok_tersedia'] ?>"<?php endif; ?>
                                                   data-stok="<?= (int) $product['stok_tersedia'] ?>"
                                                   data-preorder="<?= !empty($product['is_preorder']) ? '1' : '0' ?>">
                                            <button class="btn btn-outline-secondary" type="button" id="qty-plus">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($product['is_preorder'])): ?>
                                            <small class="text-info d-block mt-1">
                                                <i class="bi bi-clock-history"></i> Jumlah tidak dibatasi stok — produk akan disiapkan sesuai pesanan
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted d-block mt-1">
                                                Maksimal <strong><?= (int) $product['stok_tersedia'] ?></strong> pcs sesuai stok tersedia
                                            </small>
                                        <?php endif; ?>
                                        <div id="qty-error" class="text-danger small mt-1" style="display: none;">
                                            <i class="bi bi-exclamation-circle"></i> Jumlah tidak boleh melebihi stok
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn <?= !empty($product['is_preorder']) ? 'btn-info text-white' : 'btn-crimson' ?> btn-lg" id="add-to-cart" data-id="<?= $product['id'] ?>">
                                        <?php if (!empty($product['is_preorder'])): ?>
                                            <i class="bi bi-bag-plus"></i> Pesan (Pre-Order)
                                        <?php else: ?>
                                            <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                        <?php endif; ?>
                                    </button>
                                    <a href="<?= base_url('katalog') ?>" class="btn btn-outline-crimson">
                                        <i class="bi bi-arrow-left"></i> Kembali ke Katalog
                                    </a>
                                </div>
                            <?php elseif (!session()->get('isLoggedIn') && $canBuy): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <a href="<?= base_url('auth/login') ?>" class="alert-link">Login</a> untuk menambahkan ke keranjang
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Stok sedang habis
                                </div>
                                <a href="<?= base_url('katalog') ?>" class="btn btn-outline-crimson">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Katalog
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($product['deskripsi']): ?>
                        <div class="row mt-5">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3">Deskripsi Produk</h5>
                                <div class="text-muted">
                                    <?= nl2br(esc($product['deskripsi'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    const $qty = $('#quantity');
    if (!$qty.length) return;
    const maxStok = parseInt($qty.data('stok'), 10) || 0;
    const isPreorder = String($qty.data('preorder')) === '1';

    function clampQty(val) {
        val = parseInt(val, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (!isPreorder && val > maxStok) val = maxStok;
        return val;
    }

    function validateQty() {
        const v = parseInt($qty.val(), 10);
        const overStock = !isPreorder && v > maxStok;
        if (isNaN(v) || v < 1 || overStock) {
            $('#qty-error').show();
            $('#add-to-cart').prop('disabled', true);
            return false;
        }
        $('#qty-error').hide();
        $('#add-to-cart').prop('disabled', false);
        return true;
    }

    // Quantity controls
    $('#qty-minus').on('click', function() {
        $qty.val(clampQty(parseInt($qty.val(), 10) - 1));
        validateQty();
    });

    $('#qty-plus').on('click', function() {
        $qty.val(clampQty(parseInt($qty.val(), 10) + 1));
        validateQty();
    });

    $qty.on('input change', function() {
        $qty.val(clampQty($qty.val()));
        validateQty();
    });

    // Add to cart
    $('#add-to-cart').off('click.cartAdd').on('click.cartAdd', function(e) {
        e.preventDefault();
        if (!validateQty()) return;

        const produkId = $(this).data('id');
        const jumlah = parseInt($qty.val(), 10);
        const btn = $(this);
        const originalText = btn.html();
        if (btn.data('processing')) return;
        btn.data('processing', true);

        showLoading(btn);

        $.ajax({
            url: '<?= base_url('customer/cart/add') ?>',
            method: 'POST',
            data: appendCsrfTokenToData({
                produk_id: produkId,
                jumlah: jumlah
            }),
            dataType: 'json',
            success: function(response) {
                updateCsrfTokenFromResponse(response);
                if (response.success) {
                    showToast('Produk berhasil ditambahkan ke keranjang!', 'success');
                    updateCartCount();
                    $qty.val(1);
                } else {
                    showToast(response.message || 'Gagal menambahkan ke keranjang', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    window.location.href = '<?= base_url('auth/login') ?>';
                } else {
                    const msg = resolveAjaxErrorMessage(xhr, 'Terjadi kesalahan. Silakan coba lagi.');
                    showToast(msg, 'error');
                }
            },
            complete: function() {
                btn.data('processing', false);
                hideLoading(btn, originalText);
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
