<?php
// ============================================
// Add Owner - Backend API
// Inserts a new owner into the OWNER table
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
$first_name = isset($_POST["first_name"]) ? trim($_POST["first_name"]) : "";
$last_name  = isset($_POST["last_name"])  ? trim($_POST["last_name"])  : "";
$phone      = isset($_POST["phone"])      ? trim($_POST["phone"])      : "";
$email      = isset($_POST["email"])      ? trim($_POST["email"])      : "";

// 5. Validate required fields
if ($first_name === "") {
    echo json_encode([
        "success" => false,
        "message" => "First name is required."
    ]);
    exit;
}

if ($last_name === "") {
    echo json_encode([
        "success" => false,
        "message" => "Last name is required."
    ]);
    exit;
}

if ($phone === "") {
    echo json_encode([
        "success" => false,
        "message" => "Phone number is required."
    ]);
    exit;
}

// 6. Validate email format only when a value is provided
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format."
    ]);
    exit;
}

// 7. Prepare the INSERT statement (prevents SQL injection)
$sql = "INSERT INTO owner (first_name, last_name, phone, email) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Check if the statement was prepared successfully
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to prepare statement."
    ]);
    exit;
}

// 8. Bind the parameters and execute
//    "ssss" means all four parameters are strings
$stmt->bind_param("ssss", $first_name, $last_name, $phone, $email);

if ($stmt->execute()) {
    // 9. Success — return the new owner's ID
    echo json_encode([
        "success"  => true,
        "message"  => "Owner added successfully.",
        "owner_id" => $conn->insert_id
    ]);
} else {
    // 10. Execution failed — return the error
    echo json_encode([
        "success" => false,
        "message" => "Failed to add owner: " . $stmt->error
    ]);
}

// 11. Clean up
$stmt->close();
$conn->close();
?>
