<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/booking.php';

requireLogin();

$currentUser = getCurrentUser();
$bookingManager = new BookingManager();
$error = '';
$success = '';

// Get booking ID from URL
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$bookingId) {
    header('Location: index.php?view=bookings');
    exit();
}

// Get booking details
$booking = $bookingManager->getBookingById($bookingId, $currentUser['id']);

if (!$booking) {
    $_SESSION['error_message'] = 'Booking not found or you do not have permission to modify it.';
    header('Location: index.php?view=bookings');
    exit();
}

// Check if booking can be modified
$bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['start_time']);
$now = new DateTime();

if ($bookingDateTime <= $now || $booking['status'] === 'cancelled') {
    $_SESSION['error_message'] = 'Cannot modify past or cancelled bookings.';
    header('Location: index.php?view=bookings');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingDate = $_POST['booking_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);
    $attendees = (int)$_POST['attendees'];
    
    // Get room details
    $database = new Database();
    $db = $database->getConnection();
    $roomQuery = "SELECT * FROM rooms WHERE id = ?";
    $roomStmt = $db->prepare($roomQuery);
    $roomStmt->execute([$booking['room_id']]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    
    // Validate booking data
    $bookingData = [
        'room_id' => $booking['room_id'],
        'user_id' => $currentUser['id'],
        'user_name' => $currentUser['name'],
        'room_name' => $booking['room_name'],
        'booking_date' => $bookingDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'purpose' => $purpose,
        'attendees' => $attendees,
        'price_per_hour' => $room['price_per_hour']
    ];
    
    $errors = $bookingManager->validateBooking($bookingData);
    
    // Check for conflicts excluding current booking
    if ($bookingManager->hasConflict($booking['room_id'], $bookingDate, $startTime, $endTime, $bookingId)) {
        $errors[] = 'This time slot conflicts with another booking';
    }
    
    if (empty($errors)) {
        // Calculate new total cost
        $startDateTime = new DateTime($startTime);
        $endDateTime = new DateTime($endTime);
        $duration = $startDateTime->diff($endDateTime);
        $hours = $duration->h + ($duration->i / 60);
        $totalCost = $hours * $room['price_per_hour'];
        
        // Update booking
        $updateQuery = "UPDATE bookings SET booking_date = ?, start_time = ?, end_time = ?, purpose = ?, attendees = ?, total_cost = ? WHERE id = ? AND user_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$bookingDate, $startTime, $endTime, $purpose, $attendees, $totalCost, $bookingId, $currentUser['id']])) {
            $_SESSION['success_message'] = 'Booking updated successfully!';
            header('Location: index.php?view=bookings');
            exit();
        } else {
            $error = 'Failed to update booking. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Booking - Conference Room Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="building-2" class="w-5 h-5 text-white"></i>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-900">Conference Room Booking</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="index.php?view=bookings" class="text-sm text-gray-500 hover:text-gray-700">← Back to Bookings</a>
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <a href="logout.php" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Modify Booking</h2>
                <p class="text-gray-600">Update your reservation for <?php echo htmlspecialchars($booking['room_name']); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="booking_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" id="booking_date" name="booking_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($booking['booking_date']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="attendees" class="block text-sm font-medium text-gray-700 mb-1">Attendees</label>
                        <input type="number" id="attendees" name="attendees" min="1" required
                               value="<?php echo $booking['attendees']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required
                               value="<?php echo htmlspecialchars($booking['start_time']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                        <input type="time" id="end_time" name="end_time" required
                               value="<?php echo htmlspecialchars($booking['end_time']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Meeting Purpose</label>
                    <textarea id="purpose" name="purpose" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Brief description of your meeting..."><?php echo htmlspecialchars($booking['purpose']); ?></textarea>
                </div>
                
                <div class="flex gap-2 pt-4">
                    <a href="index.php?view=bookings" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-center">
                        Cancel
                    </a>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Booking
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>
