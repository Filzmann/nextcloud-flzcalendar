<dialog id="flz-calendar-entry-dialog" class="flz-calendar-dialog" aria-labelledby="flz-calendar-entry-dialog-title">
    <form id="flz-calendar-entry-form" method="dialog">
        <header class="flz-calendar-dialog__header">
            <h2 id="flz-calendar-entry-dialog-title"><?php p($l->t('Eintrag')); ?></h2>
            <button type="button" id="flz-calendar-cancel-edit" class="flz-calendar-icon-button" aria-label="<?php p($l->t('Dialog schließen')); ?>" title="<?php p($l->t('Schließen')); ?>">×</button>
        </header>
        <input id="flz-calendar-entry-id" type="hidden">
        <div class="flz-calendar-dialog__fields">
            <div class="flz-calendar-readonly-field">
                <span id="flz-calendar-employee-label"><?php p($l->t('Mitarbeiter*in')); ?></span>
                <output id="flz-calendar-employee-name" aria-labelledby="flz-calendar-employee-label"></output>
                <input id="flz-calendar-employee" type="hidden">
            </div>
            <input id="flz-calendar-type" type="hidden" value="shift">
            <label><?php p($l->t('Beginn')); ?> <input id="flz-calendar-start" type="datetime-local" required aria-describedby="flz-calendar-time-help"></label>
            <label><?php p($l->t('Ende')); ?> <input id="flz-calendar-end" type="datetime-local" required aria-describedby="flz-calendar-time-help"></label>
            <label id="flz-calendar-title-field"><span id="flz-calendar-title-label"><?php p($l->t('Titel')); ?></span><input id="flz-calendar-title" maxlength="255" aria-describedby="flz-calendar-title-help"></label>
        </div>
        <fieldset id="flz-calendar-recurrence-fields" class="flz-calendar-recurrence" hidden>
            <legend><?php p($l->t('Wiederholung')); ?></legend>
            <label for="flz-calendar-recurrence-frequency"><?php p($l->t('Häufigkeit')); ?></label>
            <select id="flz-calendar-recurrence-frequency">
                <option value=""><?php p($l->t('Einmalig')); ?></option>
                <option value="daily"><?php p($l->t('Täglich')); ?></option>
                <option value="weekly"><?php p($l->t('Wöchentlich')); ?></option>
                <option value="monthly"><?php p($l->t('Monatlich')); ?></option>
            </select>
            <div id="flz-calendar-recurrence-options" class="flz-calendar-recurrence__options" hidden>
                <label for="flz-calendar-recurrence-interval"><?php p($l->t('Intervall')); ?></label>
                <input id="flz-calendar-recurrence-interval" type="number" min="1" max="365" value="1" inputmode="numeric">
                <label for="flz-calendar-recurrence-until"><?php p($l->t('Enddatum')); ?></label>
                <input id="flz-calendar-recurrence-until" type="date">
                <fieldset id="flz-calendar-recurrence-weekdays" class="flz-calendar-recurrence__weekdays" hidden>
                    <legend><?php p($l->t('Wochentage')); ?></legend>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="1"><?php p($l->t('Mo')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="2"><?php p($l->t('Di')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="3"><?php p($l->t('Mi')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="4"><?php p($l->t('Do')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="5"><?php p($l->t('Fr')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="6"><?php p($l->t('Sa')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="7"><?php p($l->t('So')); ?></label>
                </fieldset>
                <small><?php p($l->t('Mindestens zwei, höchstens 500 Vorkommen. Monate ohne den gewählten Kalendertag werden ausgelassen.')); ?></small>
            </div>
        </fieldset>
        <small id="flz-calendar-title-help"><?php p($l->t('Titel ist bei Diensten optional.')); ?></small>
        <p id="flz-calendar-time-help" class="flz-calendar-dialog__hint" aria-live="polite"></p>
        <footer class="flz-calendar-dialog__actions">
            <button type="button" id="flz-calendar-dialog-cancel"><?php p($l->t('Abbrechen')); ?></button>
            <button type="submit" class="primary"><?php p($l->t('Speichern')); ?></button>
        </footer>
    </form>
</dialog>
