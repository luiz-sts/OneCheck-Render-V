<?php
/** @var array $cfg @var array|null $imovel @var array|null $end @var bool $mostrarCodigo */
$cfg = ImovelService::config();
$imovel = $imovel ?? [];
$end = $end ?? [
    'logradouro' => $imovel['endereco'] ?? '',
    'numero' => '', 'complemento' => '', 'bairro' => '',
    'cidade' => $imovel['cidade'] ?? '', 'estado' => $imovel['estado'] ?? '',
    'cep' => $imovel['cep'] ?? '', 'latitude' => null, 'longitude' => null,
];
$mostrarCodigo = $mostrarCodigo ?? !isset($imovel['id']);
?>
<div class="row g-3">
    <?php if ($mostrarCodigo): ?>
    <div class="col-md-3">
        <label class="form-label">Código</label>
        <input name="codigo" class="form-control" required placeholder="APT-101"
               value="<?= e($imovel['codigo'] ?? $_POST['codigo'] ?? '') ?>">
    </div>
    <div class="col-md-9">
    <?php else: ?>
    <div class="col-md-3">
        <label class="form-label">Código</label>
        <input class="form-control" value="<?= e($imovel['codigo'] ?? '') ?>" disabled>
    </div>
    <div class="col-md-9">
    <?php endif; ?>
        <label class="form-label">Título</label>
        <input name="titulo" class="form-control" required value="<?= e($imovel['titulo'] ?? '') ?>">
    </div>

    <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
            <?php foreach ($cfg['tipos'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($imovel['tipo'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Tamanho (m²)</label>
        <input type="number" step="0.01" min="0" name="tamanho_m2" class="form-control"
               value="<?= e(isset($imovel['tamanho_m2']) ? (string) $imovel['tamanho_m2'] : '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Garagem</label>
        <select name="garagem" class="form-select">
            <?php foreach ($cfg['garagem'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($imovel['garagem'] ?? 'nenhuma') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!$mostrarCodigo): ?>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <?php foreach ($cfg['status'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($imovel['status'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="col-12"><hr class="my-1"><h2 class="h6 text-muted">Endereço (RF04)</h2></div>

    <div class="col-md-3">
        <label class="form-label">CEP</label>
        <div class="input-group">
            <input name="cep" id="campo-cep" class="form-control" placeholder="00000-000"
                   value="<?= e($end['cep'] ?? '') ?>" maxlength="9">
            <button type="button" class="btn btn-outline-secondary" id="btn-buscar-cep" title="ViaCEP">Buscar</button>
        </div>
        <div class="form-text">Preenche logradouro, bairro, cidade e UF automaticamente.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Logradouro</label>
        <input name="logradouro" id="campo-logradouro" class="form-control" required
               value="<?= e($end['logradouro'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Número</label>
        <input name="numero" class="form-control" value="<?= e($end['numero'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Complemento</label>
        <input name="complemento" class="form-control" value="<?= e($end['complemento'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Bairro</label>
        <input name="bairro" id="campo-bairro" class="form-control" value="<?= e($end['bairro'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Cidade</label>
        <input name="cidade" id="campo-cidade" class="form-control" required value="<?= e($end['cidade'] ?? '') ?>">
    </div>
    <div class="col-md-1">
        <label class="form-label">UF</label>
        <input name="estado" id="campo-estado" class="form-control" maxlength="2" required
               value="<?= e($end['estado'] ?? '') ?>">
    </div>

    <div class="col-md-3">
        <label class="form-label">Latitude</label>
        <input name="latitude" id="campo-lat" class="form-control" readonly
               value="<?= e($end['latitude'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Longitude</label>
        <input name="longitude" id="campo-lng" class="form-control" readonly
               value="<?= e($end['longitude'] ?? '') ?>">
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <span class="small text-muted" id="geo-status">
            <?= ($end['latitude'] ?? null) ? 'Coordenadas salvas ou serão obtidas ao salvar (Nominatim).' : 'Geocodificação ao salvar.' ?>
        </span>
    </div>

    <div class="col-12">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control" rows="2"><?= e($imovel['observacoes'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="geocodificar" id="geocodificar" value="1" checked>
            <label class="form-check-label" for="geocodificar">Atualizar coordenadas GPS ao salvar (Nominatim)</label>
        </div>
    </div>
</div>
