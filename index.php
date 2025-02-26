<?php
/**
 * Community Dinners - Main Page
 * 
 * This file displays the current dinner information.
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get current dinner data
$dinner = getCurrentDinner();
$date = new DateTime($dinner['date']);
$timeObj = DateTime::createFromFormat('H:i', $dinner['time']);

// Include header
include 'includes/header.php';
?>

<div class="dinner-container">
    <h2>Community Dinner</h2>
    
    <div id="dinner-details" class="dinner-details-section">
        <h3>Dinner Details</h3>
        
        <div class="dinner-date">
            <strong>Date:</strong> <?php echo $date->format('l, F j, Y'); ?>
        </div>
        
        <div class="dinner-theme">
            <strong>Theme:</strong> 
            <span id="theme-display">
                <?php echo empty($dinner['theme']) ? '<em>No theme set</em>' : htmlspecialchars($dinner['theme']); ?>
            </span>
            
            <?php if (isLoggedIn()): ?>
            <button id="edit-theme-btn" class="edit-btn">Edit</button>
            
            <div id="theme-form" class="edit-form" style="display: none;">
                <input type="text" id="theme-input" value="<?php echo htmlspecialchars($dinner['theme']); ?>">
                <button id="save-theme-btn" class="save-btn">Save</button>
                <button id="cancel-theme-btn" class="cancel-btn">Cancel</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="dinner-location">
            <strong>Location:</strong> 
            <span id="location-display">
                <?php echo empty($dinner['location']) ? '<em>No location set</em>' : htmlspecialchars($dinner['location']); ?>
            </span>
            
            <?php if (isLoggedIn()): ?>
            <button id="edit-location-btn" class="edit-btn">Edit</button>
            
            <div id="location-form" class="edit-form" style="display: none;">
                <input type="text" id="location-input" value="<?php echo htmlspecialchars($dinner['location']); ?>">
                <button id="save-location-btn" class="save-btn">Save</button>
                <button id="cancel-location-btn" class="cancel-btn">Cancel</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="dinner-time">
            <strong>Time:</strong> 
            <span id="time-display">
                <?php echo $timeObj ? $timeObj->format('g:i A') : '6:00 PM'; ?>
            </span>
            
            <?php if (isLoggedIn()): ?>
            <button id="edit-time-btn" class="edit-btn">Edit</button>
            
            <div id="time-form" class="edit-form" style="display: none;">
                <input type="time" id="time-input" value="<?php echo htmlspecialchars($dinner['time']); ?>">
                <button id="save-time-btn" class="save-btn">Save</button>
                <button id="cancel-time-btn" class="cancel-btn">Cancel</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dinner-sections">
        <div class="section-column">
            <div id="volunteers-section" class="section">
                <h3>Volunteers</h3>
                
                <div id="volunteers-container">
                    <?php echo generateVolunteersHTML($dinner['volunteers']); ?>
                </div>
            </div>
            
            <div id="rsvp-section" class="section">
                <h3>RSVP</h3>
                
                <?php if (isLoggedIn()): ?>
                <div class="rsvp-form">
                    <h4>RSVP for this dinner</h4>
                    
                    <?php
                    // Check if user has already RSVP'd
                    $userRsvp = null;
                    foreach ($dinner['rsvp'] as $rsvp) {
                        if ($rsvp['name'] === $_SESSION['user']['name']) {
                            $userRsvp = $rsvp;
                            break;
                        }
                    }
                    ?>
                    
                    <form id="rsvp-form" class="ajax-form">
                        <div class="form-group">
                            <label for="adults">Adults (18+):</label>
                            <input type="number" id="adults" name="adults" min="0" value="<?php echo $userRsvp ? $userRsvp['adults'] : 1; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="teens">Teens (13-17):</label>
                            <input type="number" id="teens" name="teens" min="0" value="<?php echo $userRsvp ? $userRsvp['teens'] : 0; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="children">Children (5-12):</label>
                            <input type="number" id="children" name="children" min="0" value="<?php echo $userRsvp ? $userRsvp['children'] : 0; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="under5">Under 5:</label>
                            <input type="number" id="under5" name="under5" min="0" value="<?php echo $userRsvp ? $userRsvp['under5'] : 0; ?>">
                        </div>
                        
                        <div class="form-group">
                            <div id="donation-calculation">
                                <?php
                                if ($userRsvp) {
                                    $donation = calculateDonation($userRsvp['adults'], $userRsvp['teens'], $userRsvp['children'], $userRsvp['under5']);
                                    echo 'Recommended Donation: $' . number_format($donation, 2);
                                } else {
                                    $config = getConfig();
                                    echo 'Recommended Donation: $' . number_format($config['donation_amounts']['adults'], 2);
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" id="rsvp-submit" class="btn btn-primary">
                                <?php echo $userRsvp ? 'Update RSVP' : 'Submit RSVP'; ?>
                            </button>
                            
                            <?php if ($userRsvp): ?>
                            <button type="button" id="remove-rsvp" class="btn btn-danger">Remove RSVP</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <div id="rsvp-container">
                    <?php echo generateRSVPHTML($dinner['rsvp']); ?>
                </div>
            </div>
        </div>
        
        <div class="section-column">
            <div id="menu-section" class="section">
                <h3>Menu</h3>
                
                <?php if (isLoggedIn()): ?>
                <div class="menu-form">
                    <h4>Add to the Menu</h4>
                    
                    <form id="menu-form" class="ajax-form">
                        <div class="form-group">
                            <label for="item">Item:</label>
                            <input type="text" id="item" name="item" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category" required>
                                <option value="main_dishes">Main Dish</option>
                                <option value="sides">Side</option>
                                <option value="drinks">Drink</option>
                                <option value="appetizers">Appetizer</option>
                                <option value="supplies">Supplies</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add Item</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <div id="menu-container">
                    <?php echo generateMenuHTML($dinner['menu']); ?>
                </div>
            </div>
            
            <div id="notes-section" class="section">
                <h3>Notes</h3>
                
                <?php if (isLoggedIn()): ?>
                <div class="notes-form">
                    <h4>Add a Note</h4>
                    
                    <form id="notes-form" class="ajax-form">
                        <div class="form-group">
                            <textarea id="note-text" name="text" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add Note</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <div id="notes-container">
                    <?php echo generateNotesHTML($dinner['notes']); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isAdmin()): ?>
    <div class="admin-actions">
        <button id="archive-dinner" class="btn btn-danger">End Current Dinner & Create New One</button>
    </div>
    <?php endif; ?>
</div>

<script>
    // Store the last update timestamp
    let lastUpdateTime = <?php echo time() * 1000; ?>;
    
    // Start polling for updates when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Start polling
        startPolling();
        
        // Note: setupForms() is already called in main.js
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>