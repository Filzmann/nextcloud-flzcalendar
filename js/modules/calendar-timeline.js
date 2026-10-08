(function() {
    'use strict';

    /**
     * Zweck: Berechnet ein gemeinsames, komprimiertes Tagesraster für alle Zellen einer Kalenderansicht.
     * Zusammenspiel: WeekTable erzeugt einmal das Zeitraumslayout; CalendarCell positioniert Dienste und Sperrtermine darin.
     * Vertrag: Jede Eintragsgrenze wird zur gemeinsamen Rasterlinie. Belegte Intervalle sind mindestens hoch genug für eine kompakte Karte.
     */
    class CalendarTimeline {
        layout(entries, days) {
            const segments = days.flatMap(day => entries.map(entry => this.segment(entry, day)).filter(Boolean));
            const points = [...new Set([360, 1260, ...segments.flatMap(segment => [segment.start, segment.end])])].sort((a, b) => a - b);
            return { points, rows: this.rows(points, segments) };
        }

        segment(entry, day) {
            const dayStart = new Date(day);
            dayStart.setHours(0, 0, 0, 0);
            const dayEnd = new Date(dayStart);
            dayEnd.setDate(dayEnd.getDate() + 1);
            const entryStart = new Date(entry.start);
            const entryEnd = new Date(entry.end);
            if (entryStart >= dayEnd || entryEnd <= dayStart) return null;
            return {
                start: entryStart <= dayStart ? 0 : entryStart.getHours() * 60 + entryStart.getMinutes(),
                end: entryEnd >= dayEnd ? 1440 : entryEnd.getHours() * 60 + entryEnd.getMinutes(),
            };
        }

        rows(points, segments) {
            const heights = points.slice(0, -1).map((start, index) => {
                const end = points[index + 1];
                return Math.max(4, Math.min(18, Math.round((end - start) / 10)));
            });
            for (const segment of segments) {
                const startIndex = points.indexOf(segment.start);
                const endIndex = points.indexOf(segment.end);
                const indices = Array.from({ length: endIndex - startIndex }, (_, offset) => startIndex + offset);
                const currentHeight = indices.reduce((sum, index) => sum + heights[index], 0);
                if (currentHeight < 64 && indices.length) heights[indices[0]] += 64 - currentHeight;
            }
            return heights.map(height => `${height}px`).join(' ');
        }

        gridRow(layout, entry, day) {
            const segment = this.segment(entry, day);
            if (!segment) return '';
            return `${layout.points.indexOf(segment.start) + 1} / ${layout.points.indexOf(segment.end) + 1}`;
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.modules = window.FlzCalendar.modules || {};
    window.FlzCalendar.modules.CalendarTimeline = CalendarTimeline;
}());
