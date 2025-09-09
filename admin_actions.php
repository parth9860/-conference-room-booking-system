<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/admin.php';

requireAdmin();

$adminManager = new AdminManager();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_room':
        $roomData = [
            'name' => trim($_POST['name']),
            'capacity' => (int)$_POST['capacity'],
            'location' => trim($_POST['location']),
            'amenities' => trim($_POST['amenities']),
            'description' => trim($_POST['description']),
            'image_url' => trim($_POST['image_url']),
            'price_per_hour' => (float)$_POST['price_per_hour'],
            'available' => isset($_POST['available'])
        ];
        
        if ($adminManager->addRoom($roomData)) {
            $_SESSION['success_message'] = 'Room added successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to add room.';
        }
        break;
        
    case 'edit_room':
        $roomId = (int)$_POST['room_id'];
        $roomData = [
            'name' => trim($_POST['name']),
            'capacity' => (int)$_POST['capacity'],
            'location' => trim($_POST['location']),
            'amenities' => trim($_POST['amenities']),
            'description' => trim($_POST['description']),
            'image_url' => trim($_POST['image_url']),
            'price_per_hour' => (float)$_POST['price_per_hour'],
            'available' => isset($_POST['available'])
        ];
        
        if ($adminManager->updateRoom($roomId, $roomData)) {
            $_SESSION['success_message'] = 'Room updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update room.';
        }
        break;
        
    case 'delete_room':
        $roomId = (int)$_POST['room_id'];
        $result = $adminManager->deleteRoom($roomId);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        break;
        
    case 'update_booking_status':
        $bookingId = (int)$_POST['booking_id'];
        $status = $_POST['status'];
        
        if ($adminManager->updateBookingStatus($bookingId, $status)) {
            $_SESSION['success_message'] = 'Booking status updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update booking status.';
        }
        break;
        
    case 'update_user_role':
        $userId = (int)$_POST['user_id'];
        $role = $_POST['role'];
        
        if ($adminManager->updateUserRole($userId, $role)) {
            $_SESSION['success_message'] = 'User role updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update user role.';
        }
        break;
}

header('Location: index.php?view=admin');
exit();
?>
