<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViaCepService
{
    public function getAddressByCep(string $cep): array
    {
        $cleanCep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cleanCep) !== 8) {
            throw new \Exception('O CEP deve conter 8 dígitos.');
        }

        try {
            $response = Http::baseUrl(rtrim(config('services.viacep.base_url'), '/'))
                ->acceptJson()
                ->timeout((int) config('services.viacep.timeout', 5))
                ->retry(
                    (int) config('services.viacep.retry_times', 3),
                    (int) config('services.viacep.retry_sleep_ms', 150)
                )
                ->get("{$cleanCep}/json/")
                ->throw();
        } catch (RequestException $e) {
            Log::error('Falha na integracao ViaCEP.', [
                'cep' => $cleanCep,
                'status' => $e->response?->status(),
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Serviço de consulta de endereço indisponível no momento.');
        }

        $data = $response->json();

        if (isset($data['erro'])) {
            throw new \Exception('CEP não encontrado.');
        }

        return $data;
    }
}
