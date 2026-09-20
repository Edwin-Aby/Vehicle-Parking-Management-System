<?php

header("Content-Type: application/json");

require_once "../../config/database.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data"
    ]);
    exit;
}
$owner_id = $data["owner_id"] ?? null;
$vehicle_number = $data["vehicle_number"] ?? null;
$slot_number = $data["slot_number"] ?? null;
$location_id = $data["location_id"] ?? null;
$booking_date = $data["booking_date"] ?? null;
$start_time = $data["start_time"] ?? null;
$end_time = $data["end_time"] ?? null;
if (
    $owner_id === null ||
    $vehicle_number === null ||
    $slot_number === null ||
    $location_id === null ||
    $booking_date === null ||
    $start_time === null ||
    $end_time === null
) {
    echo json_encode([
        "success" => false,
        "message" => "All booking details are required"
    ]);
    exit;
}
$stmt = $conn->prepare("SELECT owner_id FROM owner WHERE owner_id = ?");

$stmt->bind_param("i", $owner_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Owner not found"
    ]);
    exit;
}

$stmt->close();
$stmt = $conn->prepare("SELECT vehicle_number, vehicle_type, owner_id FROM vehicle WHERE vehicle_number = ?");

$stmt->bind_param("s", $vehicle_number);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle not found"
    ]);
    exit;
}

$vehicle = $result->fetch_assoc();

$stmt->close();
if ((int)$vehicle["owner_id"] !== (int)$owner_id) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle does not belong to this owner"
    ]);
    exit;
}
$stmt = $conn->prepare("SELECT location_id FROM parking_location WHERE location_id = ?");

$stmt->bind_param("i", $location_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parking location not found"
    ]);
    exit;
}

$stmt->close();
$stmt = $conn->prepare(
    "SELECT slot_number, slot_type, location_id, status
     FROM parking_slot
     WHERE slot_number = ?"
);

$stmt->bind_param("s", $slot_number);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parking slot not found"
    ]);
    exit;
}

$slot = $result->fetch_assoc();

$stmt->close();
if ((int)$slot["location_id"] !== (int)$location_id) {
    echo json_encode([
        "success" => false,
        "message" => "Parking slot does not belong to this location"
    ]);
    exit;
}
if ($vehicle["vehicle_type"] !== $slot["slot_type"]) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle type does not match parking slot type"
    ]);
    exit;
}
$today = date("Y-m-d");

if ($booking_date < $today) {
    echo json_encode([
        "success" => false,
        "message" => "Booking date cannot be in the past"
    ]);
    exit;
}

if ($start_time >= $end_time) {
    echo json_encode([
        "success" => false,
        "message" => "Start time must be before end time"
    ]);
    exit;
}
$stmt = $conn->prepare(
    "SELECT booking_id
     FROM booking
     WHERE slot_number = ?
       AND booking_date = ?
       AND booking_status IN ('Pending', 'Confirmed')
       AND start_time < ?
       AND end_time > ?"
);

$stmt->bind_param(
    "ssss",
    $slot_number,
    $booking_date,
    $end_time,
    $start_time
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parking slot is already booked for the selected time"
    ]);
    exit;
}

$stmt->close();