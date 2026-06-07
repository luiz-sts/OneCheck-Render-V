<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('admin');

$user = Auth::user();

$usuarios = Database::pdo()->query(
    'SELECT id, nome, email, perfil, ativo, mfa_enabled, mfa_obrigatorio, criado_em FROM usuarios ORDER BY nome'
)->fetchAll();

$pageTitle = 'Usuários';
$activeMenu = 'usuarios';
require ONECHECK_ROOT . '/includes/header.php';
flash_render();
page_header('Usuários', 'Equipe com acesso ao painel e ao app mobile',
    '<a href="' . e(base_url('usuarios/novo.php')) . '" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo usuário</a>');
?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>MFA</th>
                    <th>Ativo</th>
                    <th>Cadastro</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= e($u['nome']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= badge_status('perfil', $u['perfil']) ?></td>
                    <td>
                        <?php if ((int) ($u['mfa_enabled'] ?? 0)): ?>
                        <span class="badge text-bg-success" title="MFA ativo">2FA</span>
                        <?php elseif ((int) ($u['mfa_obrigatorio'] ?? 0)): ?>
                        <span class="badge text-bg-warning text-dark">Pendente</span>
                        <?php else: ?>
                        <span class="badge text-bg-light text-dark">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $u['ativo']): ?>
                        <span class="badge text-bg-success">Sim</span>
                        <?php else: ?>
                        <span class="badge text-bg-secondary">Não</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= format_date($u['criado_em']) ?></td>
                    <td class="text-end">
                        <?php if ((int) $u['id'] === (int) $user['id']): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('usuarios/perfil.php')) ?>">Meu perfil</a>
                        <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('usuarios/editar.php?id=' . $u['id'])) ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
