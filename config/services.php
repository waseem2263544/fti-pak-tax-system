<?php

return [

    'sharepoint' => [
        // The SharePoint site whose default document library the browser opens.
        'site' => env('SHAREPOINT_SITE', 'FairTaxInternational723'),

        // The folder the Documents browser opens at and treats as its root.
        // Operations/3. Clients — set to null to browse the whole library.
        'root_folder' => env('SHAREPOINT_ROOT_FOLDER', '01CC2DZ2K4AMXS7NOOSNAJ37X5YARIIZDF'),

        'root_label' => env('SHAREPOINT_ROOT_LABEL', 'Clients'),
    ],


    'wht_mcp' => [
        // Secret embedded in the MCP connector URL. Empty disables the endpoint.
        'secret' => env('WHT_MCP_SECRET'),
    ],

    'wht_api' => [
        // Shared token for the read-only WHT endpoints used by the wht-psid skill.
        'token' => env('WHT_API_TOKEN'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
