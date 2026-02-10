<?php
session_start();

// Initialize pins if not set
if (!isset($_SESSION['pins'])) {
    $_SESSION['pins'] = [];
    
    // Generate 15 random pins
    $names = ['Mountain View', 'River Crossing', 'Forest Peak', 'Valley Point', 'Ocean Vista', 
              'Desert Oasis', 'Lake Shore', 'Canyon Edge', 'Hilltop', 'Meadow Center',
              'Rocky Point', 'Pine Grove', 'Summit Station', 'Creek Bend', 'Plateau Base'];
    
    $notes = [
        'Beautiful scenic viewpoint',
        'Historic landmark site',
        'Popular hiking destination',
        'Camping area available',
        'Wildlife observation point',
        'Photography hotspot',
        'Natural spring nearby',
        'Ancient rock formations',
        'Bird watching area',
        'Fishing spot',
        'Picnic area',
        'Trail junction',
        'Emergency shelter',
        'Weather station',
        'Research point'
    ];
    
    for ($i = 0; $i < 15; $i++) {
        $_SESSION['pins'][] = [
            'id' => uniqid(),
            'name' => $names[$i],
            'x' => rand(50, 750),
            'y' => rand(50, 550),
            'note' => $notes[$i],
            'height' => rand(50, 3500)
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    
    if (isset($input['action'])) {
        if ($input['action'] === 'add_pin') {
            // Validate required fields
            if (!isset($input['name']) || !isset($input['x']) || !isset($input['y']) || 
                !isset($input['note']) || !isset($input['height'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            // Validate and sanitize data
            $name = htmlspecialchars(trim($input['name']), ENT_QUOTES, 'UTF-8');
            $x = filter_var($input['x'], FILTER_VALIDATE_INT);
            $y = filter_var($input['y'], FILTER_VALIDATE_INT);
            $note = htmlspecialchars(trim($input['note']), ENT_QUOTES, 'UTF-8');
            $height = filter_var($input['height'], FILTER_VALIDATE_INT);
            
            if ($x === false || $y === false || $height === false || empty($name) || empty($note)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid data types or empty fields']);
                exit;
            }
            
            $pin = [
                'id' => uniqid(),
                'name' => $name,
                'x' => $x,
                'y' => $y,
                'note' => $note,
                'height' => $height
            ];
            $_SESSION['pins'][] = $pin;
            echo json_encode(['success' => true, 'pin' => $pin]);
            exit;
        } elseif ($input['action'] === 'get_pins') {
            echo json_encode(['success' => true, 'pins' => $_SESSION['pins']]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LassoMap - Interactive Pin Map</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 20px;
            max-width: 900px;
            width: 100%;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #48bb78;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #38a169;
        }
        
        .btn.active {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            transform: scale(1.05);
        }
        
        #map-container {
            position: relative;
            width: 800px;
            height: 600px;
            margin: 0 auto;
            background: linear-gradient(180deg, #e0f2fe 0%, #bae6fd 50%, #7dd3fc 100%);
            border: 3px solid #333;
            border-radius: 8px;
            overflow: hidden;
            cursor: crosshair;
        }
        
        #map-canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }
        
        .pin {
            position: absolute;
            width: 30px;
            height: 30px;
            transform: translate(-15px, -15px);
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .pin:hover {
            transform: translate(-15px, -15px) scale(1.2);
        }
        
        .pin.selected {
            filter: drop-shadow(0 0 8px #fbbf24);
        }
        
        .pin svg {
            width: 100%;
            height: 100%;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #333;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-item label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item p {
            color: #333;
            padding: 8px;
            background: #f7fafc;
            border-radius: 5px;
        }
        
        .selected-pins-list {
            position: absolute;
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            z-index: 100;
            display: none;
        }
        
        .selected-pins-list h3 {
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .selected-pin-item {
            padding: 8px;
            margin-bottom: 8px;
            background: #f7fafc;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .selected-pin-item:hover {
            background: #e0f2fe;
        }
        
        .selected-pin-item strong {
            color: #333;
        }
        
        .selected-pin-item small {
            color: #666;
            display: block;
            margin-top: 4px;
        }
        
        .instruction {
            text-align: center;
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️ LassoMap - Interactive Pin Map</h1>
        
        <div class="controls">
            <button class="btn btn-primary" id="add-pin-btn">📍 Add Pin</button>
            <button class="btn btn-secondary" id="lasso-btn">⭕ Lasso Select</button>
        </div>
        
        <div id="map-container">
            <canvas id="map-canvas" width="800" height="600"></canvas>
            <div id="selected-pins-list" class="selected-pins-list"></div>
        </div>
        
        <div class="instruction">
            Click on a pin to view details | Use Lasso Select to select multiple pins
        </div>
    </div>
    
    <!-- Pin Info Modal -->
    <div id="pin-info-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📍 Pin Information</h2>
                <span class="close" onclick="closePinInfo()">&times;</span>
            </div>
            <div id="pin-info-content"></div>
        </div>
    </div>
    
    <!-- Add Pin Modal -->
    <div id="add-pin-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📍 Add New Pin</h2>
                <span class="close" onclick="closeAddPin()">&times;</span>
            </div>
            <form id="add-pin-form">
                <div class="form-group">
                    <label>Pin Name:</label>
                    <input type="text" id="pin-name" required>
                </div>
                <div class="form-group">
                    <label>Note:</label>
                    <textarea id="pin-note" required></textarea>
                </div>
                <div class="form-group">
                    <label>Height (meters above sea level):</label>
                    <input type="number" id="pin-height" required>
                </div>
                <input type="hidden" id="pin-x">
                <input type="hidden" id="pin-y">
                <button type="submit" class="btn btn-primary">Add Pin</button>
            </form>
        </div>
    </div>
    
    <script>
        let pins = <?php echo json_encode($_SESSION['pins']); ?>;
        let lassoMode = false;
        let lassoPoints = [];
        let isDrawing = false;
        let selectedPins = [];
        let addPinMode = false;
        
        // Utility function to escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        const mapContainer = document.getElementById('map-container');
        const canvas = document.getElementById('map-canvas');
        const ctx = canvas.getContext('2d');
        
        // Render all pins
        function renderPins() {
            mapContainer.querySelectorAll('.pin').forEach(pin => pin.remove());
            
            pins.forEach(pin => {
                const pinEl = document.createElement('div');
                pinEl.className = 'pin';
                pinEl.style.left = pin.x + 'px';
                pinEl.style.top = pin.y + 'px';
                pinEl.dataset.id = pin.id;
                pinEl.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="#ef4444" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                `;
                
                pinEl.addEventListener('click', (e) => {
                    if (!lassoMode && !addPinMode) {
                        e.stopPropagation();
                        showPinInfo(pin);
                    }
                });
                
                mapContainer.appendChild(pinEl);
            });
        }
        
        // Show pin information
        function showPinInfo(pin) {
            const content = document.getElementById('pin-info-content');
            content.innerHTML = `
                <div class="info-item">
                    <label>Name:</label>
                    <p>${escapeHtml(pin.name)}</p>
                </div>
                <div class="info-item">
                    <label>Location:</label>
                    <p>X: ${pin.x}, Y: ${pin.y}</p>
                </div>
                <div class="info-item">
                    <label>Height:</label>
                    <p>${pin.height} meters above sea level</p>
                </div>
                <div class="info-item">
                    <label>Note:</label>
                    <p>${escapeHtml(pin.note)}</p>
                </div>
            `;
            document.getElementById('pin-info-modal').style.display = 'block';
        }
        
        function closePinInfo() {
            document.getElementById('pin-info-modal').style.display = 'none';
        }
        
        // Add pin functionality
        document.getElementById('add-pin-btn').addEventListener('click', () => {
            addPinMode = !addPinMode;
            lassoMode = false;
            document.getElementById('add-pin-btn').classList.toggle('active');
            document.getElementById('lasso-btn').classList.remove('active');
            mapContainer.style.cursor = addPinMode ? 'crosshair' : 'default';
            clearLasso();
        });
        
        mapContainer.addEventListener('click', (e) => {
            if (addPinMode && !lassoMode) {
                const rect = mapContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                document.getElementById('pin-x').value = Math.round(x);
                document.getElementById('pin-y').value = Math.round(y);
                document.getElementById('add-pin-modal').style.display = 'block';
            }
        });
        
        document.getElementById('add-pin-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const newPin = {
                action: 'add_pin',
                name: document.getElementById('pin-name').value,
                note: document.getElementById('pin-note').value,
                height: parseInt(document.getElementById('pin-height').value),
                x: parseInt(document.getElementById('pin-x').value),
                y: parseInt(document.getElementById('pin-y').value)
            };
            
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newPin)
            });
            
            const data = await response.json();
            if (data.success) {
                pins.push(data.pin);
                renderPins();
                closeAddPin();
                document.getElementById('add-pin-form').reset();
            }
        });
        
        function closeAddPin() {
            document.getElementById('add-pin-modal').style.display = 'none';
            addPinMode = false;
            document.getElementById('add-pin-btn').classList.remove('active');
            mapContainer.style.cursor = 'default';
        }
        
        // Lasso selection
        document.getElementById('lasso-btn').addEventListener('click', () => {
            lassoMode = !lassoMode;
            addPinMode = false;
            document.getElementById('lasso-btn').classList.toggle('active');
            document.getElementById('add-pin-btn').classList.remove('active');
            mapContainer.style.cursor = lassoMode ? 'crosshair' : 'default';
            if (!lassoMode) {
                clearLasso();
            }
        });
        
        mapContainer.addEventListener('mousedown', (e) => {
            if (lassoMode) {
                isDrawing = true;
                lassoPoints = [];
                const rect = mapContainer.getBoundingClientRect();
                lassoPoints.push({
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                });
            }
        });
        
        mapContainer.addEventListener('mousemove', (e) => {
            if (lassoMode && isDrawing) {
                const rect = mapContainer.getBoundingClientRect();
                lassoPoints.push({
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                });
                drawLasso();
            }
        });
        
        mapContainer.addEventListener('mouseup', (e) => {
            if (lassoMode && isDrawing) {
                isDrawing = false;
                checkPinsInLasso();
            }
        });
        
        function drawLasso() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            if (lassoPoints.length < 2) return;
            
            ctx.beginPath();
            ctx.moveTo(lassoPoints[0].x, lassoPoints[0].y);
            
            for (let i = 1; i < lassoPoints.length; i++) {
                ctx.lineTo(lassoPoints[i].x, lassoPoints[i].y);
            }
            
            ctx.strokeStyle = '#667eea';
            ctx.lineWidth = 3;
            ctx.stroke();
            
            ctx.fillStyle = 'rgba(102, 126, 234, 0.1)';
            ctx.fill();
        }
        
        function clearLasso() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            lassoPoints = [];
            selectedPins = [];
            document.querySelectorAll('.pin').forEach(pin => pin.classList.remove('selected'));
            document.getElementById('selected-pins-list').style.display = 'none';
        }
        
        function clearLassoPath() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            lassoPoints = [];
        }
        
        function isPointInPolygon(point, polygon) {
            let inside = false;
            for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
                const xi = polygon[i].x, yi = polygon[i].y;
                const xj = polygon[j].x, yj = polygon[j].y;
                
                const intersect = ((yi > point.y) !== (yj > point.y))
                    && (point.x < (xj - xi) * (point.y - yi) / (yj - yi) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }
        
        function checkPinsInLasso() {
            if (lassoPoints.length < 3) {
                clearLasso();
                return;
            }
            
            selectedPins = [];
            
            pins.forEach(pin => {
                if (isPointInPolygon({ x: pin.x, y: pin.y }, lassoPoints)) {
                    selectedPins.push(pin);
                }
            });
            
            // Update visual selection
            document.querySelectorAll('.pin').forEach(pinEl => {
                const pinId = pinEl.dataset.id;
                if (selectedPins.some(p => p.id === pinId)) {
                    pinEl.classList.add('selected');
                } else {
                    pinEl.classList.remove('selected');
                }
            });
            
            if (selectedPins.length > 0) {
                showSelectedPinsList();
            }
            
            clearLassoPath();
        }
        
        function showSelectedPinsList() {
            const listEl = document.getElementById('selected-pins-list');
            listEl.innerHTML = `<h3>Selected Pins (${selectedPins.length})</h3>`;
            
            selectedPins.forEach(pin => {
                const item = document.createElement('div');
                item.className = 'selected-pin-item';
                item.innerHTML = `
                    <strong>${escapeHtml(pin.name)}</strong>
                    <small>Height: ${pin.height}m | Location: (${pin.x}, ${pin.y})</small>
                `;
                item.addEventListener('click', () => showPinInfo(pin));
                listEl.appendChild(item);
            });
            
            listEl.style.display = 'block';
            listEl.style.left = '10px';
            listEl.style.top = '10px';
        }
        
        // Close modals when clicking outside
        window.onclick = (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        };
        
        // Initial render
        renderPins();
    </script>
</body>
</html>
