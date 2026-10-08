<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Command;

use OCA\FlzCalendar\Service\CalendarDemoPackService;
use OCP\IL10N;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Zweck: Erzeugt idempotent benannte Demokonten, Gruppenmitgliedschaften und neutrale Kalendereintraege. */
final class SeedDemoCommand extends Command {
    public function __construct(
        private CalendarDemoPackService $demoPack,
        private IL10N $l10n,
    ) { parent::__construct(); }

    protected function configure(): void {
        $this->setName('flzcalendar:demo:seed')->setDescription($this->l10n->t('Creates complete Filzmann Calendar demo accounts and demo data.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $result = $this->demoPack->install();
        $accountCount = $result['accounts']['createdUsers'] + $result['accounts']['reusedUsers'];
        $message = $this->l10n->t('%s demo accounts synchronised; calendar entries created for %s, %s already existed.', [
            $accountCount,
            $result['createdCalendars'],
            $result['skippedCalendars'],
        ]);
        $output->writeln("<info>{$message}</info>");
        return self::SUCCESS;
    }
}
