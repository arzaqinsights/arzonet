<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TrackingService
{
    /**
     * Rewrite all links in the HTML to include tracking redirects.
     */
    public function injectTracking(string $html, EmailLog $log): string
    {
        // 1. Inject Tracking Pixel
        $pixelUrl = route('track.open', ['token' => $log->tracking_token]);
        $pixelHtml = '<img src="' . $pixelUrl . '" width="1" height="1" style="display:none !important;" alt="" />';
        
        if (str_contains($html, '</body>')) {
            $html = str_replace('</body>', $pixelHtml . '</body>', $html);
        } else {
            $html .= $pixelHtml;
        }

        // 2. Rewrite Links
        $html = preg_replace_callback('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"([^>]*)>/i', function ($matches) use ($log) {
            $url = $matches[1];
            $rest = $matches[2];

            // Skip anchor links, tel, mailto, and already tracked links
            if (empty($url) || $url[0] === '#' || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:') || str_contains($url, '/track/click')) {
                return $matches[0];
            }

            $trackingUrl = route('track.click', [
                'token' => $log->tracking_token,
                'url'   => base64_encode($url)
            ]);

            return '<a href="' . $trackingUrl . '"' . $rest . '>';
        }, $html);

        // 3. Unsubscribe Link (if tag exists)
        $unsubUrl = route('unsubscribe', ['token' => $log->tracking_token]);
        $html = str_replace(['{unsubscribe_url}', '{{unsubscribe_url}}'], $unsubUrl, $html);

        return $html;
    }

    /**
     * Parse User Agent for basic device/browser info.
     */
    public function parseMetadata(string $userAgent): array
    {
        $data = [
            'device' => 'Desktop',
            'browser' => 'Unknown',
            'is_proxy' => false,
        ];

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            $data['device'] = 'Mobile';
        }

        if (str_contains($ua, 'chrome')) $data['browser'] = 'Chrome';
        elseif (str_contains($ua, 'firefox')) $data['browser'] = 'Firefox';
        elseif (str_contains($ua, 'safari')) $data['browser'] = 'Safari';
        elseif (str_contains($ua, 'edge')) $data['browser'] = 'Edge';

        // Detect Proxies (Gmail, Outlook, etc.)
        if (str_contains($ua, 'googleimageproxy')) {
            $data['is_proxy'] = true;
            $data['proxy_provider'] = 'Gmail';
        }

        return $data;
    }
}
