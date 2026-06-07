<?php
$menu = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'speedometer2', 'url' => 'dashboard/index.php'],
    ['id' => 'imoveis', 'label' => 'Imóveis', 'icon' => 'houses', 'url' => 'imoveis/index.php'],
    ['id' => 'vistorias', 'label' => 'Vistorias', 'icon' => 'camera', 'url' => 'vistorias/index.php'],
    ['id' => 'contratos', 'label' => 'Contratos', 'icon' => 'file-earmark-text', 'url' => 'contratos/index.php'],
    ['id' => 'problemas', 'label' => 'Problemas', 'icon' => 'exclamation-triangle', 'url' => 'problemas/index.php'],
    ['id' => 'usuarios', 'label' => 'Usuários', 'icon' => 'people', 'url' => 'usuarios/index.php'],
];
?>
<?php
function sidebar_nav(string $activeMenu): void
{
    global $menu;
    echo '<nav class="nav flex-column gap-1 px-2">';
    foreach ($menu as $item) {
        $active = $activeMenu === $item['id'] ? 'active' : '';
        echo '<a class="nav-link rounded ' . $active . '" href="' . e(base_url($item['url'])) . '">';
        echo '<i class="bi bi-' . e($item['icon']) . ' me-2"></i>' . e($item['label']) . '</a>';
    }
    echo '</nav><hr class="mx-3"><p class="px-3 small text-muted mb-0">API mobile em <code>/api</code> para o APK Kotlin enviar fotos das vistorias.</p>';
}
?>
<aside class="col-lg-2 col-xl-2 d-none d-lg-block border-end bg-white app-sidebar py-3">
    <?php sidebar_nav($activeMenu); ?>
</aside>

<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMobile">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <?php sidebar_nav($activeMenu); ?>
    </div>
</div>
