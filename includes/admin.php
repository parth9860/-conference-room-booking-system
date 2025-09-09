<?php
require_once 'config/database.php';

class AdminManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getAllRooms() {
        $query = "SELECT * FROM rooms ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addRoom($data) {
        $query = "INSERT INTO rooms (name, capacity, location, amenities, description, image_url, price_per_hour, available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['name'],
            $data['capacity'],
            $data['location'],
            $data['amenities'],
            $data['description'],
            $data['image_url'],
            $data['price_per_hour'],
            $data['available'] ? 1 : 0
        ]);
    }
    
    public function updateRoom($id, $data) {
        $query = "UPDATE rooms SET name = ?, capacity = ?, location = ?, amenities = ?, description = ?, image_url = ?, price_per_hour = ?, available = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['name'],
            $data['capacity'],
            $data['location'],
            $data['amenities'],
            $data['description'],
            $data['image_url'],
            $data['price_per_hour'],
            $data['available'] ? 1 : 0,
            $id
        ]);
    }
    
    public function deleteRoom($id) {
        // First check if room has any bookings
        $bookingQuery = "SELECT COUNT(*) as count FROM bookings WHERE room_id = ? AND status != 'cancelled'";
        $bookingStmt = $this->db->prepare($bookingQuery);
        $bookingStmt->execute([$id]);
        $bookingCount = $bookingStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($bookingCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete room with active bookings'];
        }
        
        $query = "DELETE FROM rooms WHERE id = ?";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([$id])) {
            return ['success' => true, 'message' => 'Room deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete room'];
    }
    
    public function getRoomById($id) {
        $query = "SELECT * FROM rooms WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllBookings($filters = []) {
        $query = "SELECT b.*, u.name as user_name, u.email as user_email, r.name as room_name 
                  FROM bookings b 
                  JOIN users u ON b.user_id = u.id 
                  JOIN rooms r ON b.room_id = r.id
                  WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query .= " AND b.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (r.name LIKE ? OR b.purpose LIKE ? OR u.name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $query .= " ORDER BY b.booking_date DESC, b.start_time DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateBookingStatus($bookingId, $status) {
        $query = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $bookingId]);
    }
    
    public function getSystemStats() {
        $stats = [];
        
        // Total rooms
        $roomQuery = "SELECT COUNT(*) as count FROM rooms";
        $roomStmt = $this->db->prepare($roomQuery);
        $roomStmt->execute();
        $stats['total_rooms'] = $roomStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total bookings
        $bookingQuery = "SELECT COUNT(*) as count FROM bookings";
        $bookingStmt = $this->db->prepare($bookingQuery);
        $bookingStmt->execute();
        $stats['total_bookings'] = $bookingStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Pending bookings
        $pendingQuery = "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'";
        $pendingStmt = $this->db->prepare($pendingQuery);
        $pendingStmt->execute();
        $stats['pending_bookings'] = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total revenue
        $revenueQuery = "SELECT SUM(total_cost) as revenue FROM bookings WHERE status = 'confirmed'";
        $revenueStmt = $this->db->prepare($revenueQuery);
        $revenueStmt->execute();
        $stats['total_revenue'] = $revenueStmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?: 0;
        
        // Total users
        $userQuery = "SELECT COUNT(*) as count FROM users";
        $userStmt = $this->db->prepare($userQuery);
        $userStmt->execute();
        $stats['total_users'] = $userStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Today's bookings
        $todayQuery = "SELECT COUNT(*) as count FROM bookings WHERE booking_date = CURDATE() AND status = 'confirmed'";
        $todayStmt = $this->db->prepare($todayQuery);
        $todayStmt->execute();
        $stats['today_bookings'] = $todayStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
    
    public function getAllUsers() {
        $query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateUserRole($userId, $role) {
        $query = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$role, $userId]);
    }
}
?>
