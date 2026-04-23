# BKN SIASN API Gateway

Server-side proxy murni untuk mengakses BKN SIASN API (`apimws.bkn.go.id`). Gateway ini di-deploy di server yang IP-nya sudah di-whitelist oleh BKN, sehingga aplikasi lokal bisa mengkonsumsi API BKN melalui proxy ini.

## Arsitektur

Gateway ini didesain murni sebagai **Proxy**.

Aplikasi lokal (SIMPEG) bertugas me-request token APIM & SSO ke BKN, lalu menyertakannya di HTTP Header saat mengakses Gateway. Gateway akan meneruskan request & header tersebut secara utuh ke API REST BKN.

```
┌──────────────┐       ┌──────────────────┐       ┌──────────────────┐
│  Aplikasi    │       │  BKN Gateway     │       │  BKN SIASN API   │
│  Lokal       │──────▶│  (Lumen Proxy)   │──────▶│  apimws.bkn.go.id│
│  (SIMPEG)    │       │  bkn-gateway.test│       │  :8243           │
│              │◀──────│  + Proxy         │◀──────│                  │
│              │       │                  │       │                  │
└──────────────┘       └──────────────────┘       └──────────────────┘
     HTTP Request         Forward Request            Response
     + X-Api-Key          + Forward All Headers
     + Authorization
     + Auth
```

## Fitur

- **Proxy Murni** — Meneruskan semua HTTP method (GET, POST, PUT, PATCH, DELETE) beserta seluruh Header bawaan client.
- **Sangat Ringan** — Berjalan secara *stateless* dan murni sebagai *pass-through*.
- **File upload** — Support multipart/form-data untuk upload file.
- **API key protection** — Proxy dilindungi dengan API key `X-Api-Key`.

## Persyaratan

- PHP >= 7.3
- Composer
- Server dengan IP yang di-whitelist oleh BKN

## Instalasi

1. Clone repository:

```bash
git clone https://github.com/kanekescom/bkn-gateway.git
cd bkn-gateway
```

2. Install dependencies:

```bash
composer install
```

3. Salin file environment:

```bash
cp .env.example .env
```

4. Konfigurasi `.env`:

```env
APP_NAME="BKN SIASN API Gateway"

# API Key untuk proteksi gateway (buat sendiri, misal random string)
GATEWAY_API_KEY=your-secret-api-key
```

## Konfigurasi

| Variabel | Deskripsi | Default |
|----------|-----------|---------|
| `GATEWAY_API_KEY` | Secret key untuk melindungi proxy | (wajib diisi) |
| `SIASN_TIMEOUT` | Request timeout (detik) | `60` |
| `SIASN_VERIFY_SSL` | Verifikasi SSL | `true` |

## Endpoint

### Health Check

```
GET /
```

Response:
```json
{
  "name": "BKN SIASN API Gateway",
  "status": "running"
}
```

### Proxy SIASN API

```
ANY /api/siasn/{path}
```

Header wajib untuk dikirim oleh Client:
- `X-Api-Key`: Secret API Key Gateway Anda.
- `Authorization`: `Bearer <apim_token>` (Token APIM BKN).
- `Auth`: `bearer <sso_token>` (Token SSO BKN).
- Header opsional lainnya (misal: `Content-Type: application/json`).

Gateway akan meneruskan (*forward*) semua header di atas (kecuali `X-Api-Key` dan header internal) secara utuh ke BKN.

## Contoh Penggunaan

### cURL

```bash
# GET - Data Utama PNS
curl -H "X-Api-Key: your-secret-api-key" \
  -H "Authorization: Bearer my_apim_token" \
  -H "Auth: bearer my_sso_token" \
  https://bkn-gateway.test/api/siasn/pns/data-utama/123456789012345678

# POST - Photo PNS
curl -X POST \
  -H "X-Api-Key: your-secret-api-key" \
  -H "Authorization: Bearer my_apim_token" \
  -H "Auth: bearer my_sso_token" \
  -H "Content-Type: application/json" \
  -d '{"nip":"123456789012345678"}' \
  https://bkn-gateway.test/api/siasn/pns/photo
```

### Integrasi di Aplikasi Laravel (Client)

Aplikasi client (misal: SIMPEG) yang memanggil layanan Gateway:

```php
use Illuminate\Support\Facades\Http;

// Buat HTTP client yang menyertakan token dari aplikasi Anda
$gateway = Http::baseUrl('https://bkn-gateway.test/api/siasn')
    ->withHeaders([
        'X-Api-Key'     => 'your-secret-api-key',
        'Authorization' => 'Bearer ' . $apimToken,
        'Auth'          => 'bearer ' . $ssoToken,
    ]);

// Eksekusi request API
$response = $gateway->get("/pns/data-utama/{$nip}");
$dataUtama = $response->json();
```

## Mapping Path

Path setelah `/api/siasn/` sama persis dengan path BKN API asli (tanpa base URL):

| BKN API Asli | Via Gateway |
|---|---|
| `apimws.bkn.go.id:8243/apisiasn/1.0/pns/data-utama/{nip}` | `bkn-gateway.test/api/siasn/pns/data-utama/{nip}` |
| `apimws.bkn.go.id:8243/apisiasn/1.0/pns/photo` | `bkn-gateway.test/api/siasn/pns/photo` |
| `apimws.bkn.go.id:8243/apisiasn/1.0/referensi/ref-unor` | `bkn-gateway.test/api/siasn/referensi/ref-unor` |

## Struktur Project

```
bkn-gateway/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ProxyController.php    # Catch-all proxy controller (Forwarder)
│   │   └── Middleware/
│   │       └── ApiKeyMiddleware.php   # API key authentication untuk Gateway
├── config/
│   └── gateway.php                    # Konfigurasi mode dan rahasia Gateway
├── routes/
│   └── web.php                        # Route definitions
├── .env.example
├── composer.json
└── README.md
```

## Troubleshooting

| Error | Penyebab | Solusi |
|-------|----------|--------|
| `401 Unauthorized` (dari Gateway) | API key salah/tidak ada | Cek `X-Api-Key` header dan `GATEWAY_API_KEY` di `.env` |
| `401 Unauthorized` (dari BKN) | Token kedaluwarsa / salah | Pastikan client men-generate dan mengirim token `Authorization` dan `Auth` yang valid |
| `502 Gateway Error` | Gagal koneksi ke BKN API | Cek koneksi server dan pastikan IP server Gateway sudah di-whitelist BKN |

## License

MIT
