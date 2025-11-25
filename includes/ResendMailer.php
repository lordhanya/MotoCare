<?php
class ResendMailer
{
    private $apiKey;
    private $from;

    public function __construct($apiKey, $from)
    {
        $this->apiKey = $apiKey;
        $this->from = $from;
    }

    /**
     * Send email via Resend API.
     * @param string|array $to single email or array of emails
     * @param string $subject
     * @param string $htmlHtml HTML body
     * @param string|null $textText plain text alternative (optional)
     * @return array response or throws Exception on failure
     */
    public function send($to, $subject, $html, $textText = null)
    {
        $url = "https://api.resend.com/emails";

        $payload = [
            "from" => $this->from,
            "to"   => $to,
            "subject" => $subject,
            "html" => $html
        ];

        if ($textText !== null) {
            $payload["text"] = $textText;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception("cURL Error while sending email: {$curlErr}");
        }

        $decoded = json_decode($resp, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return $decoded;
        }

        // throw an exception with returned details
        $errMsg = isset($decoded['error']) ? json_encode($decoded) : $resp;
        throw new Exception("Resend API error (HTTP {$httpCode}): {$errMsg}");
    }
}
