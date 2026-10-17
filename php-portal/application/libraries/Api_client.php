<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_client {

    protected $ci;
    protected $api_base_url;
    protected $api_key;
    protected $timeout_seconds = 30;

    public function __construct() {
        $this->ci =& get_instance();
        // Load API configuration from environment or fallback
        $this->api_base_url = getenv('API_BASE_URL') ?: 'http://api:8000';
        $this->api_key = getenv('API_KEY') ?: 'atlas-secret-key-prod-2025';
    }

    /**
     * Send chat query to FastAPI Python backend
     */
    public function send_chat_query($client_id, $conversation_id, $question) {
        $endpoint = rtrim($this->api_base_url, '/') . '/chat';
        $payload = json_encode([
            'client_id' => $client_id,
            'conversation_id' => $conversation_id,
            'question' => $question
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->api_key,
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout_seconds);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error || $http_code >= 500 || !$response) {
            return [
                'success' => false,
                'error' => 'The report assistant is temporarily unavailable. Please retry later or contact support.',
                'raw_error' => $curl_error
            ];
        }

        $decoded = json_decode($response, true);
        if ($http_code === 200 && is_array($decoded)) {
            $decoded['success'] = true;
            return $decoded;
        }

        return [
            'success' => false,
            'error' => isset($decoded['detail']) ? $decoded['detail'] : 'Unable to complete your market inquiry.'
        ];
    }

    /**
     * Fetch escalations for client
     */
    public function get_escalations($client_id) {
        $endpoint = rtrim($this->api_base_url, '/') . '/escalations?client_id=' . urlencode($client_id);
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . $this->api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            return isset($data['escalations']) ? $data['escalations'] : [];
        }
        return [];
    }
}
