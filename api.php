<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Ensure data directory exists
$dataDir = __DIR__ . '/data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Default data structures
$defaultDinners = [
    'current' => [
        'date' => '2025-03-01',
        'theme' => '',
        'location' => '',
        'notes' => '',
        'dishes' => [
            'mainDishes' => [],
            'sides' => [],
            'supplies' => []
        ],
        'rsvps' => []
    ],
    'next' => [
        'date' => '2025-03-08',
        'theme' => '',
        'location' => '',
        'notes' => '',
        'dishes' => [
            'mainDishes' => [],
            'sides' => [],
            'supplies' => []
        ],
        'rsvps' => []
    ],
    'archived' => []
];

$defaultSettings = [
    'prices' => [
        'adult' => 10,
        'teen' => 8,
        'child' => 5,
        'underFive' => 0
    ],
    'aboutContent' => ''
];

// Helper function to read JSON file
function readJsonFile($filename) {
    global $dataDir, $defaultDinners, $defaultSettings;
    $path = $dataDir . '/' . $filename;
    
    if (!file_exists($path)) {
        // Create default file if it doesn't exist
        $defaultData = $filename === 'dinners.json' ? $defaultDinners : $defaultSettings;
        file_put_contents($path, json_encode($defaultData, JSON_PRETTY_PRINT));
        return $defaultData;
    }
    
    $content = file_get_contents($path);
    return json_decode($content, true);
}

// Helper function to write JSON file
function writeJsonFile($filename, $data) {
    global $dataDir;
    $path = $dataDir . '/' . $filename;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_current':
        $dinners = readJsonFile('dinners.json');
        echo json_encode($dinners['current']);
        break;

    case 'get_next':
        $dinners = readJsonFile('dinners.json');
        echo json_encode($dinners['next']);
        break;

    case 'update_dinner':
        $type = $_GET['type'] ?? 'current';
        $dinners = readJsonFile('dinners.json');
        $data = json_decode(file_get_contents('php://input'), true);
        $dinners[$type] = $data;
        if (writeJsonFile('dinners.json', $dinners)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update dinner data']);
        }
        break;

    case 'get_settings':
        $settings = readJsonFile('settings.json');
        echo json_encode($settings);
        break;

    case 'update_settings':
        $data = json_decode(file_get_contents('php://input'), true);
        if (writeJsonFile('settings.json', $data)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update settings']);
        }
        break;

    case 'archive':
        $dinners = readJsonFile('dinners.json');
        
        // Add current dinner to archived list
        $currentDinner = $dinners['current'];
        $currentDinner['archivedAt'] = date('c');
        $currentDinner['comments'] = []; // Initialize comments array for archived dinners
        array_unshift($dinners['archived'], $currentDinner);
        
        // Move next week's dinner to current
        $dinners['current'] = $dinners['next'];
        
        // Create new next week's dinner
        $nextDate = date('Y-m-d', strtotime($dinners['current']['date'] . ' +7 days'));
        $dinners['next'] = [
            'date' => $nextDate,
            'theme' => '',
            'location' => '',
            'notes' => '',
            'dishes' => [
                'mainDishes' => [],
                'sides' => [],
                'supplies' => []
            ],
            'rsvps' => []
        ];
        
        if (writeJsonFile('dinners.json', $dinners)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to archive dinner']);
        }
        break;

    case 'get_archived':
        $dinners = readJsonFile('dinners.json');
        echo json_encode($dinners['archived']);
        break;

    case 'update_archived':
        $dinners = readJsonFile('dinners.json');
        $archivedDinners = json_decode(file_get_contents('php://input'), true);
        $dinners['archived'] = $archivedDinners;
        if (writeJsonFile('dinners.json', $dinners)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update archived dinners']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>