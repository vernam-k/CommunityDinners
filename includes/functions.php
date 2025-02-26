<?php
/**
 * Community Dinners - Core Functions
 * 
 * This file contains the core functions for the Community Dinners website.
 */

require_once __DIR__ . '/../config.php';

/**
 * Get the current dinner data
 * 
 * @return array Current dinner data
 */
function getCurrentDinner() {
    static $cache = null;
    static $cacheTime = 0;
    
    $now = time();
    $dinnerFile = DINNERS_PATH . '/current.json';
    
    // Check if file exists
    if (!file_exists($dinnerFile)) {
        // Create a new dinner if none exists
        $dinner = createNewDinner();
        return $dinner;
    }
    
    $lastModified = filemtime($dinnerFile);
    
    // Use cached data if it's fresh (less than 2 seconds old)
    if ($cache !== null && $cacheTime >= $lastModified && $now - $cacheTime < 2) {
        return $cache;
    }
    
    // Load fresh data
    $content = file_get_contents($dinnerFile);
    $dinner = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If JSON is invalid, create a new dinner
        $dinner = createNewDinner();
    }
    
    $cache = $dinner;
    $cacheTime = $now;
    
    return $dinner;
}

/**
 * Save the current dinner data
 * 
 * @param array $dinner Dinner data to save
 * @return bool Success status
 */
