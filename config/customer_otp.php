<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer Login Testing OTP Gate
    |--------------------------------------------------------------------------
    | Testing OTP mode is strictly for disposable local/testing environments.
    | All three conditions must pass for testing OTP mode to operate:
    |   1. Application environment is in `approved_environments` (local/testing)
    |   2. CUSTOMER_TEST_OTP_ALLOWED=true is explicitly set
    |   3. Admin selects Testing OTP mode in Website Manager
    */
    'testing_allowed' => (bool) env('CUSTOMER_TEST_OTP_ALLOWED', false),

    'approved_environments' => ['local', 'testing'],

    'test_otp_code' => (string) env('CUSTOMER_TEST_OTP_CODE', '123456'),
];
