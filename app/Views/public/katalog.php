<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero__inner text-center">
            <span class="eyebrow"><i class="bi bi-grid-3x3-gap-fill"></i> Katalog Produk</span>
            <h1 class="page-hero__title">Produk Segar Langsung dari Peternakan</h1>
            <p class="page-hero__lead">
                Pilihan ayam kampung super, DOC, telur, dan pakan berkualitas.
                Stok diperbarui setiap hari.
            </p>
        </div>
    </div>
</section>

<div class="container my-5">

    <!-- Filter Section -->
    <div class="panel mb-4 py-3">
        <form method="GET" action="<?= base_url('katalog') ?>" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1" for="filter-keyword">Cari produk</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="filter-keyword" type="text" name="keyword" class="form-control"
                        placeholder="Ketik nama produk…" value="<?= esc($keyword ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" for="filter-kategori">Kategori</label>
                <select id="filter-kategori" name="kategori" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($selected_kategori == $cat['id']) ? 'selected' : '' ?>>
                            <?= esc($cat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-crimson">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="panel text-center py-5">
            <i class="bi bi-search display-4 text-brand mb-3"></i>
            <h3 class="mb-2">Tidak ada produk yang ditemukan</h3>
            <p class="text-muted mb-3">Coba kata kunci lain atau ubah filter kategori.</p>
            <a href="<?= base_url('katalog') ?>" class="btn btn-outline-crimson btn-pill">
                <i class="bi bi-arrow-counterclockwise"></i> Reset filter
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card product-card h-100">
                        <?php if ($product['foto']): ?>
                            <img src="<?= base_url($product['foto']) ?>" class="card-img-top"
                                alt="<?= esc($product['nama_ayam']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 220px; background: linear-gradient(135deg, #FBF6F0, #F3EADD); color: #C99B6A;">
                                <i class="bi bi-image fs-1"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <span class="badge bg-success mb-2"><?= esc($product['nama_kategori']) ?></span>
                            <h5 class="product-title"><?= esc($product['nama_ayam']) ?></h5>

                            <?php if ($product['usia_berat']): ?>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-info-circle"></i> <?= esc($product['usia_berat']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="product-price mb-0">Rp <?= number_format($product['harga'], 0, ',', '.') ?></span>

                                <?php if (!empty($product['is_preorder'])): ?>
                                    <span class="badge bg-info">Pre-Order</span>
                                <?php elseif ($product['stok_tersedia'] >= 5): ?>
                                    <span class="badge bg-success">Tersedia</span>
                                <?php elseif ($product['stok_tersedia'] > 0): ?>
                                    <span class="badge bg-warning">Terbatas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Habis</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($product['is_preorder']) && !empty($product['estimasi_pre_order'])): ?>
                                <p class="text-info small mb-2">
                                    <i class="bi bi-clock-history"></i> Estimasi siap: <?= esc($product['estimasi_pre_order']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-grid gap-2">
                                <a href="<?= base_url("katalog/detail/{$product['id']}") ?>"
                                    class="btn btn-outline-crimson btn-sm">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </a>
                                <?php
                                $canBuy = !empty($product['is_preorder']) || $product['stok_tersedia'] > 0;
                                ?>
                                <?php if (session()->get('role') === 'PELANGGAN' && $canBuy): ?>
                                    <button type="button"
                                        class="btn <?= !empty($product['is_preorder']) ? 'btn-info text-white' : 'btn-crimson' ?> btn-sm add-to-cart"
                                        data-bs-toggle="modal" data-bs-target="#modalAddCart" data-id="<?= $product['id'] ?>"
                                        data-nama="<?= esc($product['nama_ayam']) ?>" data-harga="<?= $product['harga'] ?>"
                                        data-stok="<?= (int) $product['stok_tersedia'] ?>"
                                        data-preorder="<?= !empty($product['is_preorder']) ? '1' : '0' ?>"
                                        data-estimasi="<?= esc($product['estimasi_pre_order'] ?? '') ?>">
                                        <?php if (!empty($product['is_preorder'])): ?>
                                            <i class="bi bi-bag-plus"></i> Pesan (Pre-Order)
                                        <?php else: ?>
                                            <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                        <?php endif; ?>
                                    </button>
                                <?php elseif (!session()->get('isLoggedIn') && $canBuy): ?>
                                    <a href="<?= base_url('auth/login') ?>" class="btn btn-crimson btn-sm">
                                        <i class="bi bi-box-arrow-in-right"></i> Login untuk Beli
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (session()->get('role') === 'PELANGGAN'): ?>
    <!-- Add to Cart Modal -->
    <div class="modal fade" id="modalAddCart" tabindex="-1" aria-labelledby="modalAddCartLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-crimson text-white">
                    <h5 class="modal-title" id="modalAddCartLabel">
                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal-produk-id">
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Produk</label>
                        <h5 class="fw-bold mb-0" id="modal-nama"></h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Harga Satuan</label>
                        <div class="text-crimson fw-bold fs-5" id="modal-harga"></div>
                    </div>
                    <div class="mb-3">
                        <label for="modal-qty" class="form-label fw-bold">Jumlah Pesanan</label>
                        <div class="input-group input-group-lg">
                            <button class="btn btn-outline-secondary" type="button" id="modal-qty-minus">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="number" class="form-control text-center fw-bold" id="modal-qty" value="1" min="1">
                            <button class="btn btn-outline-secondary" type="button" id="modal-qty-plus">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1" id="modal-stok-info">
                            Stok tersedia: <strong id="modal-stok">0</strong> pcs
                        </small>
                        <small class="text-info d-block mt-1" id="modal-preorder-info" style="display: none;">
                            <i class="bi bi-clock-history"></i> Pre-Order — Estimasi siap: <strong
                                id="modal-estimasi"></strong>
                        </small>
                        <div id="modal-qty-error" class="text-danger small mt-1" style="display: none;">
                            <i class="bi bi-exclamation-circle"></i> Jumlah melebihi stok tersedia
                        </div>
                    </div>
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Subtotal:</span>
                            <span class="text-crimson fw-bold fs-5" id="modal-subtotal">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Batal
                    </button>
                    <button type="button" class="btn btn-crimson" id="modal-add-btn">
                        <i class="bi bi-cart-plus"></i> Tambahkan
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        const $modal = $('#modalAddCart');
        const $qty = $('#modal-qty');
        const $nama = $('#modal-nama');
        const $harga = $('#modal-harga');
        const $stok = $('#modal-stok');
        const $subtotal = $('#modal-subtotal');
        const $err = $('#modal-qty-error');
        const $addBtn = $('#modal-add-btn');

        let currentStok = 1;
        let currentHarga = 0;
        let currentProdukId = null;
        let currentPreorder = false;

        function formatRp(n) {
            return 'Rp ' + parseInt(n, 10).toLocaleString('id-ID');
        }

        function clampQty(v) {
            v = parseInt(v, 10);
            if (isNaN(v) || v < 1) v = 1;
            // Preorder items have no stock cap; cap only when not preorder
            if (!currentPreorder && v > currentStok) v = currentStok;
            return v;
        }

        function recompute() {
            const v = parseInt($qty.val(), 10);
            const withinStock = currentPreorder ? true : (v <= currentStok);
            const valid = !isNaN(v) && v >= 1 && withinStock;
            $err.toggle(!valid);
            $addBtn.prop('disabled', !valid);
            $subtotal.text(formatRp((valid ? v : 0) * currentHarga));
        }

        // Populate modal when opened
        $modal.on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            currentProdukId = button.data('id');
            currentStok = parseInt(button.data('stok'), 10) || 0;
            currentHarga = parseFloat(button.data('harga')) || 0;
            currentPreorder = String(button.data('preorder')) === '1';
            const estimasi = button.data('estimasi') || '';

            $nama.text(button.data('nama'));
            $harga.text(formatRp(currentHarga));

            if (currentPreorder) {
                $('#modal-stok-info').hide();
                $('#modal-preorder-info').show();
                $('#modal-estimasi').text(estimasi || '-');
                $qty.removeAttr('max');
                $addBtn.html('<i class="bi bi-bag-plus"></i> Pesan (Pre-Order)');
            } else {
                $('#modal-stok-info').show();
                $('#modal-preorder-info').hide();
                $stok.text(currentStok);
                $qty.attr('max', Math.max(currentStok, 1));
                $addBtn.html('<i class="bi bi-cart-plus"></i> Tambahkan');
            }

            $qty.val(1);
            recompute();
        });

        $('#modal-qty-minus').on('click', function () {
            $qty.val(clampQty(parseInt($qty.val(), 10) - 1));
            recompute();
        });

        $('#modal-qty-plus').on('click', function () {
            $qty.val(clampQty(parseInt($qty.val(), 10) + 1));
            recompute();
        });

        $qty.on('input change', function () {
            $qty.val(clampQty($qty.val()));
            recompute();
        });

        $addBtn.off('click.cartAdd').on('click.cartAdd', function () {
            const jumlah = parseInt($qty.val(), 10);
            if (!currentProdukId || isNaN(jumlah) || jumlah < 1) return;
            if (!currentPreorder && jumlah > currentStok) return;

            const btn = $(this);
            const originalText = btn.html();
            if (btn.data('processing')) return;
            btn.data('processing', true);
            showLoading(btn);

            $.ajax({
                url: '<?= base_url('customer/cart/add') ?>',
                method: 'POST',
                data: appendCsrfTokenToData({
                    produk_id: currentProdukId,
                    jumlah: jumlah
                }),
                dataType: 'json',
                success: function (response) {
                    updateCsrfTokenFromResponse(response);
                    if (response.success) {
                        showToast('Produk berhasil ditambahkan ke keranjang!', 'success');
                        updateCartCount();
                        bootstrap.Modal.getInstance($modal[0]).hide();
                    } else {
                        showToast(response.message || 'Gagal menambahkan ke keranjang', 'error');
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 401) {
                        window.location.href = '<?= base_url('auth/login') ?>';
                    } else {
                        const msg = resolveAjaxErrorMessage(xhr, 'Terjadi kesalahan. Silakan coba lagi.');
                        showToast(msg, 'error');
                    }
                },
                complete: function () {
                    btn.data('processing', false);
                    hideLoading(btn, originalText);
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>