<?php

namespace App\Http\Requests\Concerns;

use Closure;

/**
 * Shared signature_data/signature_file validation rules for the reception
 * and delivery Form Requests, so both questionnaires enforce the exact same
 * signature contract (drawn PNG data URI XOR uploaded PNG file).
 */
trait ValidatesSignatureData
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function signatureRules(): array
    {
        return [
            // Signature can be either drawn on the canvas (a base64 PNG data
            // URI) or uploaded as a standalone PNG file — exactly one of the
            // two is required. 'nullable' lets an empty signature_data value
            // (sent as '' by the hidden input and normalized to null by
            // Laravel's ConvertEmptyStringsToNull middleware) skip the
            // starts_with/string checks instead of failing them outright,
            // so the "signature is missing" message comes from
            // required_without instead of a raw starts_with failure.
            //
            // The prefix is pinned to the exact PNG data URI header (not
            // just "data:image/") so a tampered hidden field can't smuggle
            // in another image type, and the closure below confirms the
            // base64 payload actually decodes to real PNG bytes rather than
            // arbitrary data wearing a PNG-looking prefix.
            'signature_data' => [
                'nullable',
                'required_without:signature_file',
                'string',
                'starts_with:data:image/png;base64,',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->isValidPngDataUri($value)) {
                        $fail('La firma dibujada no es válida. Borra la firma e inténtalo de nuevo.');
                    }
                },
            ],
            'signature_file' => ['nullable', 'required_without:signature_data', 'image', 'mimes:png', 'max:5120'],
        ];
    }

    private function isValidPngDataUri(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        $prefix = 'data:image/png;base64,';
        $base64 = substr($value, strlen($prefix));

        if ($base64 === '') {
            return false;
        }

        $contents = base64_decode($base64, true);

        if ($contents === false) {
            return false;
        }

        return @getimagesizefromstring($contents) !== false;
    }
}
