<?php
declare(strict_types=1);

// Em produção (Render) usa HTTPS — secure = true
$isProduction = !empty($_SERVER['HTTPS']) || 
                (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
                str_contains($_SERVER['HTTP_HOST'] ?? '', 'onrender.com');

return [
    'name'     => 'ONECHECK_SESSID',
    'lifetime' => 60 * 60 * 8,
    'secure'   => $isProduction,
    'httponly' => true,
    'samesite' => 'Lax',
];
