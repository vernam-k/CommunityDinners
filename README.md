# Community Dinners

A simple PHP website for organizing community dinner events. This application allows community members to coordinate dinner events, sign up to bring food, volunteer, and RSVP.

## Features

- Simple name-based login system
- Dinner details management (theme, location, time)
- Menu item signup (main dishes, sides, drinks, appetizers, supplies)
- Volunteer signup (setup, cleanup)
- RSVP system with recommended donation calculation
- Notes section for communication
- Editable About page with WYSIWYG editor
- Dinner archiving
- Configuration settings

## Technical Details

- Built with PHP
- Uses JSON files for data storage (no SQL database required)
- Dynamic updates without page refreshes
- Mobile-responsive design

## Setup

1. Upload the files to your web server
2. Ensure the `data` and `logs` directories are writable by the web server
3. Access the site through your web browser
4. Login with any name to access all features (all logged-in users have full access)

## Directory Structure

- `assets/` - CSS, JavaScript, and image files
- `data/` - JSON data storage
  - `dinners/` - Current and archived dinner data
  - `config.json` - Site configuration
  - `users.json` - User information
  - `about.json` - About page content
- `includes/` - PHP include files
- `logs/` - Log files

## License

The Unlicense - see the LICENSE file for details.

## Source Code

The source code for this project is available on [GitHub](https://github.com/vernam-k/CommunityDinners).