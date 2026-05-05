<?php
$uri = uri_string(); // e.g. "" for home, "katalog", "tentang", "kontak"
$isHome   = ($uri === '' || $uri === '/');
$isKatalog = str_starts_with($uri, 'katalog');
$isTentang = str_starts_with($uri, 'tentang');
$isKontak  = str_starts_with($uri, 'kontak');
?>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= base_url('/') ?>">
            <img src="<?= base_url('img/logo.png') ?>" alt="DIX Game Farm" height="40" width="40"
                class="me-2 rounded-circle">
            DIX Game Farm
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= $isHome ? 'active' : '' ?>" href="<?= base_url('/') ?>">
                        <i class="bi bi-house-door"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isKatalog ? 'active' : '' ?>" href="<?= base_url('katalog') ?>">
                        <i class="bi bi-grid"></i> Katalog
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isTentang ? 'active' : '' ?>" href="<?= base_url('tentang') ?>">
                        <i class="bi bi-info-circle"></i> Tentang
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isKontak ? 'active' : '' ?>" href="<?= base_url('kontak') ?>">
                        <i class="bi bi-telephone"></i> Kontak
                    </a>
                </li>

                <?php if (session()->get('isLoggedIn')): ?>
                    <?php if (session()->get('role') === 'PELANGGAN'): ?>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?= base_url('customer/cart') ?>">
                                <i class="bi bi-cart3"></i> Keranjang
                                <span class="badge bg-crimson position-absolute top-0 start-100 translate-middle rounded-pill"
                                    id="cart-count" style="display:none;">0</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?= esc(session()->get('nama_lengkap')) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= base_url('customer/dashboard') ?>">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a></li>
                                <li><a class="dropdown-item" href="<?= base_url('customer/profile') ?>">
                                        <i class="bi bi-person-gear"></i> Edit Profil
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?= base_url('auth/logout') ?>">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-shield-check"></i> Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= base_url('admin/dashboard') ?>">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?= base_url('auth/logout') ?>">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($uri, 'auth/login') ? 'active' : '' ?>"
                            href="<?= base_url('auth/login') ?>">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-crimson btn-pill btn-sm px-3" href="<?= base_url('auth/register') ?>">
                            Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (session()->get('role') === 'PELANGGAN'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkJQuery = setInterval(function () {
                if (typeof jQuery !== 'undefined') {
                    clearInterval(checkJQuery);
                    updateCartCount();
                }
            }, 50);
        });

        function updateCartCount() {
            $.get('<?= base_url('customer/cart/count') ?>', function (data) {
                var $badge = $('#cart-count');
                if (data.count > 0) {
                    $badge.text(data.count).show();
                } else {
                    $badge.hide();
                }
            });
        }
    </script>
<?php endif; ?>
