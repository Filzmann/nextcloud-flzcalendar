<dialog id="adc-meeting-dialog" class="adc-dialog adc-meeting-dialog" aria-labelledby="adc-meeting-dialog-title">
    <form id="adc-meeting-form">
        <header class="adc-dialog__header">
            <h2 id="adc-meeting-dialog-title"><?php p($l->t('Find a meeting gap')); ?></h2>
            <button type="button" id="adc-meeting-close" class="adc-icon-button icon-close" aria-label="<?php p($l->t('Close dialog')); ?>" title="<?php p($l->t('Close')); ?>"></button>
        </header>
        <p id="adc-meeting-week"></p>
        <label for="adc-meeting-search"><?php p($l->t('Search participants')); ?></label>
        <input id="adc-meeting-search" type="search" autocomplete="off">
        <fieldset class="adc-meeting-people"><legend><?php p($l->t('At least two people')); ?></legend><div id="adc-meeting-people"></div></fieldset>
        <label><?php p($l->t('Duration in minutes')); ?> <input id="adc-meeting-duration" type="number" min="15" max="480" step="15" value="60" required></label>
        <label><?php p($l->t('Title for the block')); ?> <input id="adc-meeting-title" maxlength="255" placeholder="<?php p($l->t('e.g. team meeting')); ?>"></label>
        <div class="adc-dialog__actions">
            <button type="button" id="adc-meeting-cancel"><?php p($l->t('Cancel')); ?></button>
            <button type="submit" class="primary"><?php p($l->t('Search gaps')); ?></button>
        </div>
        <div id="adc-meeting-results" class="adc-meeting-results" aria-live="polite"></div>
    </form>
</dialog>
