<?php
/* ========================================
   MIDTRANS PAYMENT CONFIGURATION (TEMPLATE)
======================================== */

// Set your Merchant ID, Client Key, and Server Key here
// In production, these should be set via environment variables.
define('MIDTRANS_MERCHANT_ID', getenv('MIDTRANS_MERCHANT_ID') ?: 'YOUR_SANDBOX_MERCHANT_ID');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: 'YOUR_SANDBOX_CLIENT_KEY');
define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: 'YOUR_SANDBOX_SERVER_KEY');
define('MIDTRANS_IS_PRODUCTION', filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: false, FILTER_VALIDATE_BOOLEAN));

// Endpoint URLs depending on environment
define('MIDTRANS_SNAP_URL', MIDTRANS_IS_PRODUCTION 
    ? 'https://app.midtrans.com/snap/v1/transactions' 
    : 'https://app.sandbox.midtrans.com/snap/v1/transactions'
);
