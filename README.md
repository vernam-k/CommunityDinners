# Community Dinners Website

A web application for coordinating weekly community dinner events. The site helps manage RSVPs, coordinate dishes, track volunteers, and maintain a history of past dinners.

## Features

### Current and Next Week's Dinners
- Theme, location, and volunteer coordination
- Dish and supply management with contributor tracking
- RSVP system with age-based recommended donations
- Notes and announcements section
- Automatic transition between weeks

### Previous Dinners Archive
- Complete history of past dinners
- Attendance statistics
- Dishes brought and contributors
- Comment system for post-dinner feedback

### Admin Settings
- Configurable recommended donation amounts:
  * Adults (18+)
  * Teens (13-17)
  * Children (5-12)
  * Under 5 (always free)
- Archive timing display

### About Page
- Fully editable content section
- Customizable community guidelines
- Participation requirements
- Food standards

## Technical Details

### File Structure
```
community-dinners/
├── index.html          # Current dinner page
├── next-week.html      # Next week's dinner planning
├── previous-dinners.html # Archived dinners
├── admin.html          # Settings page
├── about.html          # About/guidelines page
├── api.php            # Backend API
├── archive.php        # Archiving script
├── script.js          # Core JavaScript
├── styles.css         # Styling
└── data/              # Data storage
    ├── dinners.json   # Dinner data
    └── settings.json  # Site settings
```

### Data Storage
The site uses JSON files for data storage:

#### dinners.json
```json
{
  "current": {
    "date": "2025-03-01",
    "theme": "",
    "location": "",
    "notes": "",
    "dishes": {
      "mainDishes": [],
      "sides": [],
      "supplies": []
    },
    "rsvps": []
  },
  "next": {
    "date": "2025-03-08",
    ...
  },
  "archived": []
}
```

#### settings.json
```json
{
  "prices": {
    "adult": 10,
    "teen": 8,
    "child": 5,
    "underFive": 0
  },
  "aboutContent": ""
}
```

### Automatic Archiving
- Occurs at 8:00 PM every Saturday
- Current dinner moves to archive
- Next week becomes current
- New next week dinner created
- Logs activity to data/archive.log

## Setup Instructions

1. File Upload
   ```bash
   # Upload all files maintaining this structure:
   public_html/communitydinners/
   ├── *.html files
   ├── *.php files
   ├── *.js files
   ├── *.css files
   └── data/
   ```

2. Permissions
   ```bash
   chmod 755 api.php archive.php
   chmod 755 data
   chmod 644 data/*.json
   ```

3. Cron Job Setup in cPanel
   - Access cPanel's Cron Jobs section
   - Add new job:
     ```
     * * * * * php /home/yourusername/public_html/communitydinners/archive.php
     ```
   - Replace 'yourusername' with your cPanel username
   - The script internally checks for 8:00 PM Saturday

## Mobile Support
- Responsive design for all screen sizes
- Touch-friendly interface
- Compact navigation menu
- Dark mode support

## Browser Compatibility
- Works in all modern browsers
- Responsive layout adapts to screen size
- Dark mode respects system preferences

## Maintenance

### Logs
- Check data/archive.log for archiving activity
- Monitor for any errors or issues

### Backups
- Regularly backup the data directory
- Contains all dinner history and settings

### Troubleshooting
- Check file permissions if saving fails
- Verify cron job is running
- Monitor archive.log for timing issues

## Security Notes
- Place data directory outside web root if possible
- Keep backup copies of JSON files
- Monitor disk space for log files

## Development

### Adding Features
- JavaScript in script.js
- Styling in styles.css
- Backend API in api.php
- Data structure in JSON files

### API Endpoints
- GET /api.php?action=get_current
- GET /api.php?action=get_next
- GET /api.php?action=get_archived
- GET /api.php?action=get_settings
- POST /api.php?action=update_dinner&type=current
- POST /api.php?action=update_dinner&type=next
- POST /api.php?action=update_settings
- POST /api.php?action=archive

## Support
For issues or questions:
1. Check file permissions
2. Verify cron job setup
3. Check archive.log
4. Ensure data directory is writable