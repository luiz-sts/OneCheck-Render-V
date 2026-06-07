<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pdo = Database::pdo();
$statusF = $_GET['status'] ?? '';
$busca = trim($_GET['q'] ?? '');

$sql = 'SELECT c.*, i.codigo AS imovel_codigo, i.titulo AS imovel_titulo
        FROM contratos c
        INNER JOIN imoveis i ON i.id = c.imovel_id WHERE 1=1';
$params = [];

if ($statusF !== '') {
    $sql .= ' AND c.status = ?';
    $params[] = $statusF;
}
if ($busca !== '') {
    $sql .= ' AND (c.numero LIKE ? OR c.locatario_nome LIKE ? OR i.codigo LIKE ?)';
    $like = '%' . $busca . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
$sql .= ' ORDER BY c.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contratos = $stmt->fetchAll();

$pageTitle = 'Contratos';
$activeMenu = 'contratos';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Contratos', 'Locação, anexos e acompanhamento',
    '<a href="' . e(base_url('contratos/novo.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo contrato</a>');
?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Número, locatário, imóvel..."
                       value="<?= e($busca) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos os status</option>
                    <?php foreach (['rascunho','ativo','encerrado','cancelado'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statusF === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="<?= e(base_url('contratos/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Número</th>
                    <th>Imóvel</th>
                    <th>Locatário</th>
                    <th>Valor</th>
                    <th>Início</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$contratos): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum contrato.</td></tr>
                <?php else: foreach ($contratos as $c): ?>
                <tr>
                    <td class="fw-semibold"><?= e($c['numero']) ?></td>
                    <td>
                        <div><?= e($c['imovel_codigo']) ?></div>
                        <div class="small text-muted"><?= e($c['imovel_titulo']) ?></div>
                    </td>
                    <td><?= e($c['locatario_nome']) ?></td>
                    <td><?= format_money((float) $c['valor_aluguel']) ?></td>
                    <td><?= format_date($c['data_inicio']) ?></td>
                    <td><?= badge_status('contrato', $c['status']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('contratos/detalhes.php?id=' . $c['id'])) ?>">Detalhes</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
