<?php
declare(strict_types=1);

namespace App\Helpers;

class UserAgent
{
    public static function parse(string $ua): array
    {
        $device  = 'unknown';
        $browser = 'Unknown';
        $os      = 'Unknown';

        // Device
        if (preg_match('/tablet|ipad/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|android|iphone/i', $ua)) {
            $device = 'mobile';
        } else {
            $device = 'desktop';
        }

        // Browser
        if (preg_match('/Edg\//i', $ua))            $browser = 'Edge';
        elseif (preg_match('/OPR\//i', $ua))        $browser = 'Opera';
        elseif (preg_match('/Chrome\//i', $ua))     $browser = 'Chrome';
        elseif (preg_match('/Firefox\//i', $ua))    $browser = 'Firefox';
        elseif (preg_match('/Safari\//i', $ua))     $browser = 'Safari';

        // OS
        if (preg_match('/Windows NT/i', $ua))       $os = 'Windows';
        elseif (preg_match('/Mac OS X/i', $ua))     $os = 'macOS';
        elseif (preg_match('/Android/i', $ua))      $os = 'Android';
        elseif (preg_match('/iOS|iPhone|iPad/i', $ua)) $os = 'iOS';
        elseif (preg_match('/Linux/i', $ua))        $os = 'Linux';

        return compact('device', 'browser', 'os');
    }
}
