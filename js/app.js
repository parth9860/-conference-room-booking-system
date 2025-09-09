// Main application JavaScript
class ConferenceBookingApp {
  constructor() {
    this.currentUser = null
    this.rooms = []
    this.bookings = []
    this.currentTab = "rooms"

    this.init()
  }

  async init() {
    // Check if user is logged in
    await this.checkAuth()

    if (!this.currentUser) {
      window.location.href = "login.html"
      return
    }

    this.setupEventListeners()
    await this.loadData()
    this.updateUI()
  }

  async checkAuth() {
    try {
      const response = await fetch("api/check_auth.php")
      const data = await response.json()

      if (data.success) {
        this.currentUser = data.user
      }
    } catch (error) {
      console.error("Auth check failed:", error)
    }
  }

  setupEventListeners() {
    // Tab switching
    document.querySelectorAll(".tab-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        this.switchTab(e.target.dataset.tab)
      })
    })

    // Logout
    document.getElementById("logoutBtn").addEventListener("click", this.logout.bind(this))

    // Search and filter
    document.getElementById("searchInput").addEventListener("input", this.filterRooms.bind(this))
    document.getElementById("capacityFilter").addEventListener("change", this.filterRooms.bind(this))

    // Modal controls
    document.getElementById("closeModal").addEventListener("click", this.closeBookingModal.bind(this))
    document.getElementById("cancelBooking").addEventListener("click", this.closeBookingModal.bind(this))

    // Booking form
    document.getElementById("bookingForm").addEventListener("submit", this.submitBooking.bind(this))

    // Cost calculation
    document.getElementById("startTime").addEventListener("change", this.calculateCost.bind(this))
    document.getElementById("endTime").addEventListener("change", this.calculateCost.bind(this))
  }

  async loadData() {
    await Promise.all([this.loadRooms(), this.loadBookings(), this.loadStats()])
  }

  async loadRooms() {
    try {
      const response = await fetch("api/rooms.php")
      const data = await response.json()

      if (data.success) {
        this.rooms = data.rooms
        this.renderRooms()
      }
    } catch (error) {
      console.error("Failed to load rooms:", error)
    }
  }

  async loadBookings() {
    try {
      const response = await fetch("api/bookings.php")
      const data = await response.json()

      if (data.success) {
        this.bookings = data.bookings
        this.renderBookings()
      }
    } catch (error) {
      console.error("Failed to load bookings:", error)
    }
  }

  async loadStats() {
    try {
      const response = await fetch("api/stats.php")
      const data = await response.json()

      if (data.success) {
        this.updateStats(data.stats)
      }
    } catch (error) {
      console.error("Failed to load stats:", error)
    }
  }

  updateUI() {
    // Update user welcome message
    document.getElementById("userWelcome").textContent = `Welcome, ${this.currentUser.name}`

    // Show admin tab if user is admin
    if (this.currentUser.is_admin) {
      document.getElementById("adminTab").classList.remove("hidden")
    }
  }

  updateStats(stats) {
    document.getElementById("totalRooms").textContent = stats.total_rooms || 0
    document.getElementById("availableRooms").textContent = stats.available_rooms || 0
    document.getElementById("myBookings").textContent = stats.my_bookings || 0
    document.getElementById("monthlyBookings").textContent = stats.monthly_bookings || 0
  }

  switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll(".tab-btn").forEach((btn) => {
      btn.classList.remove("active")
      if (btn.dataset.tab === tabName) {
        btn.classList.add("active")
      }
    })

    // Update tab content
    document.querySelectorAll(".tab-content").forEach((content) => {
      content.classList.add("hidden")
    })

    document.getElementById(`${tabName}Tab`).classList.remove("hidden")
    this.currentTab = tabName

    // Load tab-specific data
    if (tabName === "admin" && this.currentUser.is_admin) {
      this.loadAdminData()
    }
  }

  renderRooms() {
    const grid = document.getElementById("roomGrid")
    grid.innerHTML = ""

    this.rooms.forEach((room) => {
      const roomCard = this.createRoomCard(room)
      grid.appendChild(roomCard)
    })
  }

  createRoomCard(room) {
    const div = document.createElement("div")
    div.className = "room-card bg-white rounded-lg shadow-sm overflow-hidden"

    const isAvailable = this.isRoomAvailable(room.id)
    const statusClass = isAvailable ? "status-available" : "status-occupied"
    const statusText = isAvailable ? "Available" : "Occupied"

    div.innerHTML = `
            <img src="${room.image || "/placeholder.svg?height=200&width=300"}" 
                 alt="${room.name}" class="w-full h-48 object-cover">
            <div class="p-6">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">${room.name}</h3>
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                        ${statusText}
                    </span>
                </div>
                <p class="text-gray-600 text-sm mb-3">${room.location}</p>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-gray-500">Capacity: ${room.capacity} people</span>
                    <span class="text-lg font-bold text-blue-600">$${room.hourly_rate}/hr</span>
                </div>
                <div class="flex flex-wrap gap-1 mb-4">
                    ${room.amenities
                      .split(",")
                      .map((amenity) => `<span class="amenity-tag">${amenity.trim()}</span>`)
                      .join("")}
                </div>
                <button onclick="app.openBookingModal(${room.id})" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors ${!isAvailable ? "opacity-50 cursor-not-allowed" : ""}"
                        ${!isAvailable ? "disabled" : ""}>
                    ${isAvailable ? "Book Room" : "Not Available"}
                </button>
            </div>
        `

    return div
  }

  renderBookings() {
    const list = document.getElementById("bookingsList")
    list.innerHTML = ""

    if (this.bookings.length === 0) {
      list.innerHTML = '<div class="p-6 text-center text-gray-500">No bookings found</div>'
      return
    }

    this.bookings.forEach((booking) => {
      const bookingCard = this.createBookingCard(booking)
      list.appendChild(bookingCard)
    })
  }

  createBookingCard(booking) {
    const div = document.createElement("div")
    div.className = "booking-card p-6"

    const statusClass =
      booking.status === "confirmed"
        ? "text-green-600"
        : booking.status === "pending"
          ? "text-yellow-600"
          : "text-red-600"

    div.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-medium text-gray-900">${booking.room_name}</h4>
                    <p class="text-sm text-gray-600">${booking.date} • ${booking.start_time} - ${booking.end_time}</p>
                    <p class="text-sm text-gray-500 mt-1">${booking.purpose}</p>
                </div>
                <div class="text-right">
                    <span class="text-lg font-bold text-gray-900">$${booking.total_cost}</span>
                    <p class="text-sm ${statusClass} capitalize">${booking.status}</p>
                </div>
            </div>
            <div class="mt-4 flex space-x-2">
                <button onclick="app.modifyBooking(${booking.id})" 
                        class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                    Modify
                </button>
                <button onclick="app.cancelBooking(${booking.id})" 
                        class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                    Cancel
                </button>
            </div>
        `

    return div
  }

  filterRooms() {
    const searchTerm = document.getElementById("searchInput").value.toLowerCase()
    const minCapacity = Number.parseInt(document.getElementById("capacityFilter").value) || 0

    const filteredRooms = this.rooms.filter((room) => {
      const matchesSearch =
        room.name.toLowerCase().includes(searchTerm) ||
        room.location.toLowerCase().includes(searchTerm) ||
        room.amenities.toLowerCase().includes(searchTerm)
      const matchesCapacity = room.capacity >= minCapacity

      return matchesSearch && matchesCapacity
    })

    this.renderFilteredRooms(filteredRooms)
  }

  renderFilteredRooms(rooms) {
    const grid = document.getElementById("roomGrid")
    grid.innerHTML = ""

    rooms.forEach((room) => {
      const roomCard = this.createRoomCard(room)
      grid.appendChild(roomCard)
    })
  }

  isRoomAvailable(roomId) {
    const now = new Date()
    const currentTime = now.getHours() * 60 + now.getMinutes()
    const today = now.toISOString().split("T")[0]

    const currentBookings = this.bookings.filter(
      (booking) => booking.room_id == roomId && booking.date === today && booking.status === "confirmed",
    )

    return !currentBookings.some((booking) => {
      const startTime = this.timeToMinutes(booking.start_time)
      const endTime = this.timeToMinutes(booking.end_time)
      return currentTime >= startTime && currentTime < endTime
    })
  }

  timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(":").map(Number)
    return hours * 60 + minutes
  }

  openBookingModal(roomId) {
    const room = this.rooms.find((r) => r.id == roomId)
    if (!room) return

    document.getElementById("roomId").value = roomId
    document.getElementById("bookingModal").classList.remove("hidden")

    // Set minimum date to today
    const today = new Date().toISOString().split("T")[0]
    document.getElementById("bookingDate").min = today
    document.getElementById("bookingDate").value = today
  }

  closeBookingModal() {
    document.getElementById("bookingModal").classList.add("hidden")
    document.getElementById("bookingForm").reset()
    document.getElementById("totalCost").textContent = "$0"
  }

  calculateCost() {
    const roomId = document.getElementById("roomId").value
    const startTime = document.getElementById("startTime").value
    const endTime = document.getElementById("endTime").value

    if (!roomId || !startTime || !endTime) return

    const room = this.rooms.find((r) => r.id == roomId)
    if (!room) return

    const start = this.timeToMinutes(startTime)
    const end = this.timeToMinutes(endTime)

    if (end <= start) {
      document.getElementById("totalCost").textContent = "$0"
      return
    }

    const hours = (end - start) / 60
    const cost = hours * room.hourly_rate

    document.getElementById("totalCost").textContent = `$${cost.toFixed(2)}`
  }

  async submitBooking(e) {
    e.preventDefault()

    const formData = new FormData(e.target)

    try {
      const response = await fetch("api/book_room.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        this.showMessage("Booking created successfully!", "success")
        this.closeBookingModal()
        await this.loadBookings()
        await this.loadStats()
      } else {
        this.showMessage(data.message || "Booking failed", "error")
      }
    } catch (error) {
      console.error("Booking error:", error)
      this.showMessage("An error occurred while booking", "error")
    }
  }

  async cancelBooking(bookingId) {
    if (!confirm("Are you sure you want to cancel this booking?")) return

    try {
      const response = await fetch("api/cancel_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ booking_id: bookingId }),
      })

      const data = await response.json()

      if (data.success) {
        this.showMessage("Booking cancelled successfully!", "success")
        await this.loadBookings()
        await this.loadStats()
      } else {
        this.showMessage(data.message || "Cancellation failed", "error")
      }
    } catch (error) {
      console.error("Cancellation error:", error)
      this.showMessage("An error occurred while cancelling", "error")
    }
  }

  async logout() {
    try {
      await fetch("api/logout.php", { method: "POST" })
      window.location.href = "login.html"
    } catch (error) {
      console.error("Logout error:", error)
      window.location.href = "login.html"
    }
  }

  showMessage(message, type) {
    // Create a temporary message element
    const messageDiv = document.createElement("div")
    messageDiv.className = `fixed top-4 right-4 p-4 rounded-md z-50 ${type === "success" ? "message-success" : "message-error"}`
    messageDiv.textContent = message

    document.body.appendChild(messageDiv)

    setTimeout(() => {
      messageDiv.remove()
    }, 3000)
  }

  async loadAdminData() {
    // Load admin-specific data and render admin panel
    // This would include room management, user management, etc.
    console.log("Loading admin data...")
  }
}

// Initialize the app when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.app = new ConferenceBookingApp()
})
