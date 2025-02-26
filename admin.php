<?php
/**
 * Community Dinners - Admin Page
 * 
 * This file allows administrators to configure dinner settings.
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

// Get current configuration
$config = getConfig();

// Include header
include 'includes/header.php';
?>

<div class="admin-container">
    <h2>Admin Settings</h2>
    
    <div class="admin-section">
        <h3>Dinner Configuration</h3>
        
        <form id="config-form" class="ajax-form">
            <div class="form-group">
                <label for="dinner_day">Default Dinner Day:</label>
                <select id="dinner_day" name="dinner_day">
                    <option value="0" <?php echo $config['dinner_day'] == 0 ? 'selected' : ''; ?>>Sunday</option>
                    <option value="1" <?php echo $config['dinner_day'] == 1 ? 'selected' : ''; ?>>Monday</option>
                    <option value="2" <?php echo $config['dinner_day'] == 2 ? 'selected' : ''; ?>>Tuesday</option>
                    <option value="3" <?php echo $config['dinner_day'] == 3 ? 'selected' : ''; ?>>Wednesday</option>
                    <option value="4" <?php echo $config['dinner_day'] == 4 ? 'selected' : ''; ?>>Thursday</option>
                    <option value="5" <?php echo $config['dinner_day'] == 5 ? 'selected' : ''; ?>>Friday</option>
                    <option value="6" <?php echo $config['dinner_day'] == 6 ? 'selected' : ''; ?>>Saturday</option>
                </select>
                <p class="help-text">This is the default day of the week for community dinners.</p>
            </div>
            
            <h4>Recommended Donation Amounts</h4>
            
            <div class="form-group">
                <label for="adult_donation">Adults (18+):</label>
                <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" id="adult_donation" name="adult_donation" min="0" step="0.01" value="<?php echo $config['donation_amounts']['adults']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="teen_donation">Teens (13-17):</label>
                <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" id="teen_donation" name="teen_donation" min="0" step="0.01" value="<?php echo $config['donation_amounts']['teens']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="children_donation">Children (5-12):</label>
                <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" id="children_donation" name="children_donation" min="0" step="0.01" value="<?php echo $config['donation_amounts']['children']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="under5_donation">Under 5:</label>
                <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" id="under5_donation" name="under5_donation" min="0" step="0.01" value="<?php echo $config['donation_amounts']['under5']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Configuration</button>
            </div>
        </form>
    </div>
    
    <div class="admin-section">
        <h3>Dinner Management</h3>
        
        <div class="admin-actions">
            <button id="archive-dinner" class="btn btn-danger">End Current Dinner & Create New One</button>
            <p class="help-text">This will archive the current dinner and create a new one for the next scheduled date.</p>
        </div>
    </div>
    
    <div class="admin-section">
        <h3>Archive Log</h3>
        
        <?php
        $logFile = LOGS_PATH . '/archive_log.txt';
        if (file_exists($logFile)) {
            $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if (!empty($logs)) {
                echo '<div class="log-container">';
                echo '<ul class="log-list">';
                
                foreach (array_reverse($logs) as $log) {
                    echo '<li>' . htmlspecialchars($log) . '</li>';
                }
                
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<p>No archive logs found.</p>';
            }
        } else {
            echo '<p>No archive logs found.</p>';
        }
        ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set up config form submission
        const configForm = document.getElementById('config-form');
        
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(configForm);
            
            fetch('api.php?action=update_config', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message);
                } else {
                    showMessage('error', data.message);
                }
            })
            .catch(error => {
                showMessage('error', 'An error occurred. Please try again.');
            });
        });
        
        // Set up archive dinner button
        const archiveButton = document.getElementById('archive-dinner');
        
        archiveButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to end the current dinner and create a new one? This action cannot be undone.')) {
                fetch('api.php?action=archive_dinner', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showMessage('error', 'An error occurred. Please try again.');
                });
            }
        });
        
        // Helper function to show messages
        function showMessage(type, message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.textContent = message;
            
            document.querySelector('.admin-container').prepend(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>