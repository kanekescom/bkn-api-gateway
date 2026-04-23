# BKN Web Service API Gateway

Server-side proxy untuk mengakses BKN Web Service API (`apimws.bkn.go.id`). Gateway ini di-deploy di server yang IP-nya sudah di-whitelist oleh BKN, sehingga aplikasi lokal bisa mengkonsumsi API BKN melalui proxy ini.

## Arsitektur

Gateway ini didesain sebagai **Proxy**.

Aplikasi lokal (SIMPEG) bertugas me-request token APIM & SSO ke BKN, lalu menyertakannya di HTTP Header saat mengakses Gateway. Gateway akan meneruskan request & header tersebut secara utuh ke API REST BKN.

```
┌──────────────┐          ┌────────────────────┐          ┌──────────────────────┐
│  Aplikasi    │          │  BKN Gateway       │          │  BKN Web Service API │
│  Lokal       │────────▶│  (Lumen Proxy)     │────────▶ │  apimws.bkn.go.id    │
│  (SIMPEG)    │          │                    │          │  :8243               │
│              │◀────────│                    │◀──────── │                      │
└──────────────┘          └────────────────────┘          └──────────────────────┘
   HTTP Request             Forward Request                   Response
   + X-Api-Key              + Forward All Headers
   + Authorization
   + Auth
```

## Fitur

- **Proxy** — Meneruskan semua HTTP method (GET, POST, PUT, PATCH, DELETE) beserta seluruh Header bawaan client.
- **Sangat Ringan** — Berjalan secara *stateless*.
- **File upload** — Support multipart/form-data untuk upload file.
- **API key protection** — Proxy dilindungi dengan API key `X-Api-Key`.

## Persyaratan

- PHP >= 7.3
- Composer
- Server dengan IP yang di-whitelist oleh BKN

## Instalasi

1. Clone repository:

```bash
git clone https://github.com/kanekescom/bkn-api-gateway.git
cd bkn-api-gateway
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
APP_NAME="BKN Web Service API Gateway"
APP_URL=https://your-gateway-domain.com

# API Key untuk proteksi gateway (buat sendiri, misal random string)
GATEWAY_API_KEY=your-secret-api-key
```

> **Catatan:** Ganti `your-gateway-domain.com` dengan domain atau IP server Anda yang sudah di-whitelist oleh BKN.

## Konfigurasi

| Variabel | Deskripsi | Default |
|----------|-----------|---------|
| `GATEWAY_API_KEY` | Secret key untuk melindungi proxy | (wajib diisi) |
| `SIASN_WS_URL` | Base URL BKN Web Service API (host + port) | `https://apimws.bkn.go.id:8243` |
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
  "name": "BKN Web Service API Gateway",
  "status": "running"
}
```

### Proxy SIASN API

```
ANY /api/{path}
```

Path setelah `/api/` akan diteruskan langsung ke `SIASN_WS_URL` dan Gateway mendukung semua endpoint SIASN API.

Header wajib untuk dikirim oleh Client:
- `X-Api-Key`: Secret API Key Gateway Anda.
- `Authorization`: `Bearer <apim_token>` (Token APIM BKN).
- `Auth`: `bearer <sso_token>` (Token SSO BKN).
- Header opsional lainnya (misal: `Content-Type: application/json`).

Gateway akan meneruskan (*forward*) semua header di atas (kecuali `X-Api-Key` dan header internal) secara utuh ke BKN.

## Contoh Penggunaan

> **Catatan:** Ganti `https://your-gateway-domain.com` di bawah ini dengan URL gateway Anda yang sebenarnya.

### cURL

```bash
# GET - Data Utama PNS (SIASN API)
curl -H "X-Api-Key: your-secret-api-key" \
  -H "Authorization: Bearer my_apim_token" \
  -H "Auth: bearer my_sso_token" \
  https://your-gateway-domain.com/api/apisiasn/1.0/pns/data-utama/123456789012345678

# GET - Referensi Agama (Referensi API)
curl -H "X-Api-Key: your-secret-api-key" \
  -H "Authorization: Bearer my_apim_token" \
  -H "Auth: bearer my_sso_token" \
  https://your-gateway-domain.com/api/referensi_siasn/1/agama

# POST - Photo PNS (SIASN API)
curl -X POST \
  -H "X-Api-Key: your-secret-api-key" \
  -H "Authorization: Bearer my_apim_token" \
  -H "Auth: bearer my_sso_token" \
  -H "Content-Type: application/json" \
  -d '{"nip":"123456789012345678"}' \
  https://your-gateway-domain.com/api/apisiasn/1.0/pns/photo
```

### Integrasi di Aplikasi Laravel (Client)

Aplikasi client (misal: SIMPEG) yang memanggil layanan Gateway:

```php
use Illuminate\Support\Facades\Http;

// Buat HTTP client ke Gateway
$gateway = Http::baseUrl('https://your-gateway-domain.com/api')
    ->withHeaders([
        'X-Api-Key'     => 'your-secret-api-key',
        'Authorization' => 'Bearer ' . $apimToken,
        'Auth'          => 'bearer ' . $ssoToken,
    ]);

// SIASN API
$response = $gateway->get("/apisiasn/1.0/pns/data-utama/{$nip}");
$dataUtama = $response->json();

// Referensi API
$response = $gateway->get("/referensi_siasn/1/ref-unor");
$referensi = $response->json();
```

## Mapping Path

Path setelah `/api/` diteruskan apa adanya ke SIASN API:

| BKN API Asli | Via Gateway |
|---|---|
| `apimws.bkn.go.id:8243/apisiasn/1.0/pns/data-utama/{nip}` | `your-gateway-domain.com/api/apisiasn/1.0/pns/data-utama/{nip}` |
| `apimws.bkn.go.id:8243/apisiasn/1.0/pns/photo` | `your-gateway-domain.com/api/apisiasn/1.0/pns/photo` |
| `apimws.bkn.go.id:8243/referensi_siasn/1/ref-unor` | `your-gateway-domain.com/api/referensi_siasn/1/ref-unor` |

## Struktur Project

```
/
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
| `502 Gateway Error` | Gagal koneksi ke BKN Web Service API | Cek koneksi server dan pastikan IP server Gateway sudah di-whitelist BKN |

## License

MIT
