<?php
/**
 * Community Dinners - Archive Page
 * 
 * This file displays archived dinners.
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get archived dinners
$archivedDinners = getArchivedDinners();

// Get specific dinner if ID is provided
$selectedDinner = null;
if (isset($_GET['id'])) {
    $dinnerId = $_GET['id'];
    $selectedDinner = getArchivedDinner($dinnerId);
}

// Include header
include 'includes/header.php';
?>

<div class="archive-container">
    <h2>Dinner Archives</h2>
    
    <?php if (empty($archivedDinners)): ?>
    <p class="empty-message">No archived dinners found.</p>
    <?php else: ?>
    
    <div class="archive-layout">
        <div class="archive-list">
            <h3>Past Dinners</h3>
            
            <ul class="dinner-list">
                <?php foreach ($archivedDinners as $dinner): ?>
                <?php
                $date = new DateTime($dinner['date']);
                $isSelected = $selectedDinner && $selectedDinner['id'] === $dinner['id'];
                ?>
                <li class="dinner-item <?php echo $isSelected ? 'selected' : ''; ?>">
                    <a href="archive.php?id=<?php echo urlencode($dinner['id']); ?>">
                        <div class="dinner-date"><?php echo $date->format('F j, Y'); ?></div>
                        <div class="dinner-theme"><?php echo empty($dinner['theme']) ? 'No theme' : htmlspecialchars($dinner['theme']); ?></div>
                        <div class="dinner-count"><?php echo $dinner['rsvp_count']; ?> attendees</div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="archive-details">
            <?php if ($selectedDinner): ?>
            <?php
            $date = new DateTime($selectedDinner['date']);
            $timeObj = DateTime::createFromFormat('H:i', $selectedDinner['time']);
            ?>
            
            <div class="dinner-header">
                <h3>
                    <?php echo $date->format('l, F j, Y'); ?>
                    <?php if (!empty($selectedDinner['theme'])): ?>
                    - <?php echo htmlspecialchars($selectedDinner['theme']); ?>
                    <?php endif; ?>
                </h3>
            </div>
            
            <div class="dinner-info">
                <div class="info-item">
                    <strong>Location:</strong> 
                    <?php echo empty($selectedDinner['location']) ? 'Not specified' : htmlspecialchars($selectedDinner['location']); ?>
                </div>
                
                <div class="info-item">
                    <strong>Time:</strong> 
                    <?php echo $timeObj ? $timeObj->format('g:i A') : '6:00 PM'; ?>
                </div>
            </div>
            
            <div class="archive-sections">
                <div class="section-column">
                    <div class="section">
                        <h4>Menu</h4>
                        
                        <?php
                        $menu = $selectedDinner['menu'];
                        $categories = [
                            'main_dishes' => 'Main Dishes',
                            'sides' => 'Sides',
                            'drinks' => 'Drinks',
                            'appetizers' => 'Appetizers',
                            'supplies' => 'Supplies'
                        ];
                        
                        $hasItems = false;
                        foreach ($categories as $key => $label) {
                            if (!empty($menu[$key])) {
                                $hasItems = true;
                                break;
                            }
                        }
                        
                        if (!$hasItems) {
                            echo '<p class="empty-message">No menu items recorded.</p>';
                        } else {
                            foreach ($categories as $key => $label) {
                                if (empty($menu[$key])) {
                                    continue;
                                }
                                
                                echo '<div class="menu-category">';
                                echo '<h5>' . htmlspecialchars($label) . '</h5>';
                                echo '<ul class="item-list">';
                                
                                foreach ($menu[$key] as $item) {
                                    echo '<li>';
                                    echo '<span class="item-name">' . htmlspecialchars($item['item']) . '</span>';
                                    echo '<span class="item-contributor">by ' . htmlspecialchars($item['name']) . '</span>';
                                    echo '</li>';
                                }
                                
                                echo '</ul>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="section">
                        <h4>Volunteers</h4>
                        
                        <?php
                        $volunteers = $selectedDinner['volunteers'];
                        $roles = [
                            'setup' => 'Setup',
                            'cleanup' => 'Cleanup'
                        ];
                        
                        $hasVolunteers = false;
                        foreach ($roles as $key => $label) {
                            if (!empty($volunteers[$key])) {
                                $hasVolunteers = true;
                                break;
                            }
                        }
                        
                        if (!$hasVolunteers) {
                            echo '<p class="empty-message">No volunteers recorded.</p>';
                        } else {
                            foreach ($roles as $key => $label) {
                                if (empty($volunteers[$key])) {
                                    continue;
                                }
                                
                                echo '<div class="volunteer-role">';
                                echo '<h5>' . htmlspecialchars($label) . '</h5>';
                                echo '<ul class="volunteer-list">';
                                
                                foreach ($volunteers[$key] as $volunteer) {
                                    echo '<li>' . htmlspecialchars($volunteer['name']) . '</li>';
                                }
                                
                                echo '</ul>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="section-column">
                    <div class="section">
                        <h4>Attendance</h4>
                        
                        <?php
                        $rsvps = $selectedDinner['rsvp'];
                        
                        if (empty($rsvps)) {
                            echo '<p class="empty-message">No attendance recorded.</p>';
                        } else {
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
                            echo '<div class="rsvp-summary">';
                            echo '<p>Total RSVPs: ' . count($rsvps) . '</p>';
                            echo '<p>Total People: ' . $totalPeople . '</p>';
                            echo '<ul>';
                            echo '<li>Adults (18+): ' . $totalAdults . '</li>';
                            echo '<li>Teens (13-17): ' . $totalTeens . '</li>';
                            echo '<li>Children (5-12): ' . $totalChildren . '</li>';
                            echo '<li>Under 5: ' . $totalUnder5 . '</li>';
                            echo '</ul>';
                            echo '<p>Total Recommended Donation: $' . number_format($totalDonation, 2) . '</p>';
                            echo '</div>';
                            
                            // RSVP list
                            echo '<div class="rsvp-list">';
                            echo '<h5>Attendees</h5>';
                            echo '<ul>';
                            
                            foreach ($rsvps as $rsvp) {
                                $partyTotal = $rsvp['adults'] + $rsvp['teens'] + $rsvp['children'] + $rsvp['under5'];
                                
                                echo '<li>';
                                echo '<span class="rsvp-name">' . htmlspecialchars($rsvp['name']) . '</span>';
                                echo '<span class="rsvp-party">Party of ' . $partyTotal . '</span>';
                                echo '</li>';
                            }
                            
                            echo '</ul>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="section">
                        <h4>Notes</h4>
                        
                        <?php
                        $notes = $selectedDinner['notes'];
                        
                        if (empty($notes)) {
                            echo '<p class="empty-message">No notes recorded.</p>';
                        } else {
                            echo '<ul class="notes-list">';
                            
                            foreach ($notes as $note) {
                                $timestamp = new DateTime($note['timestamp']);
                                
                                echo '<li class="note-item">';
                                echo '<div class="note-header">';
                                echo '<span class="note-author">' . htmlspecialchars($note['name']) . '</span>';
                                echo '<span class="note-time">' . $timestamp->format('M j, g:i A') . '</span>';
                                echo '</div>';
                                echo '<div class="note-text">' . nl2br(htmlspecialchars($note['text'])) . '</div>';
                                echo '</li>';
                            }
                            
                            echo '</ul>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="select-prompt">
                <p>Select a dinner from the list to view details.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>