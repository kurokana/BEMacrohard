<?php
require_once __DIR__ . '/../config.php';

class Midtrans {
    /**
     * Dapatkan Snap Token dari Midtrans
     *
     * @param string $orderId
     * @param float|int $grossAmount
     * @param array $customerDetails
     * @param array $itemDetails
     * @return array
     * @throws Exception
     */
    public static function getSnapToken($orderId, $grossAmount, $customerDetails = [], $itemDetails = []) {
        if (!extension_loaded('curl')) {
            throw new Exception("Ekstensi cURL tidak aktif di server PHP.");
        }

        $serverKey = MIDTRANS_SERVER_KEY;
        $url = MIDTRANS_IS_PRODUCTION 
            ? 'https://app.midtrans.com/snap/v1/transactions' 
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $grossAmount,
            ]
        ];

        if (!empty($customerDetails)) {
            $payload['customer_details'] = $customerDetails;
        }

        if (!empty($itemDetails)) {
            $payload['item_details'] = $itemDetails;
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($serverKey . ':')
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $errorMsg);
        }
        
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        } else {
            $errorDetail = isset($result['error_messages']) 
                ? implode(', ', $result['error_messages']) 
                : ($result['message'] ?? $response);
            throw new Exception("Midtrans Snap API Error (HTTP $httpCode): " . $errorDetail);
        }
    }

    /**
     * Verifikasi signature notifikasi webhook Midtrans
     *
     * @param array $notification
     * @return bool
     */
    public static function verifyNotification($notification) {
        $orderId     = $notification['order_id'] ?? '';
        $statusCode  = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $signature   = $notification['signature_key'] ?? '';
        $serverKey   = MIDTRANS_SERVER_KEY;

        if (!$orderId || !$statusCode || !$grossAmount || !$signature) {
            return false;
        }

        $payload = $orderId . $statusCode . $grossAmount . $serverKey;
        $localSignature = hash('sha512', $payload);

        return hash_equals($localSignature, $signature);
    }
}
