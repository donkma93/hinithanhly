<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shared Database Protection
    |--------------------------------------------------------------------------
    |
    | Set DB_SHARED_DATABASE=true when multiple sites/branches share the same
    | physical database using different DB_PREFIX values. When enabled (or when
    | DB_PREFIX is non-empty), destructive commands such as db:wipe and
    | migrate:fresh are blocked to protect other branches' tables.
    |
    */
    'shared_database' => (bool) env('DB_SHARED_DATABASE', false),

    /*
    |--------------------------------------------------------------------------
    | Current Branch Identity
    |--------------------------------------------------------------------------
    */
    'current_key' => env('BRANCH_KEY', 'cs1'),
    'current_name' => env('BRANCH_NAME', env('APP_NAME', 'Cơ sở 1')),

    /*
    |--------------------------------------------------------------------------
    | Fallback Payment Bank Config (used only when DB setting is empty)
    |--------------------------------------------------------------------------
    */
    'default_bank' => [
        'bank_name' => env('APP_BANK_NAME', ''),
        'bank_account' => env('APP_BANK_ACCOUNT', ''),
        'bank_account_name' => env('APP_BANK_ACCOUNT_NAME', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Read-Only Portal & Shared Sales Branches
    |--------------------------------------------------------------------------
    |
    | JSON array defining branches in the shared database. Each entry requires:
    |   - key: short slug (e.g. "cs1", "cs2")
    |   - name: display label (e.g. "Cơ sở 1", "Cơ sở 2")
    |   - prefix: DB table prefix in the shared database (e.g. "", "cs2_")
    |   - url: optional public URL for resolving product images across branches
    |
    | Leave empty to use only the current site's database prefix.
    |
    */
    'portal_raw' => env('PORTAL_BRANCHES', ''),
];
