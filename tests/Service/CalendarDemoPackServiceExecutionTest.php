<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service {
    final class DemoAccountProvisioningService {
        public array $calls = [];
        public function provision(string $pack, array $fixtures): array {
            $this->calls[] = [$pack, $fixtures];
            return ['createdUsers' => 1, 'reusedUsers' => 1, 'createdGroups' => 2];
        }
    }
}

namespace OCA\FlzCalendar\Repository {
    use OCA\FlzCalendar\Model\CalendarEntry;
    final class CalendarEntryRepository {
        public array $saved = [];
        public array $existing = ['demo-a' => true];
        public function existsCreatedByForEmployee(string $actor, string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end): bool {
            if ($actor !== 'demo-seed' || $end <= $start) throw new \RuntimeException('Ungültiger Demo-Zeitraum.');
            return $this->existing[$uid] ?? false;
        }
        public function save(CalendarEntry $entry, string $actor): int {
            $this->saved[] = [$entry, $actor];
            return 100 + count($this->saved);
        }
    }
}

namespace OCA\FlzCalendar\Service {
    final class DemoFixtureCatalog {
        public function all(): array {
            return [
                ['uid' => 'demo-a', 'name' => 'Demo A', 'groups' => ['role-a']],
                ['uid' => 'demo-b', 'name' => 'Demo B', 'groups' => ['role-b', 'area-b']],
            ];
        }
    }
}

namespace {

    use OCA\FlzCalendar\Repository\CalendarEntryRepository;
    use OCA\FlzCalendar\Service\CalendarDemoPackService;
    use OCA\FlzCalendar\Service\DemoFixtureCatalog;
    use OCA\LocalBase\Service\DemoAccountProvisioningService;

    $accounts = new DemoAccountProvisioningService();
    $entries = new CalendarEntryRepository();
    $result = (new CalendarDemoPackService($accounts, $entries, new DemoFixtureCatalog()))->install();

    if ($accounts->calls !== [[
        'flz-full-suite-demo',
        [
            ['uid' => 'demo-a', 'displayName' => 'Demo A', 'groups' => ['role-a']],
            ['uid' => 'demo-b', 'displayName' => 'Demo B', 'groups' => ['role-b', 'area-b']],
        ],
    ]]) {
        throw new RuntimeException('Demo-Preflight erhält nicht ausschließlich neutrale Konten und Gruppen.');
    }
    if ($result !== [
        'accounts' => ['createdUsers' => 1, 'reusedUsers' => 1, 'createdGroups' => 2],
        'createdCalendars' => 1,
        'skippedCalendars' => 1,
    ]) {
        throw new RuntimeException('Demo-Pack zählt neue und vorhandene Kalender nicht idempotent.');
    }
    if (count($entries->saved) !== 3 || array_unique(array_column($entries->saved, 1)) !== ['demo-seed']) {
        throw new RuntimeException('Demo-Pack erzeugt nicht genau Dienst, internen Termin und Sperrtermin.');
    }
    [$shift, $inside, $blocked] = array_column($entries->saved, 0);
    if ($shift->type() !== 'shift' || $inside->type() !== 'appointment' || $blocked->type() !== 'appointment'
        || $inside->parentEntryId() !== 101 || $blocked->parentEntryId() !== null
        || $shift->end()->getTimestamp() - $shift->start()->getTimestamp() !== 8 * 3600
        || $inside->title() !== 'Neutraler Teamtermin' || $blocked->title() !== 'Neutraler Sperrtermin') {
        throw new RuntimeException('Synthetische Demo-Kalendereinträge verletzen Typ-, Zuordnungs- oder Zeitvertrag.');
    }

    echo "CalendarDemoPackServiceExecutionTest: OK\n";
}
