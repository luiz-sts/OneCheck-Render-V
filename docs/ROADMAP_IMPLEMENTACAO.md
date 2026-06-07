# Roadmap de implementação — One Check (painel web)

Ordem sugerida de desenvolvimento após executar `003_schema_checklist_completo.sql`.

Legenda: ✅ feito (base atual) · 🟡 parcial · ⬜ a fazer

---

## Sprint 0 — Preparação (1 dia)

| Tarefa | Arquivo / ação |
|--------|----------------|
| ⬜ Executar migração 003 no MySQL (porta **3307**) | `database/migrations/003_schema_checklist_completo.sql` |
| ⬜ Rodar `sync-para-xampp.bat` após cada alteração | raiz do projeto |
| ⬜ Criar helpers: `AuditLog`, `Uuid`, `Geocoder`, `MailQueue` | `includes/` |
| ⬜ Atualizar `config/permissions.php` com perfil `locatario` | `config/permissions.php` |

---

## Sprint 1 — Usuários e autenticação (RF01, RF02, RNF01, RNF02)

| RF/RNF | Entrega | Telas / API |
|--------|---------|-------------|
| RF01 | CRUD completo + perfil **locatário** | `usuarios/` (já existe — estender) |
| RF02 | MFA TOTP no login web | `public/login.php` → passo 2 com código |
| RNF01 | JWT access + refresh (opcional web; API sim) | `api/auth/refresh.php`, tabela `auth_refresh_tokens` |
| RNF02 | MFA obrigatório admin/vistoriador | flag `mfa_obrigatorio` em `usuarios` |

**Arquivos novos sugeridos:**
- `includes/Mfa.php` — gerar secret, validar TOTP (lib: `robthree/twofactorauth` ou implementação leve)
- `includes/JwtAuth.php`
- `public/mfa-setup.php` — QR code para Google Authenticator

---

## Sprint 2 — Imóveis, endereço e mapa (RF03–RF07, RNF04, RNF05)

| RF/RNF | Entrega | Telas |
|--------|---------|-------|
| RF03 | Tamanho m² + garagem no formulário | `imoveis/novo.php`, `editar.php` |
| RF04 | Endereço na tabela `enderecos` | `imoveis/endereco.php` ou aba em editar |
| RF05 | CRUD de cômodos por imóvel | `imoveis/comodos.php` |
| RF07 | Status: disponível, locado, em vistoria | badges + selects |
| RF06 | Mapa Leaflet | `imoveis/mapa.php` |
| RNF05 | ViaCEP + Nominatim ao salvar CEP | `includes/Geocoder.php` |

**Fluxo:** salvar imóvel → salvar `enderecos` → chamar geocoder → exibir pin no mapa.

---

## Sprint 3 — Contratos e locatário (RF08–RF10)

| RF | Entrega | Telas |
|----|---------|-------|
| RF08 | Contrato com `locatario_usuario_id` (select de usuários locatário) | `contratos/novo.php`, `detalhes.php` |
| RF09 | Bloqueio 2º contrato ativo (trigger já no SQL + mensagem na UI) | validação no PHP |
| RF10 | Encerrar contrato → imóvel `disponivel` (trigger SQL + botão) | `contratos/detalhes.php` |

**Extra:** `contratos/historico.php` no detalhe do imóvel — lista todos os contratos.

---

## Sprint 4 — Checklist admin (RF11–RF15, RF13, RF14)

| RF | Entrega | Telas |
|----|---------|-------|
| RF11 | Criar checklist (inicial/encerramento) a partir do contrato | `checklists/novo.php` |
| RF11 | Copiar `imovel_comodos` + templates → `checklist_comodos` + `checklist_itens` | `includes/ChecklistService.php` |
| RF15 | Agendar vistoria | `agendamentos/index.php`, `novo.php` |
| RF13 | Lista com filtros de status | `checklists/index.php` |
| RF12 | Botão “Enviar ao locatário” → status `pendente_aceite` | `checklists/detalhes.php` |
| RF14 | Gerar PDF inicial/final | `checklists/pdf.php` (TCPDF ou Dompdf) |

