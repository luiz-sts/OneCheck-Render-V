# Integração APK Kotlin ↔ OneCheck API

Base URL de exemplo (ajuste ao seu IP/servidor):

```
http://192.168.0.10/onecheck/api/
```

## Fluxo recomendado

1. **Login** → recebe `token` Bearer (válido 30 dias)
2. **Listar imóveis** → usuário escolhe o imóvel no app
3. **Criar vistoria** → recebe `vistoria_id`
4. Para cada cômodo, **upload da foto** com `vistoria_id` + `comodo`
5. No painel web, fotos aparecem em **Vistorias → Fotos**

## 1. Login

`POST /api/auth/login.php`

```json
{
  "email": "admin@onecheck.local",
  "senha": "admin123",
  "dispositivo": "Samsung S23"
}
```

Resposta:

```json
{
  "ok": true,
  "token": "abc123...",
  "usuario": { "id": 1, "nome": "Administrador", ... }
}
```

Header nas próximas requisições:

```
Authorization: Bearer {token}
```

## 2. Listar imóveis

`GET /api/imoveis/list.php`

## 3. Criar vistoria

`POST /api/vistorias/create.php`

```json
{
  "imovel_id": 1,
  "tipo": "entrada",
  "data_vistoria": "2026-05-24",
  "observacoes": "Vistoria de entrada"
}
```

## 4. Enviar foto do cômodo

`POST /api/vistorias/upload.php`  
`Content-Type: multipart/form-data`

| Campo        | Tipo   | Obrigatório |
|-------------|--------|-------------|
| vistoria_id | int    | sim         |
| comodo      | string | sim         |
| foto        | file   | sim         |
| observacao  | string | não         |
| latitude    | float  | não         |
| longitude   | float  | não         |

### Exemplo Retrofit (Kotlin)

```kotlin
interface OneCheckApi {
    @POST("auth/login.php")
    suspend fun login(@Body body: LoginRequest): ApiResponse<LoginData>

    @GET("imoveis/list.php")
    suspend fun imoveis(@Header("Authorization") auth: String): ApiResponse<ImoveisData>

    @POST("vistorias/create.php")
    suspend fun criarVistoria(
        @Header("Authorization") auth: String,
        @Body body: CriarVistoriaRequest
    ): ApiResponse<CriarVistoriaData>

    @Multipart
    @POST("vistorias/upload.php")
    suspend fun uploadFoto(
        @Header("Authorization") auth: String,
        @Part("vistoria_id") vistoriaId: RequestBody,
        @Part("comodo") comodo: RequestBody,
        @Part foto: MultipartBody.Part
    ): ApiResponse<UploadFotoData>
}

// Header: "Bearer $token"
```

## Cômodos sugeridos no app

Use identificadores fixos para facilitar filtros no web:

- `sala`, `cozinha`, `quarto_1`, `quarto_2`, `banheiro`, `area_servico`, `varanda`, `garagem`

## Teste rápido com curl

```bash
curl -X POST http://localhost/onecheck/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@onecheck.local\",\"senha\":\"admin123\"}"
```

```bash
curl -X POST http://localhost/onecheck/api/vistorias/upload.php \
  -H "Authorization: Bearer SEU_TOKEN" \
  -F "vistoria_id=1" \
  -F "comodo=sala" \
  -F "foto=@/caminho/foto.jpg"
```

## Emulador Android

Use `10.0.2.2` para acessar o `localhost` do PC no emulador.
