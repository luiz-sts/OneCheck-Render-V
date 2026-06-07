<?php
/** @var string $pageTitle */
/** @var string|null $activeMenu */
$pageTitle = $pageTitle ?? 'OneCheck';
$activeMenu = $activeMenu ?? '';
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · OneCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-body">
<?php require ONECHECK_ROOT . '/includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php require ONECHECK_ROOT . '/includes/sidebar.php'; ?>
        <main class="col-lg-10 col-xl-10 ms-auto px-4 py-4 app-main">
