<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();
if ((Auth::user()['perfil'] ?? '') === 'locatario') {
    redirect(base_url('locatario/index.php'));
}

$pdo = Database::pdo();

$stats = [
    'imoveis'    => (int) $pdo->query('SELECT COUNT(*) FROM imoveis')->fetchColumn(),
    'vistorias'  => (int) $pdo->query("SELECT COUNT(*) FROM vistorias WHERE status != 'cancelada'")->fetchColumn(),
    'fotos'      => (int) $pdo->query('SELECT COUNT(*) FROM vistoria_fotos')->fetchColumn(),
    'contratos'  => (int) $pdo->query("SELECT COUNT(*) FROM contratos WHERE status = 'ativo'")->fetchColumn(),
    'problemas'  => (int) $pdo->query("SELECT COUNT(*) FROM problemas WHERE status = 'aberto'")->fetchColumn(),
];

$ultimasFotos = $pdo->query(
    'SELECT f.id, f.comodo, f.arquivo_path, f.origem, f.criado_em,
            v.id AS vistoria_id, i.codigo AS imovel_codigo
     FROM vistoria_fotos f
     INNER JOIN vistorias v ON v.id = f.vistoria_id
     INNER JOIN imoveis i ON i.id = v.imovel_id
     ORDER BY f.criado_em DESC LIMIT 6'
)->fetchAll();

$problemasUrgentes = $pdo->query(
    "SELECT p.id, p.titulo, p.prioridade, i.codigo
     FROM problemas p INNER JOIN imoveis i ON i.id = p.imovel_id
     WHERE p.status IN ('aberto','em_analise') AND p.prioridade IN ('urgente','alta')
     ORDER BY FIELD(p.prioridade,'urgente','alta') LIMIT 5"
)->fetchAll();

$vistoriasRecentes = $pdo->query(
    'SELECT v.id, v.tipo, v.status, v.data_vistoria, i.codigo
     FROM vistorias v INNER JOIN imoveis i ON i.id = v.imovel_id
     ORDER BY v.id DESC LIMIT 5'
)->fetchAll();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Dashboard', 'Visão geral do OneCheck');
?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Imóveis', 'value' => $stats['imoveis'], 'icon' => 'houses', 'color' => 'primary', 'url' => 'imoveis/index.php'],
        ['label' => 'Vistorias', 'value' => $stats['vistorias'], 'icon' => 'camera', 'color' => 'success', 'url' => 'vistorias/index.php'],
        ['label' => 'Fotos', 'value' => $stats['fotos'], 'icon' => 'image', 'color' => 'info', 'url' => 'vistorias/fotos.php'],
        ['label' => 'Contratos ativos', 'value' => $stats['contratos'], 'icon' => 'file-earmark-text', 'color' => 'warning', 'url' => 'contratos/index.php'],
        ['label' => 'Problemas abertos', 'value' => $stats['problemas'], 'icon' => 'exclamation-triangle', 'color' => 'danger', 'url' => 'problemas/index.php'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="col-6 col-md-4 col-xl">
        <a href="<?= e(base_url($c['url'])) ?>" class="text-decoration-none">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small"><?= e($c['label']) ?></div>
                            <div class="fs-3 fw-bold text-dark"><?= (int) $c['value'] ?></div>
                        </div>
                        <i class="bi bi-<?= e($c['icon']) ?> text-<?= e($c['color']) ?> fs-3"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <span class="fw-semibold">Últimas fotos (mobile / web)</span>
                <a href="<?= e(base_url('vistorias/fotos.php')) ?>" class="small">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (!$ultimasFotos): ?>
                <p class="text-muted small mb-0">Nenhuma foto. O APK envia para <code>POST /api/vistorias/upload.php</code>.</p>
                <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($ultimasFotos as $foto): ?>
                    <div class="col-4 col-md-4">
                        <div class="card photo-card h-100">
                            <img src="<?= e(base_url($foto['arquivo_path'])) ?>" alt="">
                            <div class="card-body p-2 small">
                                <strong><?= e($foto['comodo']) ?></strong>
                                <div class="text-muted" style="font-size:.7rem"><?= e($foto['imovel_codigo']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Problemas urgentes</div>
            <ul class="list-group list-group-flush">
                <?php if (!$problemasUrgentes): ?>
                <li class="list-group-item text-muted small">Nenhum problema urgente.</li>
                <?php else: foreach ($problemasUrgentes as $pr): ?>
                <li class="list-group-item d-flex justify-content-between small">
                    <a href="<?= e(base_url('problemas/detalhes.php?id=' . $pr['id'])) ?>"><?= e($pr['titulo']) ?></a>
                    <?= badge_status('prioridade', $pr['prioridade']) ?>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Vistorias recentes</div>
            <ul class="list-group list-group-flush">
                <?php if (!$vistoriasRecentes): ?>
                <li class="list-group-item text-muted small">Nenhuma vistoria.</li>
                <?php else: foreach ($vistoriasRecentes as $vr): ?>
                <li class="list-group-item d-flex justify-content-between small">
                    <span>#<?= (int) $vr['id'] ?> · <?= e($vr['codigo']) ?> (<?= e($vr['tipo']) ?>)</span>
                    <a href="<?= e(base_url('vistorias/detalhes.php?id=' . $vr['id'])) ?>"><?= badge_status('vistoria', $vr['status']) ?></a>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
        <div class="mt-3 d-grid gap-2">
            <a href="<?= e(base_url('imoveis/novo.php')) ?>" class="btn btn-outline-primary btn-sm">+ Imóvel</a>
            <a href="<?= e(base_url('vistorias/nova.php')) ?>" class="btn btn-outline-success btn-sm">+ Vistoria</a>
            <a href="<?= e(base_url('contratos/novo.php')) ?>" class="btn btn-outline-warning btn-sm">+ Contrato</a>
        </div>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
