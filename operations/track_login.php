<?php
function trackLogin($username, $conn) {
    // Get IP address
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // Get user agent string
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Detect OS
    $os = "Unknown";
    $os_array = array(
        '/windows nt 10/i'      => 'Windows 10',
        '/windows nt 6.3/i'     => 'Windows 8.1',
        '/windows nt 6.2/i'     => 'Windows 8',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/windows nt 6.0/i'     => 'Windows Vista',
        '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     => 'Windows XP',
        '/windows xp/i'         => 'Windows XP',
        '/macintosh|mac os x/i' => 'macOS',
        '/mac_powerpc/i'        => 'Mac OS 9',
        '/linux/i'              => 'Linux',
        '/ubuntu/i'             => 'Ubuntu',
        '/iphone/i'             => 'iPhone',
        '/ipod/i'              => 'iPod',
        '/ipad/i'              => 'iPad',
        '/android/i'           => 'Android',
        '/webos/i'             => 'Mobile'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os = $value;
            break;
        }
    }

    // Detect browser
    $browser = "Unknown";
    if (preg_match('/Edg/i', $user_agent)) {
        $browser = 'Microsoft Edge';
    } elseif (preg_match('/MSIE|Trident/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/OPR|Opera/i', $user_agent)) {
        $browser = 'Opera';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    }

    // Detect device type
    $device = "Desktop";
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        $device = "Tablet";
    } else if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        $device = "Mobile";
    }

    // Insert into login_logs
    $sql = "INSERT INTO login_logs (username, ip, os, browser, device, login_date) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $ip, $os, $browser, $device);
    $stmt->execute();
}
?> 