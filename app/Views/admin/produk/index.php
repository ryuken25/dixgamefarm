<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-md-2">
            <?= $this->include('components/admin_sidebar') ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <span class="eyebrow">Admin</span>
                    <h2 class="fw-bold mb-0" style="color:var(--brand-ink);">Manajemen Produk</h2>
                </div>
                <button class="btn btn-crimson btn-pill" id="btnTambahProduk">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Produk
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <?php
                $totalAktif   = count(array_filter($products, fn($p) => $p['is_active']));
                $stokMenipis  = count(array_filter($products, fn($p) => $p['stok_tersedia'] > 0 && $p['stok_tersedia'] <= 5));
                $stokHabis    = count(array_filter($products, fn($p) => $p['stok_tersedia'] == 0 && !$p['is_preorder']));
                ?>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4 text-center">
                        <i class="bi bi-box-seam fs-3 mb-2 d-block" style="color:var(--brand-primary);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= count($products) ?></h5>
                        <small class="text-muted">Total Produk</small>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4 text-center">
                        <i class="bi bi-check-circle fs-3 mb-2 d-block" style="color:var(--success);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $totalAktif ?></h5>
                        <small class="text-muted">Produk Aktif</small>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4 text-center">
                        <i class="bi bi-exclamation-triangle fs-3 mb-2 d-block" style="color:var(--warning);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $stokMenipis ?></h5>
                        <small class="text-muted">Stok Menipis</small>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="panel p-4 text-center">
                        <i class="bi bi-x-circle fs-3 mb-2 d-block" style="color:var(--danger);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--brand-ink);"><?= $stokHabis ?></h5>
                        <small class="text-muted">Stok Habis</small>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="panel p-0 overflow-hidden">
                <div class="px-4 py-3 d-flex align-items-center justify-content-between"
                    style="border-bottom:1px solid var(--brand-border);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam" style="color:var(--brand-primary);"></i>
                        <h5 class="panel__title mb-0">Daftar Produk</h5>
                    </div>
                    <span class="badge" style="background:rgba(230,126,34,.12); color:var(--brand-primary);">
                        <?= count($products) ?> produk
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="productsTable">
                        <thead style="background:var(--bg-surface);">
                            <tr>
                                <th class="ps-4 py-3" style="width:70px;">Foto</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Dibooking</th>
                                <th>Status</th>
                                <th class="pe-4" style="width:120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        Belum ada produk. Klik "Tambah Produk" untuk menambahkan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product):
                                    $stok = (int)$product['stok_tersedia'];
                                    if ($stok == 0)        $stokClass = 'bg-danger';
                                    elseif ($stok <= 5)    $stokClass = 'bg-warning';
                                    else                   $stokClass = 'bg-success';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php if ($product['foto']): ?>
                                                <img src="<?= base_url($product['foto']) ?>"
                                                    alt="<?= esc($product['nama_ayam']) ?>"
                                                    class="rounded"
                                                    style="width:50px;height:50px;object-fit:cover;">
                                            <?php else: ?>
                                                <div class="rounded d-flex align-items-center justify-content-center"
                                                    style="width:50px;height:50px;background:var(--bg-surface);">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold" style="color:var(--brand-ink);">
                                                <?= esc($product['nama_ayam']) ?>
                                            </div>
                                            <?php if (!empty($product['usia_berat'])): ?>
                                                <small class="text-muted"><?= esc($product['usia_berat']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= esc($product['nama_kategori'] ?? '—') ?></span>
                                        </td>
                                        <td class="fw-bold" style="color:var(--brand-primary);">
                                            Rp <?= number_format($product['harga'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $stokClass ?>"><?= $stok ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= (int)$product['stok_dibooking'] > 0 ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= (int)$product['stok_dibooking'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($product['is_active']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-x-circle me-1"></i>Nonaktif
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($product['is_preorder'])): ?>
                                                <span class="badge bg-info ms-1"
                                                    title="<?= esc($product['estimasi_pre_order'] ?? 'Pre-order') ?>">
                                                    <i class="bi bi-clock-history me-1"></i>PO
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4">
                                            <button class="btn btn-sm btn-outline-crimson btn-pill me-1 edit-product"
                                                data-id="<?= $product['id'] ?>"
                                                data-kategori-id="<?= $product['kategori_id'] ?>"
                                                data-nama="<?= esc($product['nama_ayam']) ?>"
                                                data-usia-berat="<?= esc($product['usia_berat'] ?? '') ?>"
                                                data-harga="<?= $product['harga'] ?>"
                                                data-stok="<?= $product['stok_tersedia'] ?>"
                                                data-deskripsi="<?= esc($product['deskripsi'] ?? '') ?>"
                                                data-foto="<?= esc($product['foto'] ?? '') ?>"
                                                data-active="<?= $product['is_active'] ? '1' : '0' ?>"
                                                data-preorder="<?= !empty($product['is_preorder']) ? '1' : '0' ?>"
                                                data-estimasi="<?= esc($product['estimasi_pre_order'] ?? '') ?>"
                                                title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-pill delete-product"
                                                data-id="<?= $product['id'] ?>"
                                                data-nama="<?= esc($product['nama_ayam']) ?>"
                                                data-booked="<?= (int)$product['stok_dibooking'] ?>"
                                                title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-color:var(--brand-border); border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--brand-border);">
                <h5 class="modal-title fw-bold" id="modalTitle" style="color:var(--brand-ink);">Tambah Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productForm" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" id="productId" name="product_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="kategori_id" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori_id" name="kategori_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= esc($category['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="error-kategori_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="nama_ayam" class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_ayam" name="nama_ayam"
                                placeholder="Contoh: Ayam Bangkok Super" required>
                            <div class="invalid-feedback" id="error-nama_ayam"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="usia_berat" class="form-label fw-semibold">Usia / Berat</label>
                            <input type="text" class="form-control" id="usia_berat" name="usia_berat"
                                placeholder="Contoh: 3-4 bulan / 1.5 kg">
                        </div>
                        <div class="col-md-6">
                            <label for="harga" class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga" name="harga"
                                    min="0" step="500" placeholder="0" required>
                            </div>
                            <div class="invalid-feedback" id="error-harga"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="stok_tersedia" class="form-label fw-semibold">Stok Tersedia <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok_tersedia" name="stok_tersedia"
                                min="0" value="0" required>
                            <div class="invalid-feedback" id="error-stok_tersedia"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="foto" class="form-label fw-semibold">Foto Produk</label>
                            <input type="file" class="form-control" id="foto" name="foto"
                                accept="image/jpg,image/jpeg,image/png">
                            <div class="form-text">Format: JPG, JPEG, PNG. Maks. 2MB</div>
                            <div class="invalid-feedback" id="error-foto"></div>
                        </div>
                        <div class="col-12" id="fotoPreviewContainer" style="display:none;">
                            <label class="form-label fw-semibold">Preview</label>
                            <div class="d-flex align-items-center gap-2">
                                <img id="fotoPreview" src="" alt="Preview" class="rounded"
                                    style="max-width:160px; max-height:120px; object-fit:cover; border:1px solid var(--brand-border);">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-pill" id="removeFotoPreview">
                                    <i class="bi bi-x me-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="deskripsi" class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                placeholder="Deskripsi produk (opsional)"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="is_active">Produk Aktif</label>
                            </div>
                            <div class="form-text">Produk nonaktif tidak tampil di katalog.</div>
                        </div>
                        <div class="col-12">
                            <hr style="border-color:var(--brand-border);">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_preorder" name="is_preorder" value="1">
                                <label class="form-check-label fw-semibold" for="is_preorder">
                                    <i class="bi bi-clock-history me-1"></i>Produk Pre-Order (DOC)
                                </label>
                            </div>
                            <div class="form-text">Jika aktif, produk bisa dipesan meskipun stok = 0.</div>
                        </div>
                        <div class="col-12" id="preorder_estimasi_container" style="display:none;">
                            <label for="estimasi_pre_order" class="form-label fw-semibold">Estimasi Siap</label>
                            <input type="text" class="form-control" id="estimasi_pre_order" name="estimasi_pre_order"
                                placeholder='Contoh: "2-3 minggu setelah konfirmasi"'>
                            <div class="form-text">Ditampilkan ke pelanggan sebagai estimasi fulfillment pre-order.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--brand-border);">
                <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-crimson btn-pill" id="saveProduct">
                    <i class="bi bi-check-lg me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-color:var(--brand-border); border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--brand-border);">
                <h5 class="modal-title fw-bold" style="color:var(--danger);">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle fs-1 d-block mb-3" style="color:var(--danger);"></i>
                <p>Yakin ingin menghapus produk <strong id="deleteProductName"></strong>?</p>
                <small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>
            </div>
            <div class="modal-footer justify-content-center" style="border-top:1px solid var(--brand-border);">
                <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-pill" id="confirmDelete">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    let isEdit = false;
    let deleteProductId = null;

    function clearValidation() {
        $('#productForm .is-invalid').removeClass('is-invalid');
        $('#productForm .invalid-feedback').text('');
    }

    function showValidationErrors(errors) {
        clearValidation();
        for (const [field, message] of Object.entries(errors)) {
            $('#' + field).addClass('is-invalid');
            $('#error-' + field).text(message);
        }
    }

    $('#btnTambahProduk').click(function () {
        isEdit = false;
        $('#productForm')[0].reset();
        $('#productId').val('');
        $('#modalTitle').text('Tambah Produk');
        $('#is_active').prop('checked', true);
        $('#is_preorder').prop('checked', false);
        $('#preorder_estimasi_container').hide();
        $('#estimasi_pre_order').val('');
        $('#fotoPreviewContainer').hide();
        $('#fotoPreview').attr('src', '');
        clearValidation();
        $('#productModal').modal('show');
    });

    $('#is_preorder').on('change', function () {
        $(this).is(':checked')
            ? $('#preorder_estimasi_container').slideDown(150)
            : $('#preorder_estimasi_container').slideUp(150).find('#estimasi_pre_order').val('');
    });

    $('#foto').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            showToast('Ukuran file maksimal 2MB', 'error');
            $(this).val('');
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            $('#fotoPreview').attr('src', e.target.result);
            $('#fotoPreviewContainer').show();
        };
        reader.readAsDataURL(file);
    });

    $('#removeFotoPreview').click(function () {
        $('#foto').val('');
        $('#fotoPreview').attr('src', '');
        $('#fotoPreviewContainer').hide();
    });

    $(document).on('click', '.edit-product', function () {
        isEdit = true;
        clearValidation();
        const preorder = String($(this).data('preorder')) === '1';

        $('#productId').val($(this).data('id'));
        $('#kategori_id').val($(this).data('kategori-id'));
        $('#nama_ayam').val($(this).data('nama'));
        $('#usia_berat').val($(this).data('usia-berat'));
        $('#harga').val($(this).data('harga'));
        $('#stok_tersedia').val($(this).data('stok'));
        $('#deskripsi').val($(this).data('deskripsi'));
        $('#is_active').prop('checked', String($(this).data('active')) === '1');
        $('#is_preorder').prop('checked', preorder);
        $('#estimasi_pre_order').val($(this).data('estimasi') || '');
        $('#preorder_estimasi_container').toggle(preorder);
        $('#modalTitle').text('Edit Produk');

        const foto = $(this).data('foto');
        if (foto) {
            $('#fotoPreview').attr('src', '<?= base_url() ?>/' + foto);
            $('#fotoPreviewContainer').show();
        } else {
            $('#fotoPreview').attr('src', '');
            $('#fotoPreviewContainer').hide();
        }
        $('#foto').val('');
        $('#productModal').modal('show');
    });

    $('#saveProduct').click(function () {
        const formData = new FormData($('#productForm')[0]);
        const productId = $('#productId').val();
        if (!$('#is_active').is(':checked')) formData.set('is_active', '0');
        if (!$('#is_preorder').is(':checked')) formData.set('is_preorder', '0');

        const url = productId
            ? '<?= base_url('admin/produk/update') ?>/' + productId
            : '<?= base_url('admin/produk/create') ?>';

        const btn = $(this);
        const orig = btn.html();
        showLoading(btn);

        $.ajax({
            url, method: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#productModal').modal('hide');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(res.message, 'error');
                }
            },
            error: function (xhr) {
                const res = xhr.responseJSON;
                if (res && res.errors) {
                    showValidationErrors(res.errors);
                    showToast('Periksa kembali data yang diisi', 'error');
                } else {
                    showToast(res && res.message ? res.message : 'Terjadi kesalahan.', 'error');
                }
            },
            complete: () => hideLoading(btn, orig)
        });
    });

    $(document).on('click', '.delete-product', function () {
        const booked = $(this).data('booked');
        if (booked > 0) {
            showToast('Tidak dapat menghapus produk dengan stok dibooking (' + booked + ' unit)', 'error');
            return;
        }
        deleteProductId = $(this).data('id');
        $('#deleteProductName').text($(this).data('nama'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').click(function () {
        if (!deleteProductId) return;
        const btn = $(this);
        const orig = btn.html();
        showLoading(btn);

        $.ajax({
            url: '<?= base_url('admin/produk/delete') ?>/' + deleteProductId,
            method: 'POST', dataType: 'json',
            data: { <?= csrf_token() ?>: '<?= csrf_hash() ?>' },
            success: function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#deleteModal').modal('hide');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(res.message, 'error');
                }
            },
            error: function (xhr) {
                showToast(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            },
            complete: function () {
                hideLoading(btn, orig);
                deleteProductId = null;
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
