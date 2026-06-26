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
        // Use admin subdomain for tracking URLs (production server only handles admin.*)
        $domain = config('app.domain', parse_url(config('app.url'), PHP_URL_HOST));
        $baseUrl = 'https://admin.' . $domain;


        // 1. Inject Tracking Pixel
        $pixelUrl = $baseUrl . '/t/o/' . $log->tracking_token;
        $pixelHtml = '<img src="' . $pixelUrl . '" width="1" height="1" style="display:none !important;" alt="" />';
        
        if (str_contains($html, '</body>')) {
            $html = str_replace('</body>', $pixelHtml . '</body>', $html);
        } else {
            $html .= $pixelHtml;
        }

        // 2. Rewrite Links
        $html = preg_replace_callback('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"([^>]*)>/i', function ($matches) use ($log, $baseUrl) {
            $url = $matches[1];
            $rest = $matches[2];

            // Skip anchor links, tel, mailto, and already tracked links
            if (empty($url) || $url[0] === '#' || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:') || str_contains($url, '/track/click')) {
                return $matches[0];
            }

            // Auto-append UTM tags if not present and it is an absolute external link
            if (!str_contains($url, 'utm_source') && !str_starts_with($url, '/')) {
                $campaignName = $log->campaign?->name ?? 'campaign';
                $campaignSlug = \Illuminate\Support\Str::slug($campaignName);
                $utmQuery = http_build_query([
                    'utm_source'   => 'arzonet',
                    'utm_medium'   => 'email',
                    'utm_campaign' => $campaignSlug ?: 'campaign_' . $log->campaign_id,
                ]);
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . $utmQuery;
            }

            $trackingUrl = $baseUrl . '/t/c/' . $log->tracking_token . '?url=' . base64_encode($url);

            return '<a href="' . $trackingUrl . '"' . $rest . '>';
        }, $html);

        // 3. Unsubscribe Link (if tag exists)
        $unsubUrl = $baseUrl . '/unsubscribe/' . $log->tracking_token;
        $html = str_replace(['{unsubscribe_url}', '{{unsubscribe_url}}'], $unsubUrl, $html);

        return $html;
    }

    /**
     * Parse User Agent for basic device/browser/OS info.
     */
    public function parseMetadata(string $userAgent): array
    {
        $data = [
            'device' => 'Desktop',
            'os' => 'Unknown',
            'browser' => 'Unknown',
            'is_proxy' => false,
            'proxy_provider' => null,
        ];

        $ua = strtolower($userAgent);

        // 1. Device Detection
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet') || str_contains($ua, 'playbook')) {
            $data['device'] = 'Tablet';
        } elseif (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'ipod') || str_contains($ua, 'blackberry')) {
            $data['device'] = 'Mobile';
        }

        // 2. OS Detection
        if (str_contains($ua, 'windows')) {
            $data['os'] = 'Windows';
        } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            $data['os'] = 'iOS';
        } elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
            $data['os'] = 'macOS';
        } elseif (str_contains($ua, 'android')) {
            $data['os'] = 'Android';
        } elseif (str_contains($ua, 'linux')) {
            $data['os'] = 'Linux';
        }

        // 3. Browser Detection
        if (str_contains($ua, 'edg/')) $data['browser'] = 'Edge';
        elseif (str_contains($ua, 'chrome') || str_contains($ua, 'crios')) $data['browser'] = 'Chrome';
        elseif (str_contains($ua, 'firefox') || str_contains($ua, 'fxios')) $data['browser'] = 'Firefox';
        elseif (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) $data['browser'] = 'Safari';
        elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr/')) $data['browser'] = 'Opera';

        // 4. Detect Proxies & Bots (Gmail, Yahoo, Apple MPP, etc.)
        if (str_contains($ua, 'googleimageproxy')) {
            $data['is_proxy'] = true;
            $data['proxy_provider'] = 'Gmail Prefetch';
        } elseif (str_contains($ua, 'yahoo-mail-proxy') || str_contains($ua, 'yahooimageproxy')) {
            $data['is_proxy'] = true;
            $data['proxy_provider'] = 'Yahoo Proxy';
        } elseif (str_contains($ua, 'apple-mail') || str_contains($ua, 'applemail') || (str_contains($ua, 'mac os') && str_contains($ua, 'cloudflare'))) {
            $data['is_proxy'] = true;
            $data['proxy_provider'] = 'Apple MPP';
        }

        return $data;
    }

    /**
     * Resolve GeoIP location from IP address.
     */
    public function resolveGeo(string $ip): array
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return ['country' => 'Localhost', 'region' => 'Localhost', 'city' => 'Localhost'];
        }

        $cacheKey = "ip_geo:{$ip}";
        try {
            $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
            if ($cached) {
                return json_decode($cached, true) ?: ['country' => 'Unknown', 'region' => 'Unknown', 'city' => 'Unknown'];
            }

            // Query IP-API with a 2 second timeout
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful() && $response->json('status') === 'success') {
                $geo = [
                    'country' => $response->json('country') ?? 'Unknown',
                    'region'  => $response->json('regionName') ?? 'Unknown',
                    'city'    => $response->json('city') ?? 'Unknown',
                ];
                \Illuminate\Support\Facades\Redis::setex($cacheKey, 86400 * 7, json_encode($geo)); // Cache for 7 days
                return $geo;
            } else {
                $geo = ['country' => 'Unknown', 'region' => 'Unknown', 'city' => 'Unknown'];
                \Illuminate\Support\Facades\Redis::setex($cacheKey, 3600, json_encode($geo)); // Cache failure for 1 hour to prevent spamming
                return $geo;
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return ['country' => 'Unknown', 'region' => 'Unknown', 'city' => 'Unknown'];
    }
}
