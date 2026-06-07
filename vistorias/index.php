<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pdo = Database::pdo();
$imovelId = isset($_GET['imovel_id']) ? (int) $_GET['imovel_id'] : null;
$statusF = $_GET['status'] ?? '';

$sql = 'SELECT v.*, i.codigo, i.titulo, u.nome AS vistoriador,
        (SELECT COUNT(*) FROM vistoria_fotos f WHERE f.vistoria_id = v.id) AS total_fotos
     FROM vistorias v
     INNER JOIN imoveis i ON i.id = v.imovel_id
     INNER JOIN usuarios u ON u.id = v.usuario_id
     WHERE 1=1';
$params = [];

if ($imovelId) {
    $sql .= ' AND v.imovel_id = ?';
    $params[] = $imovelId;
}
if ($statusF !== '') {
    $sql .= ' AND v.status = ?';
    $params[] = $statusF;
}
$sql .= ' ORDER BY v.id DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vistorias = $stmt->fetchAll();

$pageTitle = 'Vistorias';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Vistorias', 'Entrada, saída e vistorias periódicas',
    '<a href="' . e(base_url('vistorias/fotos.php')) . '" class="btn btn-outline-primary btn-sm"><i class="bi bi-images me-1"></i>Fotos</a>'
    . '<a href="' . e(base_url('vistorias/nova.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova</a>');
?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach (['rascunho','em_andamento','concluida','cancelada'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusF === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Imóvel</th>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Vistoriador</th>
                    <th>Status</th>
                    <th>Fotos</th>
                    <th>Origem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$vistorias): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma vistoria.</td></tr>
                <?php else: foreach ($vistorias as $v): ?>
                <tr>
                    <td><?= (int) $v['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($v['codigo']) ?></div>
                        <div class="small text-muted"><?= e($v['titulo']) ?></div>
                    </td>
                    <td><?= e($v['tipo']) ?></td>
                    <td><?= format_date($v['data_vistoria']) ?></td>
                    <td class="small"><?= e($v['vistoriador']) ?></td>
                    <td><?= badge_status('vistoria', $v['status']) ?></td>
                    <td>
                        <span class="badge text-bg-light text-dark"><?= (int) $v['total_fotos'] ?></span>
                    </td>
                    <td>
                        <?php if ((int) $v['sincronizado_mobile']): ?>
                        <i class="bi bi-phone text-primary" title="Mobile"></i>
                        <?php else: ?>
                        <i class="bi bi-globe text-secondary" title="Web"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('vistorias/detalhes.php?id=' . $v['id'])) ?>">Detalhes</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('vistorias/fotos.php?vistoria_id=' . $v['id'])) ?>">Fotos</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
