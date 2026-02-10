# LassoMap

An interactive PHP-based map application with pin management and lasso selection functionality.

## Features

- **Interactive Map**: 800x600px map with a beautiful gradient background
- **Pin Management**: Create, view, and manage pins with detailed information
- **15 Pre-generated Pins**: Sample pins with random locations and data
- **Lasso Selection**: Draw freehand around multiple pins to select them
- **Click to View**: Click any pin to see its full details
- **Session Storage**: Pins persist during your session using PHP sessions

## Pin Properties

Each pin contains:
- **Name**: Descriptive name of the location
- **Location**: X and Y coordinates on the map
- **Note**: Additional text information
- **Height**: Elevation in meters above sea level

## Installation & Usage

1. Make sure you have PHP 7.0 or higher installed
2. Clone this repository
3. Start the PHP development server:
   ```bash
   php -S localhost:8000
   ```
4. Open your browser and navigate to `http://localhost:8000/index.php`

## How to Use

### Viewing Pin Information
- Click on any red pin marker to view its details in a modal

### Adding New Pins
1. Click the "📍 Add Pin" button
2. Click anywhere on the map to place the pin
3. Fill in the pin details (name, note, height)
4. Click "Add Pin" to save

### Lasso Selection
1. Click the "⭕ Lasso Select" button
2. Click and drag on the map to draw a selection area
3. Release to complete the selection
4. A list will appear showing all selected pins
5. Click any pin in the list to view its details

## Security Features

- XSS prevention through input sanitization
- Input validation for all form fields
- Cryptographically secure ID generation
- Error handling for network requests

## Technologies Used

- PHP (Session management and data storage)
- HTML5 Canvas (Lasso drawing)
- Vanilla JavaScript (Interactive functionality)
- CSS3 (Styling and animations)

## Browser Compatibility

Works in all modern browsers that support:
- HTML5 Canvas
- ES6 JavaScript
- CSS3

## License

MIT License - Feel free to use and modify as needed.
