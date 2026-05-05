<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero__inner text-center">
            <span class="eyebrow">Akun Saya</span>
            <h1 class="page-hero__title">Edit Profil</h1>
            <p class="page-hero__lead">Kelola informasi akun dan keamanan login Anda.</p>
        </div>
    </div>
</section>

<div class="container py-5">

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0 ps-3">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">

        <!-- Profile Info -->
        <div class="col-lg-7">
            <div class="panel p-4">
                <div class="panel__header d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width:48px;height:48px;background:rgba(230,126,34,.12);">
                        <i class="bi bi-person-vcard fs-4" style="color:var(--brand-primary);"></i>
                    </div>
                    <div>
                        <h5 class="panel__title mb-0">Informasi Akun</h5>
                        <small class="text-muted">Perbarui nama, nomor HP, dan alamat Anda</small>
                    </div>
                </div>

                <form method="POST" action="<?= base_url('customer/profile/update') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control bg-light" value="<?= esc($user['email']) ?>"
                            readonly disabled>
                        <div class="form-text">Email tidak dapat diubah.</div>
                    </div>

                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                            value="<?= esc(old('nama_lengkap', $user['nama_lengkap'])) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label fw-semibold">Nomor HP <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" class="form-control" id="no_hp" name="no_hp"
                                value="<?= esc(old('no_hp', $user['no_hp'])) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="alamat_lengkap" class="form-label fw-semibold">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="3"
                            required><?= esc(old('alamat_lengkap', $user['alamat_lengkap'])) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-crimson btn-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-5">
            <div class="panel p-4">
                <div class="panel__header d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width:48px;height:48px;background:rgba(230,126,34,.12);">
                        <i class="bi bi-shield-lock fs-4" style="color:var(--brand-primary);"></i>
                    </div>
                    <div>
                        <h5 class="panel__title mb-0">Ganti Password</h5>
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                </div>

                <?php if (session()->getFlashdata('password_form_open') || session()->getFlashdata('error')): ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <?= esc(session()->getFlashdata('error')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('customer/profile/update-password') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="password_lama" class="form-label fw-semibold">Password Lama <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePass('password_lama', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_baru" class="form-label fw-semibold">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password_baru" name="password_baru"
                                minlength="8" required>
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePass('password_baru', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirm" class="form-label fw-semibold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-crimson btn-pill px-4 w-100">
                        <i class="bi bi-key me-1"></i> Ganti Password
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?= $this->section('scripts') ?>
<script>
function togglePass(id, btn) {
    const inp = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
