<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/booking.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
    $currentUser = getCurrentUser();
    
    $bookingManager = new BookingManager();
    
    // Verify booking belongs to user
    $booking = $bookingManager->getBookingById($bookingId, $currentUser['id']);
    
    if (!$booking) {
        $_SESSION['error_message'] = 'Booking not found or you do not have permission to cancel it.';
    } elseif ($booking['status'] === 'cancelled') {
        $_SESSION['error_message'] = 'Booking is already cancelled.';
    } else {
        // Check if booking is in the future
        $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['start_time']);
        $now = new DateTime();
        
        if ($bookingDateTime <= $now) {
            $_SESSION['error_message'] = 'Cannot cancel past bookings.';
        } else {
            if ($bookingManager->cancelBooking($bookingId, $currentUser['id'])) {
                $_SESSION['success_message'] = 'Booking cancelled successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to cancel booking. Please try again.';
            }
        }
    }
}

header('Location: index.php?view=bookings');
exit();
?>
