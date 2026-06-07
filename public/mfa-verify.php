<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::homeUrl());
}

$pending = Auth::mfaPending();
if (!$pending || $pending['mode'] !== 'verify') {
    redirect(base_url('public/login.php'));
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if (Auth::verifyMfaAndLogin((int) $pending['user_id'], $code)) {
        redirect(Auth::homeUrl());
    }
    $erro = 'Código inválido. Tente novamente.';
}

$user = Auth::fetchUserById((int) $pending['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificação MFA · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
    <div class="card shadow-lg border-0" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 mb-1 text-center">Autenticação em 2 fatores</h1>
            <p class="text-muted text-center small mb-4">
                Olá, <?= e($user['nome'] ?? '') ?>. Informe o código do seu app autenticador.
            </p>

            <?php if ($erro): ?>
            <div class="alert alert-danger py-2"><?= e($erro) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="mb-4">
                    <label class="form-label" for="code">Código de 6 dígitos</label>
                    <input type="text" class="form-control form-control-lg text-center" id="code" name="code"
                           inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
                           placeholder="000000">
                </div>
                <button type="submit" class="btn btn-primary w-100">Verificar</button>
            </form>
            <p class="text-center mt-3 mb-0">
                <a href="<?= e(base_url('public/login.php')) ?>" class="small">Voltar ao login</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
