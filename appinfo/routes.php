<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#week', 'url' => '/api/week', 'verb' => 'GET'],
        ['name' => 'api#range', 'url' => '/api/range', 'verb' => 'GET'],
        ['name' => 'api#create', 'url' => '/api/entries', 'verb' => 'POST'],
        ['name' => 'api#update', 'url' => '/api/entries/{id}', 'verb' => 'PUT'],
        ['name' => 'api#delete', 'url' => '/api/entries/{id}', 'verb' => 'DELETE'],
        ['name' => 'api#preferences', 'url' => '/api/preferences', 'verb' => 'GET'],
        ['name' => 'api#savePreferences', 'url' => '/api/preferences', 'verb' => 'PUT'],
        ['name' => 'api#saveShiftDefaults', 'url' => '/api/preferences/shifts', 'verb' => 'PUT'],
        ['name' => 'api#saveCalendarSync', 'url' => '/api/preferences/calendar-sync', 'verb' => 'PUT'],
        ['name' => 'external_calendar#status', 'url' => '/api/external-calendars', 'verb' => 'GET'],
        ['name' => 'external_calendar#connectCalDav', 'url' => '/api/external-calendars/caldav', 'verb' => 'POST'],
        ['name' => 'external_calendar#disconnect', 'url' => '/api/external-calendars/{provider}', 'verb' => 'DELETE'],
        ['name' => 'external_calendar#googleStart', 'url' => '/api/external-calendars/google/start', 'verb' => 'POST'],
        ['name' => 'external_calendar#googleCallback', 'url' => '/oauth/google/callback', 'verb' => 'GET'],
        ['name' => 'meeting#gaps', 'url' => '/api/meeting-gaps', 'verb' => 'POST'],
        ['name' => 'meeting#block', 'url' => '/api/meetings', 'verb' => 'POST'],
        ['name' => 'meeting#update', 'url' => '/api/meetings/{meetingUid}', 'verb' => 'PUT'],
        ['name' => 'meeting#delete', 'url' => '/api/meetings/{meetingUid}', 'verb' => 'DELETE'],
        ['name' => 'demo_admin#install', 'url' => '/api/admin/demo-pack/install', 'verb' => 'POST'],
        ['name' => 'google_oauth_admin#save', 'url' => '/api/admin/google-oauth', 'verb' => 'PUT'],
        ['name' => 'google_oauth_admin#remove', 'url' => '/api/admin/google-oauth', 'verb' => 'DELETE'],
        ['name' => 'external_calendar_admin#testCalDav', 'url' => '/api/admin/external-calendars/caldav/test', 'verb' => 'POST'],
    ],
];
