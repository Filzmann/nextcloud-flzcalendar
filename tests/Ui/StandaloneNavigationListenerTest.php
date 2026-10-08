<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event {} interface IEventListener { public function handle(Event $event): void; } }
namespace OCP\Navigation\Events { class LoadAdditionalEntriesEvent extends \OCP\EventDispatcher\Event {} }
namespace OCP { interface IUser {} interface IUserSession { public function getUser(): ?IUser; } interface IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string; public function imagePath(string $appName, string $file): string; } interface INavigationManager { public const TYPE_APPS = 'link'; public function add(callable $entry): void; } interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\App { interface IAppManager { public function isEnabledForUser($appId, $user = null); } }

namespace {

    use OCA\FlzCalendar\Listener\StandaloneNavigationListener;
    use OCA\LocalBase\Service\StandaloneAppNavigationService;
    use OCP\App\IAppManager;
    use OCP\INavigationManager;
    use OCP\IL10N;
    use OCP\IURLGenerator;
    use OCP\IUser;
    use OCP\IUserSession;
    use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

    $user = new class implements IUser {};
    $session = new class($user) implements IUserSession { public function __construct(private IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $apps = new class implements IAppManager { public function isEnabledForUser($appId, $user = null): bool { return false; } };
    $nav = new class implements INavigationManager { public array $entries = []; public function add(callable $entry): void { $this->entries[] = $entry; } };
    $url = new class implements IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string { return $routeName; } public function imagePath(string $appName, string $file): string { return "$appName/$file"; } };
    $l10n = new class implements IL10N { public array $calls = []; public function t(string $text, array $parameters = []): string { $this->calls[] = [$text, $parameters]; return "translated:{$text}"; } };
    $listener = new StandaloneNavigationListener(new StandaloneAppNavigationService($session, $apps, $nav, $url), $l10n);
    $listener->handle(new LoadAdditionalEntriesEvent());
    $entry = ($nav->entries[0] ?? static fn(): array => [])();
    if (($entry['id'] ?? '') !== 'flzcalendar' || ($entry['name'] ?? '') !== 'translated:Calendar' || ($entry['href'] ?? '') !== 'flzcalendar.page.index' || $l10n->calls !== [['Calendar', []]]) throw new RuntimeException('Standalone-Kalendernavigation ist nicht lokalisiert oder technisch stabil.');
    echo "Filzmann Kalender standalone navigation test passed\n";
}