function saveCurrentDinner($dinner) {
    $dinnerFile = DINNERS_PATH . '/current.json';
    return file_put_contents($dinnerFile, json_encode($dinner, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

/**
 * Create a new dinner
 * 
 * @param string $date Optional specific date for the dinner
 * @return array New dinner data
 */
function createNewDinner($date = null) {
    if ($date === null) {
        $date = getNextDinnerDate();
    }
    
    $dinnerId = 'dinner_' . str_replace('-', '', $date);
    
    $dinner = [
        'id' => $dinnerId,
        'date' => $date,
        'theme' => '',
        'location' => '',
        'time' => '18:00',
        'volunteers' => [
            'setup' => [],
            'cleanup' => []
        ],
        'menu' => [
            'main_dishes' => [],
            'sides' => [],
            'drinks' => [],
            'appetizers' => [],
            'supplies' => []
        ],
        'notes' => [],
        'rsvp' => []
    ];
    
    saveCurrentDinner($dinner);
    return $dinner;
}

/**
 * Get the next dinner date based on configuration
 * 
 * @return string Next dinner date (YYYY-MM-DD)
 */
function getNextDinnerDate() {
    $config = getConfig();
    $dinnerDay = $config['dinner_day'];
    
    $today = new DateTime();
    $dayOfWeek = (int)$today->format('w'); // 0 (Sunday) to 6 (Saturday)
    
    // Calculate days until next dinner
    $daysUntil = ($dinnerDay - $dayOfWeek + 7) % 7;
    
    // If today is dinner day but it's after 6 PM, schedule for next week
    if ($daysUntil === 0 && (int)$today->format('G') >= 18) {
        $daysUntil = 7;
    }
    
    // Get the date of the next dinner
    $nextDinner = clone $today;
    $nextDinner->modify("+{$daysUntil} days");
    
    return $nextDinner->format('Y-m-d');
}

/**
 * Archive the current dinner and create a new one
 * 
 * @param string $userName User who initiated the archive
 * @return array New dinner data
 */
function archiveCurrentDinner($userName) {
    $currentDinner = getCurrentDinner();
    $dinnerId = $currentDinner['id'];
    $dinnerDate = $currentDinner['date'];
    
    // Save to archive
    $archiveFile = DINNERS_PATH . '/archived/' . $dinnerId . '.json';
    file_put_contents($archiveFile, json_encode($currentDinner, JSON_PRETTY_PRINT), LOCK_EX);
    
    // Log the archive action
    $logMessage = date('Y-m-d H:i:s') . " - Dinner {$dinnerDate} archived by {$userName}\n";
    file_put_contents(LOGS_PATH . '/archive_log.txt', $logMessage, FILE_APPEND | LOCK_EX);
    
    // Create new dinner
    return createNewDinner();
}

/**
 * Get a list of archived dinners
 * 
 * @return array List of archived dinners with basic info
 */
function getArchivedDinners() {
    $archivedDir = DINNERS_PATH . '/archived';
    $files = glob($archivedDir . '/*.json');
    $dinners = [];
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $dinner = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            $dinners[] = [
                'id' => $dinner['id'],
                'date' => $dinner['date'],
                'theme' => $dinner['theme'],
                'rsvp_count' => count($dinner['rsvp'])
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($dinners, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    return $dinners;
}

/**
 * Get a specific archived dinner
 * 
 * @param string $dinnerId ID of the dinner to retrieve
 * @return array|null Dinner data or null if not found
 */
function getArchivedDinner($dinnerId) {
    $archiveFile = DINNERS_PATH . '/archived/' . $dinnerId . '.json';
    
    if (!file_exists($archiveFile)) {
        return null;
    }
    
    $content = file_get_contents($archiveFile);
    $dinner = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $dinner;
}

/**
 * Calculate the recommended donation for a party
 * 
 * @param int $adults Number of adults (18+)
 * @param int $teens Number of teens (13-17)
 * @param int $children Number of children (5-12)
 * @param int $under5 Number of children under 5
 * @return float Recommended donation amount
 */
function calculateDonation($adults, $teens, $children, $under5) {
    $config = getConfig();
    $amounts = $config['donation_amounts'];
    
    return ($adults * $amounts['adults']) + 
           ($teens * $amounts['teens']) + 
           ($children * $amounts['children']) + 
           ($under5 * $amounts['under5']);
}

/**
 * Get user data by name
 * 
 * @param string $name User name
 * @return array|null User data or null if not found
 */
function getUserByName($name) {
    $users = getUsers();
    
    foreach ($users as $user) {
        if (strtolower($user['name']) === strtolower($name)) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Get all users
 * 
 * @return array List of users
 */
function getUsers() {
    $usersFile = DATA_PATH . '/users.json';
    
    if (!file_exists($usersFile)) {
        return [];
    }
    
    $content = file_get_contents($usersFile);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    
    return $data['users'] ?? [];
}

/**
 * Save users data
 * 
 * @param array $users List of users
 * @return bool Success status
 */
function saveUsers($users) {
    $usersFile = DATA_PATH . '/users.json';
    $data = ['users' => $users];
    return file_put_contents($usersFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

/**
 * Create a new user
 * 
 * @param string $name User name
 * @return array|false New user data or false if user already exists
 */
function createUser($name) {
    if (getUserByName($name) !== null) {
        return false;
    }
    
    $users = getUsers();
    $userId = count($users) + 1;
    
    $newUser = [
        'id' => (string)$userId,
        'name' => $name,
        'created_at' => date('c')
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    return $newUser;
}

/**
 * Check if updates are available since a timestamp
 * 
 * @param int $lastUpdate Last update timestamp (milliseconds)
 * @return array Update status and data
 */
function checkForUpdates($lastUpdate) {
    $dinnerFile = DINNERS_PATH . '/current.json';
    $lastModified = filemtime($dinnerFile) * 1000; // Convert to milliseconds
    
    if ($lastModified > $lastUpdate) {
        $dinner = getCurrentDinner();
        
        return [
            'hasUpdates' => true,
            'timestamp' => time() * 1000,
            'updates' => [
                'menu' => generateMenuHTML($dinner['menu']),
                'volunteers' => generateVolunteersHTML($dinner['volunteers']),
                'rsvp' => generateRSVPHTML($dinner['rsvp']),
                'notes' => generateNotesHTML($dinner['notes']),
                'details' => generateDetailsHTML($dinner)
            ]
        ];
    }
    
    return [
        'hasUpdates' => false,
        'timestamp' => time() * 1000
    ];
}

/**
 * Generate HTML for the menu section
 * 
 * @param array $menu Menu data
 * @return string HTML content
 */
function generateMenuHTML($menu) {
    $html = '';
    $categories = [
        'main_dishes' => 'Main Dishes',
        'sides' => 'Sides',
        'drinks' => 'Drinks',
        'appetizers' => 'Appetizers',
        'supplies' => 'Supplies'
    ];
    
    foreach ($categories as $key => $label) {
        $html .= '<div class="menu-category">';
        $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
        $html .= '<ul class="item-list">';
        
        if (empty($menu[$key])) {
            $html .= '<li class="empty-message">No items yet</li>';
        } else {
            foreach ($menu[$key] as $item) {
                $html .= '<li>';
                $html .= '<span class="item-name">' . htmlspecialchars($item['item']) . '</span>';
                $html .= '<span class="item-contributor">by ' . htmlspecialchars($item['name']) . '</span>';
                
                // Add remove button if user is logged in and is the contributor
                if (isset($_SESSION['user']) && $_SESSION['user']['name'] === $item['name']) {
                    $html .= '<button class="remove-item" data-category="' . $key . '" data-item="' . htmlspecialchars($item['item']) . '">Remove</button>';
                }
                
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Generate HTML for the volunteers section
 * 
 * @param array $volunteers Volunteers data
 * @return string HTML content
 */
function generateVolunteersHTML($volunteers) {
    $html = '';
    $roles = [
        'setup' => 'Setup',
        'cleanup' => 'Cleanup'
    ];
    
    foreach ($roles as $key => $label) {
        $html .= '<div class="volunteer-role">';
        $html .= '<h3>' . htmlspecialchars($label) . '</h3>';
        $html .= '<ul class="volunteer-list">';
        
        if (empty($volunteers[$key])) {
            $html .= '<li class="empty-message">No volunteers yet</li>';
        } else {
            foreach ($volunteers[$key] as $volunteer) {
                $html .= '<li>';
                $html .= htmlspecialchars($volunteer['name']);
                
                // Add remove button if user is logged in and is the volunteer
                if (isset($_SESSION['user']) && $_SESSION['user']['name'] === $volunteer['name']) {
                    $html .= '<button class="remove-volunteer" data-role="' . $key . '">Remove</button>';
                }
                
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        
        // Add signup button if user is logged in
        if (isset($_SESSION['user'])) {
            $html .= '<button class="volunteer-signup" data-role="' . $key . '">Sign Up</button>';
        }
        
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Generate HTML for the RSVP section
 * 
 * @param array $rsvps RSVP data
 * @return string HTML content
 */
function generateRSVPHTML($rsvps) {
    $html = '';
    
    // Calculate totals
    $totalAdults = 0;
    $totalTeens = 0;
    $totalChildren = 0;
    $totalUnder5 = 0;
    $totalDonation = 0;
    
    foreach ($rsvps as $rsvp) {
        $totalAdults += $rsvp['adults'];
        $totalTeens += $rsvp['teens'];
        $totalChildren += $rsvp['children'];
        $totalUnder5 += $rsvp['under5'];
        $totalDonation += calculateDonation($rsvp['adults'], $rsvp['teens'], $rsvp['children'], $rsvp['under5']);
    }
    
    $totalPeople = $totalAdults + $totalTeens + $totalChildren + $totalUnder5;
    
    // Summary section
    $html .= '<div class="rsvp-summary">';
    $html .= '<h3>RSVP Summary</h3>';
    $html .= '<p>Total RSVPs: ' . count($rsvps) . '</p>';
    $html .= '<p>Total People: ' . $totalPeople . '</p>';
    $html .= '<ul>';
    $html .= '<li>Adults (18+): ' . $totalAdults . '</li>';
    $html .= '<li>Teens (13-17): ' . $totalTeens . '</li>';
    $html .= '<li>Children (5-12): ' . $totalChildren . '</li>';
    $html .= '<li>Under 5: ' . $totalUnder5 . '</li>';
    $html .= '</ul>';
    $html .= '<p>Total Recommended Donation: $' . number_format($totalDonation, 2) . '</p>';
    $html .= '</div>';
    
    // RSVP list
    $html .= '<div class="rsvp-list">';
    $html .= '<h3>RSVPs</h3>';
    
    if (empty($rsvps)) {
        $html .= '<p class="empty-message">No RSVPs yet</p>';
    } else {
        $html .= '<ul>';
        foreach ($rsvps as $rsvp) {
            $partyTotal = $rsvp['adults'] + $rsvp['teens'] + $rsvp['children'] + $rsvp['under5'];
            $donation = calculateDonation($rsvp['adults'], $rsvp['teens'], $rsvp['children'], $rsvp['under5']);
            
            $html .= '<li>';
            $html .= '<span class="rsvp-name">' . htmlspecialchars($rsvp['name']) . '</span>';
            $html .= '<span class="rsvp-party">Party of ' . $partyTotal . '</span>';
            $html .= '<span class="rsvp-donation">Recommended Donation: $' . number_format($donation, 2) . '</span>';
            
            // Add details button
            $html .= '<button class="rsvp-details-toggle" data-name="' . htmlspecialchars($rsvp['name']) . '">Details</button>';
            
            // Hidden details section
            $html .= '<div class="rsvp-details" id="rsvp-details-' . htmlspecialchars($rsvp['name']) . '" style="display: none;">';
            $html .= '<ul>';
            $html .= '<li>Adults (18+): ' . $rsvp['adults'] . '</li>';
            $html .= '<li>Teens (13-17): ' . $rsvp['teens'] . '</li>';
            $html .= '<li>Children (5-12): ' . $rsvp['children'] . '</li>';
            $html .= '<li>Under 5: ' . $rsvp['under5'] . '</li>';
            $html .= '</ul>';
            
            // Add remove button if user is logged in and is the RSVP owner
            if (isset($_SESSION['user']) && $_SESSION['user']['name'] === $rsvp['name']) {
                $html .= '<button class="remove-rsvp">Remove RSVP</button>';
                $html .= '<button class="edit-rsvp">Edit RSVP</button>';
            }
            
            $html .= '</div>'; // End details
            
            $html .= '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for the notes section
 * 
 * @param array $notes Notes data
 * @return string HTML content
 */
function generateNotesHTML($notes) {
    $html = '<div class="notes-container">';
    $html .= '<h3>Notes</h3>';
    
    if (empty($notes)) {
        $html .= '<p class="empty-message">No notes yet</p>';
    } else {
        $html .= '<ul class="notes-list">';
        foreach ($notes as $index => $note) {
            $timestamp = new DateTime($note['timestamp']);
            
            $html .= '<li class="note-item">';
            $html .= '<div class="note-header">';
            $html .= '<span class="note-author">' . htmlspecialchars($note['name']) . '</span>';
            $html .= '<span class="note-time">' . $timestamp->format('M j, g:i A') . '</span>';
            $html .= '</div>';
            $html .= '<div class="note-text">' . nl2br(htmlspecialchars($note['text'])) . '</div>';
            
            // Add remove button if user is logged in and is the note author
            if (isset($_SESSION['user']) && $_SESSION['user']['name'] === $note['name']) {
                $html .= '<button class="remove-note" data-index="' . $index . '">Remove</button>';
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate HTML for the dinner details section
 *
 * @param array $dinner Dinner data
 * @return string HTML content
 */
function generateDetailsHTML($dinner) {
    $date = new DateTime($dinner['date']);
    $timeObj = DateTime::createFromFormat('H:i', $dinner['time']);
    
    $html = '<h3>Dinner Details</h3>';
    
    // Date
    $html .= '<div class="dinner-date">';
    $html .= '<strong>Date:</strong> ' . $date->format('l, F j, Y');
    $html .= '</div>';
    
    // Theme
    $html .= '<div class="dinner-theme">';
    $html .= '<strong>Theme:</strong> ';
    $html .= '<span id="theme-display">';
    $html .= empty($dinner['theme']) ? '<em>No theme set</em>' : htmlspecialchars($dinner['theme']);
    $html .= '</span>';
    
    // Edit button and form if user is logged in
    if (isset($_SESSION['user'])) {
        $html .= '<button id="edit-theme-btn" class="edit-btn">Edit</button>';
        
        $html .= '<div id="theme-form" class="edit-form" style="display: none;">';
        $html .= '<input type="text" id="theme-input" value="' . htmlspecialchars($dinner['theme']) . '">';
        $html .= '<button id="save-theme-btn" class="save-btn">Save</button>';
        $html .= '<button id="cancel-theme-btn" class="cancel-btn">Cancel</button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    // Location
    $html .= '<div class="dinner-location">';
    $html .= '<strong>Location:</strong> ';
    $html .= '<span id="location-display">';
    $html .= empty($dinner['location']) ? '<em>No location set</em>' : htmlspecialchars($dinner['location']);
    $html .= '</span>';
    
    // Edit button and form if user is logged in
    if (isset($_SESSION['user'])) {
        $html .= '<button id="edit-location-btn" class="edit-btn">Edit</button>';
        
        $html .= '<div id="location-form" class="edit-form" style="display: none;">';
        $html .= '<input type="text" id="location-input" value="' . htmlspecialchars($dinner['location']) . '">';
        $html .= '<button id="save-location-btn" class="save-btn">Save</button>';
        $html .= '<button id="cancel-location-btn" class="cancel-btn">Cancel</button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    // Time
    $html .= '<div class="dinner-time">';
    $html .= '<strong>Time:</strong> ';
    $html .= '<span id="time-display">';
    $html .= $timeObj ? $timeObj->format('g:i A') : '6:00 PM';
    $html .= '</span>';
    
    // Edit button and form if user is logged in
    if (isset($_SESSION['user'])) {
        $html .= '<button id="edit-time-btn" class="edit-btn">Edit</button>';
        
        $html .= '<div id="time-form" class="edit-form" style="display: none;">';
        $html .= '<input type="time" id="time-input" value="' . htmlspecialchars($dinner['time']) . '">';
        $html .= '<button id="save-time-btn" class="save-btn">Save</button>';
        $html .= '<button id="cancel-time-btn" class="cancel-btn">Cancel</button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Initialize users.json if it doesn't exist
$usersFile = DATA_PATH . '/users.json';
if (!file_exists($usersFile)) {
    saveUsers([]);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}