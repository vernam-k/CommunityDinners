/**
 * Community Dinners - Main JavaScript
 * 
 * This file handles dynamic updates and form submissions.
 */

// Start polling for updates
function startPolling() {
    // Poll every 3 seconds
    setInterval(checkForUpdates, 3000);
    console.log('Started polling for updates');
}

// Check for updates
function checkForUpdates() {
    const updateStatus = document.getElementById('update-status');
    updateStatus.textContent = 'Checking for updates...';
    
    fetch(`api.php?action=check_updates&last=${lastUpdateTime}&nocache=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.hasUpdates) {
                updateChangedSections(data.updates);
                lastUpdateTime = data.timestamp;
                updateStatus.textContent = 'Updated just now';
                
                // Reset status after 3 seconds
                setTimeout(() => {
                    updateStatus.textContent = 'Up to date';
                }, 3000);
            } else {
                updateStatus.textContent = 'Up to date';
            }
        })
        .catch(error => {
            console.error('Update check failed:', error);
            updateStatus.textContent = 'Update check failed';
        });
}

// Update changed sections
function updateChangedSections(updates) {
    // Update only the sections that have changed
    if (updates.menu) {
        document.getElementById('menu-container').innerHTML = updates.menu;
        highlightSection('menu-container');
    }
    
    if (updates.volunteers) {
        document.getElementById('volunteers-container').innerHTML = updates.volunteers;
        highlightSection('volunteers-container');
    }
    
    if (updates.rsvp) {
        document.getElementById('rsvp-container').innerHTML = updates.rsvp;
        highlightSection('rsvp-container');
    }
    
    if (updates.notes) {
        document.getElementById('notes-container').innerHTML = updates.notes;
        highlightSection('notes-container');
    }
    
    if (updates.details) {
        document.getElementById('dinner-details').innerHTML = updates.details;
        highlightSection('dinner-details');
    }
    
    // Re-attach event handlers
    setupForms();
}

// Highlight a section that was updated
function highlightSection(sectionId) {
    const section = document.getElementById(sectionId);
    section.classList.remove('highlight-update');
    
    // Trigger reflow
    void section.offsetWidth;
    
    section.classList.add('highlight-update');
}

// Set up forms and button handlers
function setupForms() {
    // Theme editing
    setupEditForm('theme');
    
    // Location editing
    setupEditForm('location');
    
    // Time editing
    setupEditForm('time');
    
    // Menu form
    setupMenuForm();
    
    // Volunteer signup
    setupVolunteerButtons();
    
    // RSVP form
    setupRsvpForm();
    
    // Notes form
    setupNotesForm();
    
    // Archive dinner button
    setupArchiveButton();
    
    // RSVP details toggle
    setupRsvpDetailsToggle();
}

// Set up edit form for a field (theme, location, time)
function setupEditForm(field) {
    const displayEl = document.getElementById(`${field}-display`);
    const editBtn = document.getElementById(`edit-${field}-btn`);
    const formEl = document.getElementById(`${field}-form`);
    const inputEl = document.getElementById(`${field}-input`);
    const saveBtn = document.getElementById(`save-${field}-btn`);
    const cancelBtn = document.getElementById(`cancel-${field}-btn`);
    
    if (!editBtn || !formEl || !inputEl || !saveBtn || !cancelBtn) {
        return;
    }
    
    // Remove any existing event listeners by cloning and replacing the elements
    const newEditBtn = editBtn.cloneNode(true);
    editBtn.parentNode.replaceChild(newEditBtn, editBtn);
    
    const newSaveBtn = saveBtn.cloneNode(true);
    saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
    
    const newCancelBtn = cancelBtn.cloneNode(true);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    // Show edit form when edit button is clicked
    newEditBtn.addEventListener('click', function() {
        displayEl.style.display = 'none';
        newEditBtn.style.display = 'none';
        formEl.style.display = 'block';
        inputEl.focus();
    });
    
    // Hide edit form when cancel button is clicked
    newCancelBtn.addEventListener('click', function() {
        displayEl.style.display = 'inline';
        newEditBtn.style.display = 'inline';
        formEl.style.display = 'none';
    });
    
    // Save changes when save button is clicked
    newSaveBtn.addEventListener('click', function() {
        const value = inputEl.value;
        
        const formData = new FormData();
        formData.append(field, value);
        
        fetch(`api.php?action=update_${field}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                
                // Update display
                if (field === 'time') {
                    // Format time for display
                    const timeObj = new Date(`2000-01-01T${value}`);
                    displayEl.textContent = timeObj.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                } else {
                    displayEl.textContent = value || `No ${field} set`;
                }
                
                // Hide edit form
                displayEl.style.display = 'inline';
                newEditBtn.style.display = 'inline';
                formEl.style.display = 'none';
                
                // Re-setup forms to ensure all event handlers are properly attached
                setupForms();
            } else {
                showMessage('error', data.message);
            }
        })
        .catch(error => {
            showMessage('error', 'An error occurred. Please try again.');
        });
    });
}

