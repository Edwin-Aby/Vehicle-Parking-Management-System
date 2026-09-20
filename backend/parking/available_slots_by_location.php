<?php
// ==================================================
// Location-wise Available Slots - Backend API
// Returns available parking slots for a location
// and vehicle type
// ==================================================

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

// Read GET parameters
$location_id = isset($_GET["location_id"]) ? trim($_GET["location_id"]) : "";
$vehicle_type = isset($_GET["vehicle_type"]) ? trim($_GET["vehicle_type"]) : "";

// Validate location ID
if ($location_id === "" || !filter_var($location_id, FILTER_VALIDATE_INT)) {
    echo json_encode([
        "success" => false,
        "message" => "A valid location ID is required."
    ]);
    exit;
}

$location_id = (int) $location_id;

// Validate vehicle type
$allowed_types = ["Car", "Bike"];

if (!in_array($vehicle_type, $allowed_types, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid vehicle type. Allowed types: Car, Bike."
    ]);
    exit;
}

// Check that the location exists
$location_stmt = $conn->prepare(
    "SELECT location_id, location_name
     FROM parking_location
     WHERE location_id = ?"
);

if (!$location_stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare location query."
    ]);
    exit;
}

$location_stmt->bind_param("i", $location_id);
$location_stmt->execute();

$location_result = $location_stmt->get_result();

if ($location_result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parking location not found."
    ]);
    $location_stmt->close();
    $conn->close();
    exit;
}

$location = $location_result->fetch_assoc();
$location_stmt->close();

// Get hourly rate
$rate_stmt = $conn->prepare(
    "SELECT rate_per_hour
     FROM parking_rate
     WHERE vehicle_type = ?"
);

if (!$rate_stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare rate query."
    ]);
    $conn->close();
    exit;
}

$rate_stmt->bind_param("s", $vehicle_type);
$rate_stmt->execute();

$rate_result = $rate_stmt->get_result();

if ($rate_result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "No parking rate found for vehicle type: " . $vehicle_type
    ]);
    $rate_stmt->close();
    $conn->close();
    exit;
}

$rate_row = $rate_result->fetch_assoc();
$rate_per_hour = $rate_row["rate_per_hour"];

$rate_stmt->close();

// Get available slots for this location and vehicle type
$slot_stmt = $conn->prepare(
    "SELECT slot_number, slot_type, status
     FROM parking_slot
     WHERE location_id = ?
     AND slot_type = ?
     AND status = 'Available'
     ORDER BY slot_number ASC"
);

if (!$slot_stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare slot query."
    ]);
    $conn->close();
    exit;
}

$slot_stmt->bind_param("is", $location_id, $vehicle_type);
$slot_stmt->execute();

$slot_result = $slot_stmt->get_result();

$available_slots = [];

while ($row = $slot_result->fetch_assoc()) {
    $available_slots[] = $row;
}

$slot_stmt->close();

// Return response
echo json_encode([
    "success" => true,
    "location_id" => $location_id,
    "location_name" => $location["location_name"],
    "vehicle_type" => $vehicle_type,
    "rate_per_hour" => $rate_per_hour,
    "available_slots" => $available_slots
]);

$conn->close();
?>