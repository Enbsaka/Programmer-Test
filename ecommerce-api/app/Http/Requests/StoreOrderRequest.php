<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'in:credit_card,pix,boleto'],
        ];
    }
}
