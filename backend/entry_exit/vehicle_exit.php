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

    record_id
*/


$record_id = $_POST["record_id"] ?? null;


/* Validate record ID */

if (!$record_id || !filter_var($record_id, FILTER_VALIDATE_INT)) {
    echo json_encode([
        "success" => false,
        "message" => "Valid record_id is required."
    ]);
    exit;
}

$record_id = (int)$record_id;


/* Start transaction */

$conn->begin_transaction();

try {

    /* Get active parking record */

    $stmt = $conn->prepare(
        "SELECT
            pr.record_id,
            pr.vehicle_number,
            pr.slot_number,
            pr.rate_id,
            pr.entry_time,
            pr.exit_time,
            pr.booking_id,
            r.vehicle_type,
            r.rate_per_hour
         FROM parking_record pr
         INNER JOIN parking_rate r
            ON pr.rate_id = r.rate_id
         WHERE pr.record_id = ?
         AND pr.exit_time IS NULL"
    );

    $stmt->bind_param("i", $record_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Active parking record not found."
        ]);
        exit;
    }

    $record = $result->fetch_assoc();

    $stmt->close();


    /* Set exit time */

    $result_time = $conn->query("SELECT NOW()");
    $time_row = $result_time->fetch_row();
    $exit_time = $time_row[0];


    /*
        Calculate parking duration.

        Duration is calculated in minutes first.
        Billing is charged per started hour.
    */

    $entry_timestamp = strtotime($record["entry_time"]);
    $exit_timestamp = strtotime($exit_time);

    $duration_minutes = ceil(
        ($exit_timestamp - $entry_timestamp) / 60
    );

    if ($duration_minutes < 1) {
        $duration_minutes = 1;
    }


    /* Convert minutes into billable hours */

    $parking_hours = ceil($duration_minutes / 60);

    $parking_fee = $parking_hours * $record["rate_per_hour"];


    /* Update parking record */

    $stmt = $conn->prepare(
        "UPDATE parking_record
         SET exit_time = ?
         WHERE record_id = ?"
    );

    $stmt->bind_param(
        "si",
        $exit_time,
        $record_id
    );

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Failed to record vehicle exit."
        ]);
        exit;
    }

    $stmt->close();


    /* Make parking slot available again */

    $stmt = $conn->prepare(
        "UPDATE parking_slot
         SET status = 'Available'
         WHERE slot_number = ?"
    );

    $stmt->bind_param(
        "s",
        $record["slot_number"]
    );

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->rollback();

        echo json_encode([
            "success" => false,
            "message" => "Failed to update parking slot status."
        ]);
        exit;
    }

    $stmt->close();


    /* Complete the transaction */

    $conn->commit();


    /* Success response */

    echo json_encode([
        "success" => true,
        "message" => "Vehicle exit recorded successfully.",
        "data" => [
            "record_id" => $record_id,
            "booking_id" => $record["booking_id"],
            "vehicle_number" => $record["vehicle_number"],
            "slot_number" => $record["slot_number"],
            "entry_time" => $record["entry_time"],
            "exit_time" => $exit_time,
            "duration_minutes" => $duration_minutes,
            "parking_hours" => $parking_hours,
            "rate_per_hour" => $record["rate_per_hour"],
            "parking_fee" => $parking_fee,
            "status" => "Available"
        ]
    ]);


    $conn->close();

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred."
    ]);

    $conn->close();
}

?>