<?php
// ============================================
// Get Parking Locations - Backend API
// Returns all parking locations from the database
// ============================================

header("Content-Type: application/json");

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);
    exit;
}

// Include database connection
require_once __DIR__ . "/../../config/database.php";

// Get all parking locations
$sql = "SELECT location_id, location_name, address, latitude, longitude
        FROM parking_location
        ORDER BY location_id ASC";

$result = $conn->query($sql);

// Check if query failed
if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to fetch parking locations."
    ]);
    $conn->close();
    exit;
}

// Store locations
$locations = [];

while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

// Return response
echo json_encode([
    "success" => true,
    "locations" => $locations
]);

$conn->close();
?>