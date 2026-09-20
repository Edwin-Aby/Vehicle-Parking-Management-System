<?php

header("Content-Type: application/json");

require_once "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Vehicle Entry API
|--------------------------------------------------------------------------
| Method: POST
| Input:
| {
|     "booking_id": 1
| }
|
| This API:
| 1. Checks whether the booking exists.
| 2. Checks whether the booking is Confirmed.
| 3. Gets the vehicle and parking slot details.
| 4. Checks whether the slot is available.
| 5. Gets the parking rate.
| 6. Creates a parking record with the current entry time.
| 7. Changes the parking slot status to Occupied.
|--------------------------------------------------------------------------
*/


// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}


// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data"
    ]);
    exit;
}


// Get booking ID
$booking_id = $data["booking_id"] ?? null;


// Validate booking ID
if ($booking_id === null || !is_numeric($booking_id)) {
    echo json_encode([
        "success" => false,
        "message" => "Valid booking_id is required"
    ]);
    exit;
}


$booking_id = (int)$booking_id;


// Start transaction
$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | Step 1: Get booking details
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            booking_id,
            owner_id,
            vehicle_number,
            slot_number,
            location_id,
            booking_date,
            start_time,
            end_time,
            booking_status
         FROM booking
         WHERE booking_id = ?"
    );

    $stmt->bind_param("i", $booking_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Booking not found"
        ]);
        exit;
    }

    $booking = $result->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 2: Check booking status
    |--------------------------------------------------------------------------
    */

    if ($booking["booking_status"] !== "Confirmed") {

        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Vehicle entry is allowed only for confirmed bookings"
        ]);
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 3: Get vehicle details
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            vehicle_number,
            vehicle_type,
            owner_id
         FROM vehicle
         WHERE vehicle_number = ?"
    );

    $stmt->bind_param("s", $booking["vehicle_number"]);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Vehicle not found"
        ]);
        exit;
    }

    $vehicle = $result->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 4: Verify vehicle belongs to booking owner
    |--------------------------------------------------------------------------
    */

    if ((int)$vehicle["owner_id"] !== (int)$booking["owner_id"]) {

        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Vehicle does not belong to the booking owner"
        ]);
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 5: Get parking slot details
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            slot_number,
            slot_type,
            status,
            location_id
         FROM parking_slot
         WHERE slot_number = ?"
    );

    $stmt->bind_param("s", $booking["slot_number"]);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Parking slot not found"
        ]);
        exit;
    }

    $slot = $result->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 6: Verify slot belongs to booking location
    |--------------------------------------------------------------------------
    */

    if ((int)$slot["location_id"] !== (int)$booking["location_id"]) {

        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Parking slot does not belong to the booking location"
        ]);
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 7: Verify vehicle type matches slot type
    |--------------------------------------------------------------------------
    */

    if ($vehicle["vehicle_type"] !== $slot["slot_type"]) {

        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Vehicle type does not match parking slot type"
        ]);
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 8: Check whether slot is available
    |--------------------------------------------------------------------------
    */

    if ($slot["status"] !== "Available") {

        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Parking slot is currently occupied"
        ]);
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Step 9: Check for an existing active parking record
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT record_id
         FROM parking_record
         WHERE booking_id = ?
           AND exit_time IS NULL"
    );

    $stmt->bind_param("i", $booking_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Vehicle entry has already been recorded for this booking"
        ]);
        exit;
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 10: Get parking rate
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT rate_id
         FROM parking_rate
         WHERE vehicle_type = ?"
    );

    $stmt->bind_param("s", $vehicle["vehicle_type"]);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Parking rate not found for this vehicle type"
        ]);
        exit;
    }

    $rate = $result->fetch_assoc();

    $rate_id = (int)$rate["rate_id"];

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 11: Create parking record
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO parking_record
        (
            vehicle_number,
            slot_number,
            rate_id,
            entry_time,
            booking_id
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW(),
            ?
        )"
    );

    $stmt->bind_param(
        "ssii",
        $booking["vehicle_number"],
        $booking["slot_number"],
        $rate_id,
        $booking_id
    );

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Failed to create parking record"
        ]);
        exit;
    }

    $record_id = $conn->insert_id;

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 12: Change slot status to Occupied
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE parking_slot
         SET status = 'Occupied'
         WHERE slot_number = ?"
    );

    $stmt->bind_param("s", $booking["slot_number"]);

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Failed to update parking slot status"
        ]);
        exit;
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Step 13: Commit transaction
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | Step 14: Send success response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Vehicle entry recorded successfully",
        "data" => [
            "record_id" => $record_id,
            "booking_id" => $booking_id,
            "vehicle_number" => $booking["vehicle_number"],
            "slot_number" => $booking["slot_number"],
            "location_id" => $booking["location_id"],
            "entry_time" => date("Y-m-d H:i:s"),
            "status" => "Occupied"
        ]
    ]);

} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback if any unexpected error occurs
    |--------------------------------------------------------------------------
    */

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred"
    ]);
}

?>