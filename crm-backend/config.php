<?php

// Copy this file to config.php and fill in your database details.
return [
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=crm;charset=utf8mb4',
        'user' => 'crm',
        'password' => 'crm_password',
    ],

    // Origin of the frontend when it runs on another host or port,
    // for example 'http://localhost:5173'. Leave empty if the frontend
    // is served from the same origin as this API.
    'cors_origin' => '',

    'session_name' => 'CRMSESSID',

    // true: error responses include the exception message (development only)
    'debug' => false,

    // Outgoing email (quotations). 'outbox' only saves emails to the emails table,
    // shown by GET /api/outbox. To deliver real email, add a transport in src/Mailer.php.
    // Optional: these are the defaults when the section is missing.
    'mail' => [
        'transport' => 'outbox',
        'from_address' => 'quotes@crmpro.example',
        'from_name' => 'CRMPro',
    ],
];
