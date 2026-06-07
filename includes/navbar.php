<nav class="navbar navbar-dark bg-primary shadow-sm app-navbar">
    <div class="container-fluid">
        <button class="btn btn-outline-light btn-sm d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-semibold" href="<?= e(base_url('dashboard/index.php')) ?>">
            <i class="bi bi-building-check me-2"></i>OneCheck
        </a>
        <?php if ($user): ?>
        <div class="d-flex align-items-center gap-2 text-white-50 small">
            <a class="btn btn-sm btn-outline-light d-none d-md-inline" href="<?= e(base_url('usuarios/perfil.php')) ?>"><?= e($user['nome']) ?></a>
            <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('public/logout.php')) ?>">Sair</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
