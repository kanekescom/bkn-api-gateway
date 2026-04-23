<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    /**
     * Proxy request to SIASN API.
     *
     * @param  Request  $request
     * @param  string|null  $path
     * @return \Illuminate\Http\Response
     */
    public function proxy(Request $request, $path = '')
    {
        try {
            $method = strtolower($request->method());
            $targetPath = '/' . ltrim($path, '/');
            // Base URL SIASN Production
            $baseUrl = config('gateway.siasn_url');
                
            $url = rtrim($baseUrl, '/') . $targetPath;

            // Ambil semua header dari client, KECUALI header internal/spesifik
            $excludeHeaders = [
                'host', 
                'x-api-key', 
                'content-length', 
                'connection', 
                'accept-encoding',
                'postman-token',
                'user-agent'
            ];
            
            $headers = [];
            foreach ($request->headers->all() as $key => $values) {
                if (!in_array(strtolower($key), $excludeHeaders)) {
                    $headers[$key] = $values[0];
                }
            }

            // Inisiasi HTTP Client
            $client = Http::withHeaders($headers)
                ->timeout(config('gateway.timeout', 60))
                ->withOptions([
                    'verify' => config('gateway.verify_ssl', true)
                ]);

            // Eksekusi request berdasarkan method
            switch ($method) {
                case 'get':
                    $response = $client->get($url, $request->query());
                    break;

                case 'post':
                    if ($request->hasFile('file')) {
                        $response = $this->forwardMultipart($client, $url, $request);
                    } else {
                        $response = $client->post($url, $request->all());
                    }
                    break;

                case 'put':
                    $response = $client->put($url, $request->all());
                    break;

                case 'patch':
                    $response = $client->patch($url, $request->all());
                    break;

                case 'delete':
                    $response = $client->delete($url, $request->all());
                    break;

                default:
                    return response()->json([
                        'error' => 'Unsupported HTTP method: ' . $method,
                    ], 405);
            }

            // Filter response headers yang akan dikembalikan
            $respHeaders = $this->filterResponseHeaders($response->headers());

            // Return response dari BKN
            return response($response->body(), $response->status())
                ->withHeaders($respHeaders);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Gateway Error',
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Forward multipart/form-data request with file uploads.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $client
     * @param  string  $url
     * @param  Request  $request
     * @return \Illuminate\Http\Client\Response
     */
    protected function forwardMultipart($client, string $url, Request $request)
    {
        $multipart = $client->asMultipart();

        // Attach files
        foreach ($request->allFiles() as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $multipart = $multipart->attach(
                        $key . '[]',
                        file_get_contents($f->getRealPath()),
                        $f->getClientOriginalName()
                    );
                }
            } else {
                $multipart = $multipart->attach(
                    $key,
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                );
            }
        }

        // Attach other form data
        foreach ($request->except(array_keys($request->allFiles())) as $key => $value) {
            $multipart = $multipart->attach($key, $value);
        }

        return $multipart->post($url);
    }

    /**
     * Filter response headers to forward back to the client.
     *
     * @param  array  $headers
     * @return array
     */
    protected function filterResponseHeaders(array $headers): array
    {
        $exclude = [
            'transfer-encoding',
            'connection',
            'keep-alive',
            'proxy-authenticate',
            'proxy-authorization',
            'te',
            'trailers',
            'upgrade',
        ];

        $filtered = [];
        foreach ($headers as $key => $values) {
            if (! in_array(strtolower($key), $exclude)) {
                $filtered[$key] = is_array($values) ? implode(', ', $values) : $values;
            }
        }

        return $filtered;
    }
}
