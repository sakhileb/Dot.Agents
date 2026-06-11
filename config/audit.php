<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain audit log entries before they are pruned by
    | the model:prune scheduled command. Override via AUDIT_LOG_RETENTION_DAYS
    | in your .env file.
    |
    */

    'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 90),

];
