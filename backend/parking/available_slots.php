<?php
// ================================================
// Available Slots - Backend API
// Returns available parking slots and hourly rate
// for a given vehicle type (Car or Bike)
// ================================================

// 1. Set the response type to JSON
header("Content-Type: application/json");

// 2. Allow only GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);
    exit;
}

// 3. Include the shared database connection
require_once __DIR__ . "/../../config/database.php";

// 4. Read the GET parameter and trim whitespace
$vehicle_type = isset($_GET["vehicle_type"]) ? trim($_GET["vehicle_type"]) : "";

// 5. Validate that vehicle_type is provided
if ($vehicle_type === "") {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle type is required. Allowed types: Car, Bike."
    ]);
    exit;
}

// 6. Validate vehicle_type (only "Car" or "Bike" allowed)
$allowed_types = ["Car", "Bike"];
if (!in_array($vehicle_type, $allowed_types, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid vehicle type. Allowed types: Car, Bike."
    ]);
    exit;
}

// 7. Get the hourly rate for this vehicle type from PARKING_RATE
$rate_stmt = $conn->prepare("SELECT rate_per_hour FROM parking_rate WHERE vehicle_type = ?");
if (!$rate_stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare rate query."
    ]);
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

$rate_row     = $rate_result->fetch_assoc();
$rate_per_hour = $rate_row["rate_per_hour"];
$rate_stmt->close();

// 8. Get all available parking slots matching the vehicle type
$slot_stmt = $conn->prepare(
    "SELECT slot_number, slot_type, status FROM parking_slot WHERE slot_type = ? AND status = 'Available'"
);
if (!$slot_stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare slot query."
    ]);
    $conn->close();
    exit;
}

$slot_stmt->bind_param("s", $vehicle_type);
$slot_stmt->execute();
$slot_result = $slot_stmt->get_result();

// 9. Collect the available slots into an array
$available_slots = [];
while ($row = $slot_result->fetch_assoc()) {
    $available_slots[] = $row;
}
$slot_stmt->close();

// 10. Build and return the response
if (count($available_slots) > 0) {
    // Slots found
    echo json_encode([
        "success"         => true,
        "vehicle_type"    => $vehicle_type,
        "rate_per_hour"   => $rate_per_hour,
        "available_slots" => $available_slots
    ]);
} else {
    // No slots available right now
    echo json_encode([
        "success"         => true,
        "vehicle_type"    => $vehicle_type,
        "rate_per_hour"   => $rate_per_hour,
        "available_slots" => [],
        "message"         => "No available parking slots for vehicle type: " . $vehicle_type
    ]);
}

// 11. Clean up
$conn->close();
?>
