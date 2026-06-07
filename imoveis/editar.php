<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$id = get_int('id');
$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT * FROM imoveis WHERE id = ?');
$stmt->execute([$id]);
$imovel = $stmt->fetch();

if (!$imovel) {
    flash_set('error', 'Imóvel não encontrado.');
    redirect(base_url('imoveis/index.php'));
}

$end = ImovelService::getEnderecoPrincipal($id) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = ImovelService::parsePostImovel($_POST);
    $d['codigo'] = $imovel['codigo'];
    $erro = ImovelService::validar($d, false);
    if ($erro) {
        flash_set('error', $erro);
    } else {
        ImovelService::atualizar($id, $d, isset($_POST['geocodificar']));
        flash_set('success', 'Imóvel atualizado.');
        redirect(base_url('imoveis/detalhes.php?id=' . $id));
    }
}

$pageTitle = 'Editar imóvel';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Editar ' . $imovel['codigo'], '',
    '<a href="' . e(base_url('imoveis/detalhes.php?id=' . $id)) . '" class="btn btn-link btn-sm">Cancelar</a>');
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post">
            <?php $mostrarCodigo = false;
            require __DIR__ . '/_form_campos.php'; ?>
            <div class="mt-3">
                <button class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<script>document.body.dataset.baseUrl = <?= json_encode(base_url('')) ?>;</script>
<script src="<?= e(asset_url('js/imoveis-form.js')) ?>"></script>
<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
