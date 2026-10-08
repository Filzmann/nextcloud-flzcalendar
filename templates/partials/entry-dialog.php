<dialog id="flz-calendar-entry-dialog" class="flz-calendar-dialog" aria-labelledby="flz-calendar-entry-dialog-title">
    <form id="flz-calendar-entry-form" method="dialog">
        <header class="flz-calendar-dialog__header">
            <h2 id="flz-calendar-entry-dialog-title"><?php p($l->t('Entry')); ?></h2>
            <button type="button" id="flz-calendar-cancel-edit" class="flz-calendar-icon-button" aria-label="<?php p($l->t('Close dialog')); ?>" title="<?php p($l->t('Close')); ?>">×</button>
        </header>
        <input id="flz-calendar-entry-id" type="hidden">
        <div class="flz-calendar-dialog__fields">
            <div class="flz-calendar-readonly-field">
                <span id="flz-calendar-employee-label"><?php p($l->t('Employee')); ?></span>
                <output id="flz-calendar-employee-name" aria-labelledby="flz-calendar-employee-label"></output>
                <input id="flz-calendar-employee" type="hidden">
            </div>
            <input id="flz-calendar-type" type="hidden" value="shift">
            <label><?php p($l->t('Start')); ?> <input id="flz-calendar-start" type="datetime-local" required aria-describedby="flz-calendar-time-help"></label>
            <label><?php p($l->t('End')); ?> <input id="flz-calendar-end" type="datetime-local" required aria-describedby="flz-calendar-time-help"></label>
            <label id="flz-calendar-title-field"><span id="flz-calendar-title-label"><?php p($l->t('Title')); ?></span><input id="flz-calendar-title" maxlength="255" aria-describedby="flz-calendar-title-help"></label>
        </div>
        <fieldset id="flz-calendar-recurrence-fields" class="flz-calendar-recurrence" hidden>
            <legend><?php p($l->t('Recurrence')); ?></legend>
            <label for="flz-calendar-recurrence-frequency"><?php p($l->t('Frequency')); ?></label>
            <select id="flz-calendar-recurrence-frequency">
                <option value=""><?php p($l->t('Once')); ?></option>
                <option value="daily"><?php p($l->t('Daily')); ?></option>
                <option value="weekly"><?php p($l->t('Weekly')); ?></option>
                <option value="monthly"><?php p($l->t('Monthly')); ?></option>
            </select>
            <div id="flz-calendar-recurrence-options" class="flz-calendar-recurrence__options" hidden>
                <label for="flz-calendar-recurrence-interval"><?php p($l->t('Interval')); ?></label>
                <input id="flz-calendar-recurrence-interval" type="number" min="1" max="365" value="1" inputmode="numeric">
                <label for="flz-calendar-recurrence-until"><?php p($l->t('End date')); ?></label>
                <input id="flz-calendar-recurrence-until" type="date">
                <fieldset id="flz-calendar-recurrence-weekdays" class="flz-calendar-recurrence__weekdays" hidden>
                    <legend><?php p($l->t('Weekdays')); ?></legend>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="1"><?php p($l->t('Mon')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="2"><?php p($l->t('Tue')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="3"><?php p($l->t('Wed')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="4"><?php p($l->t('Thu')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="5"><?php p($l->t('Fri')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="6"><?php p($l->t('Sat')); ?></label>
                    <label><input type="checkbox" name="flz-calendar-recurrence-weekday" value="7"><?php p($l->t('Sun')); ?></label>
                </fieldset>
                <small><?php p($l->t('At least two and no more than 500 occurrences. Months without the selected calendar day are skipped.')); ?></small>
            </div>
        </fieldset>
        <small id="flz-calendar-title-help"><?php p($l->t('The title is optional for shifts.')); ?></small>
        <p id="flz-calendar-time-help" class="flz-calendar-dialog__hint" aria-live="polite"></p>
        <footer class="flz-calendar-dialog__actions">
            <button type="button" id="flz-calendar-dialog-cancel"><?php p($l->t('Cancel')); ?></button>
            <button type="submit" class="primary"><?php p($l->t('Save')); ?></button>
        </footer>
    </form>
</dialog>
