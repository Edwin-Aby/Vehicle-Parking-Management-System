<?php

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);
    exit;
}

require_once __DIR__ . "/../../config/database.php";


/*
    Optional GET parameters:

    owner_id
    vehicle_number
    booking_id

    Examples:

    get_bookings.php
    get_bookings.php?owner_id=1
    get_bookings.php?booking_id=2
*/


$owner_id = $_GET["owner_id"] ?? null;
$vehicle_number = $_GET["vehicle_number"] ?? null;
$booking_id = $_GET["booking_id"] ?? null;


/* Get a specific booking */

if ($booking_id !== null) {

    if (!filter_var($booking_id, FILTER_VALIDATE_INT)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid booking ID."
        ]);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT
            b.booking_id,
            b.owner_id,
            CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
            b.vehicle_number,
            v.vehicle_type,
            b.slot_number,
            b.location_id,
            pl.location_name,
            pl.address,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.booking_status
         FROM booking b
         INNER JOIN owner o
            ON b.owner_id = o.owner_id
         INNER JOIN vehicle v
            ON b.vehicle_number = v.vehicle_number
         INNER JOIN parking_location pl
            ON b.location_id = pl.location_id
         WHERE b.booking_id = ?"
    );

    $stmt->bind_param("i", $booking_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Booking not found."
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $booking = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "booking" => $booking
    ]);

    $stmt->close();
    $conn->close();
    exit;
}


/* Get bookings */

$sql = "SELECT
            b.booking_id,
            b.owner_id,
            CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
            b.vehicle_number,
            v.vehicle_type,
            b.slot_number,
            b.location_id,
            pl.location_name,
            pl.address,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.booking_status
        FROM booking b
        INNER JOIN owner o
            ON b.owner_id = o.owner_id
        INNER JOIN vehicle v
            ON b.vehicle_number = v.vehicle_number
        INNER JOIN parking_location pl
            ON b.location_id = pl.location_id";

$params = [];
$types = "";
$conditions = [];


/* Filter by owner */

if ($owner_id !== null) {

    if (!filter_var($owner_id, FILTER_VALIDATE_INT)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid owner ID."
        ]);
        $conn->close();
        exit;
    }

    $conditions[] = "b.owner_id = ?";
    $types .= "i";
    $params[] = $owner_id;
}


/* Filter by vehicle */

if ($vehicle_number !== null) {

    $conditions[] = "b.vehicle_number = ?";
    $types .= "s";
    $params[] = $vehicle_number;
}


/* Add conditions */

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}


/* Sort newest booking first */

$sql .= " ORDER BY b.booking_id DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare booking query."
    ]);
    $conn->close();
    exit;
}


/* Bind parameters if filters were provided */

if (count($params) > 0) {

    $bind_params = [];
    $bind_params[] = $types;

    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }

    call_user_func_array(
        [$stmt, "bind_param"],
        $bind_params
    );
}


$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}


echo json_encode([
    "success" => true,
    "count" => count($bookings),
    "bookings" => $bookings
]);


$stmt->close();
$conn->close();

?>