<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pdo = Database::pdo();
$statusF = $_GET['status'] ?? '';
$prioridadeF = $_GET['prioridade'] ?? '';

$sql = 'SELECT p.*, i.codigo AS imovel_codigo, u.nome AS autor
        FROM problemas p
        INNER JOIN imoveis i ON i.id = p.imovel_id
        INNER JOIN usuarios u ON u.id = p.criado_por
        WHERE 1=1';
$params = [];

if ($statusF !== '') {
    $sql .= ' AND p.status = ?';
    $params[] = $statusF;
}
if ($prioridadeF !== '') {
    $sql .= ' AND p.prioridade = ?';
    $params[] = $prioridadeF;
}
$sql .= ' ORDER BY FIELD(p.prioridade, \'urgente\',\'alta\',\'media\',\'baixa\'), p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$problemas = $stmt->fetchAll();

$pageTitle = 'Problemas';
$activeMenu = 'problemas';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Problemas', 'Pendências encontradas nas vistorias',
    '<a href="' . e(base_url('problemas/novo.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo</a>');
?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form class="row g-2" method="get">
            <div class="col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Status</option>
                    <?php foreach (['aberto','em_analise','resolvido','cancelado'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusF === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="prioridade" class="form-select form-select-sm">
                    <option value="">Prioridade</option>
                    <?php foreach (['urgente','alta','media','baixa'] as $p): ?>
                    <option value="<?= e($p) ?>" <?= $prioridadeF === $p ? 'selected' : '' ?>><?= e(ucfirst($p)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-sm">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Título</th>
                    <th>Imóvel</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Registrado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$problemas): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum problema.</td></tr>
                <?php else: foreach ($problemas as $p): ?>
                <tr>
                    <td class="fw-semibold"><?= e($p['titulo']) ?></td>
                    <td><?= e($p['imovel_codigo']) ?></td>
                    <td><?= badge_status('prioridade', $p['prioridade']) ?></td>
                    <td><?= badge_status('problema', $p['status']) ?></td>
                    <td class="small text-muted"><?= format_datetime($p['criado_em']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('problemas/detalhes.php?id=' . $p['id'])) ?>">Ver</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
