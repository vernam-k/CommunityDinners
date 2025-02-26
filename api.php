<?php
/**
 * Community Dinners - API Handler
 * 
 * This file handles AJAX requests for the Community Dinners website.
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle different API actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_updates':
        $lastUpdate = (int)($_GET['last'] ?? 0);
        echo json_encode(checkForUpdates($lastUpdate));
        break;
        
    case 'update_theme':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to update the theme']);
            break;
        }
        
        $theme = sanitize($_POST['theme'] ?? '');
        $dinner = getCurrentDinner();
        $dinner['theme'] = $theme;
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Theme updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update theme']);
        }
        break;
        
    case 'update_location':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to update the location']);
            break;
        }
        
        $location = sanitize($_POST['location'] ?? '');
        $dinner = getCurrentDinner();
        $dinner['location'] = $location;
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update location']);
        }
        break;
        
    case 'update_time':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to update the time']);
            break;
        }
        
        $time = sanitize($_POST['time'] ?? '');
        
        // Validate time format (HH:MM)
        if (!preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
            echo json_encode(['success' => false, 'message' => 'Invalid time format']);
            break;
        }
        
        $dinner = getCurrentDinner();
        $dinner['time'] = $time;
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Time updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update time']);
        }
        break;
        
    case 'add_menu_item':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to add menu items']);
            break;
        }
        
        $item = sanitize($_POST['item'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $user = getCurrentUser();
        
        if (empty($item)) {
            echo json_encode(['success' => false, 'message' => 'Item cannot be empty']);
            break;
        }
        
        $validCategories = ['main_dishes', 'sides', 'drinks', 'appetizers', 'supplies'];
        if (!in_array($category, $validCategories)) {
            echo json_encode(['success' => false, 'message' => 'Invalid category']);
            break;
        }
        
        $dinner = getCurrentDinner();
        $dinner['menu'][$category][] = [
            'item' => $item,
            'name' => $user['name']
        ];
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Menu item added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add menu item']);
        }
        break;
        
    case 'remove_menu_item':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to remove menu items']);
            break;
        }
        
        $item = sanitize($_POST['item'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $user = getCurrentUser();
        
        $validCategories = ['main_dishes', 'sides', 'drinks', 'appetizers', 'supplies'];
        if (!in_array($category, $validCategories)) {
            echo json_encode(['success' => false, 'message' => 'Invalid category']);
            break;
        }
        
        $dinner = getCurrentDinner();
        $found = false;
        
        foreach ($dinner['menu'][$category] as $key => $menuItem) {
            if ($menuItem['item'] === $item && $menuItem['name'] === $user['name']) {
                unset($dinner['menu'][$category][$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'Item not found or you are not the contributor']);
            break;
        }
        
        // Reindex array
        $dinner['menu'][$category] = array_values($dinner['menu'][$category]);
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Menu item removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove menu item']);
        }
        break;
        
    case 'volunteer_signup':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to volunteer']);
            break;
        }
        
        $role = sanitize($_POST['role'] ?? '');
        $user = getCurrentUser();
        
        $validRoles = ['setup', 'cleanup'];
        if (!in_array($role, $validRoles)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            break;
        }
        
        $dinner = getCurrentDinner();
        
        // Check if already volunteered
        foreach ($dinner['volunteers'][$role] as $volunteer) {
            if ($volunteer['name'] === $user['name']) {
                echo json_encode(['success' => false, 'message' => 'You are already volunteering for this role']);
                break 2;
            }
        }
        
        $dinner['volunteers'][$role][] = [
            'name' => $user['name']
        ];
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Volunteer signup successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to sign up as volunteer']);
        }
        break;
        
    case 'remove_volunteer':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to remove volunteer signup']);
            break;
        }
        
        $role = sanitize($_POST['role'] ?? '');
        $user = getCurrentUser();
        
        $validRoles = ['setup', 'cleanup'];
        if (!in_array($role, $validRoles)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            break;
        }
        
        $dinner = getCurrentDinner();
        $found = false;
        
        foreach ($dinner['volunteers'][$role] as $key => $volunteer) {
            if ($volunteer['name'] === $user['name']) {
                unset($dinner['volunteers'][$role][$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'You are not volunteering for this role']);
            break;
        }
        
        // Reindex array
        $dinner['volunteers'][$role] = array_values($dinner['volunteers'][$role]);
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Volunteer signup removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove volunteer signup']);
        }
        break;
        
    case 'add_rsvp':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to RSVP']);
            break;
        }
        
        $adults = (int)($_POST['adults'] ?? 0);
        $teens = (int)($_POST['teens'] ?? 0);
        $children = (int)($_POST['children'] ?? 0);
        $under5 = (int)($_POST['under5'] ?? 0);
        $user = getCurrentUser();
        
        if ($adults < 0 || $teens < 0 || $children < 0 || $under5 < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid party numbers']);
            break;
        }
        
        if ($adults + $teens + $children + $under5 === 0) {
            echo json_encode(['success' => false, 'message' => 'Party size cannot be zero']);
            break;
        }
        
        $dinner = getCurrentDinner();
        
        // Check if already RSVP'd
        $existingKey = null;
        foreach ($dinner['rsvp'] as $key => $rsvp) {
            if ($rsvp['name'] === $user['name']) {
                $existingKey = $key;
                break;
            }
        }
        
        $rsvpData = [
            'name' => $user['name'],
            'adults' => $adults,
            'teens' => $teens,
            'children' => $children,
            'under5' => $under5,
            'timestamp' => date('c')
        ];
        
        if ($existingKey !== null) {
            $dinner['rsvp'][$existingKey] = $rsvpData;
            $message = 'RSVP updated successfully';
        } else {
            $dinner['rsvp'][] = $rsvpData;
            $message = 'RSVP added successfully';
        }
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save RSVP']);
        }
        break;
        
    case 'remove_rsvp':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to remove RSVP']);
            break;
        }
        
        $user = getCurrentUser();
        $dinner = getCurrentDinner();
        $found = false;
        
        foreach ($dinner['rsvp'] as $key => $rsvp) {
            if ($rsvp['name'] === $user['name']) {
                unset($dinner['rsvp'][$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'RSVP not found']);
            break;
        }
        
        // Reindex array
        $dinner['rsvp'] = array_values($dinner['rsvp']);
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'RSVP removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove RSVP']);
        }
        break;
        
    case 'add_note':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to add notes']);
            break;
        }
        
        $text = sanitize($_POST['text'] ?? '');
        $user = getCurrentUser();
        
        if (empty($text)) {
            echo json_encode(['success' => false, 'message' => 'Note cannot be empty']);
            break;
        }
        
        $dinner = getCurrentDinner();
        $dinner['notes'][] = [
            'text' => $text,
            'name' => $user['name'],
            'timestamp' => date('c')
        ];
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Note added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add note']);
        }
        break;
        
    case 'remove_note':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to remove notes']);
            break;
        }
        
        $index = (int)($_POST['index'] ?? -1);
        $user = getCurrentUser();
        
        if ($index < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid note index']);
            break;
        }
        
        $dinner = getCurrentDinner();
        
        if (!isset($dinner['notes'][$index])) {
            echo json_encode(['success' => false, 'message' => 'Note not found']);
            break;
        }
        
        if ($dinner['notes'][$index]['name'] !== $user['name']) {
            echo json_encode(['success' => false, 'message' => 'You can only remove your own notes']);
            break;
        }
        
        unset($dinner['notes'][$index]);
        
        // Reindex array
        $dinner['notes'] = array_values($dinner['notes']);
        
        if (saveCurrentDinner($dinner)) {
            echo json_encode(['success' => true, 'message' => 'Note removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove note']);
        }
        break;
        
    case 'archive_dinner':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to archive the dinner']);
            break;
        }
        
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Only administrators can archive dinners']);
            break;
        }
        
        $user = getCurrentUser();
        $newDinner = archiveCurrentDinner($user['name']);
        
        if ($newDinner) {
            echo json_encode(['success' => true, 'message' => 'Dinner archived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to archive dinner']);
        }
        break;
        
    case 'get_config':
        // This endpoint returns the current configuration
        $config = getConfig();
        echo json_encode([
            'success' => true,
            'config' => $config
        ]);
        break;
        
    case 'update_config':
        if (!isLoggedIn() || !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Only administrators can update configuration']);
            break;
        }
        
        $dinnerDay = (int)($_POST['dinner_day'] ?? 6);
        $adultDonation = (float)($_POST['adult_donation'] ?? 10);
        $teenDonation = (float)($_POST['teen_donation'] ?? 6);
        $childrenDonation = (float)($_POST['children_donation'] ?? 3);
        $under5Donation = (float)($_POST['under5_donation'] ?? 0);
        
        // Validate dinner day (0-6)
        if ($dinnerDay < 0 || $dinnerDay > 6) {
            echo json_encode(['success' => false, 'message' => 'Invalid dinner day']);
            break;
        }
        
        // Validate donation amounts
        if ($adultDonation < 0 || $teenDonation < 0 || $childrenDonation < 0 || $under5Donation < 0) {
            echo json_encode(['success' => false, 'message' => 'Donation amounts cannot be negative']);
            break;
        }
        
        $config = getConfig();
        $config['dinner_day'] = $dinnerDay;
        $config['donation_amounts'] = [
            'adults' => $adultDonation,
            'teens' => $teenDonation,
            'children' => $childrenDonation,
            'under5' => $under5Donation
        ];
        
        if (saveConfig($config)) {
            echo json_encode(['success' => true, 'message' => 'Configuration updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update configuration']);
        }
        break;
        
    case 'update_about':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to update the About page']);
            break;
        }
        
        $content = $_POST['content'] ?? '';
        $user = getCurrentUser();
        
        // Sanitize content but allow HTML tags for the WYSIWYG editor
        // We're not using the sanitize() function here because it would strip HTML tags
        
        $aboutFile = DATA_PATH . '/about.json';
        $aboutData = [];
        
        if (file_exists($aboutFile)) {
            $aboutData = json_decode(file_get_contents($aboutFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $aboutData = [];
            }
        }
        
        $aboutData['content'] = $content;
        $aboutData['last_updated'] = date('Y-m-d H:i:s');
        $aboutData['last_updated_by'] = $user['name'];
        
        if (file_put_contents($aboutFile, json_encode($aboutData, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
            echo json_encode([
                'success' => true,
                'message' => 'About page updated successfully',
                'last_updated' => $aboutData['last_updated'],
                'last_updated_by' => $aboutData['last_updated_by']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update About page']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}