// Set up menu form
function setupMenuForm() {
    const menuForm = document.getElementById('menu-form');
    
    if (!menuForm) {
        return;
    }
    
    // Remove any existing event listeners by cloning and replacing the element
    const newMenuForm = menuForm.cloneNode(true);
    menuForm.parentNode.replaceChild(newMenuForm, menuForm);
    
    newMenuForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const item = document.getElementById('item').value;
        const category = document.getElementById('category').value;
        
        const formData = new FormData();
        formData.append('item', item);
        formData.append('category', category);
        
        fetch('api.php?action=add_menu_item', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                newMenuForm.reset();
                // Re-setup forms to ensure all event handlers are properly attached
                setupForms();
            } else {
                showMessage('error', data.message);
            }
        })
        .catch(error => {
            showMessage('error', 'An error occurred. Please try again.');
        });
    });
    
    // Set up remove buttons for menu items
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            const item = this.dataset.item;
            
            if (confirm(`Are you sure you want to remove "${item}" from the menu?`)) {
                const formData = new FormData();
                formData.append('category', category);
                formData.append('item', item);
                
                fetch('api.php?action=remove_menu_item', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        // Re-setup forms to ensure all event handlers are properly attached
                        setupForms();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showMessage('error', 'An error occurred. Please try again.');
                });
            }
        });
    });
}

// Set up volunteer buttons
function setupVolunteerButtons() {
    // Remove existing event listeners by replacing buttons
    document.querySelectorAll('.volunteer-signup').forEach(button => {
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });
    
    // Volunteer signup buttons
    document.querySelectorAll('.volunteer-signup').forEach(button => {
        button.addEventListener('click', function() {
            const role = this.dataset.role;
            
            const formData = new FormData();
            formData.append('role', role);
            
            fetch('api.php?action=volunteer_signup', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message);
                    // Re-setup forms to ensure all event handlers are properly attached
                    setupForms();
                } else {
                    showMessage('error', data.message);
                }
            })
            .catch(error => {
                showMessage('error', 'An error occurred. Please try again.');
            });
        });
    });
    
    // Remove volunteer buttons
    document.querySelectorAll('.remove-volunteer').forEach(button => {
        button.addEventListener('click', function() {
            const role = this.dataset.role;
            
            if (confirm('Are you sure you want to remove yourself from this volunteer role?')) {
                const formData = new FormData();
                formData.append('role', role);
                
                fetch('api.php?action=remove_volunteer', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        // Re-setup forms to ensure all event handlers are properly attached
                        setupForms();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showMessage('error', 'An error occurred. Please try again.');
                });
            }
        });
    });
}

// Set up RSVP form
function setupRsvpForm() {
    const rsvpForm = document.getElementById('rsvp-form');
    const removeRsvpBtn = document.getElementById('remove-rsvp');
    
    if (!rsvpForm) {
        return;
    }
    
    // Remove any existing event listeners by cloning and replacing the form
    const newRsvpForm = rsvpForm.cloneNode(true);
    rsvpForm.parentNode.replaceChild(newRsvpForm, rsvpForm);
    
    // Also clone and replace the remove button if it exists
    if (removeRsvpBtn) {
        const newRemoveBtn = removeRsvpBtn.cloneNode(true);
        removeRsvpBtn.parentNode.replaceChild(newRemoveBtn, removeRsvpBtn);
    }
    
    // Update donation calculation when inputs change
    const adultsInput = document.getElementById('adults');
    const teensInput = document.getElementById('teens');
    const childrenInput = document.getElementById('children');
    const under5Input = document.getElementById('under5');
    const donationCalc = document.getElementById('donation-calculation');
    
    function updateDonationCalculation() {
        const adults = parseInt(adultsInput.value) || 0;
        const teens = parseInt(teensInput.value) || 0;
        const children = parseInt(childrenInput.value) || 0;
        const under5 = parseInt(under5Input.value) || 0;
        
        // Show loading state
        donationCalc.textContent = 'Calculating donation...';
        
        // Fetch current donation rates
        fetch('api.php?action=get_config')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const rates = data.config.donation_amounts;
                    const donation = (adults * rates.adults) +
                                    (teens * rates.teens) +
                                    (children * rates.children) +
                                    (under5 * rates.under5);
                    
                    donationCalc.textContent = `Recommended Donation: $${donation.toFixed(2)}`;
                } else {
                    throw new Error('Failed to get config data');
                }
            })
            .catch(error => {
                console.error('Failed to get donation rates:', error);
                
                // Use default rates if there's an error
                const defaultRates = {
                    adults: 10,
                    teens: 6,
                    children: 3,
                    under5: 0
                };
                
                const donation = (adults * defaultRates.adults) +
                                (teens * defaultRates.teens) +
                                (children * defaultRates.children) +
                                (under5 * defaultRates.under5);
                
                donationCalc.textContent = `Recommended Donation: $${donation.toFixed(2)}`;
            });
    }
    
    if (adultsInput && teensInput && childrenInput && under5Input && donationCalc) {
        // Add event listeners for both change and input events to update in real-time
        adultsInput.addEventListener('change', updateDonationCalculation);
        adultsInput.addEventListener('input', updateDonationCalculation);
        
        teensInput.addEventListener('change', updateDonationCalculation);
        teensInput.addEventListener('input', updateDonationCalculation);
        
        childrenInput.addEventListener('change', updateDonationCalculation);
        childrenInput.addEventListener('input', updateDonationCalculation);
        
        under5Input.addEventListener('change', updateDonationCalculation);
        under5Input.addEventListener('input', updateDonationCalculation);
        
        // Calculate initial donation amount when the form loads
        updateDonationCalculation();
    }
    
    // Submit RSVP form
    newRsvpForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const adults = parseInt(adultsInput.value) || 0;
        const teens = parseInt(teensInput.value) || 0;
        const children = parseInt(childrenInput.value) || 0;
        const under5 = parseInt(under5Input.value) || 0;
        
        if (adults + teens + children + under5 === 0) {
            showMessage('error', 'Party size cannot be zero');
            return;
        }
        
        const formData = new FormData();
        formData.append('adults', adults);
        formData.append('teens', teens);
        formData.append('children', children);
        formData.append('under5', under5);
        
        fetch('api.php?action=add_rsvp', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                // Re-setup forms to ensure all event handlers are properly attached
                setupForms();
            } else {
                showMessage('error', data.message);
            }
        })
        .catch(error => {
            showMessage('error', 'An error occurred. Please try again.');
        });
    });
    
    // Remove RSVP button
    const newRemoveRsvpBtn = document.getElementById('remove-rsvp');
    if (newRemoveRsvpBtn) {
        newRemoveRsvpBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove your RSVP?')) {
                fetch('api.php?action=remove_rsvp', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        // Re-setup forms to ensure all event handlers are properly attached
                        setupForms();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showMessage('error', 'An error occurred. Please try again.');
                });
            }
        });
    }
}

