<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('admin');

$erro = '';
$perfis = ['admin', 'gestor', 'vistoriador', 'visualizador', 'locatario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = post_str('nome');
    $email = post_str('email');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'vistoriador';
    $mfaObr = isset($_POST['mfa_obrigatorio']) ? 1 : 0;

    if (!in_array($perfil, $perfis, true)) {
        $erro = 'Perfil inválido.';
    } elseif ($nome === '' || $email === '' || strlen($senha) < 6) {
        $erro = 'Nome, e-mail e senha (mín. 6 caracteres) são obrigatórios.';
    } else {
        if (Mfa::isMandatoryForProfile($perfil)) {
            $mfaObr = 1;
        }
        try {
            Database::pdo()->prepare(
                'INSERT INTO usuarios (uuid, nome, email, senha_hash, perfil, mfa_obrigatorio)
                 VALUES (UUID(), ?, ?, ?, ?, ?)'
            )->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil, $mfaObr]);
            $id = (int) Database::pdo()->lastInsertId();
            AuditLog::record('create', 'usuarios', (string) $id, null, ['email' => $email, 'perfil' => $perfil]);
            flash_set('success', 'Usuário criado.');
            redirect(base_url('usuarios/index.php'));
        } catch (PDOException $e) {
            $erro = 'E-mail já cadastrado.';
        }
    }
}

$pageTitle = 'Novo usuário';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
page_header('Novo usuário', '', '<a href="' . e(base_url('usuarios/index.php')) . '" class="btn btn-link btn-sm">Voltar</a>');
?>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input name="nome" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Senha</label>
                <input type="password" name="senha" class="form-control" required minlength="6">
            </div>
            <div class="col-md-4">
                <label class="form-label">Perfil (RF01)</label>
                <select name="perfil" class="form-select" id="perfil">
                    <?php foreach ($perfis as $pr): ?>
                    <option value="<?= e($pr) ?>"><?= e(ucfirst($pr)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="mfa_obrigatorio" id="mfa_obr" checked>
                    <label class="form-check-label" for="mfa_obr">MFA obrigatório (RNF02)</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Criar usuário</button>
            </div>
        </form>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
