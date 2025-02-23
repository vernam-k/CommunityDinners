const express = require('express');
const fs = require('fs').promises;
const path = require('path');
const app = express();
const port = 3000;

// Middleware
app.use(express.json());
app.use(express.static('.'));

// Helper function to read JSON file
async function readJsonFile(filename) {
    const data = await fs.readFile(path.join(__dirname, 'data', filename), 'utf8');
    return JSON.parse(data);
}

// Helper function to write JSON file
async function writeJsonFile(filename, data) {
    await fs.writeFile(
        path.join(__dirname, 'data', filename),
        JSON.stringify(data, null, 2),
        'utf8'
    );
}

// Get current dinner
app.get('/api/dinner/current', async (req, res) => {
    try {
        const dinners = await readJsonFile('dinners.json');
        res.json(dinners.current);
    } catch (error) {
        res.status(500).json({ error: 'Failed to read dinner data' });
    }
});

// Get next week's dinner
app.get('/api/dinner/next', async (req, res) => {
    try {
        const dinners = await readJsonFile('dinners.json');
        res.json(dinners.next);
    } catch (error) {
        res.status(500).json({ error: 'Failed to read dinner data' });
    }
});

// Update dinner data
app.post('/api/dinner/:type', async (req, res) => {
    try {
        const { type } = req.params;
        const dinners = await readJsonFile('dinners.json');
        dinners[type] = req.body;
        await writeJsonFile('dinners.json', dinners);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Failed to update dinner data' });
    }
});

// Get settings
app.get('/api/settings', async (req, res) => {
    try {
        const settings = await readJsonFile('settings.json');
        res.json(settings);
    } catch (error) {
        res.status(500).json({ error: 'Failed to read settings' });
    }
});

// Update settings
app.post('/api/settings', async (req, res) => {
    try {
        await writeJsonFile('settings.json', req.body);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Failed to update settings' });
    }
});

// Archive current dinner
app.post('/api/archive', async (req, res) => {
    try {
        const dinners = await readJsonFile('dinners.json');
        
        // Add current dinner to archived list with timestamp
        const archivedDinner = {
            ...dinners.current,
            archivedAt: new Date().toISOString()
        };
        dinners.archived.unshift(archivedDinner);
        
        // Move next week's dinner to current
        dinners.current = dinners.next;
        
        // Create new next week's dinner
        const nextDate = new Date(dinners.current.date);
        nextDate.setDate(nextDate.getDate() + 7);
        dinners.next = {
            date: nextDate.toISOString().split('T')[0],
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
        
        await writeJsonFile('dinners.json', dinners);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Failed to archive dinner' });
    }
});

// Get archived dinners
app.get('/api/archived', async (req, res) => {
    try {
        const dinners = await readJsonFile('dinners.json');
        res.json(dinners.archived);
    } catch (error) {
        res.status(500).json({ error: 'Failed to read archived dinners' });
    }
});

// Schedule dinner archiving
function scheduleArchiving() {
    const now = new Date();
    const nextSaturday = new Date(now);
    nextSaturday.setDate(now.getDate() + (6 - now.getDay()));
    nextSaturday.setHours(20, 0, 0, 0);
    
    if (now > nextSaturday) {
        nextSaturday.setDate(nextSaturday.getDate() + 7);
    }
    
    const timeUntilArchive = nextSaturday - now;
    setTimeout(async () => {
        try {
            await fetch('http://localhost:' + port + '/api/archive', { method: 'POST' });
            scheduleArchiving(); // Schedule next archive
        } catch (error) {
            console.error('Failed to archive dinner:', error);
            scheduleArchiving(); // Retry scheduling
        }
    }, timeUntilArchive);
}

// Start server
app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
    scheduleArchiving();
});