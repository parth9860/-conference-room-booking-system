<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $currentUser = getCurrentUser();
    
    $roomId = (int)$_POST['room_id'];
    $roomName = trim($_POST['room_name']);
    $bookingDate = $_POST['booking_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);
    $attendees = (int)$_POST['attendees'];
    $pricePerHour = (float)$_POST['price_per_hour'];
    
    // Validation
    $errors = [];
    
    if (empty($bookingDate) || empty($startTime) || empty($endTime) || empty($purpose)) {
        $errors[] = 'Please fill in all required fields';
    }
    
    if ($startTime >= $endTime) {
        $errors[] = 'End time must be after start time';
    }
    
    // Check room capacity
    $roomQuery = "SELECT capacity FROM rooms WHERE id = ?";
    $roomStmt = $db->prepare($roomQuery);
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        $errors[] = 'Room not found';
    } elseif ($attendees > $room['capacity']) {
        $errors[] = 'Number of attendees cannot exceed room capacity (' . $room['capacity'] . ')';
    }
    
    // Check for booking conflicts
    $conflictQuery = "SELECT id FROM bookings WHERE room_id = ? AND booking_date = ? AND status != 'cancelled' AND (
        (start_time <= ? AND end_time > ?) OR 
        (start_time < ? AND end_time >= ?) OR 
        (start_time >= ? AND end_time <= ?)
    )";
    $conflictStmt = $db->prepare($conflictQuery);
    $conflictStmt->execute([$roomId, $bookingDate, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
    
    if ($conflictStmt->fetch()) {
        $errors[] = 'This time slot is already booked';
    }
    
    if (empty($errors)) {
        // Calculate total cost
        $startDateTime = new DateTime($startTime);
        $endDateTime = new DateTime($endTime);
        $duration = $startDateTime->diff($endDateTime);
        $hours = $duration->h + ($duration->i / 60);
        $totalCost = $hours * $pricePerHour;
        
        // Insert booking
        $insertQuery = "INSERT INTO bookings (room_id, user_id, user_name, room_name, booking_date, start_time, end_time, purpose, attendees, total_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        $insertStmt = $db->prepare($insertQuery);
        
        if ($insertStmt->execute([$roomId, $currentUser['id'], $currentUser['name'], $roomName, $bookingDate, $startTime, $endTime, $purpose, $attendees, $totalCost])) {
            $_SESSION['success_message'] = 'Booking confirmed successfully!';
            header('Location: index.php?view=bookings');
            exit();
        } else {
            $errors[] = 'Failed to create booking. Please try again.';
        }
    }
    
    // If there are errors, redirect back with error message
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: index.php?view=rooms');
    exit();
}

// If not POST request, redirect to rooms
header('Location: index.php?view=rooms');
exit();
?>
