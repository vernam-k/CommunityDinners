<?php
// Only run on Saturdays at 8:00 PM
$now = new DateTime();
if ($now->format('w') === '6' && $now->format('H') === '20') {
    // Make request to archive endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/api.php?action=archive');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    // Log the result
    $logFile = __DIR__ . '/data/archive.log';
    file_put_contents(
        $logFile,
        date('Y-m-d H:i:s') . ' Archive attempt result: ' . $result . "\n",
        FILE_APPEND
    );
}
?>