<?php
/**
 * Community Dinners - Configuration File
 * 
 * This file contains the basic configuration settings for the Community Dinners website.
 */

// Timezone setting
date_default_timezone_set('America/Chicago');

// Path configurations
define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('DINNERS_PATH', DATA_PATH . '/dinners');
define('LOGS_PATH', BASE_PATH . '/logs');

// Default dinner settings
$DEFAULT_CONFIG = [
    'dinner_day' => 6, // 0 = Sunday, 6 = Saturday
    'timezone' => 'America/Chicago',
    'donation_amounts' => [
        'adults' => 10,    // 18+
        'teens' => 6,      // 13-17
        'children' => 3,   // 5-12
        'under5' => 0      // Under 5
    ]
];

/**
 * Get the site configuration
 * 
 * @return array Site configuration
 */
function getConfig() {
    global $DEFAULT_CONFIG;
    
    $configFile = DATA_PATH . '/config.json';
    
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $config;
        }
    }
    
    // If config file doesn't exist or is invalid, create it with defaults
    saveConfig($DEFAULT_CONFIG);
    return $DEFAULT_CONFIG;
}

/**
 * Save the site configuration
 * 
 * @param array $config Configuration to save
 * @return bool Success status
 */
function saveConfig($config) {
    $configFile = DATA_PATH . '/config.json';
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// Initialize config if it doesn't exist
if (!file_exists(DATA_PATH . '/config.json')) {
    saveConfig($DEFAULT_CONFIG);
}