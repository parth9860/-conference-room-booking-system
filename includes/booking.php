<?php
require_once 'config/database.php';

class BookingManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function createBooking($data) {
        // Validation
        $errors = $this->validateBooking($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Calculate total cost
        $startDateTime = new DateTime($data['start_time']);
        $endDateTime = new DateTime($data['end_time']);
        $duration = $startDateTime->diff($endDateTime);
        $hours = $duration->h + ($duration->i / 60);
        $totalCost = $hours * $data['price_per_hour'];
        
        // Insert booking
        $query = "INSERT INTO bookings (room_id, user_id, user_name, room_name, booking_date, start_time, end_time, purpose, attendees, total_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([
            $data['room_id'],
            $data['user_id'],
            $data['user_name'],
            $data['room_name'],
            $data['booking_date'],
            $data['start_time'],
            $data['end_time'],
            $data['purpose'],
            $data['attendees'],
            $totalCost
        ])) {
            return ['success' => true, 'booking_id' => $this->db->lastInsertId()];
        }
        
        return ['success' => false, 'errors' => ['Failed to create booking']];
    }
    
    public function validateBooking($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['booking_date']) || empty($data['start_time']) || empty($data['end_time']) || empty($data['purpose'])) {
            $errors[] = 'Please fill in all required fields';
        }
        
        // Time validation
        if ($data['start_time'] >= $data['end_time']) {
            $errors[] = 'End time must be after start time';
        }
        
        // Check room capacity
        $roomQuery = "SELECT capacity FROM rooms WHERE id = ?";
        $roomStmt = $this->db->prepare($roomQuery);
        $roomStmt->execute([$data['room_id']]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            $errors[] = 'Room not found';
        } elseif ($data['attendees'] > $room['capacity']) {
            $errors[] = 'Number of attendees cannot exceed room capacity (' . $room['capacity'] . ')';
        }
        
        // Check for conflicts
        if ($this->hasConflict($data['room_id'], $data['booking_date'], $data['start_time'], $data['end_time'])) {
            $errors[] = 'This time slot is already booked';
        }
        
        return $errors;
    }
    
    public function hasConflict($roomId, $date, $startTime, $endTime, $excludeBookingId = null) {
        $query = "SELECT id FROM bookings WHERE room_id = ? AND booking_date = ? AND status != 'cancelled' AND (
            (start_time <= ? AND end_time > ?) OR 
            (start_time < ? AND end_time >= ?) OR 
            (start_time >= ? AND end_time <= ?)
        )";
        
        $params = [$roomId, $date, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime];
        
        if ($excludeBookingId) {
            $query .= " AND id != ?";
            $params[] = $excludeBookingId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    }
    
    public function getUserBookings($userId, $filters = []) {
        $query = "SELECT * FROM bookings WHERE user_id = ?";
        $params = [$userId];
        
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (room_name LIKE ? OR purpose LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $sortBy = $filters['sort_by'] ?? 'booking_date';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        
        $query .= " ORDER BY {$sortBy} {$sortOrder}, start_time {$sortOrder}";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBookingById($bookingId, $userId = null) {
        $query = "SELECT * FROM bookings WHERE id = ?";
        $params = [$bookingId];
        
        if ($userId) {
            $query .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateBookingStatus($bookingId, $status, $userId = null) {
        $query = "UPDATE bookings SET status = ? WHERE id = ?";
        $params = [$status, $bookingId];
        
        if ($userId) {
            $query .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    public function cancelBooking($bookingId, $userId) {
        return $this->updateBookingStatus($bookingId, 'cancelled', $userId);
    }
    
    public function getBookingStats($userId = null) {
        $baseQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'confirmed' THEN total_cost ELSE 0 END) as total_spent,
            SUM(CASE WHEN booking_date >= CURDATE() AND status = 'confirmed' THEN 1 ELSE 0 END) as upcoming
            FROM bookings";
        
        if ($userId) {
            $baseQuery .= " WHERE user_id = ?";
            $stmt = $this->db->prepare($baseQuery);
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->prepare($baseQuery);
            $stmt->execute();
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllBookings($filters = []) {
        $query = "SELECT b.*, u.name as user_name, u.email as user_email 
                  FROM bookings b 
                  JOIN users u ON b.user_id = u.id 
                  WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query .= " AND b.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (b.room_name LIKE ? OR b.purpose LIKE ? OR u.name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $sortBy = $filters['sort_by'] ?? 'booking_date';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        
        $query .= " ORDER BY b.{$sortBy} {$sortOrder}, b.start_time {$sortOrder}";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