**Status do checklist (máquina de estados):**

```
em_preenchimento
    → pendente_envio_locatario  (vistoriador submeteu — app)
    → pendente_aceite           (admin enviou ao locatário)
    → aceito | rejeitado
rejeitado → pendente_revisao    (admin revisa)
```

Registrar cada mudança em `checklist_status_historico` + `log_operacao`.

---

## Sprint 5 — Portal do locatário (RF21–RF24)

Área separada (layout simplificado):

| RF | Tela |
|----|------|
| RF21 | `locatario/index.php` — meu imóvel + checklist atual |
| RF22 | `locatario/checklist.php` — visualizar itens + botão **Aceitar** |
| RF23 | Modal rejeitar com motivo obrigatório |
| RF24 | Link download PDF |
| RF25 | `locatario/problema-novo.php` — abrir problema com foto |

**Segurança:** `Auth::requireRole('locatario')` — só vê contrato/checklist do próprio `locatario_usuario_id`.

---

## Sprint 6 — Problemas completos (RF25–RF28)

| RF | Entrega |
|----|---------|
| RF25 | Upload foto na abertura → `problema_fotos` |
| RF26–27 | Admin adiciona atualização → `problema_atualizacoes` + timeline na view |
| RF28 | Ao criar problema/atualização → insert em `notificacoes_email` + `includes/Mailer.php` |

Telas: estender `problemas/detalhes.php` com timeline e formulário de atualização.

---

## Sprint 7 — Dashboard e API externa (RF29, RNF03, RNF06)

| RF/RNF | Entrega |
|--------|---------|
| RF29 | Cards: imóveis locados, checklists pendentes aceite, problemas abertos, agendamentos futuros |
| RNF03 | Middleware `AuditLog::record()` em todos os POST/PUT/DELETE |
| RNF06 | `api/external/imoveis.php` — header `X-Api-Key` → `api_keys` |

---

## Sprint 8 — Segurança e produção (RNF07, RNF08)

| RNF | Entrega |
|-----|---------|
| RNF07 | Migrar PKs antigas para UUID em novas entidades (já no 003); INT legado pode permanecer até v2 |
| RNF08 | HTTPS no Apache, remover `install.php`, rate limit login |

---

## Estrutura de pastas alvo (PHP)

```
onecheck/
├── api/
│   ├── auth/          login, refresh, mfa
│   ├── external/      API key (RNF06)
│   └── vistorias/     (legado — convergir com checklists)
├── checklists/        RF11–15, RF13–14
├── agendamentos/      RF15
├── locatario/         RF21–24, RF25
├── imoveis/
│   ├── comodos.php    RF05
│   └── mapa.php       RF06
├── includes/
│   ├── ChecklistService.php
│   ├── Geocoder.php
│   ├── AuditLog.php
│   ├── Mfa.php
│   └── Mailer.php
└── docs/
    └── ROADMAP_IMPLEMENTACAO.md  (este arquivo)
```

---

## Compatibilidade com o que já existe

| Módulo atual | Destino |
|--------------|---------|
| `vistorias/` + fotos | Mantém para operação simples; **convergir** para checklist formal ou deprecar |
| `vistoria_checklist` (002) | Substituído por `checklists` + `checklist_itens` |
| API `upload.php` | Apontar para `checklist_item_fotos` no Sprint 4 |

---

## Como executar a migração 003

```bash
# XAMPP — porta 3307 neste PC
C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root -p onecheck < database/migrations/003_schema_checklist_completo.sql
```

Ou importar pelo **phpMyAdmin** → banco `onecheck` → Importar.

Se der erro em `ADD COLUMN IF NOT EXISTS`, use MySQL 8.0.29+ ou remova `IF NOT EXISTS` e execute coluna por coluna.

---

## Estimativa grossa

| Sprint | Dias (1 dev) |
|--------|----------------|
| 0–1 | 3–5 |
| 2–3 | 5–7 |
| 4–5 | 8–12 |
| 6–7 | 5–7 |
| 8 | 2–3 |
| **Total web** | **~25–35 dias** |

*(App mobile Kotlin fora desta estimativa.)*
