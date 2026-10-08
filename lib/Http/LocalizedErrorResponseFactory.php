<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Http;

use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;

/** Builds public API failures from a locale-independent code and an English L10N source key. */
final class LocalizedErrorResponseFactory {
    public function __construct(private IL10N $l10n) {}

    public function create(string $code, string $message, int $status, array $parameters = []): JSONResponse {
        return new JSONResponse([
            'code' => $code,
            'error' => $this->l10n->t($message, $parameters),
        ], $status);
    }
}
