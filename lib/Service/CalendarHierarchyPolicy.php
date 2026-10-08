<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\LocalBase\Organization\FlzOrganizationHierarchy;

/**
 * Zweck: Bildet die transitive organisatorische Weisungshierarchie für Kalenderbearbeitung ab.
 * Vertrag: Eine Leitungsrolle darf alle ihr direkt oder indirekt zugeordneten Zielrollen bearbeiten.
 */
final class CalendarHierarchyPolicy extends FlzOrganizationHierarchy {}
