<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$cfg = ImovelService::config();
$pdo = Database::pdo();
$busca = trim($_GET['q'] ?? '');
$statusF = $_GET['status'] ?? '';

$sql = 'SELECT i.*,
        (SELECT COUNT(*) FROM vistorias v WHERE v.imovel_id = i.id) AS total_vistorias,
        (SELECT COUNT(*) FROM imovel_comodos c WHERE c.imovel_id = i.id AND c.ativo = 1) AS total_comodos,
        e.latitude, e.longitude
        FROM imoveis i
        LEFT JOIN enderecos e ON e.imovel_id = i.id AND e.principal = 1
        WHERE 1=1';
$params = [];

if ($busca !== '') {
    $sql .= ' AND (i.codigo LIKE ? OR i.titulo LIKE ? OR i.endereco LIKE ? OR i.cidade LIKE ?)';
    $like = '%' . $busca . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($statusF !== '') {
    $sql .= ' AND i.status = ?';
    $params[] = $statusF;
}
$sql .= ' ORDER BY i.codigo ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$imoveis = $stmt->fetchAll();

$pageTitle = 'Imóveis';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Imóveis', 'Cadastro com endereço, cômodos e mapa',
    '<a href="' . e(base_url('imoveis/mapa.php')) . '" class="btn btn-outline-primary btn-sm"><i class="bi bi-map me-1"></i>Mapa</a>'
    . '<a href="' . e(base_url('imoveis/novo.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo imóvel</a>');
?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-5">
                <label class="form-label small mb-1">Buscar</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Código, título, endereço..."
                       value="<?= e($busca) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status (RF07)</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($cfg['status'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $statusF === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="<?= e(base_url('imoveis/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Título / Endereço</th>
                    <th>m²</th>
                    <th>Status</th>
                    <th>GPS</th>
                    <th>Cômodos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$imoveis): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum imóvel encontrado.</td></tr>
                <?php else: foreach ($imoveis as $i): ?>
                <tr>
                    <td class="fw-semibold"><?= e($i['codigo']) ?></td>
                    <td>
                        <div><?= e($i['titulo']) ?></div>
                        <div class="small text-muted"><?= e($i['endereco']) ?></div>
                    </td>
                    <td class="small"><?= $i['tamanho_m2'] ? e((string) $i['tamanho_m2']) : '—' ?></td>
                    <td><?= badge_status('imovel', $i['status']) ?></td>
                    <td>
                        <?php if ($i['latitude']): ?>
                        <i class="bi bi-geo-alt-fill text-success" title="Com coordenadas"></i>
                        <?php else: ?>
                        <i class="bi bi-geo-alt text-muted" title="Sem GPS"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $i['total_comodos'] ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('imoveis/detalhes.php?id=' . $i['id'])) ?>">Ver</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('imoveis/editar.php?id=' . $i['id'])) ?>">Editar</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
