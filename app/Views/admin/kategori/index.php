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
                    <h2 class="fw-bold mb-0" style="color:var(--brand-ink);">Manajemen Kategori</h2>
                </div>
                <button class="btn btn-crimson btn-pill" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
                </button>
            </div>

            <div class="panel p-0 overflow-hidden">
                <div class="px-4 py-3 d-flex align-items-center justify-content-between"
                    style="border-bottom:1px solid var(--brand-border);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-tags" style="color:var(--brand-primary);"></i>
                        <h5 class="panel__title mb-0">Daftar Kategori</h5>
                    </div>
                    <span class="badge" style="background:rgba(230,126,34,.12); color:var(--brand-primary);">
                        <?= count($categories) ?> kategori
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="categoriesTable">
                        <thead style="background:var(--bg-surface);">
                            <tr>
                                <th class="ps-4 py-3">#</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Produk</th>
                                <th>Dibuat</th>
                                <th class="pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $category['id'] ?></td>
                                    <td class="fw-semibold" style="color:var(--brand-ink);">
                                        <?= esc($category['nama_kategori']) ?>
                                    </td>
                                    <td class="text-muted small"><?= esc($category['deskripsi']) ?: '—' ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $category['jumlah_produk'] ?? 0 ?> produk
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d/m/Y', strtotime($category['created_at'])) ?>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <button class="btn btn-sm btn-outline-crimson btn-pill me-1 edit-category"
                                            data-id="<?= $category['id'] ?>"
                                            data-nama="<?= esc($category['nama_kategori']) ?>"
                                            data-deskripsi="<?= esc($category['deskripsi']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-pill delete-category"
                                            data-id="<?= $category['id'] ?>"
                                            data-nama="<?= esc($category['nama_kategori']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-color:var(--brand-border); border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--brand-border);">
                <h5 class="modal-title fw-bold" id="modalTitle" style="color:var(--brand-ink);">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <?= csrf_field() ?>
                    <input type="hidden" id="categoryId" name="category_id">

                    <div class="mb-3">
                        <label for="nama_kategori" class="form-label fw-semibold">
                            Nama Kategori <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label fw-semibold">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--brand-border);">
                <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-crimson btn-pill" id="saveCategory">
                    <i class="bi bi-save me-1"></i> Simpan
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

    $('#categoryModal').on('show.bs.modal', function () {
        if (!isEdit) {
            $('#categoryForm')[0].reset();
            $('#categoryId').val('');
            $('#modalTitle').text('Tambah Kategori');
        }
        isEdit = false;
    });

    $('.edit-category').click(function () {
        isEdit = true;
        $('#categoryId').val($(this).data('id'));
        $('#nama_kategori').val($(this).data('nama'));
        $('#deskripsi').val($(this).data('deskripsi'));
        $('#modalTitle').text('Edit Kategori');
        $('#categoryModal').modal('show');
    });

    $('#saveCategory').click(function () {
        const formData = new FormData($('#categoryForm')[0]);
        const categoryId = $('#categoryId').val();
        const url = categoryId
            ? '<?= base_url('admin/kategori/update') ?>/' + categoryId
            : '<?= base_url('admin/kategori/create') ?>';

        const btn = $(this);
        showLoading(btn);

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    $('#categoryModal').modal('hide');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    const errors = response.errors
                        ? Object.values(response.errors).join('\n')
                        : (response.message || 'Validasi gagal');
                    showToast(errors, 'error');
                }
            },
            error: function (xhr) {
                const res = xhr.responseJSON;
                const msg = res && res.errors
                    ? Object.values(res.errors).join('\n')
                    : (res && res.message ? res.message : 'Terjadi kesalahan.');
                showToast(msg, 'error');
            },
            complete: function () {
                hideLoading(btn, '<i class="bi bi-save me-1"></i> Simpan');
            }
        });
    });

    $('.delete-category').click(function () {
        const id   = $(this).data('id');
        const nama = $(this).data('nama');

        if (!confirm('Yakin ingin menghapus kategori "' + nama + '"?')) return;

        $.ajax({
            url: '<?= base_url('admin/kategori/delete') ?>/' + id,
            method: 'POST',
            dataType: 'json',
            data: { <?= csrf_token() ?>: '<?= csrf_hash() ?>' },
            success: function (response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(response.message || 'Gagal menghapus.', 'error');
                }
            },
            error: function () {
                showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
