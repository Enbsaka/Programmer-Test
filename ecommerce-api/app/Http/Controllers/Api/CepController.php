<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ViaCepService;

class CepController extends Controller
{
    public function consultar(string $cep, ViaCepService $viaCepService)
    {
        try {
            $endereco = $viaCepService->getAddressByCep($cep);

            return response()->json([
                'message' => 'Consulta realizada com sucesso.',
                'data' => $endereco,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
