<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the query string of the character list. Runs before the controller;
 * on failure the visitor is sent to the first page instead of seeing an error.
 */
final class CharacterListRequest extends FormRequest
{
    /** Values the API documents for the two fixed-choice filters. Also used to draw the dropdowns. */
    public const STATUSES = ['alive', 'dead', 'unknown'];

    public const GENDERS = ['female', 'male', 'genderless', 'unknown'];

    /** Where to send the visitor when validation fails. */
    protected $redirect = '/characters';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
            'species' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', Rule::in(self::GENDERS)],
        ];
    }

    /** The validated page number, 1 when absent. */
    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    /**
     * The validated filters that were actually given, keyed by name.
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        $given = [];
        foreach (['name', 'status', 'species', 'gender'] as $key) {
            $value = $this->validated($key);
            if (is_string($value) && $value !== '') {
                $given[$key] = $value;
            }
        }

        return $given;
    }
}
