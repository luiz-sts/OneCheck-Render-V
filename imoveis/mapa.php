<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$pontos = ImovelService::listarParaMapa();
$geojson = array_map(static fn($p) => [
    'id'       => (int) $p['id'],
    'codigo'   => $p['codigo'],
    'titulo'   => $p['titulo'],
    'status'   => $p['status'],
    'lat'      => (float) $p['latitude'],
    'lng'      => (float) $p['longitude'],
    'endereco' => trim($p['logradouro'] . ($p['numero'] ? ', ' . $p['numero'] : '') . ' — ' . $p['cidade']),
    'url'      => base_url('imoveis/detalhes.php?id=' . $p['id']),
], $pontos);

$pageTitle = 'Mapa de imóveis';
$activeMenu = 'imoveis';
require ONECHECK_ROOT . '/includes/header.php';
page_header('Mapa de imóveis', 'RF06 · Leaflet + OpenStreetMap',
    '<a href="' . e(base_url('imoveis/index.php')) . '" class="btn btn-outline-secondary btn-sm">Lista</a>');
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div id="mapa-imoveis" style="height: 520px; width: 100%;"></div>
    </div>
</div>
<p class="small text-muted mt-2">
    <?= count($pontos) ?> imóvel(is) com coordenadas. Edite o imóvel e marque “Atualizar coordenadas GPS” para incluir no mapa.
</p>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const pontos = <?= json_encode($geojson, JSON_UNESCAPED_UNICODE) ?>;
const map = L.map('mapa-imoveis').setView([-23.55, -46.63], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap'
}).addTo(map);

const markers = [];
pontos.forEach(p => {
  const m = L.marker([p.lat, p.lng]).addTo(map)
    .bindPopup(`<strong>${p.codigo}</strong><br>${p.titulo}<br><small>${p.endereco}</small><br><a href="${p.url}">Ver detalhes</a>`);
  markers.push(m);
});
if (markers.length) {
  const group = L.featureGroup(markers);
  map.fitBounds(group.getBounds().pad(0.2));
}
</script>

<?php require ONECHECK_ROOT . '/includes/footer.php'; ?>
