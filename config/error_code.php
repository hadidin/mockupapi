<?php

return [
    'api' => array (
        'general' => array (
            'whitelist_failed' => 'call from non whitelisp ip',
        ),
        'get_ticket_info' => array (
            'invalid_ticket' => 'Cannot get ticket information',
            'paid_ticket' => 'Ticket already paid',
            'missing_params' => 'required param is missing',
            'error_occured' =>'error occurred while saving, retry',
        ),
        'external_auth_payment' => array (
            'amount_different' => 'amount is different',
            'missing_params' => 'required param is missing',
            'paid_ticket' => 'Try to pay for paid ticket',
            'invalid_request' => 'vendor return error on request',
            'error_occured' => 'error occurred while saving, retry',
            'used_auth_code' => 'used authorization code',
        )
    ),
];