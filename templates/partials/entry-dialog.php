<dialog id="adc-entry-dialog" class="adc-dialog" aria-labelledby="adc-entry-dialog-title">
    <form id="adc-entry-form" method="dialog">
        <header class="adc-dialog__header">
            <h2 id="adc-entry-dialog-title"><?php p($l->t('Entry')); ?></h2>
            <button type="button" id="adc-cancel-edit" class="adc-icon-button" aria-label="<?php p($l->t('Close dialog')); ?>" title="<?php p($l->t('Close')); ?>">×</button>
        </header>
        <input id="adc-entry-id" type="hidden">
        <div class="adc-dialog__fields">
            <label><?php p($l->t('Employee')); ?> <select id="adc-employee" required></select></label>
            <input id="adc-type" type="hidden" value="shift">
            <label><?php p($l->t('Start')); ?> <input id="adc-start" type="datetime-local" required aria-describedby="adc-time-help"></label>
            <label><?php p($l->t('End')); ?> <input id="adc-end" type="datetime-local" required aria-describedby="adc-time-help"></label>
            <label id="adc-title-field"><span id="adc-title-label"><?php p($l->t('Title')); ?></span><input id="adc-title" maxlength="255" aria-describedby="adc-title-help"></label>
        </div>
        <fieldset id="adc-recurrence-fields" class="adc-recurrence" hidden>
            <legend><?php p($l->t('Recurrence')); ?></legend>
            <label for="adc-recurrence-frequency"><?php p($l->t('Frequency')); ?></label>
            <select id="adc-recurrence-frequency">
                <option value=""><?php p($l->t('Once')); ?></option>
                <option value="daily"><?php p($l->t('Daily')); ?></option>
                <option value="weekly"><?php p($l->t('Weekly')); ?></option>
                <option value="monthly"><?php p($l->t('Monthly')); ?></option>
            </select>
            <div id="adc-recurrence-options" class="adc-recurrence__options" hidden>
                <label for="adc-recurrence-interval"><?php p($l->t('Interval')); ?></label>
                <input id="adc-recurrence-interval" type="number" min="1" max="365" value="1" inputmode="numeric">
                <label for="adc-recurrence-until"><?php p($l->t('End date')); ?></label>
                <input id="adc-recurrence-until" type="date">
                <fieldset id="adc-recurrence-weekdays" class="adc-recurrence__weekdays" hidden>
                    <legend><?php p($l->t('Weekdays')); ?></legend>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="1"><?php p($l->t('Mon')); ?></label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="2"><?php p($l->t('Tue')); ?></label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="3"><?php p($l->t('Wed')); ?></label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="4"><?php p($l->t('Thu')); ?></label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="5"><?php p($l->t('Fri')); ?></label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="6"><?php p($l->t('Sat')); ?></label>
                    <label><input type="checkbox" name="adc-recurrence-weekday" value="7"><?php p($l->t('Sun')); ?></label>
                </fieldset>
                <small><?php p($l->t('At least two and no more than 500 occurrences. Months without the selected calendar day are skipped.')); ?></small>
            </div>
        </fieldset>
        <small id="adc-title-help"><?php p($l->t('The title is optional for shifts.')); ?></small>
        <p id="adc-time-help" class="adc-dialog__hint" aria-live="polite"></p>
        <footer class="adc-dialog__actions">
            <button type="button" id="adc-dialog-cancel"><?php p($l->t('Cancel')); ?></button>
            <button type="submit" class="primary"><?php p($l->t('Save')); ?></button>
        </footer>
    </form>
</dialog>