// Set up notes form
function setupNotesForm() {
    const notesForm = document.getElementById('notes-form');
    
    if (!notesForm) {
        return;
    }
    
    // Remove any existing event listeners by cloning and replacing the form
    const newNotesForm = notesForm.cloneNode(true);
    notesForm.parentNode.replaceChild(newNotesForm, notesForm);
    
    newNotesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const text = document.getElementById('note-text').value;
        
        if (!text.trim()) {
            showMessage('error', 'Note cannot be empty');
            return;
        }
        
        const formData = new FormData();
        formData.append('text', text);
        
        fetch('api.php?action=add_note', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                newNotesForm.reset();
                // Re-setup forms to ensure all event handlers are properly attached
                setupForms();
            } else {
                showMessage('error', data.message);
            }
        })
        .catch(error => {
            showMessage('error', 'An error occurred. Please try again.');
        });
    });
    
    // Set up remove buttons for notes
    document.querySelectorAll('.remove-note').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.dataset.index;
            
            if (confirm('Are you sure you want to remove this note?')) {
                const formData = new FormData();
                formData.append('index', index);
                
                fetch('api.php?action=remove_note', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                        // Re-setup forms to ensure all event handlers are properly attached
                        setupForms();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showMessage('error', 'An error occurred. Please try again.');
                });
            }
        });
    });
}

// Set up archive dinner button
function setupArchiveButton() {
    const archiveBtn = document.getElementById('archive-dinner');
    
    if (!archiveBtn) {
        return;
    }
    
    // Remove any existing event listeners by cloning and replacing the button
    const newArchiveBtn = archiveBtn.cloneNode(true);
    archiveBtn.parentNode.replaceChild(newArchiveBtn, archiveBtn);
    
    newArchiveBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to end the current dinner and create a new one? This action cannot be undone.')) {
            fetch('api.php?action=archive_dinner', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
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
}

// Set up RSVP details toggle
function setupRsvpDetailsToggle() {
    // Remove existing event listeners by replacing buttons
    document.querySelectorAll('.rsvp-details-toggle').forEach(button => {
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });
    
    document.querySelectorAll('.rsvp-details-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.dataset.name;
            const detailsEl = document.getElementById(`rsvp-details-${name}`);
            
            if (detailsEl) {
                if (detailsEl.style.display === 'none') {
                    detailsEl.style.display = 'block';
                    this.textContent = 'Hide Details';
                } else {
                    detailsEl.style.display = 'none';
                    this.textContent = 'Details';
                }
            }
        });
    });
}

// Show a message
function showMessage(type, message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message`;
    messageDiv.textContent = message;
    
    // Find a good place to show the message
    const container = document.querySelector('.dinner-container') || 
                      document.querySelector('.admin-container') || 
                      document.querySelector('.archive-container') || 
                      document.querySelector('.login-container') || 
                      document.querySelector('.container');
    
    if (container) {
        container.prepend(messageDiv);
        
        // Remove the message after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Set up forms and button handlers
    setupForms();
});