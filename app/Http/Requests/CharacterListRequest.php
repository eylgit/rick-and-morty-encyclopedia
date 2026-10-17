<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the query string of the character list. Runs before the controller;
 * on failure the visitor is sent to the first page instead of seeing an error.
 */
final class CharacterListRequest extends FormRequest
{
    /** Where to send the visitor when validation fails. */
    protected $redirect = '/characters';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** The validated page number, 1 when absent. */
    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }
}
