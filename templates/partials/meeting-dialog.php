<dialog id="flz-calendar-meeting-dialog" class="flz-calendar-dialog flz-calendar-meeting-dialog" aria-labelledby="flz-calendar-meeting-dialog-title">
    <form id="flz-calendar-meeting-form">
        <header class="flz-calendar-dialog__header">
            <h2 id="flz-calendar-meeting-dialog-title"><?php p($l->t('Find a meeting gap')); ?></h2>
            <button type="button" id="flz-calendar-meeting-close" class="flz-calendar-icon-button icon-close" aria-label="<?php p($l->t('Close dialog')); ?>" title="<?php p($l->t('Close')); ?>"></button>
        </header>
        <p id="flz-calendar-meeting-week"></p>
        <label for="flz-calendar-meeting-search"><?php p($l->t('Search participants')); ?></label>
        <input id="flz-calendar-meeting-search" type="search" autocomplete="off">
        <fieldset class="flz-calendar-meeting-people"><legend><?php p($l->t('At least two people')); ?></legend><div id="flz-calendar-meeting-people"></div></fieldset>
        <label><?php p($l->t('Duration in minutes')); ?> <input id="flz-calendar-meeting-duration" type="number" min="15" max="480" step="15" value="60" required></label>
        <label><?php p($l->t('Title for the block')); ?> <input id="flz-calendar-meeting-title" maxlength="255" placeholder="<?php p($l->t('e.g. team meeting')); ?>"></label>
        <div class="flz-calendar-dialog__actions">
            <button type="button" id="flz-calendar-meeting-cancel"><?php p($l->t('Cancel')); ?></button>
            <button type="submit" class="primary"><?php p($l->t('Search gaps')); ?></button>
        </div>
        <div id="flz-calendar-meeting-results" class="flz-calendar-meeting-results" aria-live="polite"></div>
    </form>
</dialog>
