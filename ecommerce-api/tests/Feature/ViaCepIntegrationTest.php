<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViaCepIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_address_data_from_viacep(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/*' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praca da Se',
                'bairro' => 'Se',
                'localidade' => 'Sao Paulo',
                'uf' => 'SP',
            ], 200),
        ]);

        $this->getJson('/api/cep/01001000')
            ->assertOk()
            ->assertJsonPath('data.cep', '01001-000')
            ->assertJsonPath('data.localidade', 'Sao Paulo');
    }

    public function test_it_returns_validation_style_error_for_invalid_cep(): void
    {
        $this->getJson('/api/cep/123')
            ->assertStatus(400)
            ->assertJsonPath('message', 'O CEP deve conter 8 dígitos.');
    }
}
