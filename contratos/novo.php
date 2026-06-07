<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pdo = Database::pdo();
$imoveis = $pdo->query('SELECT id, codigo, titulo FROM imoveis ORDER BY codigo')->fetchAll();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imovelId = (int) ($_POST['imovel_id'] ?? 0);
    $numero = post_str('numero');
    $locatario = post_str('locatario_nome');
    $doc = post_str('locatario_documento');
    $valor = (float) str_replace(['.', ','], ['', '.'], $_POST['valor_aluguel'] ?? '0');
    $inicio = $_POST['data_inicio'] ?? '';
    $fim = $_POST['data_fim'] ?? '';
    $status = $_POST['status'] ?? 'rascunho';
    $obs = post_str('observacoes');

    if ($imovelId < 1 || $numero === '' || $locatario === '' || $valor <= 0 || $inicio === '') {
        $erro = 'Preencha os campos obrigatórios.';
    } else {
        try {
            $pdo->prepare(
                'INSERT INTO contratos (imovel_id, numero, locatario_nome, locatario_documento, valor_aluguel, data_inicio, data_fim, status, observacoes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $imovelId, $numero, $locatario, $doc ?: null, $valor, $inicio,
                $fim !== '' ? $fim : null, $status, $obs ?: null,
            ]);
            if ($status === 'ativo') {
                $pdo->prepare("UPDATE imoveis SET status = 'ocupado' WHERE id = ?")->execute([$imovelId]);
            }
            flash_set('success', 'Contrato cadastrado.');
            redirect(base_url('contratos/index.php'));
        } catch (PDOException $e) {
            $erro = 'Número de contrato já existe ou dados inválidos.';
        }
    }
}

$pageTitle = 'Novo contrato';
$activeMenu = 'contratos';
require ONECHECK_ROOT . '/includes/header.php';
?>

<?php page_header('Novo contrato', '', '<a href="' . e(base_url('contratos/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>'); ?>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!$imoveis): ?>
        <div class="alert alert-warning">Cadastre um imóvel antes de criar o contrato.</div>
        <?php else: ?>
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Número do contrato</label>
                <input name="numero" class="form-control" required placeholder="CTR-2026-001">
            </div>
            <div class="col-md-8">
                <label class="form-label">Imóvel</label>
                <select name="imovel_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($imoveis as $i): ?>
                    <option value="<?= (int) $i['id'] ?>"><?= e($i['codigo']) ?> — <?= e($i['titulo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Locatário</label>
                <input name="locatario_nome" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">CPF/CNPJ</label>
                <input name="locatario_documento" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Valor aluguel (R$)</label>
                <input name="valor_aluguel" class="form-control" required placeholder="1500,00">
            </div>
            <div class="col-md-3">
                <label class="form-label">Início</label>
                <input type="date" name="data_inicio" class="form-control" required value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fim (opcional)</label>
                <input type="date" name="data_fim" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="rascunho">Rascunho</option>
                    <option value="ativo">Ativo</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Observações</label>
                <textarea name="observacoes" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Salvar contrato</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
