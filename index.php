<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Require login to access the system
requireLogin();

$database = new Database();
$db = $database->getConnection();
$currentUser = getCurrentUser();

// Get rooms with search and filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$capacityFilter = isset($_GET['capacity']) ? (int)$_GET['capacity'] : 0;

$roomQuery = "SELECT * FROM rooms WHERE 1=1";
$params = [];

if (!empty($searchTerm)) {
    $roomQuery .= " AND (name LIKE ? OR location LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if ($capacityFilter > 0) {
    $roomQuery .= " AND capacity >= ?";
    $params[] = $capacityFilter;
}

$roomQuery .= " ORDER BY name";
$roomStmt = $db->prepare($roomQuery);
$roomStmt->execute($params);
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$availableRoomsQuery = "SELECT COUNT(*) as count FROM rooms WHERE available = 1";
$availableStmt = $db->prepare($availableRoomsQuery);
$availableStmt->execute();
$availableRoomsCount = $availableStmt->fetch(PDO::FETCH_ASSOC)['count'];

$todayBookingsQuery = "SELECT COUNT(*) as count FROM bookings WHERE booking_date = CURDATE() AND status = 'confirmed'";
$todayStmt = $db->prepare($todayBookingsQuery);
$todayStmt->execute();
$totalBookingsToday = $todayStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get user's bookings
$userBookingsQuery = "SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC, start_time DESC";
$userBookingsStmt = $db->prepare($userBookingsQuery);
$userBookingsStmt->execute([$currentUser['id']]);
$userBookings = $userBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

