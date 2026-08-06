<?php

return [
    'inactivity_timeout_hours' => env('TESTER_INACTIVITY_HOURS', 3),
    'new_subject_grace_hours' => env('NEW_SUBJECT_GRACE_HOURS', 4),
    'repeat_test_cooldown_days' => env('REPEAT_TEST_COOLDOWN_DAYS', 7),
];
