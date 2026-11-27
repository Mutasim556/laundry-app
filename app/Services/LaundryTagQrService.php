<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Validation\ValidationException;

class LaundryTagQrService
{
    public const CODE_PREFIX = 'FG-T-';

    /**
     * Format a sequential integer as a human-readable tag code (e.g. FG-T-000001).
     */
    public function formatTagCode(int $sequence): string
    {
        return self::CODE_PREFIX . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a short uppercase HMAC-SHA256 signature bound to APP_KEY (no PII).
     */
    public function signTagCode(string $tagCode): string
    {
        $normalized = strtoupper(trim($tagCode));
        $secret = (string) config('app.key', 'freshgo-laundry-tag-key');

        return strtoupper(substr(hash_hmac('sha256', $normalized, $secret), 0, 10));
    }

    /**
     * Build the opaque, PII-free payload embedded into the tag's QR code.
     */
    public function buildQrPayload(string $tagCode, ?string $signature = null): string
    {
        $normalized = strtoupper(trim($tagCode));
        $sig = $signature ?: $this->signTagCode($normalized);

        return $normalized . '|' . $sig;
    }

    /**
     * Normalize and validate a scanned QR payload or manually typed tag code.
     *
     * @return array{tag_code: string, signature: string|null, input_mode: string}
     */
    public function parseAndValidateInput(string $rawInput): array
    {
        $trimmed = trim($rawInput);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'tag_code' => 'Please enter or scan a laundry tag code.',
            ]);
        }

        // Support query URLs if scanned by external camera links
        if (str_contains($trimmed, '://') || str_contains($trimmed, 'code=')) {
            $query = parse_url($trimmed, PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                if (! empty($params['code'])) {
                    $code = strtoupper(trim((string) $params['code']));
                    $sig = isset($params['sig']) ? strtoupper(trim((string) $params['sig'])) : null;

                    return $this->validateParsedComponents($code, $sig, 'qr');
                }
            }
        }

        // Support pipe-delimited signed QR payload: FG-T-000001|SIGNATURE
        if (str_contains($trimmed, '|')) {
            [$codePart, $sigPart] = array_pad(explode('|', $trimmed, 2), 2, '');
            $code = strtoupper(trim($codePart));
            $sig = strtoupper(trim($sigPart));

            return $this->validateParsedComponents($code, $sig, 'qr');
        }

        // Plain manual tag code (allow missing hyphens/case like fgt000001 or fg-t-1)
        $code = $this->normalizeManualCode($trimmed);

        return $this->validateParsedComponents($code, null, 'manual');
    }

    protected function normalizeManualCode(string $input): string
    {
        $upper = strtoupper(preg_replace('/\s+/', '', $input) ?? $input);
        if (preg_match('/^FG-?T-?(\d{1,10})$/', $upper, $matches)) {
            $num = (int) $matches[1];
            if ($num > 0) {
                return $this->formatTagCode($num);
            }
        }

        return $upper;
    }

    /**
     * @return array{tag_code: string, signature: string|null, input_mode: string}
     */
    protected function validateParsedComponents(string $code, ?string $sig, string $mode): array
    {
        if (! preg_match('/^FG-T-\d{6,}$/', $code)) {
            throw ValidationException::withMessages([
                'tag_code' => 'Invalid laundry tag code format. Expected format like FG-T-000001.',
            ]);
        }

        if ($sig !== null) {
            if ($sig === '' || ! hash_equals($this->signTagCode($code), $sig)) {
                throw ValidationException::withMessages([
                    'tag_code' => 'QR signature verification failed. This tag QR appears invalid or tampered.',
                ]);
            }
        }

        return [
            'tag_code' => $code,
            'signature' => $sig,
            'input_mode' => $mode,
        ];
    }

    /**
     * Render a crisp high-contrast SVG markup string for inline HTML display & printing.
     */
    public function renderSvg(string $payload, int $quietZoneModules = 2): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => $quietZoneModules,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'svgUseFillAttributes' => true,
            'drawLightModules' => false,
            'moduleValues' => [
                QRMatrix::M_FINDER_DARK => '#0F172A',
                QRMatrix::M_FINDER_DOT => '#0F172A',
                QRMatrix::M_ALIGNMENT_DARK => '#0F172A',
                QRMatrix::M_TIMING_DARK => '#0F172A',
                QRMatrix::M_FORMAT_DARK => '#0F172A',
                QRMatrix::M_VERSION_DARK => '#0F172A',
                QRMatrix::M_DATA_DARK => '#0F172A',
                QRMatrix::M_DARKMODULE => '#0F172A',
            ],
        ]);

        return (new QRCode($options))->render($payload);
    }

    /**
     * Render a base64 SVG data URI compatible with both <img> tags and Dompdf.
     */
    public function renderSvgDataUri(string $payload, int $quietZoneModules = 2): string
    {
        $svg = $this->renderSvg($payload, $quietZoneModules);
        if (! str_contains($svg, 'xmlns=')) {
            $svg = preg_replace('/<svg\b/', '<svg xmlns="http://www.w3.org/2000/svg"', $svg, 1) ?? $svg;
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
