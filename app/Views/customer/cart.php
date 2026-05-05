<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero__inner text-center">
            <span class="eyebrow"><i class="bi bi-cart3"></i> Belanja</span>
            <h1 class="page-hero__title">Keranjang Belanja</h1>
        </div>
    </div>
</section>

<div class="container my-5">

    <?php if (empty($cartData['items'])): ?>
        <div class="panel text-center py-5">
            <i class="bi bi-cart-x display-4 text-brand mb-3"></i>
            <h3 class="mb-2">Keranjang Anda Kosong</h3>
            <p class="text-muted mb-4">Belum ada produk di keranjang. Yuk mulai belanja!</p>
            <a href="<?= base_url('katalog') ?>" class="btn btn-crimson btn-pill px-4">
                <i class="bi bi-grid"></i> Lihat Katalog
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">

            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="panel p-0 overflow-hidden">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-list-ul text-brand me-1"></i>
                            <?= count($cartData['items']) ?> Item dalam Keranjang
                        </h6>
                        <button class="btn btn-sm btn-outline-danger" id="clear-cart">
                            <i class="bi bi-trash"></i> Kosongkan
                        </button>
                    </div>

                    <div id="cart-items-list">
                        <?php foreach ($cartData['items'] as $item): ?>
                            <div class="cart-item px-4 py-3 border-bottom" data-item-id="<?= $item['id'] ?>">
                                <div class="row align-items-center g-3">
                                    <div class="col-3 col-md-2">
                                        <?php if ($item['foto']): ?>
                                            <img src="<?= base_url($item['foto']) ?>"
                                                class="img-fluid rounded-xl shadow-soft"
                                                alt="<?= esc($item['nama_ayam']) ?>"
                                                style="aspect-ratio:1;object-fit:cover;">
                                        <?php else: ?>
                                            <div class="rounded-xl d-flex align-items-center justify-content-center"
                                                style="height:72px;background:linear-gradient(135deg,#FBF6F0,#F3EADD);color:#C99B6A;">
                                                <i class="bi bi-image fs-4"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-9 col-md-4">
                                        <p class="fw-bold mb-0 lh-sm">
                                            <?= esc($item['nama_ayam']) ?>
                                            <?php if (!empty($item['is_preorder'])): ?>
                                                <span class="badge bg-info ms-1">PO</span>
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted"><?= esc($item['nama_kategori']) ?></small>
                                        <?php if (!empty($item['is_preorder'])): ?>
                                            <br><small class="text-brand">
                                                <i class="bi bi-clock-history"></i>
                                                <?= !empty($item['estimasi_pre_order']) ? esc($item['estimasi_pre_order']) : 'Pre-Order' ?>
                                            </small>
                                        <?php else: ?>
                                            <br><small class="text-muted">Stok: <?= (int) $item['stok_tersedia'] ?></small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-6 col-md-3">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary update-qty"
                                                data-id="<?= $item['id'] ?>" data-action="decrease"
                                                <?= $item['jumlah'] <= 1 ? 'disabled' : '' ?>>
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" class="form-control text-center fw-bold qty-input"
                                                value="<?= $item['jumlah'] ?>" min="1"
                                                <?php if (empty($item['is_preorder'])): ?>max="<?= (int) $item['stok_tersedia'] ?>"<?php endif; ?>
                                                data-id="<?= $item['id'] ?>" readonly>
                                            <button class="btn btn-outline-secondary update-qty"
                                                data-id="<?= $item['id'] ?>" data-action="increase"
                                                <?php if (empty($item['is_preorder']) && $item['jumlah'] >= $item['stok_tersedia']): ?>disabled<?php endif; ?>>
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-4 col-md-2 text-end">
                                        <p class="fw-bold text-brand mb-0">
                                            Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                        </p>
                                        <small class="text-muted">@<?= number_format($item['harga'], 0, ',', '.') ?></small>
                                    </div>

                                    <div class="col-2 col-md-1 text-end">
                                        <button class="btn btn-sm btn-outline-danger remove-item"
                                            data-id="<?= $item['id'] ?>" title="Hapus item">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="panel sticky-top" style="top: 90px;">
                    <h6 class="fw-bold mb-3"><i class="bi bi-receipt text-brand me-1"></i> Ringkasan Belanja</h6>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Item</span>
                        <span class="fw-semibold"><?= count($cartData['items']) ?> item</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold fs-5">Total Harga</span>
                        <span class="fw-bold fs-5 text-brand">Rp <?= number_format($cartData['total'], 0, ',', '.') ?></span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="<?= base_url('customer/checkout') ?>" class="btn btn-crimson btn-lg btn-pill">
                            <i class="bi bi-credit-card"></i> Lanjut Checkout
                        </a>
                        <a href="<?= base_url('katalog') ?>" class="btn btn-outline-crimson btn-pill">
                            <i class="bi bi-arrow-left"></i> Lanjut Belanja
                        </a>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    let isCartBusy = false;

    function postCart(url, data, onSuccess) {
        if (isCartBusy) {
            return;
        }

        isCartBusy = true;
        $.ajax({
            url: url,
            method: 'POST',
            data: appendCsrfTokenToData(data),
            dataType: 'json',
            success: function (response) {
                updateCsrfTokenFromResponse(response);
                if (response.success) {
                    onSuccess(response);
                } else {
                    showToast(response.message || 'Terjadi kesalahan', 'error');
                }
            },
            error: function (xhr) {
                showToast(resolveAjaxErrorMessage(xhr, 'Terjadi kesalahan. Silakan coba lagi.'), 'error');
            },
            complete: function () {
                isCartBusy = false;
            }
        });
    }

    // Increase / decrease quantity buttons
    $(document).on('click', '.update-qty', function () {
        const $btn = $(this);
        const itemId = $btn.data('id');
        const action = $btn.data('action');
        const $input = $(`.qty-input[data-id="${itemId}"]`);
        const maxVal = parseInt($input.attr('max')) || Infinity;
        let newQty = parseInt($input.val()) + (action === 'increase' ? 1 : -1);

        if (newQty < 1) return;
        if (newQty > maxVal) {
            showToast('Jumlah melebihi stok tersedia', 'error');
            return;
        }

        postCart('<?= base_url('customer/cart/update') ?>', { item_id: itemId, jumlah: newQty }, function () {
            location.reload();
        });
    });

    // Remove item
    $(document).on('click', '.remove-item', function () {
        const itemId = $(this).data('id');
        confirmAction('Hapus item ini dari keranjang?', function () {
            postCart('<?= base_url('customer/cart/remove') ?>', { item_id: itemId }, function () {
                location.reload();
            });
        });
    });

    // Clear entire cart
    $('#clear-cart').on('click', function () {
        confirmAction('Kosongkan seluruh keranjang?', function () {
            postCart('<?= base_url('customer/cart/clear') ?>', {}, function () {
                location.reload();
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
