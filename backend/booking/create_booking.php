<?php

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

/*
    Expected POST data:

    owner_id
    vehicle_number
    slot_number
    location_id
    booking_date
    start_time
    end_time
*/

$owner_id = $_POST["owner_id"] ?? null;
$vehicle_number = $_POST["vehicle_number"] ?? null;
$slot_number = $_POST["slot_number"] ?? null;
$location_id = $_POST["location_id"] ?? null;
$booking_date = $_POST["booking_date"] ?? null;
$start_time = $_POST["start_time"] ?? null;
$end_time = $_POST["end_time"] ?? null;


/* Basic validation */

if (
    !$owner_id ||
    !$vehicle_number ||
    !$slot_number ||
    !$location_id ||
    !$booking_date ||
    !$start_time ||
    !$end_time
) {
    echo json_encode([
        "success" => false,
        "message" => "All booking fields are required."
    ]);
    exit;
}


/* Validate numeric IDs */

if (!filter_var($owner_id, FILTER_VALIDATE_INT)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid owner ID."
    ]);
    exit;
}

if (!filter_var($location_id, FILTER_VALIDATE_INT)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid location ID."
    ]);
    exit;
}


/* Check that owner exists */

$stmt = $conn->prepare(
    "SELECT owner_id
     FROM owner
     WHERE owner_id = ?"
);

$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Owner does not exist."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();


/* Check that vehicle exists and belongs to the owner */

$stmt = $conn->prepare(
    "SELECT vehicle_number, vehicle_type
     FROM vehicle
     WHERE vehicle_number = ?
     AND owner_id = ?"
);

$stmt->bind_param("si", $vehicle_number, $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle does not exist or does not belong to this owner."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$vehicle = $result->fetch_assoc();

$vehicle_type = $vehicle["vehicle_type"];

$stmt->close();


/* Check that location exists */

$stmt = $conn->prepare(
    "SELECT location_id, location_name
     FROM parking_location
     WHERE location_id = ?"
);

$stmt->bind_param("i", $location_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parking location does not exist."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$location = $result->fetch_assoc();

$location_name = $location["location_name"];

$stmt->close();


/* Check that slot exists and belongs to the selected location */

$stmt = $conn->prepare(
    "SELECT slot_number, slot_type, status, location_id
     FROM parking_slot
     WHERE slot_number = ?
     AND location_id = ?"
);

$stmt->bind_param("si", $slot_number, $location_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parking slot does not exist at the selected location."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$slot = $result->fetch_assoc();

$slot_type = $slot["slot_type"];
$slot_status = $slot["status"];

$stmt->close();


/* Check vehicle type and slot type */

if ($vehicle_type !== $slot_type) {
    echo json_encode([
        "success" => false,
        "message" => "Vehicle type does not match the selected parking slot."
    ]);
    $conn->close();
    exit;
}


/* Check slot availability */

if ($slot_status !== "Available") {
    echo json_encode([
        "success" => false,
        "message" => "Selected parking slot is currently unavailable."
    ]);
    $conn->close();
    exit;
}


/* Validate booking date and time */

$date_check = DateTime::createFromFormat("Y-m-d", $booking_date);

if (!$date_check || $date_check->format("Y-m-d") !== $booking_date) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid booking date. Use YYYY-MM-DD."
    ]);
    $conn->close();
    exit;
}

$start_check = DateTime::createFromFormat("H:i", $start_time);
$end_check = DateTime::createFromFormat("H:i", $end_time);

if (
    !$start_check ||
    !$end_check ||
    $start_check->format("H:i") !== $start_time ||
    $end_check->format("H:i") !== $end_time
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid booking time. Use HH:MM."
    ]);
    $conn->close();
    exit;
}


/* End time must be after start time */

if ($end_time <= $start_time) {
    echo json_encode([
        "success" => false,
        "message" => "End time must be after start time."
    ]);
    $conn->close();
    exit;
}


/* Check for overlapping bookings */

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
        "message" => "The selected slot is already booked for the requested time."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();


/* Create booking */

$booking_status = "Pending";

$stmt = $conn->prepare(
    "INSERT INTO booking
    (
        owner_id,
        vehicle_number,
        slot_number,
        location_id,
        booking_date,
        start_time,
        end_time,
        booking_status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "ississss",
    $owner_id,
    $vehicle_number,
    $slot_number,
    $location_id,
    $booking_date,
    $start_time,
    $end_time,
    $booking_status
);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to create booking."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$booking_id = $stmt->insert_id;

$stmt->close();
$conn->close();


/* Success response */

echo json_encode([
    "success" => true,
    "message" => "Booking created successfully.",
    "booking_id" => $booking_id,
    "location_name" => $location_name,
    "slot_number" => $slot_number,
    "booking_date" => $booking_date,
    "start_time" => $start_time,
    "end_time" => $end_time,
    "booking_status" => $booking_status
]);

?>