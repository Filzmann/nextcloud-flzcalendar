<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Listener;

use OCA\LocalBase\Service\StandaloneAppNavigationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** @template-implements IEventListener<LoadAdditionalEntriesEvent> */
final class StandaloneNavigationListener implements IEventListener {
    public function __construct(private StandaloneAppNavigationService $navigation, private IL10N $l10n) {
    }

    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalEntriesEvent) return;
        $this->navigation->addWhenStandalone('adcalendar', $this->l10n->t('Calendar'), 'adcalendar.page.index', 'app.svg', 80);
    }
}
