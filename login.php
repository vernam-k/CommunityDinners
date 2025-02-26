<?php
/**
 * Community Dinners - Login Page
 * 
 * This file handles user login and registration.
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    header('Location: index.php');
    exit;
}

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    
    if (empty($name)) {
        $error = 'Please enter your name';
    } else {
        if (login($name)) {
            $success = 'Login successful';
            
            // Redirect after successful login
            header('Location: index.php');
            exit;
        } else {
            $error = 'Login failed';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="login-container">
    <h2>Login / Register</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <p>Welcome to Community Dinners! Please enter your name to login or register.</p>
    
    <form method="post" action="login.php">
        <div class="form-group">
            <label for="name">Your Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Login / Register</button>
        </div>
    </form>
    
    <div class="login-info">
        <p><strong>Note:</strong> This community site uses a simple name-based login system. 
        If you're new, entering your name will create an account. If you've logged in before, 
        entering the same name will log you back in.</p>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>