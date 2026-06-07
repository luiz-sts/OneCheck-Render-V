<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::homeUrl());
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $result = Auth::loginWithPassword($email, $senha);
        match ($result['status']) {
            'ok'         => redirect(Auth::homeUrl()),
            'mfa_verify' => redirect(base_url('public/mfa-verify.php')),
            'mfa_setup'  => redirect(base_url('public/mfa-setup.php')),
            default      => $erro = 'Credenciais inválidas.',
        };
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
    <div class="card shadow-lg border-0" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 mb-1 text-center">OneCheck</h1>
            <p class="text-muted text-center small mb-4">Passo 1 — e-mail e senha</p>

            <?php if ($erro): ?>
            <div class="alert alert-danger py-2"><?= e($erro) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Continuar</button>
            </form>
            <p class="text-muted small text-center mt-3 mb-0">
                Admin e vistoriador: MFA obrigatório após o primeiro acesso.
            </p>
        </div>
    </div>
</div>
</body>
</html>
