<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class WebinarService
{
    // ── Zoom ──────────────────────────────────────────────────────────────────

    /** Get a Zoom Server-to-Server OAuth access token */
    public static function zoomAccessToken(): string
    {
        $accountId = Setting::get('zoom_account_id', '');
        $clientId  = Setting::get('zoom_api_key', '');
        $secret    = Encryption::decryptIfNeeded(Setting::get('zoom_api_secret', ''));

        if (!$accountId || !$clientId || !$secret) {
            throw new \RuntimeException('Zoom credentials not configured in Settings → Webinar.');
        }

        $ch = curl_init('https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $accountId);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . base64_encode("$clientId:$secret"),
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new \RuntimeException('Zoom auth failed. Check credentials.');
        }

        $data = json_decode($raw, true);
        return $data['access_token'] ?? '';
    }

    /** Create a Zoom meeting */
    public static function createZoomMeeting(array $params): array
    {
        $token = self::zoomAccessToken();

        $payload = json_encode([
            'topic'      => $params['title'],
            'type'       => 2, // Scheduled meeting
            'start_time' => date('Y-m-d\TH:i:s', strtotime($params['scheduled_at'])),
            'duration'   => (int)($params['duration_min'] ?? 60),
            'password'   => substr(bin2hex(random_bytes(4)), 0, 8),
            'settings'   => [
                'host_video'        => true,
                'participant_video'  => true,
                'join_before_host'  => false,
                'waiting_room'      => true,
            ],
        ]);

        $ch = curl_init('https://api.zoom.us/v2/users/me/meetings');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 201) {
            $err = json_decode($raw, true);
            throw new \RuntimeException('Zoom meeting creation failed: ' . ($err['message'] ?? $raw));
        }

        $data = json_decode($raw, true);
        return [
            'meeting_id' => (string)$data['id'],
            'join_url'   => $data['join_url'],
            'start_url'  => $data['start_url'],
            'password'   => $data['password'],
        ];
    }

    /** Delete a Zoom meeting */
    public static function deleteZoomMeeting(string $meetingId): void
    {
        $token = self::zoomAccessToken();
        $ch = curl_init('https://api.zoom.us/v2/meetings/' . $meetingId);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // ── Google Meet ───────────────────────────────────────────────────────────

    /** Create a Google Meet via Calendar API */
    public static function createGoogleMeet(array $params): array
    {
        $oauthJson = Setting::get('gmeet_oauth_json', '');
        if (!$oauthJson) {
            throw new \RuntimeException('Google OAuth JSON not configured in Settings → Webinar.');
        }

        $creds = json_decode($oauthJson, true);
        if (!$creds) {
            throw new \RuntimeException('Invalid Google OAuth JSON.');
        }

        // Get access token using service account
        $token = self::googleServiceAccountToken($creds);

        $startDt = date('Y-m-d\TH:i:s', strtotime($params['scheduled_at']));
        $endDt   = date('Y-m-d\TH:i:s', strtotime($params['scheduled_at']) + ($params['duration_min'] ?? 60) * 60);
        $tz      = date_default_timezone_get();

        $payload = json_encode([
            'summary'     => $params['title'],
            'start'       => ['dateTime' => $startDt, 'timeZone' => $tz],
            'end'         => ['dateTime' => $endDt,   'timeZone' => $tz],
            'conferenceData' => ['createRequest' => ['requestId' => bin2hex(random_bytes(8))]],
        ]);

        $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 && $code !== 201) {
            $err = json_decode($raw, true);
            throw new \RuntimeException('Google Meet creation failed: ' . ($err['error']['message'] ?? $raw));
        }

        $data    = json_decode($raw, true);
        $meetUrl = $data['conferenceData']['entryPoints'][0]['uri'] ?? $data['htmlLink'];

        return [
            'meeting_id' => $data['id'],
            'join_url'   => $meetUrl,
            'start_url'  => $meetUrl,
            'password'   => '',
        ];
    }

    private static function googleServiceAccountToken(array $creds): string
    {
        $now     = time();
        $expiry  = $now + 3600;
        $scope   = 'https://www.googleapis.com/auth/calendar';

        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss'   => $creds['client_email'] ?? '',
            'scope' => $scope,
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $expiry,
            'iat'   => $now,
        ]));

        $base    = str_replace(['+','/','='], ['-','_',''], "$header.$payload");
        $key     = $creds['private_key'] ?? '';
        openssl_sign($base, $sig, $key, 'SHA256withRSA');
        $jwt     = $base . '.' . base64_encode($sig);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($raw, true);
        return $data['access_token'] ?? '';
    }
}
