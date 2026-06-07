# Sprint 2 — Imóveis, endereço e mapa

## Entregas

| RF/RNF | Implementação |
|--------|----------------|
| RF03 | Tamanho m² + tipo de garagem nos formulários |
| RF04 | Tabela `enderecos` + sync com campos legados em `imoveis` |
| RF05 | `imoveis/comodos.php` — CRUD de cômodos; padrão ao criar imóvel |
| RF06 | `imoveis/mapa.php` — Leaflet + OpenStreetMap |
| RF07 | Status: disponível, locado, em vistoria, manutenção, inativo |
| RNF05 | ViaCEP (botão/busca CEP) + Nominatim ao salvar |

## Arquivos principais

- `includes/Geocoder.php`
- `includes/ImovelService.php`
- `config/imoveis.php`
- `api/geocode/cep.php`
- `assets/js/imoveis-form.js`

## Como testar

1. **Imóveis → Novo** — informe CEP e clique **Buscar** (ViaCEP)
2. Salve com “Atualizar coordenadas GPS” marcado
3. Abra **Detalhes** — mini-mapa no painel
4. **Mapa** — todos os imóveis com lat/lng
5. **Cômodos** — adicionar/remover ambientes

## Pré-requisito

Migração **003** executada (`enderecos`, `imovel_comodos`, colunas `tamanho_m2`, `garagem`).

## Próximo

Sprint 3 — Contratos com locatário como usuário do sistema.
