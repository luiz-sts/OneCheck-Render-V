<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$imoveis = Database::pdo()->query('SELECT id, codigo, titulo FROM imoveis ORDER BY codigo')->fetchAll();
$erro = '';

$preImovel = get_int('imovel_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imovelId = (int) ($_POST['imovel_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? 'entrada';
    $data = $_POST['data_vistoria'] ?? date('Y-m-d');

    if ($imovelId < 1) {
        $erro = 'Selecione um imóvel.';
    } else {
        Database::pdo()->prepare(
            'INSERT INTO vistorias (imovel_id, usuario_id, tipo, status, data_vistoria)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$imovelId, $user['id'], $tipo, 'em_andamento', $data]);
        redirect(base_url('vistorias/index.php'));
    }
}

$pageTitle = 'Nova vistoria';
$activeMenu = 'vistorias';
require ONECHECK_ROOT . '/includes/header.php';
?>

<h1 class="h3 mb-4">Nova vistoria</h1>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<?php if (!$imoveis): ?>
<div class="alert alert-warning">Cadastre um imóvel antes de criar a vistoria.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Imóvel</label>
                <select name="imovel_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($imoveis as $i): ?>
                    <option value="<?= (int) $i['id'] ?>" <?= $preImovel === (int) $i['id'] ? 'selected' : '' ?>>
                        <?= e($i['codigo']) ?> — <?= e($i['titulo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                    <option value="periodica">Periódica</option>
                    <option value="extra">Extra</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data</label>
                <input type="date" name="data_vistoria" class="form-control" value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Criar vistoria</button>
                <a href="<?= e(base_url('vistorias/index.php')) ?>" class="btn btn-link">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
