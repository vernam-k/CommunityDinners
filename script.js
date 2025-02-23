// Global state
let currentDinner = null;
let nextDinner = null;
let archivedDinners = [];
let prices = null;

// Default data structure
const defaultDinner = {
    date: "2025-03-01",
    theme: "",
    location: "",
    notes: "",
    dishes: {
        mainDishes: [],
        sides: [],
        supplies: []
    },
    rsvps: []
};

const defaultNextDinner = {
    date: "2025-03-08",
    theme: "",
    location: "",
    notes: "",
    dishes: {
        mainDishes: [],
        sides: [],
        supplies: []
    },
    rsvps: []
};

const defaultSettings = {
    prices: {
        adult: 10,
        teen: 8,
        child: 5,
        underFive: 0
    },
    aboutContent: document.getElementById('aboutText')?.innerHTML || ""
};

// Load saved data from API
async function loadData() {
    try {
        // Try to load existing data
        const [currentResponse, nextResponse, settingsResponse, archivedResponse] = await Promise.all([
            fetch('api.php?action=get_current'),
            fetch('api.php?action=get_next'),
            fetch('api.php?action=get_settings'),
            fetch('api.php?action=get_archived')
        ]);

        // If any data doesn't exist, create it with defaults
        if (!currentResponse.ok) {
            currentDinner = defaultDinner;
            await saveData('current');
        } else {
            currentDinner = await currentResponse.json();
        }

        if (!nextResponse.ok) {
            nextDinner = defaultNextDinner;
            await saveData('next');
        } else {
            nextDinner = await nextResponse.json();
        }

        if (!settingsResponse.ok) {
            prices = defaultSettings.prices;
            await fetch('api.php?action=update_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(defaultSettings)
            });
        } else {
            const settings = await settingsResponse.json();
            prices = settings.prices;
        }

        if (!archivedResponse.ok) {
            archivedDinners = [];
        } else {
            archivedDinners = await archivedResponse.json();
        }
        
        updateDisplay();
    } catch (error) {
        console.error('Failed to load data:', error);
        // Use defaults if loading fails
        currentDinner = defaultDinner;
        nextDinner = defaultNextDinner;
        prices = defaultSettings.prices;
        archivedDinners = [];
        updateDisplay();
    }
}