$currentView = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conference Room Booking System</title>
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
                
                <nav class="hidden md:flex space-x-8">
                    <a href="?view=dashboard" class="<?php echo $currentView === 'dashboard' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="?view=rooms" class="<?php echo $currentView === 'rooms' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                        Browse Rooms
                    </a>
                    <a href="?view=bookings" class="<?php echo $currentView === 'bookings' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                        My Bookings
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="?view=admin" class="<?php echo $currentView === 'admin' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                        Admin Panel
                    </a>
                    <?php endif; ?>
                </nav>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <a href="logout.php" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <?php if ($currentView === 'dashboard'): ?>
            <!-- Dashboard View -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Available Rooms</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $availableRoomsCount; ?></p>
                            <p class="text-xs text-gray-500">Ready to book</p>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i data-lucide="building-2" class="w-4 h-4 text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Today's Bookings</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $totalBookingsToday; ?></p>
                            <p class="text-xs text-gray-500">Active reservations</p>
                        </div>
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Peak Hours</p>
                            <p class="text-2xl font-bold text-gray-900">2-4 PM</p>
                            <p class="text-xs text-gray-500">Most popular time</p>
                        </div>
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <i data-lucide="clock" class="w-4 h-4 text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Book Your Conference Room</h2>
                <p class="text-gray-600 mb-8">Find and reserve the perfect space for your meetings</p>
                <div class="space-x-4">
                    <a href="?view=rooms" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Browse Rooms
                    </a>
                    <a href="?view=bookings" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        View My Bookings
                    </a>
                </div>
            </div>

        <?php elseif ($currentView === 'rooms'): ?>
            <!-- Rooms View -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Browse Conference Rooms</h2>
                
                <!-- Filters -->
                <form method="GET" class="flex flex-col sm:flex-row gap-4 mb-6">
                    <input type="hidden" name="view" value="rooms">
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search rooms</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Search by name or location...">
                    </div>
                    <div class="sm:w-48">
                        <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Minimum capacity</label>
                        <select id="capacity" name="capacity" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Any capacity</option>
                            <option value="4" <?php echo $capacityFilter == 4 ? 'selected' : ''; ?>>4+ people</option>
                            <option value="8" <?php echo $capacityFilter == 8 ? 'selected' : ''; ?>>8+ people</option>
                            <option value="12" <?php echo $capacityFilter == 12 ? 'selected' : ''; ?>>12+ people</option>
                            <option value="20" <?php echo $capacityFilter == 20 ? 'selected' : ''; ?>>20+ people</option>
                        </select>
                    </div>
                    <div class="sm:w-24 flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Room Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($rooms as $room): ?>
                    <?php 
                    $amenities = explode(',', $room['amenities']);
                    $isAvailable = $room['available'];
                    ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden <?php echo !$isAvailable ? 'opacity-60' : ''; ?>">
                        <div class="aspect-video relative">
                            <img src="<?php echo htmlspecialchars($room['image_url'] ?: '/placeholder.svg?height=200&width=300'); ?>" 
                                 alt="<?php echo htmlspecialchars($room['name']); ?>" 
                                 class="w-full h-full object-cover">
                            <div class="absolute top-2 right-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $isAvailable ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $isAvailable ? 'Available' : 'Occupied'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <p class="text-sm text-gray-600 flex items-center mt-1">
                                        <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i>
                                        <?php echo htmlspecialchars($room['location']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-gray-900">$<?php echo number_format($room['price_per_hour'], 0); ?></div>
                                    <div class="text-xs text-gray-500">per hour</div>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($room['description']); ?></p>
                            
                            <div class="flex items-center gap-2 mb-4">
                                <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-sm text-gray-600">Up to <?php echo $room['capacity']; ?> people</span>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?php echo htmlspecialchars(trim($amenity)); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($amenities) > 3): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        +<?php echo count($amenities) - 3; ?> more
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <button onclick="openBookingModal(<?php echo htmlspecialchars(json_encode($room)); ?>)" 
                                    class="w-full px-4 py-2 <?php echo $isAvailable ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    <?php echo !$isAvailable ? 'disabled' : ''; ?>>
                                <?php echo $isAvailable ? 'Book Now' : 'Currently Occupied'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($rooms)): ?>
                <div class="text-center py-12">
                    <i data-lucide="building-2" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No rooms found</h3>
                    <p class="text-gray-600">Try adjusting your search criteria</p>
                </div>
            <?php endif; ?>

        <?php elseif ($currentView === 'bookings'): ?>
            <!-- Bookings View -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">My Booking History</h2>
                
                <?php 
                if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($userBookings)): ?>
                    <?php 
                    $bookingStats = [
                        'total' => count($userBookings),
                        'confirmed' => count(array_filter($userBookings, fn($b) => $b['status'] === 'confirmed')),
                        'pending' => count(array_filter($userBookings, fn($b) => $b['status'] === 'pending')),
                        'cancelled' => count(array_filter($userBookings, fn($b) => $b['status'] === 'cancelled')),
                        'total_spent' => array_sum(array_map(fn($b) => $b['status'] === 'confirmed' ? $b['total_cost'] : 0, $userBookings)),
                        'upcoming' => count(array_filter($userBookings, function($b) {
                            $bookingDate = new DateTime($b['booking_date']);
                            $today = new DateTime();
                            return $bookingDate >= $today && $b['status'] === 'confirmed';
                        }))
                    ];
                    ?>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="text-2xl font-bold text-blue-600"><?php echo $bookingStats['total']; ?></div>
                            <p class="text-xs text-gray-500">Total Bookings</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="text-2xl font-bold text-green-600"><?php echo $bookingStats['confirmed']; ?></div>
                            <p class="text-xs text-gray-500">Confirmed</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="text-2xl font-bold text-blue-600"><?php echo $bookingStats['upcoming']; ?></div>
                            <p class="text-xs text-gray-500">Upcoming</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="text-2xl font-bold text-purple-600">$<?php echo number_format($bookingStats['total_spent'], 0); ?></div>
                            <p class="text-xs text-gray-500">Total Spent</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($userBookings as $booking): ?>
                            <?php 
                            $bookingDate = new DateTime($booking['booking_date']);
                            $today = new DateTime();
                            $isUpcoming = $bookingDate >= $today && $booking['status'] === 'confirmed';
                            $isPast = $bookingDate < $today;
                            $canModify = $isUpcoming && $booking['status'] !== 'cancelled';
                            ?>
                            <div class="bg-white rounded-lg shadow p-6 <?php echo $isUpcoming ? 'border-l-4 border-l-blue-500' : ($isPast && $booking['status'] === 'confirmed' ? 'border-l-4 border-l-green-500' : ''); ?>">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                            <?php echo htmlspecialchars($booking['room_name']); ?>
                                            <?php if ($isUpcoming): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                                    Upcoming
                                                </span>
                                            <?php elseif ($isPast && $booking['status'] === 'confirmed'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                    Completed
                                                </span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 flex items-center gap-4 mt-1">
                                            <span><?php echo $bookingDate->format('l, F j, Y'); ?></span>
                                            <span class="text-xs">Booked on <?php echo (new DateTime($booking['created_at']))->format('M j, Y'); ?></span>
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                        echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                            ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                    ?>">
                                        <i data-lucide="check" class="w-3 h-3 mr-1"></i>
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500">Time</p>
                                        <p class="font-medium">
                                            <?php echo (new DateTime($booking['start_time']))->format('g:i A'); ?> - 
                                            <?php echo (new DateTime($booking['end_time']))->format('g:i A'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Duration</p>
                                        <p class="font-medium">
                                            <?php 
                                            $start = new DateTime($booking['start_time']);
                                            $end = new DateTime($booking['end_time']);
                                            $duration = $start->diff($end);
                                            echo $duration->h . ' hours';
                                            ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Attendees</p>
                                        <p class="font-medium"><?php echo $booking['attendees']; ?> people</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Cost</p>
                                        <p class="font-medium text-lg">$<?php echo number_format($booking['total_cost'], 0); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Purpose</p>
                                        <p class="font-medium"><?php echo htmlspecialchars($booking['purpose']); ?></p>
                                    </div>
                                </div>

                                <?php if ($canModify): ?>
                                    <!-- Added modify and cancel booking buttons -->
                                    <div class="mt-4 pt-4 border-t flex gap-2">
                                        <a href="modify_booking.php?id=<?php echo $booking['id']; ?>" 
                                           class="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            Modify Booking
                                        </a>
                                        <form method="POST" action="cancel_booking.php" class="inline" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="px-3 py-1 text-sm border border-red-300 text-red-700 rounded hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                Cancel Booking
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i data-lucide="calendar-days" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">No Bookings Yet</h2>
                        <p class="text-gray-600 mb-8">You haven't made any room reservations yet.</p>
                        <a href="?view=rooms" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Browse Rooms
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($currentView === 'admin' && isAdmin()): ?>
            <!-- Added comprehensive admin panel with room management, booking management, and user management -->
            <?php
            require_once 'includes/admin.php';
            $adminManager = new AdminManager();
            $systemStats = $adminManager->getSystemStats();
            $allRooms = $adminManager->getAllRooms();
            $allBookings = $adminManager->getAllBookings();
            $allUsers = $adminManager->getAllUsers();
            $activeTab = $_GET['tab'] ?? 'overview';
            ?>
            
            <!-- Admin Panel -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Admin Panel</h2>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Admin Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Rooms</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $systemStats['total_rooms']; ?></p>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i data-lucide="building-2" class="w-4 h-4 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $systemStats['total_bookings']; ?></p>
                            </div>
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i data-lucide="calendar-days" class="w-4 h-4 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending Bookings</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $systemStats['pending_bookings']; ?></p>
                            </div>
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i data-lucide="clock" class="w-4 h-4 text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($systemStats['total_revenue'], 0); ?></p>
                            </div>
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i data-lucide="dollar-sign" class="w-4 h-4 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Tabs -->
            <div class="bg-white rounded-lg shadow">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6">
                        <a href="?view=admin&tab=overview" class="<?php echo $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Overview
                        </a>
                        <a href="?view=admin&tab=rooms" class="<?php echo $activeTab === 'rooms' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Room Management
                        </a>
                        <a href="?view=admin&tab=bookings" class="<?php echo $activeTab === 'bookings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Booking Management
                        </a>
                        <a href="?view=admin&tab=users" class="<?php echo $activeTab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            User Management
                        </a>
                    </nav>
                </div>

                <div class="p-6">
                    <?php if ($activeTab === 'overview'): ?>
                        <!-- Overview Tab -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Users:</span>
                                        <span class="font-medium"><?php echo $systemStats['total_users']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Today's Bookings:</span>
                                        <span class="font-medium"><?php echo $systemStats['today_bookings']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Available Rooms:</span>
                                        <span class="font-medium"><?php echo count(array_filter($allRooms, fn($r) => $r['available'])); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Occupied Rooms:</span>
</cut_off_point>

between">
                                        <span class="text-gray-600">Occupied Rooms:</span>
                                        <span class="font-medium"><?php echo count(array_filter($allRooms, fn($r) => !$r['available'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                                <div class="space-y-3">
                                    <?php 
                                    $recentBookings = array_slice($allBookings, 0, 5);
                                    foreach ($recentBookings as $booking): 
                                    ?>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <div>
                                                <p class="text-sm font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></p>
                                                <p class="text-xs text-gray-500">Booked <?php echo htmlspecialchars($booking['room_name']); ?></p>
                                            </div>
                                            <span class="text-xs text-gray-500"><?php echo (new DateTime($booking['created_at']))->format('M j'); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'rooms'): ?>
                        <!-- Room Management Tab -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Manage Rooms</h3>
                            <button onclick="openAddRoomModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i data-lucide="plus" class="w-4 h-4 mr-2 inline"></i>
                                Add Room
                            </button>
                        </div>

                        <div class="space-y-4">
                            <?php foreach ($allRooms as $room): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($room['name']); ?></h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($room['location']); ?></p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="openEditRoomModal(<?php echo htmlspecialchars(json_encode($room)); ?>)" class="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <form method="POST" action="admin_actions.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this room?')">
                                                <input type="hidden" name="action" value="delete_room">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <button type="submit" class="px-3 py-1 text-sm border border-red-300 text-red-700 rounded hover:bg-red-50">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                                        <div>
                                            <p class="text-gray-500">Capacity</p>
                                            <p class="font-medium"><?php echo $room['capacity']; ?> people</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Price</p>
                                            <p class="font-medium">$<?php echo number_format($room['price_per_hour'], 0); ?>/hour</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Status</p>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $room['available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $room['available'] ? 'Available' : 'Occupied'; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Amenities</p>
                                            <p class="font-medium"><?php echo count(explode(',', $room['amenities'])); ?> items</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($activeTab === 'bookings'): ?>
                        <!-- Booking Management Tab -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Manage Bookings</h3>

                        <div class="space-y-4">
                            <?php foreach ($allBookings as $booking): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($booking['room_name']); ?></h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($booking['user_name']); ?> • 
                                                <?php echo (new DateTime($booking['booking_date']))->format('M j, Y'); ?>
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                                echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                    ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST" action="admin_actions.php" class="inline">
                                                    <input type="hidden" name="action" value="update_booking_status">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="admin_actions.php" class="inline">
                                                    <input type="hidden" name="action" value="update_booking_status">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="px-3 py-1 text-sm border border-red-300 text-red-700 rounded hover:bg-red-50">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-4 text-sm">
                                        <div>
                                            <p class="text-gray-500">Time</p>
                                            <p class="font-medium">
                                                <?php echo (new DateTime($booking['start_time']))->format('g:i A'); ?> - 
                                                <?php echo (new DateTime($booking['end_time']))->format('g:i A'); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Attendees</p>
                                            <p class="font-medium"><?php echo $booking['attendees']; ?> people</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Cost</p>
                                            <p class="font-medium">$<?php echo number_format($booking['total_cost'], 0); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Purpose</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($booking['purpose']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Booked</p>
                                            <p class="font-medium"><?php echo (new DateTime($booking['created_at']))->format('M j, Y'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($activeTab === 'users'): ?>
                        <!-- User Management Tab -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Manage Users</h3>

                        <div class="space-y-4">
                            <?php foreach ($allUsers as $user): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                            <p class="text-xs text-gray-500">Joined <?php echo (new DateTime($user['created_at']))->format('M j, Y'); ?></p>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                            <?php if ($user['id'] != $currentUser['id']): ?>
                                                <form method="POST" action="admin_actions.php" class="inline">
                                                    <input type="hidden" name="action" value="update_user_role">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <select name="role" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1">
                                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Add New Room</h3>
                    <button onclick="closeAddRoomModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <form action="admin_actions.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_room">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                            <input type="text" id="name" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                            <input type="number" id="capacity" name="capacity" min="1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" id="location" name="location" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="price_per_hour" class="block text-sm font-medium text-gray-700 mb-1">Price per Hour ($)</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" min="0" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="amenities" class="block text-sm font-medium text-gray-700 mb-1">Amenities (comma-separated)</label>
                        <input type="text" id="amenities" name="amenities" placeholder="Projector, Whiteboard, WiFi"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                        <input type="url" id="image_url" name="image_url"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="available" name="available" checked class="mr-2">
                        <label for="available" class="text-sm font-medium text-gray-700">Room Available</label>
                    </div>
                    
                    <div class="flex gap-2 pt-4">
                        <button type="button" onclick="closeAddRoomModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Room</h3>
                    <button onclick="closeEditRoomModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <form id="editRoomForm" action="admin_actions.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="edit_room">
                    <input type="hidden" id="edit_room_id" name="room_id">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                            <input type="text" id="edit_name" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="edit_capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                            <input type="number" id="edit_capacity" name="capacity" min="1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit_location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" id="edit_location" name="location" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="edit_price_per_hour" class="block text-sm font-medium text-gray-700 mb-1">Price per Hour ($)</label>
                        <input type="number" id="edit_price_per_hour" name="price_per_hour" min="0" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="edit_amenities" class="block text-sm font-medium text-gray-700 mb-1">Amenities (comma-separated)</label>
                        <input type="text" id="edit_amenities" name="amenities" placeholder="Projector, Whiteboard, WiFi"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="edit_description" name="description" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div>
                        <label for="edit_image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                        <input type="url" id="edit_image_url" name="image_url"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="edit_available" name="available" class="mr-2">
                        <label for="edit_available" class="text-sm font-medium text-gray-700">Room Available</label>
                    </div>
                    
                    <div class="flex gap-2 pt-4">
                        <button type="button" onclick="closeEditRoomModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Book Room</h3>
                    <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <form id="bookingForm" action="book_room.php" method="POST" class="space-y-4">
                    <input type="hidden" id="roomId" name="room_id">
                    <input type="hidden" id="roomName" name="room_name">
                    <input type="hidden" id="pricePerHour" name="price_per_hour">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bookingDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" id="bookingDate" name="booking_date" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="attendees" class="block text-sm font-medium text-gray-700 mb-1">Attendees</label>
                            <input type="number" id="attendees" name="attendees" min="1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="startTime" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                            <input type="time" id="startTime" name="start_time" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="endTime" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                            <input type="time" id="endTime" name="end_time" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Meeting Purpose</label>
                        <textarea id="purpose" name="purpose" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Brief description of your meeting..."></textarea>
                    </div>
                    
                    <div id="costEstimate" class="bg-gray-50 p-3 rounded-md hidden">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Estimated Cost:</span>
                            <span id="totalCost" class="text-lg font-bold text-gray-900">$0</span>
                        </div>
                        <p id="costBreakdown" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                    
                    <div class="flex gap-2 pt-4">
                        <button type="button" onclick="closeBookingModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentRoom = null;

        function openBookingModal(room) {
            currentRoom = room;
            document.getElementById('modalTitle').textContent = 'Book ' + room.name;
            document.getElementById('roomId').value = room.id;
            document.getElementById('roomName').value = room.name;
            document.getElementById('pricePerHour').value = room.price_per_hour;
            document.getElementById('attendees').max = room.capacity;
            document.getElementById('bookingModal').classList.remove('hidden');
            document.getElementById('bookingModal').classList.add('flex');
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
            document.getElementById('bookingModal').classList.remove('flex');
            document.getElementById('bookingForm').reset();
            document.getElementById('costEstimate').classList.add('hidden');
        }

        function calculateCost() {
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            
            if (startTime && endTime && currentRoom) {
                const start = new Date('2000-01-01T' + startTime);
                const end = new Date('2000-01-01T' + endTime);
                
                if (end > start) {
                    const hours = (end - start) / (1000 * 60 * 60);
                    const totalCost = hours * currentRoom.price_per_hour;
                    
                    document.getElementById('totalCost').textContent = '$' + totalCost.toFixed(0);
                    document.getElementById('costBreakdown').textContent = hours + ' hours × $' + currentRoom.price_per_hour + '/hour';
                    document.getElementById('costEstimate').classList.remove('hidden');
                } else {
                    document.getElementById('costEstimate').classList.add('hidden');
                }
            }
        }

        function openAddRoomModal() {
            document.getElementById('addRoomModal').classList.remove('hidden');
            document.getElementById('addRoomModal').classList.add('flex');
        }

        function closeAddRoomModal() {
            document.getElementById('addRoomModal').classList.add('hidden');
            document.getElementById('addRoomModal').classList.remove('flex');
        }

        function openEditRoomModal(room) {
            document.getElementById('edit_room_id').value = room.id;
            document.getElementById('edit_name').value = room.name;
            document.getElementById('edit_capacity').value = room.capacity;
            document.getElementById('edit_location').value = room.location;
            document.getElementById('edit_price_per_hour').value = room.price_per_hour;
            document.getElementById('edit_amenities').value = room.amenities;
            document.getElementById('edit_description').value = room.description;
            document.getElementById('edit_image_url').value = room.image_url || '';
            document.getElementById('edit_available').checked = room.available == 1;
            
            document.getElementById('editRoomModal').classList.remove('hidden');
            document.getElementById('editRoomModal').classList.add('flex');
        }

        function closeEditRoomModal() {
            document.getElementById('editRoomModal').classList.add('hidden');
            document.getElementById('editRoomModal').classList.remove('flex');
        }

        document.getElementById('startTime').addEventListener('change', calculateCost);
        document.getElementById('endTime').addEventListener('change', calculateCost);

        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>
