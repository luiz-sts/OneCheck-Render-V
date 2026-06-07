# Sprint 1 — Concluído

## RF01 — Usuários
- Perfil **locatário** no CRUD (`usuarios/novo.php`, `editar.php`)
- Portal inicial: `locatario/index.php`

## RF02 / RNF02 — MFA TOTP
- Login em 2 passos: `public/login.php` → `mfa-verify.php` ou `mfa-setup.php`
- Configuração opcional em `usuarios/perfil.php`
- Admin e vistoriador: MFA obrigatório (`config/auth.php`)

## RNF01 — JWT
- `POST api/auth/login.php` — retorna `access_token` + `refresh_token` ou `mfa_required`
- `POST api/auth/mfa-verify.php` — conclui login com código
- `POST api/auth/refresh.php` — renova access token
- Tokens legados (`api_tokens`) ainda funcionam

## RNF03 — Log (início)
- `AuditLog::record()` em login/logout e CRUD de usuários

## Testar MFA (admin)

1. Login: `admin@onecheck.local` / `admin123`
2. Escaneie o QR Code no Google Authenticator
3. Informe o código de 6 dígitos
4. Nos próximos logins: senha → código MFA

## Testar API

```bash
# Login (se MFA ativo, retorna mfa_pending_token)
curl -X POST http://localhost/onecheck/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@onecheck.local\",\"senha\":\"admin123\"}"

# MFA
curl -X POST http://localhost/onecheck/api/auth/mfa-verify.php \
  -H "Content-Type: application/json" \
  -d "{\"mfa_pending_token\":\"...\",\"code\":\"123456\"}"

# Refresh
curl -X POST http://localhost/onecheck/api/auth/refresh.php \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\":\"...\"}"
```

## Pré-requisito
Migração **003** executada (colunas `mfa_*`, `log_operacao`, `auth_refresh_tokens`).
