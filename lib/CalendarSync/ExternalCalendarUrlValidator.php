<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\CalendarSync;

use InvalidArgumentException;

/** Validiert nutzerkonfigurierte CalDAV-Ziele vor der zusätzlichen SSRF-Prüfung des Nextcloud-HTTP-Clients. */
final class ExternalCalendarUrlValidator {
    public function normalize(string $url): string {
        $url = trim($url);
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https') {
            throw new InvalidArgumentException('Die Kalenderadresse muss HTTPS verwenden.');
        }
        $host = strtolower(trim((string)($parts['host'] ?? '')));
        if ($host === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Die Kalenderadresse ist ungültig.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) throw new InvalidArgumentException('Direkte IP-Adressen sind als Kalenderziel nicht erlaubt.');
        $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
        $path = '/' . ltrim((string)($parts['path'] ?? '/'), '/');
        return 'https://' . $host . $port . rtrim($path, '/') . '/';
    }

    public function sameOrigin(string $baseUrl, string $discoveredUrl): string {
        $base = parse_url($this->normalize($baseUrl));
        $target = parse_url($this->normalize($discoveredUrl));
        if (($base['host'] ?? null) !== ($target['host'] ?? null) || ($base['port'] ?? 443) !== ($target['port'] ?? 443)) {
            throw new InvalidArgumentException('Der Kalenderanbieter verweist auf einen unerwarteten Server.');
        }
        return $this->normalize($discoveredUrl);
    }
}