// Save data to API
async function saveData(type = 'current') {
    try {
        await fetch(`api.php?action=update_dinner&type=${type}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(type === 'current' ? currentDinner : nextDinner)
        });
    } catch (error) {
        console.error('Failed to save data:', error);
        alert('Failed to save changes. Please try again.');
    }
}

// Format date to always show Saturday
function formatDinnerDate(dateString) {
    const date = new Date(dateString);
    const month = date.toLocaleString('en-US', { month: 'long' });
    const day = date.getDate();
    const year = date.getFullYear();
    return `Saturday, ${month} ${day}, ${year}`;
}

// Update all display elements
function updateDisplay() {
    if (!currentDinner) return;

    // Update date
    document.getElementById('dinnerDate').textContent = formatDinnerDate(currentDinner.date);
    
    // Update theme & location
    const themeLocation = document.getElementById('themeLocation');
    if (themeLocation) {
        themeLocation.textContent = currentDinner.theme || 'Click to add theme and location...';
    }
    
    // Update notes
    const notes = document.getElementById('notes');
    if (notes) {
        notes.textContent = currentDinner.notes || 'Click to add notes...';
    }
    
    // Update dishes lists
    updateDishesList('mainDishes');
    updateDishesList('sides');
    updateDishesList('supplies');
    
    // Update RSVPs
    updateRSVPList();
}

// Update specific dishes list
function updateDishesList(category) {
    const list = document.getElementById(category);
    if (!list) return;
    
    list.innerHTML = '';
    currentDinner.dishes[category].forEach((item, index) => {
        const li = document.createElement('li');
        li.innerHTML = `
            <div class="dish-info">
                <span class="dish-name">${item.name}</span>
                <span class="dish-bringer">Brought by: ${item.bringer}</span>
            </div>
            <button onclick="removeItem('${category}', ${index})" class="remove-btn">Remove</button>
        `;
        list.appendChild(li);
    });
}

// Add new item to a dishes list
async function addItem(category) {
    const bringer = prompt('Enter your name:');
    if (!bringer) return;

    const itemName = prompt(`What ${category.replace(/([A-Z])/g, ' $1').toLowerCase()} are you bringing?`);
    if (!itemName) return;

    const item = {
        name: itemName,
        bringer: bringer
    };

    currentDinner.dishes[category].push(item);
    await saveData();
    updateDishesList(category);
}

// Remove item from a dishes list
async function removeItem(category, index) {
    const item = currentDinner.dishes[category][index];
    if (confirm(`Are you sure you want to remove "${item.name}" brought by ${item.bringer}?`)) {
        currentDinner.dishes[category].splice(index, 1);
        await saveData();
        updateDishesList(category);
    }
}

// Calculate total recommended donation
function calculateDonation() {
    if (!prices) return 0;
    
    const adults = parseInt(document.getElementById('adultsCount').value) || 0;
    const teens = parseInt(document.getElementById('teens').value) || 0;
    const children = parseInt(document.getElementById('children').value) || 0;
    
    const total = (adults * prices.adult) + 
                 (teens * prices.teen) + 
                 (children * prices.child);
    
    document.getElementById('totalDonation').textContent = total;
    return total;
}

// Add new RSVP
async function addRSVP() {
    const name = prompt('Enter your name or group name:');
    if (!name) return;
    
    const adults = parseInt(document.getElementById('adultsCount').value) || 0;
    const teens = parseInt(document.getElementById('teens').value) || 0;
    const children = parseInt(document.getElementById('children').value) || 0;
    const underFive = parseInt(document.getElementById('underFive').value) || 0;
    
    const rsvp = {
        name,
        adults,
        teens,
        children,
        underFive,
        donation: calculateDonation()
    };
    
    currentDinner.rsvps.push(rsvp);
    await saveData();
    updateRSVPList();
    
    // Reset form
    document.getElementById('adultsCount').value = 0;
    document.getElementById('teens').value = 0;
    document.getElementById('children').value = 0;
    document.getElementById('underFive').value = 0;
    calculateDonation();
}

// Update RSVP list display
function updateRSVPList() {
    const list = document.getElementById('rsvpList');
    if (!list) return;
    
    list.innerHTML = '';
    currentDinner.rsvps.forEach((rsvp, index) => {
        const div = document.createElement('div');
        div.className = 'rsvp-item';
        div.innerHTML = `
            <div class="rsvp-info">
                <strong>${rsvp.name}</strong><br>
                <span class="rsvp-details">
                    Adults: ${rsvp.adults}, 
                    13+: ${rsvp.teens}, 
                    5-12: ${rsvp.children}, 
                    Under 5: ${rsvp.underFive}
                </span><br>
                <span class="rsvp-donation">
                    Recommended Donation: $${rsvp.donation}
                </span>
            </div>
            <button onclick="removeRSVP(${index})" class="remove-btn">Remove</button>
        `;
        list.appendChild(div);
    });
}

// Remove RSVP
async function removeRSVP(index) {
    const rsvp = currentDinner.rsvps[index];
    if (confirm(`Are you sure you want to remove ${rsvp.name}'s RSVP?`)) {
        currentDinner.rsvps.splice(index, 1);
        await saveData();
        updateRSVPList();
    }
}

// Generate and copy view-only link
function copyViewOnlyLink() {
    const viewOnlyUrl = new URL(window.location.href);
    viewOnlyUrl.searchParams.set('view', 'readonly');
    navigator.clipboard.writeText(viewOnlyUrl.toString())
        .then(() => alert('View-only link copied to clipboard!'))
        .catch(() => alert('Failed to copy link. Please try again.'));
}

// Save editable content
document.querySelectorAll('.edit-field').forEach(field => {
    field.addEventListener('blur', async function() {
        if (this.id === 'themeLocation') {
            currentDinner.theme = this.textContent;
        } else if (this.id === 'notes') {
            currentDinner.notes = this.textContent;
        }
        await saveData();
    });
});

// Add input event listeners for donation calculation
document.querySelectorAll('.age-group input').forEach(input => {
    input.addEventListener('input', calculateDonation);
});

// Check if we're in view-only mode
if (new URLSearchParams(window.location.search).get('view') === 'readonly') {
    document.querySelectorAll('.edit-field').forEach(field => {
        field.contentEditable = false;
    });
    document.querySelectorAll('button').forEach(button => {
        if (!button.classList.contains('view-only')) {
            button.style.display = 'none';
        }
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    loadData();
    
    // Add navigation event listeners
    const nextWeekBtn = document.getElementById('nextWeekBtn');
    if (nextWeekBtn) {
        nextWeekBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'next-week.html';
        });
    }

    const previousDinnersBtn = document.getElementById('previousDinnersBtn');
    if (previousDinnersBtn) {
        previousDinnersBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showArchivedDinners();
            // Update active state
            document.querySelectorAll('.nav-links a').forEach(a => a.classList.remove('active'));
            previousDinnersBtn.classList.add('active');
        });
    }
});