<?php

declare(strict_types=1);

namespace OCP {
    interface IURLGenerator { public function imagePath(string $app, string $file): string; }
    interface IL10N { public function t(string $text, array $parameters = []): string; }
}
namespace OCP\Settings {
    interface IIconSection { public function getIcon(): string; public function getID(): string; public function getName(): string; public function getPriority(): int; }
}
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }

namespace {

    use OCA\FlzCalendar\Settings\AdminSection;
    use OCP\IL10N;
    use OCP\IURLGenerator;

    $urls = new class implements IURLGenerator { public function imagePath(string $app, string $file): string { return "/{$app}/{$file}"; } };
    $l10n = new class implements IL10N {
        public array $calls = [];
        public function t(string $text, array $parameters = []): string { $this->calls[] = [$text, $parameters]; return "translated:{$text}"; }
    };
    $section = new AdminSection($urls, $l10n);
    if ($section->getName() !== 'translated:Filzmann Calendar' || $l10n->calls !== [['Filzmann Calendar', []]]) {
        throw new RuntimeException('Adminabschnitt verwendet nicht Nextcloud IL10N.');
    }
    if ($section->getID() !== 'flzcalendar' || $section->getIcon() !== '/flzcalendar/app.svg' || $section->getPriority() !== 62) {
        throw new RuntimeException('Lokalisierung verändert technische Adminabschnittswerte.');
    }

    echo "AdminSectionLocalizationTest: OK\n";
}
