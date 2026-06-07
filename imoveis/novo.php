<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = ImovelService::parsePostImovel($_POST);
    $erro = ImovelService::validar($d, true);
    if (!$erro) {
        try {
            $geo = isset($_POST['geocodificar']);
            $id = ImovelService::criar($d, $geo);
            flash_set('success', 'Imóvel cadastrado com cômodos padrão.');
            redirect(base_url('imoveis/detalhes.php?id=' . $id));
        } catch (PDOException $e) {
            $erro = 'Não foi possível salvar. Código já existe?';
        }
    }
}

$pageTitle = 'Novo imóvel';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
page_header('Novo imóvel', 'RF03–RF05 · endereço com geolocalização',
    '<a href="' . e(base_url('imoveis/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" class="imovel-form">
            <?php require __DIR__ . '/_form_campos.php'; ?>
            <div class="mt-3">
                <button class="btn btn-primary">Salvar imóvel</button>
            </div>
        </form>
    </div>
</div>

<script>document.body.dataset.baseUrl = <?= json_encode(base_url('')) ?>;</script>
<script src="<?= e(asset_url('js/imoveis-form.js')) ?>"></script>
<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
