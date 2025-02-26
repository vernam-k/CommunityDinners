<?php
/**
 * Community Dinners - Authentication Functions
 * 
 * This file contains functions for user authentication.
 */

require_once __DIR__ . '/functions.php';

/**
 * Authenticate a user
 * 
 * @param string $name User name
 * @return bool Authentication success
 */
function login($name) {
    // Sanitize input
    $name = sanitize($name);
    
    if (empty($name)) {
        return false;
    }
    
    $user = getUserByName($name);
    
    if ($user === null) {
        // User doesn't exist, create a new one
        $user = createUser($name);
        
        if ($user === false) {
            return false;
        }
    }
    
    // Set session
    $_SESSION['user'] = $user;
    $_SESSION['logged_in'] = true;
    
    return true;
}

/**
 * Log out the current user
 * 
 * @return void
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get the current logged in user
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return $_SESSION['user'];
}

/**
 * Check if the current user is an admin
 *
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    // All logged-in users are considered admins
    return isLoggedIn();
}