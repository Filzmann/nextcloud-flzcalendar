<?php

declare(strict_types=1);

namespace OCP {
    interface IL10N { public function t(string $text, array $parameters = []): string; }
}
namespace OCP\AppFramework\Http {
    final class JSONResponse {
        public function __construct(private array $data = [], private int $status = 200) {}
        public function getData(): array { return $this->data; }
        public function getStatus(): int { return $this->status; }
    }
}

namespace {

    use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
    use OCP\IL10N;

    $l10n = new class implements IL10N {
        public array $calls = [];
        public function t(string $text, array $parameters = []): string {
            $this->calls[] = [$text, $parameters];
            return strtr("translated:{$text}", $parameters);
        }
    };
    $factory = new LocalizedErrorResponseFactory($l10n);
    $response = $factory->create('provider_rejected', 'Provider returned HTTP {status}.', 400, ['{status}' => '405']);

    if ($response->getStatus() !== 400
        || $response->getData() !== ['code' => 'provider_rejected', 'error' => 'translated:Provider returned HTTP 405.']
        || $l10n->calls !== [['Provider returned HTTP {status}.', ['{status}' => '405']]]) {
        throw new RuntimeException('Lokalisierter API-Fehler bewahrt Code, HTTP-Status und typisierte Platzhalter nicht.');
    }

    echo "LocalizedErrorResponseFactoryTest: OK\n";
}
