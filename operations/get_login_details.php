<?php
function getBrowserInfo($userAgent) {
    $browser = "Necunoscut";
    $version = "";

    if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
        $browser = "Internet Explorer";
        preg_match('/MSIE ([0-9]+[\.0-9]*)/', $userAgent, $matches);
        if (!empty($matches[1])) {
            $version = $matches[1];
        }
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = "Microsoft Edge";
        preg_match('/Edge\/([0-9]+[\.0-9]*)/', $userAgent, $matches);
        if (!empty($matches[1])) {
            $version = $matches[1];
        }
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = "Mozilla Firefox";
        preg_match('/Firefox\/([0-9]+[\.0-9]*)/', $userAgent, $matches);
        if (!empty($matches[1])) {
            $version = $matches[1];
        }
    } elseif (preg_match('/Chrome/i', $userAgent)) {
        $browser = "Google Chrome";
        preg_match('/Chrome\/([0-9]+[\.0-9]*)/', $userAgent, $matches);
        if (!empty($matches[1])) {
            $version = $matches[1];
        }
    } elseif (preg_match('/Safari/i', $userAgent)) {
        $browser = "Safari";
        preg_match('/Version\/([0-9]+[\.0-9]*)/', $userAgent, $matches);
        if (!empty($matches[1])) {
            $version = $matches[1];
        }
    } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
        $browser = "Opera";
        preg_match('/(?:Opera|OPR)\/([0-9]+[\.0-9]*)/', $userAgent, $matches);
        if (!empty($matches[1])) {
            $version = $matches[1];
        }
    }

    return $version ? "$browser $version" : $browser;
}

function getOperatingSystem($userAgent) {
    $os = "Necunoscut";

    $osArray = array(
        '/windows nt 10/i'      => 'Windows 10',
        '/windows nt 6.3/i'     => 'Windows 8.1',
        '/windows nt 6.2/i'     => 'Windows 8',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/windows nt 6.0/i'     => 'Windows Vista',
        '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     => 'Windows XP',
        '/windows xp/i'         => 'Windows XP',
        '/windows nt 5.0/i'     => 'Windows 2000',
        '/windows me/i'         => 'Windows ME',
        '/win98/i'             => 'Windows 98',
        '/win95/i'             => 'Windows 95',
        '/win16/i'             => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i'       => 'Mac OS 9',
        '/linux/i'             => 'Linux',
        '/ubuntu/i'            => 'Ubuntu',
        '/iphone/i'            => 'iPhone',
        '/ipod/i'              => 'iPod',
        '/ipad/i'              => 'iPad',
        '/android/i'           => 'Android',
        '/blackberry/i'        => 'BlackBerry',
        '/webos/i'             => 'Mobile'
    );

    foreach ($osArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $os = $value;
            break;
        }
    }

    return $os;
}

function getDeviceType($userAgent) {
    $device = "Desktop";

    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
        $device = "Tablet";
    } else if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
        $device = "Mobile";
    }

    return $device;
}

function getLocationInfo($ip) {
    if ($ip == '::1' || $ip == 'localhost' || $ip == '127.0.0.1') {
        return 'Local System';
    }

    // Folosim un serviciu gratuit de geolocație
    $url = "http://ip-api.com/json/" . $ip;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data && $data['status'] == 'success') {
        return array(
            'city' => $data['city'] ?? 'Necunoscut',
            'country' => $data['country'] ?? 'Necunoscut',
            'isp' => $data['isp'] ?? 'Necunoscut'
        );
    }

    return array(
        'city' => 'Necunoscut',
        'country' => 'Necunoscut',
        'isp' => 'Necunoscut'
    );
}
?> 