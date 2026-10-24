<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'price' => ['sometimes', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'tax_cost' => ['sometimes', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'manufacturing_cost' => ['sometimes', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            // La nueva divisa base no puede ser una en la que el producto ya tiene precio registrado
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id',
                Rule::notIn($this->route('product')?->prices()->pluck('currency_id')->all() ?? [])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency_id.not_in' => 'The product already has a registered price in this currency.',
        ];
    }
}
