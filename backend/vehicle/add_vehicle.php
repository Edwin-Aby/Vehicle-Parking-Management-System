<?php
// ============================================
// Add Vehicle - Backend API
// Inserts a new vehicle into the VEHICLE table
// ============================================

// 1. Set the response type to JSON
header("Content-Type: application/json");

// 2. Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

// 3. Include the shared database connection
require_once __DIR__ . "/../../config/database.php";

// 4. Read the POST fields and trim whitespace
$vehicle_number = isset($_POST["vehicle_number"]) ? trim($_POST["vehicle_number"]) : "";
$vehicle_type   = isset($_POST["vehicle_type"])   ? trim($_POST["vehicle_type"])   : "";
$owner_id       = isset($_POST["owner_id"])        ? trim($_POST["owner_id"])       : "";

// 5. Validate required fields
if ($vehicle_number === "") {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle number is required."
    ]);
    exit;
}

if ($vehicle_type === "") {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle type is required."
    ]);
    exit;
}

if ($owner_id === "") {
    echo json_encode([
        "success" => false,
        "message" => "Owner ID is required."
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

// 7. Validate that owner_id is a valid integer
if (!filter_var($owner_id, FILTER_VALIDATE_INT)) {
    echo json_encode([
        "success" => false,
        "message" => "Owner ID must be a valid integer."
    ]);
    exit;
}
$owner_id = (int) $owner_id;

// 8. Check that the referenced owner exists in the OWNER table
$owner_check = $conn->prepare("SELECT owner_id FROM owner WHERE owner_id = ?");
if (!$owner_check) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare owner check."
    ]);
    exit;
}
$owner_check->bind_param("i", $owner_id);
$owner_check->execute();
$owner_check->store_result();

if ($owner_check->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Owner ID does not exist. Please add the owner first."
    ]);
    $owner_check->close();
    exit;
}
$owner_check->close();

// 9. Check that vehicle_number is not already registered
$dup_check = $conn->prepare("SELECT vehicle_number FROM vehicle WHERE vehicle_number = ?");
if (!$dup_check) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare duplicate check."
    ]);
    exit;
}
$dup_check->bind_param("s", $vehicle_number);
$dup_check->execute();
$dup_check->store_result();

if ($dup_check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle number is already registered."
    ]);
    $dup_check->close();
    exit;
}
$dup_check->close();

// 10. Prepare the INSERT statement (prevents SQL injection)
$sql = "INSERT INTO vehicle (vehicle_number, vehicle_type, owner_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare statement."
    ]);
    exit;
}

// 11. Bind the parameters and execute
//     "ssi" = string, string, integer
$stmt->bind_param("ssi", $vehicle_number, $vehicle_type, $owner_id);

if ($stmt->execute()) {
    // 12. Success — return the vehicle number
    echo json_encode([
        "success"        => true,
        "message"        => "Vehicle added successfully.",
        "vehicle_number" => $vehicle_number
    ]);
} else {
    // 13. Execution failed — return the error
    echo json_encode([
        "success" => false,
        "message" => "Failed to add vehicle: " . $stmt->error
    ]);
}

// 14. Clean up
$stmt->close();
$conn->close();
?>
