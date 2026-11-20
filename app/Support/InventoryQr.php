<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

class InventoryQr
{
    public static function render(string $url, ?string $filename): Response
    {
        $renderer = new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd);
        $response = response((new Writer($renderer))->writeString($url), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
        ]);

        if ($filename) {
            $response->header('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));
        }

        return $response;
    }

    public static function ticketId(string $value, string $prefix, string $ticketPath): ?string
    {
        if (preg_match('/\A'.preg_quote($prefix, '/').'-([0-9]{1,19})\z/i', $value, $matches)) {
            return $matches[1];
        }

        $parts = parse_url($value);
        $scheme = strtolower($parts['scheme'] ?? '');
        $isTicketUrl = in_array($scheme, ['http', 'https'], true) || str_starts_with($value, $ticketPath.'/');
        $pattern = '~\A'.preg_quote($ticketPath, '~').'/([0-9]{1,19})/?\z~';

        return $isTicketUrl && preg_match($pattern, $parts['path'] ?? '', $matches) ? $matches[1] : null;
    }
}
