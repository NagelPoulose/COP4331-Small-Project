<?php
header('Content-Type: application/json');

// Include database connection file
include 'db_connect.php';

// Read JSON data sent from frontend
$inData = json_decode(file_get_contents('php://input'), true);

$firstName = trim($inData["firstName"] ?? "");
$lastName = trim($inData["lastName"] ?? "");
$phone = trim($inData["phone"] ?? "");
$email = trim($inData["email"] ?? "");
$userId = (int)($inData["userId"] ?? 0);

if ($firstName === "" || $lastName === "" || $phone === "" || $email === "" || $userId <= 0) {
    echo json_encode(["error" => "Missing required contact fields."]);
    $conn->close();
    exit();
}

// Duplicate check: same user cannot add the same email twice.
$checkStmt = $conn->prepare("SELECT ID FROM Contacts WHERE UserID = ? AND LOWER(Email) = LOWER(?) LIMIT 1");
$checkStmt->bind_param("is", $userId, $email);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    echo json_encode(["error" => "A contact with that email already exists."]);
    $conn->close();
    exit();
}

// SQL to add a specific contact
$stmt = $conn->prepare("INSERT INTO Contacts (FirstName, LastName, Phone, Email, UserID) VALUES (?,?,?,?,?)");
$stmt->bind_param("ssssi", $firstName, $lastName, $phone, $email, $userId);

// Execute and return JSON response
if ($stmt->execute()) {
    echo json_encode(["error" => ""]);
} else {
    echo json_encode(["error" => "Failed to add contact: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>