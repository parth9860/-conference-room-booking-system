"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Badge } from "@/components/ui/badge"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { Textarea } from "@/components/ui/textarea"
import {
  CalendarDays,
  Clock,
  Building2,
  Users,
  Wifi,
  Monitor,
  Coffee,
  MapPin,
  Check,
  Settings,
  Edit,
  Trash2,
  Plus,
} from "lucide-react"

interface User {
  id: string
  email: string
  name: string
  role: "user" | "admin"
}

interface Room {
  id: string
  name: string
  capacity: number
  location: string
  amenities: string[]
  description: string
  imageUrl: string
  pricePerHour: number
  available: boolean
}

interface Booking {
  id: string
  roomId: string
  userId: string
  userName: string
  roomName: string
  date: string
  startTime: string
  endTime: string
  purpose: string
  attendees: number
  totalCost: number
  status: "confirmed" | "pending" | "cancelled"
  createdAt: string
}

const sampleRooms: Room[] = [
  {
    id: "1",
    name: "Executive Boardroom",
    capacity: 12,
    location: "Floor 10, East Wing",
    amenities: ["Projector", "Whiteboard", "Video Conferencing", "Coffee Machine"],
    description: "Premium boardroom with city views, perfect for executive meetings and presentations.",
    imageUrl: "/modern-executive-boardroom-with-large-table.jpg",
    pricePerHour: 50,
    available: true,
  },
  {
    id: "2",
    name: "Creative Studio",
    capacity: 8,
    location: "Floor 5, West Wing",
    amenities: ["Whiteboard", "Wireless Display", "Standing Desks", "Natural Light"],
    description: "Bright, creative space designed for brainstorming and collaborative work sessions.",
    imageUrl: "/bright-creative-meeting-room-with-whiteboard.jpg",
    pricePerHour: 35,
    available: true,
  },
  {
    id: "3",
    name: "Tech Hub",
    capacity: 6,
    location: "Floor 3, North Wing",
    amenities: ["Multiple Monitors", "High-Speed Internet", "Power Outlets", "Ergonomic Chairs"],
    description: "Technology-focused room equipped for development teams and technical discussions.",
    imageUrl: "/modern-tech-meeting-room-with-monitors.jpg",
    pricePerHour: 40,
    available: false,
  },
  {
    id: "4",
    name: "Collaboration Space",
    capacity: 15,
    location: "Floor 7, Central",
    amenities: ["Projector", "Sound System", "Moveable Furniture", "Catering Setup"],
    description: "Flexible space that can be configured for various meeting types and team events.",
    imageUrl: "/flexible-collaboration-meeting-space.jpg",
    pricePerHour: 45,
    available: true,
  },
  {
    id: "5",
    name: "Quiet Focus Room",
    capacity: 4,
    location: "Floor 2, South Wing",
    amenities: ["Soundproofing", "Whiteboard", "Comfortable Seating", "Natural Light"],
    description: "Intimate space perfect for small team meetings and focused discussions.",
    imageUrl: "/small-quiet-meeting-room-with-comfortable-seating.jpg",
    pricePerHour: 25,
    available: true,
  },
  {
    id: "6",
    name: "Presentation Theater",
    capacity: 30,
    location: "Floor 1, Main Hall",
    amenities: ["Large Screen", "Audio System", "Theater Seating", "Recording Equipment"],
    description: "Professional presentation space ideal for large meetings and company events.",
    imageUrl: "/presentation-theater-with-large-screen.jpg",
    pricePerHour: 75,
    available: true,
  },
]

export default function HomePage() {
  const [currentUser, setCurrentUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [currentView, setCurrentView] = useState<"dashboard" | "rooms" | "bookings" | "admin">("dashboard")
  const [rooms, setRooms] = useState<Room[]>(sampleRooms)
  const [searchTerm, setSearchTerm] = useState("")
  const [capacityFilter, setCapacityFilter] = useState<number | null>(null)
  const [bookings, setBookings] = useState<Booking[]>([])

  useEffect(() => {
    // Check if user is logged in
    const userData = localStorage.getItem("currentUser")
    if (userData) {
      setCurrentUser(JSON.parse(userData))
    }
    setIsLoading(false)

    if (!localStorage.getItem("rooms")) {
      localStorage.setItem("rooms", JSON.stringify(sampleRooms))
    } else {
      const storedRooms = JSON.parse(localStorage.getItem("rooms") || "[]")
      setRooms(storedRooms)
    }

    const storedBookings = JSON.parse(localStorage.getItem("bookings") || "[]")
    setBookings(storedBookings)
  }, [])

  const handleLogout = () => {
    localStorage.removeItem("currentUser")
    setCurrentUser(null)
  }

  const handleCreateBooking = (booking: Omit<Booking, "id" | "createdAt">) => {
    const newBooking: Booking = {
      ...booking,
      id: Date.now().toString(),
      createdAt: new Date().toISOString(),
    }

    const updatedBookings = [...bookings, newBooking]
    setBookings(updatedBookings)
    localStorage.setItem("bookings", JSON.stringify(updatedBookings))
  }

  const handleUpdateRoom = (updatedRoom: Room) => {
    const updatedRooms = rooms.map((room) => (room.id === updatedRoom.id ? updatedRoom : room))
    setRooms(updatedRooms)
    localStorage.setItem("rooms", JSON.stringify(updatedRooms))
  }

  const handleDeleteRoom = (roomId: string) => {
    const updatedRooms = rooms.filter((room) => room.id !== roomId)
    setRooms(updatedRooms)
    localStorage.setItem("rooms", JSON.stringify(updatedRooms))
  }

  const handleAddRoom = (newRoom: Omit<Room, "id">) => {
    const room: Room = {
      ...newRoom,
      id: Date.now().toString(),
    }
    const updatedRooms = [...rooms, room]
    setRooms(updatedRooms)
    localStorage.setItem("rooms", JSON.stringify(updatedRooms))
  }

  const handleUpdateBookingStatus = (bookingId: string, status: Booking["status"]) => {
    const updatedBookings = bookings.map((booking) => (booking.id === bookingId ? { ...booking, status } : booking))
    setBookings(updatedBookings)
    localStorage.setItem("bookings", JSON.stringify(updatedBookings))
  }

  const filteredRooms = rooms.filter((room) => {
    const matchesSearch =
      room.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      room.location.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesCapacity = capacityFilter ? room.capacity >= capacityFilter : true
    return matchesSearch && matchesCapacity
  })

  const availableRoomsCount = rooms.filter((room) => room.available).length
  const totalBookingsToday = bookings.filter((booking) => {
    const today = new Date().toISOString().split("T")[0]
    return booking.date === today && booking.status === "confirmed"
  }).length

  const userBookings = currentUser ? bookings.filter((booking) => booking.userId === currentUser.id) : []

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    )
  }

  if (!currentUser) {
    return <AuthPage onLogin={setCurrentUser} />
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b bg-card">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Building2 className="h-6 w-6 text-primary" />
            <h1 className="text-xl font-semibold">Conference Room Booking</h1>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-sm text-muted-foreground">Welcome, {currentUser.name}</span>
            {currentUser.role === "admin" && <Badge variant="secondary">Admin</Badge>}
            <Button variant="outline" onClick={handleLogout}>
              Logout
            </Button>
          </div>
        </div>
      </header>

      {/* Navigation */}
      <nav className="border-b bg-muted/50">
        <div className="container mx-auto px-4">
          <div className="flex space-x-8">
            <button
              onClick={() => setCurrentView("dashboard")}
              className={`py-4 px-2 border-b-2 font-medium text-sm ${
                currentView === "dashboard"
                  ? "border-primary text-primary"
                  : "border-transparent text-muted-foreground hover:text-foreground"
              }`}
            >
              Dashboard
            </button>
            <button
              onClick={() => setCurrentView("rooms")}
              className={`py-4 px-2 border-b-2 font-medium text-sm ${
                currentView === "rooms"
                  ? "border-primary text-primary"
                  : "border-transparent text-muted-foreground hover:text-foreground"
              }`}
            >
              Browse Rooms
            </button>
            <button
              onClick={() => setCurrentView("bookings")}
              className={`py-4 px-2 border-b-2 font-medium text-sm ${
                currentView === "bookings"
                  ? "border-primary text-primary"
                  : "border-transparent text-muted-foreground hover:text-foreground"
              }`}
            >
              My Bookings
            </button>
            {currentUser.role === "admin" && (
              <button
                onClick={() => setCurrentView("admin")}
                className={`py-4 px-2 border-b-2 font-medium text-sm ${
                  currentView === "admin"
                    ? "border-primary text-primary"
                    : "border-transparent text-muted-foreground hover:text-foreground"
                }`}
              >
                <Settings className="h-4 w-4 inline mr-1" />
                Admin Panel
              </button>
            )}
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        {currentView === "dashboard" && (
          <DashboardView
            availableRoomsCount={availableRoomsCount}
            totalBookingsToday={totalBookingsToday}
            onBrowseRooms={() => setCurrentView("rooms")}
            onViewBookings={() => setCurrentView("bookings")}
          />
        )}

        {currentView === "rooms" && (
          <RoomsView
            rooms={filteredRooms}
            searchTerm={searchTerm}
            setSearchTerm={setSearchTerm}
            capacityFilter={capacityFilter}
            setCapacityFilter={setCapacityFilter}
            currentUser={currentUser}
            onCreateBooking={handleCreateBooking}
          />
        )}

        {currentView === "bookings" && <BookingsView bookings={userBookings} />}

        {currentView === "admin" && currentUser.role === "admin" && (
          <AdminPanel
            rooms={rooms}
            bookings={bookings}
            onUpdateRoom={handleUpdateRoom}
            onDeleteRoom={handleDeleteRoom}
            onAddRoom={handleAddRoom}
            onUpdateBookingStatus={handleUpdateBookingStatus}
          />
        )}
      </main>
    </div>
  )
}

function DashboardView({
  availableRoomsCount,
  totalBookingsToday,
  onBrowseRooms,
  onViewBookings,
}: {
  availableRoomsCount: number
  totalBookingsToday: number
  onBrowseRooms: () => void
  onViewBookings: () => void
}) {
  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Available Rooms</CardTitle>
            <Building2 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{availableRoomsCount}</div>
            <p className="text-xs text-muted-foreground">Ready to book</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Today's Bookings</CardTitle>
            <CalendarDays className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalBookingsToday}</div>
            <p className="text-xs text-muted-foreground">Active reservations</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Peak Hours</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">2-4 PM</div>
            <p className="text-xs text-muted-foreground">Most popular time</p>
          </CardContent>
        </Card>
      </div>

      <div className="text-center">
        <h2 className="text-2xl font-bold mb-4">Book Your Conference Room</h2>
        <p className="text-muted-foreground mb-8">Find and reserve the perfect space for your meetings</p>
        <Button size="lg" className="mr-4" onClick={onBrowseRooms}>
          Browse Rooms
        </Button>
        <Button variant="outline" size="lg" onClick={onViewBookings}>
          View My Bookings
        </Button>
      </div>
    </>
  )
}

function RoomsView({
  rooms,
  searchTerm,
  setSearchTerm,
  capacityFilter,
  setCapacityFilter,
  currentUser,
  onCreateBooking,
}: {
  rooms: Room[]
  searchTerm: string
  setSearchTerm: (term: string) => void
  capacityFilter: number | null
  setCapacityFilter: (capacity: number | null) => void
  currentUser: User
  onCreateBooking: (booking: Omit<Booking, "id" | "createdAt">) => void
}) {
  const getAmenityIcon = (amenity: string) => {
    switch (amenity.toLowerCase()) {
      case "wifi":
      case "high-speed internet":
        return <Wifi className="h-4 w-4" />
      case "projector":
      case "large screen":
      case "multiple monitors":
        return <Monitor className="h-4 w-4" />
      case "coffee machine":
      case "catering setup":
        return <Coffee className="h-4 w-4" />
      default:
        return null
    }
  }

  return (
    <div>
      <div className="mb-8">
        <h2 className="text-2xl font-bold mb-4">Browse Conference Rooms</h2>

        {/* Filters */}
        <div className="flex flex-col sm:flex-row gap-4 mb-6">
          <div className="flex-1">
            <Label htmlFor="search">Search rooms</Label>
            <Input
              id="search"
              placeholder="Search by name or location..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="sm:w-48">
            <Label htmlFor="capacity">Minimum capacity</Label>
            <select
              id="capacity"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              value={capacityFilter || ""}
              onChange={(e) => setCapacityFilter(e.target.value ? Number.parseInt(e.target.value) : null)}
            >
              <option value="">Any capacity</option>
              <option value="4">4+ people</option>
              <option value="8">8+ people</option>
              <option value="12">12+ people</option>
              <option value="20">20+ people</option>
            </select>
          </div>
        </div>
      </div>

      {/* Room Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {rooms.map((room) => (
          <Card key={room.id} className={`overflow-hidden ${!room.available ? "opacity-60" : ""}`}>
            <div className="aspect-video relative">
              <img src={room.imageUrl || "/placeholder.svg"} alt={room.name} className="w-full h-full object-cover" />
              <div className="absolute top-2 right-2">
                <Badge variant={room.available ? "default" : "secondary"}>
                  {room.available ? "Available" : "Occupied"}
                </Badge>
              </div>
            </div>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="text-lg">{room.name}</CardTitle>
                  <CardDescription className="flex items-center gap-1 mt-1">
                    <MapPin className="h-3 w-3" />
                    {room.location}
                  </CardDescription>
                </div>
                <div className="text-right">
                  <div className="text-lg font-semibold">${room.pricePerHour}</div>
                  <div className="text-xs text-muted-foreground">per hour</div>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground mb-4">{room.description}</p>

              <div className="flex items-center gap-2 mb-4">
                <Users className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">Up to {room.capacity} people</span>
              </div>

              <div className="flex flex-wrap gap-2 mb-4">
                {room.amenities.slice(0, 3).map((amenity, index) => (
                  <Badge key={index} variant="outline" className="text-xs">
                    <span className="flex items-center gap-1">
                      {getAmenityIcon(amenity)}
                      {amenity}
                    </span>
                  </Badge>
                ))}
                {room.amenities.length > 3 && (
                  <Badge variant="outline" className="text-xs">
                    +{room.amenities.length - 3} more
                  </Badge>
                )}
              </div>

              <BookingDialog
                room={room}
                currentUser={currentUser}
                onCreateBooking={onCreateBooking}
                disabled={!room.available}
              />
            </CardContent>
          </Card>
        ))}
      </div>

      {rooms.length === 0 && (
        <div className="text-center py-12">
          <Building2 className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-semibold mb-2">No rooms found</h3>
          <p className="text-muted-foreground">Try adjusting your search criteria</p>
        </div>
      )}
    </div>
  )
}

function BookingDialog({
  room,
  currentUser,
  onCreateBooking,
  disabled,
}: {
  room: Room
  currentUser: User
  onCreateBooking: (booking: Omit<Booking, "id" | "createdAt">) => void
  disabled: boolean
}) {
  const [isOpen, setIsOpen] = useState(false)
  const [bookingData, setBookingData] = useState({
    date: "",
    startTime: "",
    endTime: "",
    purpose: "",
    attendees: 1,
  })
  const [error, setError] = useState("")

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError("")

    // Validation
    if (!bookingData.date || !bookingData.startTime || !bookingData.endTime || !bookingData.purpose) {
      setError("Please fill in all required fields")
      return
    }

    if (bookingData.attendees > room.capacity) {
      setError(`Number of attendees cannot exceed room capacity (${room.capacity})`)
      return
    }

    if (bookingData.startTime >= bookingData.endTime) {
      setError("End time must be after start time")
      return
    }

    // Calculate duration and cost
    const startHour = Number.parseInt(bookingData.startTime.split(":")[0])
    const endHour = Number.parseInt(bookingData.endTime.split(":")[0])
    const duration = endHour - startHour
    const totalCost = duration * room.pricePerHour

    // Create booking
    const booking: Omit<Booking, "id" | "createdAt"> = {
      roomId: room.id,
      userId: currentUser.id,
      userName: currentUser.name,
      roomName: room.name,
      date: bookingData.date,
      startTime: bookingData.startTime,
      endTime: bookingData.endTime,
      purpose: bookingData.purpose,
      attendees: bookingData.attendees,
      totalCost,
      status: "confirmed",
    }

    onCreateBooking(booking)
    setIsOpen(false)
    setBookingData({
      date: "",
      startTime: "",
      endTime: "",
      purpose: "",
      attendees: 1,
    })
  }

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button className="w-full" disabled={disabled}>
          {disabled ? "Currently Occupied" : "Book Now"}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Book {room.name}</DialogTitle>
          <DialogDescription>Fill in the details for your meeting reservation.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="date">Date</Label>
              <Input
                id="date"
                type="date"
                value={bookingData.date}
                onChange={(e) => setBookingData({ ...bookingData, date: e.target.value })}
                min={new Date().toISOString().split("T")[0]}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="attendees">Attendees</Label>
              <Input
                id="attendees"
                type="number"
                min="1"
                max={room.capacity}
                value={bookingData.attendees}
                onChange={(e) => setBookingData({ ...bookingData, attendees: Number.parseInt(e.target.value) })}
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="startTime">Start Time</Label>
              <Input
                id="startTime"
                type="time"
                value={bookingData.startTime}
                onChange={(e) => setBookingData({ ...bookingData, startTime: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="endTime">End Time</Label>
              <Input
                id="endTime"
                type="time"
                value={bookingData.endTime}
                onChange={(e) => setBookingData({ ...bookingData, endTime: e.target.value })}
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="purpose">Meeting Purpose</Label>
            <Textarea
              id="purpose"
              placeholder="Brief description of your meeting..."
              value={bookingData.purpose}
              onChange={(e) => setBookingData({ ...bookingData, purpose: e.target.value })}
              required
            />
          </div>

          {bookingData.startTime && bookingData.endTime && bookingData.startTime < bookingData.endTime && (
            <div className="bg-muted p-3 rounded-md">
              <div className="flex justify-between items-center">
                <span className="text-sm font-medium">Estimated Cost:</span>
                <span className="text-lg font-bold">
                  $
                  {(Number.parseInt(bookingData.endTime.split(":")[0]) -
                    Number.parseInt(bookingData.startTime.split(":")[0])) *
                    room.pricePerHour}
                </span>
              </div>
              <p className="text-xs text-muted-foreground mt-1">
                {Number.parseInt(bookingData.endTime.split(":")[0]) -
                  Number.parseInt(bookingData.startTime.split(":")[0])}{" "}
                hours × ${room.pricePerHour}/hour
              </p>
            </div>
          )}

          {error && <p className="text-sm text-destructive">{error}</p>}

          <div className="flex gap-2">
            <Button type="button" variant="outline" onClick={() => setIsOpen(false)} className="flex-1">
              Cancel
            </Button>
            <Button type="submit" className="flex-1">
              Confirm Booking
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function BookingsView({ bookings }: { bookings: Booking[] }) {
  const [statusFilter, setStatusFilter] = useState<string>("all")
  const [sortBy, setSortBy] = useState<"date" | "cost" | "created">("date")
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc")
  const [searchTerm, setSearchTerm] = useState("")

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    })
  }

  const formatTime = (timeString: string) => {
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    })
  }

  const filteredAndSortedBookings = bookings
    .filter((booking) => {
      const matchesStatus = statusFilter === "all" || booking.status === statusFilter
      const matchesSearch =
        booking.roomName.toLowerCase().includes(searchTerm.toLowerCase()) ||
        booking.purpose.toLowerCase().includes(searchTerm.toLowerCase())
      return matchesStatus && matchesSearch
    })
    .sort((a, b) => {
      let comparison = 0

      switch (sortBy) {
        case "date":
          comparison = new Date(a.date).getTime() - new Date(b.date).getTime()
          break
        case "cost":
          comparison = a.totalCost - b.totalCost
          break
        case "created":
          comparison = new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime()
          break
      }

      return sortOrder === "asc" ? comparison : -comparison
    })

  const bookingStats = {
    total: bookings.length,
    confirmed: bookings.filter((b) => b.status === "confirmed").length,
    pending: bookings.filter((b) => b.status === "pending").length,
    cancelled: bookings.filter((b) => b.status === "cancelled").length,
    totalSpent: bookings.filter((b) => b.status === "confirmed").reduce((sum, b) => sum + b.totalCost, 0),
    upcomingBookings: bookings.filter((b) => {
      const bookingDate = new Date(b.date)
      const today = new Date()
      return bookingDate >= today && b.status === "confirmed"
    }).length,
  }

  if (bookings.length === 0) {
    return (
      <div className="text-center py-12">
        <CalendarDays className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
        <h2 className="text-2xl font-bold mb-4">No Bookings Yet</h2>
        <p className="text-muted-foreground mb-8">You haven't made any room reservations yet.</p>
        <Button>Browse Rooms</Button>
      </div>
    )
  }

  return (
    <div>
      <div className="mb-8">
        <h2 className="text-2xl font-bold mb-6">My Booking History</h2>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <Card>
            <CardContent className="p-4">
              <div className="text-2xl font-bold text-primary">{bookingStats.total}</div>
              <p className="text-xs text-muted-foreground">Total Bookings</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-2xl font-bold text-green-600">{bookingStats.confirmed}</div>
              <p className="text-xs text-muted-foreground">Confirmed</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-2xl font-bold text-blue-600">{bookingStats.upcomingBookings}</div>
              <p className="text-xs text-muted-foreground">Upcoming</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="text-2xl font-bold text-purple-600">${bookingStats.totalSpent}</div>
              <p className="text-xs text-muted-foreground">Total Spent</p>
            </CardContent>
          </Card>
        </div>

        <div className="flex flex-col sm:flex-row gap-4 mb-6">
          <div className="flex-1">
            <Label htmlFor="booking-search">Search bookings</Label>
            <Input
              id="booking-search"
              placeholder="Search by room name or purpose..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="sm:w-40">
            <Label htmlFor="status-filter">Status</Label>
            <select
              id="status-filter"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="all">All Status</option>
              <option value="confirmed">Confirmed</option>
              <option value="pending">Pending</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div className="sm:w-32">
            <Label htmlFor="sort-by">Sort by</Label>
            <select
              id="sort-by"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value as "date" | "cost" | "created")}
            >
              <option value="date">Date</option>
              <option value="cost">Cost</option>
              <option value="created">Created</option>
            </select>
          </div>
          <div className="sm:w-24">
            <Label htmlFor="sort-order">Order</Label>
            <select
              id="sort-order"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              value={sortOrder}
              onChange={(e) => setSortOrder(e.target.value as "asc" | "desc")}
            >
              <option value="desc">Newest</option>
              <option value="asc">Oldest</option>
            </select>
          </div>
        </div>
      </div>

      <div className="space-y-4">
        {filteredAndSortedBookings.map((booking) => {
          const bookingDate = new Date(booking.date)
          const today = new Date()
          const isUpcoming = bookingDate >= today && booking.status === "confirmed"
          const isPast = bookingDate < today

          return (
            <Card
              key={booking.id}
              className={`${isUpcoming ? "border-l-4 border-l-blue-500" : ""} ${isPast && booking.status === "confirmed" ? "border-l-4 border-l-green-500" : ""}`}
            >
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div>
                    <CardTitle className="text-lg flex items-center gap-2">
                      {booking.roomName}
                      {isUpcoming && (
                        <Badge variant="outline" className="text-blue-600 border-blue-600">
                          Upcoming
                        </Badge>
                      )}
                      {isPast && booking.status === "confirmed" && (
                        <Badge variant="outline" className="text-green-600 border-green-600">
                          Completed
                        </Badge>
                      )}
                    </CardTitle>
                    <CardDescription className="flex items-center gap-4 mt-1">
                      <span>{formatDate(booking.date)}</span>
                      <span className="text-xs">Booked on {new Date(booking.createdAt).toLocaleDateString()}</span>
                    </CardDescription>
                  </div>
                  <Badge
                    variant={
                      booking.status === "confirmed"
                        ? "default"
                        : booking.status === "pending"
                          ? "secondary"
                          : "destructive"
                    }
                  >
                    <Check className="h-3 w-3 mr-1" />
                    {booking.status}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                  <div>
                    <p className="text-muted-foreground">Time</p>
                    <p className="font-medium">
                      {formatTime(booking.startTime)} - {formatTime(booking.endTime)}
                    </p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Duration</p>
                    <p className="font-medium">
                      {Number.parseInt(booking.endTime.split(":")[0]) -
                        Number.parseInt(booking.startTime.split(":")[0])}{" "}
                      hours
                    </p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Attendees</p>
                    <p className="font-medium">{booking.attendees} people</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Cost</p>
                    <p className="font-medium text-lg">${booking.totalCost}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Purpose</p>
                    <p className="font-medium">{booking.purpose}</p>
                  </div>
                </div>

                {isUpcoming && (
                  <div className="mt-4 pt-4 border-t flex gap-2">
                    <Button variant="outline" size="sm">
                      Modify Booking
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      className="text-destructive hover:text-destructive bg-transparent"
                    >
                      Cancel Booking
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          )
        })}
      </div>

      {filteredAndSortedBookings.length === 0 && bookings.length > 0 && (
        <div className="text-center py-12">
          <CalendarDays className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-semibold mb-2">No bookings found</h3>
          <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
        </div>
      )}
    </div>
  )
}

function AdminPanel({
  rooms,
  bookings,
  onUpdateRoom,
  onDeleteRoom,
  onAddRoom,
  onUpdateBookingStatus,
}: {
  rooms: Room[]
  bookings: Booking[]
  onUpdateRoom: (room: Room) => void
  onDeleteRoom: (roomId: string) => void
  onAddRoom: (room: Omit<Room, "id">) => void
  onUpdateBookingStatus: (bookingId: string, status: Booking["status"]) => void
}) {
  const [activeTab, setActiveTab] = useState("rooms")

  const totalRevenue = bookings
    .filter((booking) => booking.status === "confirmed")
    .reduce((sum, booking) => sum + booking.totalCost, 0)

  const pendingBookings = bookings.filter((booking) => booking.status === "pending")

  return (
    <div>
      <div className="mb-8">
        <h2 className="text-2xl font-bold mb-4">Admin Panel</h2>

        {/* Admin Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Rooms</CardTitle>
              <Building2 className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{rooms.length}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Bookings</CardTitle>
              <CalendarDays className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{bookings.length}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Pending Bookings</CardTitle>
              <Clock className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{pendingBookings.length}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">${totalRevenue}</div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="rooms">Room Management</TabsTrigger>
          <TabsTrigger value="bookings">Booking Management</TabsTrigger>
        </TabsList>

        <TabsContent value="rooms" className="space-y-6">
          <div className="flex justify-between items-center">
            <h3 className="text-lg font-semibold">Manage Rooms</h3>
            <AddRoomDialog onAddRoom={onAddRoom} />
          </div>

          <div className="grid gap-4">
            {rooms.map((room) => (
              <Card key={room.id}>
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="text-lg">{room.name}</CardTitle>
                      <CardDescription>{room.location}</CardDescription>
                    </div>
                    <div className="flex gap-2">
                      <EditRoomDialog room={room} onUpdateRoom={onUpdateRoom} />
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => onDeleteRoom(room.id)}
                        className="text-destructive hover:text-destructive"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p className="text-muted-foreground">Capacity</p>
                      <p className="font-medium">{room.capacity} people</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Price</p>
                      <p className="font-medium">${room.pricePerHour}/hour</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Status</p>
                      <Badge variant={room.available ? "default" : "secondary"}>
                        {room.available ? "Available" : "Occupied"}
                      </Badge>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Amenities</p>
                      <p className="font-medium">{room.amenities.length} items</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        <TabsContent value="bookings" className="space-y-6">
          <h3 className="text-lg font-semibold">Manage Bookings</h3>

          <div className="space-y-4">
            {bookings.map((booking) => (
              <Card key={booking.id}>
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="text-lg">{booking.roomName}</CardTitle>
                      <CardDescription>
                        {booking.userName} • {new Date(booking.date).toLocaleDateString()}
                      </CardDescription>
                    </div>
                    <div className="flex gap-2">
                      <Badge
                        variant={
                          booking.status === "confirmed"
                            ? "default"
                            : booking.status === "pending"
                              ? "secondary"
                              : "destructive"
                        }
                      >
                        {booking.status}
                      </Badge>
                      {booking.status === "pending" && (
                        <>
                          <Button size="sm" onClick={() => onUpdateBookingStatus(booking.id, "confirmed")}>
                            Approve
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onUpdateBookingStatus(booking.id, "cancelled")}
                          >
                            Reject
                          </Button>
                        </>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                    <div>
                      <p className="text-muted-foreground">Time</p>
                      <p className="font-medium">
                        {new Date(`2000-01-01T${booking.startTime}`).toLocaleTimeString("en-US", {
                          hour: "numeric",
                          minute: "2-digit",
                          hour12: true,
                        })}{" "}
                        -{" "}
                        {new Date(`2000-01-01T${booking.endTime}`).toLocaleTimeString("en-US", {
                          hour: "numeric",
                          minute: "2-digit",
                          hour12: true,
                        })}
                      </p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Attendees</p>
                      <p className="font-medium">{booking.attendees} people</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Cost</p>
                      <p className="font-medium">${booking.totalCost}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Purpose</p>
                      <p className="font-medium">{booking.purpose}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Booked</p>
                      <p className="font-medium">{new Date(booking.createdAt).toLocaleDateString()}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>
      </Tabs>
    </div>
  )
}

function AddRoomDialog({ onAddRoom }: { onAddRoom: (room: Omit<Room, "id">) => void }) {
  const [isOpen, setIsOpen] = useState(false)
  const [roomData, setRoomData] = useState({
    name: "",
    capacity: 1,
    location: "",
    amenities: "",
    description: "",
    imageUrl: "",
    pricePerHour: 0,
    available: true,
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    const room: Omit<Room, "id"> = {
      ...roomData,
      amenities: roomData.amenities
        .split(",")
        .map((a) => a.trim())
        .filter((a) => a),
    }

    onAddRoom(room)
    setIsOpen(false)
    setRoomData({
      name: "",
      capacity: 1,
      location: "",
      amenities: "",
      description: "",
      imageUrl: "",
      pricePerHour: 0,
      available: true,
    })
  }

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Room
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Add New Room</DialogTitle>
          <DialogDescription>Create a new conference room for booking.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="name">Room Name</Label>
              <Input
                id="name"
                value={roomData.name}
                onChange={(e) => setRoomData({ ...roomData, name: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="capacity">Capacity</Label>
              <Input
                id="capacity"
                type="number"
                min="1"
                value={roomData.capacity}
                onChange={(e) => setRoomData({ ...roomData, capacity: Number.parseInt(e.target.value) })}
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="location">Location</Label>
            <Input
              id="location"
              value={roomData.location}
              onChange={(e) => setRoomData({ ...roomData, location: e.target.value })}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="pricePerHour">Price per Hour ($)</Label>
            <Input
              id="pricePerHour"
              type="number"
              min="0"
              value={roomData.pricePerHour}
              onChange={(e) => setRoomData({ ...roomData, pricePerHour: Number.parseInt(e.target.value) })}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="amenities">Amenities (comma-separated)</Label>
            <Input
              id="amenities"
              placeholder="Projector, Whiteboard, WiFi"
              value={roomData.amenities}
              onChange={(e) => setRoomData({ ...roomData, amenities: e.target.value })}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={roomData.description}
              onChange={(e) => setRoomData({ ...roomData, description: e.target.value })}
              required
            />
          </div>

          <div className="flex gap-2">
            <Button type="button" variant="outline" onClick={() => setIsOpen(false)} className="flex-1">
              Cancel
            </Button>
            <Button type="submit" className="flex-1">
              Add Room
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function EditRoomDialog({ room, onUpdateRoom }: { room: Room; onUpdateRoom: (room: Room) => void }) {
  const [isOpen, setIsOpen] = useState(false)
  const [roomData, setRoomData] = useState({
    name: room.name,
    capacity: room.capacity,
    location: room.location,
    amenities: room.amenities.join(", "),
    description: room.description,
    imageUrl: room.imageUrl,
    pricePerHour: room.pricePerHour,
    available: room.available,
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    const updatedRoom: Room = {
      ...room,
      ...roomData,
      amenities: roomData.amenities
        .split(",")
        .map((a) => a.trim())
        .filter((a) => a),
    }

    onUpdateRoom(updatedRoom)
    setIsOpen(false)
  }

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Edit className="h-4 w-4" />
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Edit Room</DialogTitle>
          <DialogDescription>Update room details and settings.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="edit-name">Room Name</Label>
              <Input
                id="edit-name"
                value={roomData.name}
                onChange={(e) => setRoomData({ ...roomData, name: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="edit-capacity">Capacity</Label>
              <Input
                id="edit-capacity"
                type="number"
                min="1"
                value={roomData.capacity}
                onChange={(e) => setRoomData({ ...roomData, capacity: Number.parseInt(e.target.value) })}
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="edit-location">Location</Label>
            <Input
              id="edit-location"
              value={roomData.location}
              onChange={(e) => setRoomData({ ...roomData, location: e.target.value })}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="edit-pricePerHour">Price per Hour ($)</Label>
            <Input
              id="edit-pricePerHour"
              type="number"
              min="0"
              value={roomData.pricePerHour}
              onChange={(e) => setRoomData({ ...roomData, pricePerHour: Number.parseInt(e.target.value) })}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="edit-amenities">Amenities (comma-separated)</Label>
            <Input
              id="edit-amenities"
              placeholder="Projector, Whiteboard, WiFi"
              value={roomData.amenities}
              onChange={(e) => setRoomData({ ...roomData, amenities: e.target.value })}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="edit-description">Description</Label>
            <Textarea
              id="edit-description"
              value={roomData.description}
              onChange={(e) => setRoomData({ ...roomData, description: e.target.value })}
              required
            />
          </div>

          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="edit-available"
              checked={roomData.available}
              onChange={(e) => setRoomData({ ...roomData, available: e.target.checked })}
            />
            <Label htmlFor="edit-available">Room Available</Label>
          </div>

          <div className="flex gap-2">
            <Button type="button" variant="outline" onClick={() => setIsOpen(false)} className="flex-1">
              Cancel
            </Button>
            <Button type="submit" className="flex-1">
              Update Room
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  )
}

function AuthPage({ onLogin }: { onLogin: (user: User) => void }) {
  const [loginData, setLoginData] = useState({ email: "", password: "" })
  const [registerData, setRegisterData] = useState({
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
  })
  const [error, setError] = useState("")

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault()
    setError("")

    // Get users from localStorage
    const users = JSON.parse(localStorage.getItem("users") || "[]")
    const user = users.find((u: User) => u.email === loginData.email)

    if (!user) {
      setError("User not found")
      return
    }

    // In a real app, you'd verify the password hash
    const userData = { ...user }
    localStorage.setItem("currentUser", JSON.stringify(userData))
    onLogin(userData)
  }

  const handleRegister = (e: React.FormEvent) => {
    e.preventDefault()
    setError("")

    if (registerData.password !== registerData.confirmPassword) {
      setError("Passwords do not match")
      return
    }

    if (registerData.password.length < 6) {
      setError("Password must be at least 6 characters")
      return
    }

    // Get existing users
    const users = JSON.parse(localStorage.getItem("users") || "[]")

    // Check if user already exists
    if (users.find((u: User) => u.email === registerData.email)) {
      setError("User already exists")
      return
    }

    // Create new user
    const newUser: User = {
      id: Date.now().toString(),
      email: registerData.email,
      name: registerData.name,
      role: users.length === 0 ? "admin" : "user",
    }

    // Add to users array
    users.push(newUser)
    localStorage.setItem("users", JSON.stringify(users))

    // Log in the new user
    localStorage.setItem("currentUser", JSON.stringify(newUser))
    onLogin(newUser)
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/50">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <Building2 className="h-12 w-12 text-primary mx-auto mb-4" />
          <h1 className="text-2xl font-bold">Conference Room Booking</h1>
          <p className="text-muted-foreground">Sign in to manage your reservations</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Welcome</CardTitle>
            <CardDescription>Sign in to your account or create a new one</CardDescription>
          </CardHeader>
          <CardContent>
            <Tabs defaultValue="login" className="w-full">
              <TabsList className="grid w-full grid-cols-2">
                <TabsTrigger value="login">Login</TabsTrigger>
                <TabsTrigger value="register">Register</TabsTrigger>
              </TabsList>

              <TabsContent value="login">
                <form onSubmit={handleLogin} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="Enter your email"
                      value={loginData.email}
                      onChange={(e) => setLoginData({ ...loginData, email: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                      id="password"
                      type="password"
                      placeholder="Enter your password"
                      value={loginData.password}
                      onChange={(e) => setLoginData({ ...loginData, password: e.target.value })}
                      required
                    />
                  </div>
                  {error && <p className="text-sm text-destructive">{error}</p>}
                  <Button type="submit" className="w-full">
                    Sign In
                  </Button>
                </form>
              </TabsContent>

              <TabsContent value="register">
                <form onSubmit={handleRegister} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="name">Full Name</Label>
                    <Input
                      id="name"
                      type="text"
                      placeholder="Enter your full name"
                      value={registerData.name}
                      onChange={(e) => setRegisterData({ ...registerData, name: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="reg-email">Email</Label>
                    <Input
                      id="reg-email"
                      type="email"
                      placeholder="Enter your email"
                      value={registerData.email}
                      onChange={(e) => setRegisterData({ ...registerData, email: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="reg-password">Password</Label>
                    <Input
                      id="reg-password"
                      type="password"
                      placeholder="Create a password"
                      value={registerData.password}
                      onChange={(e) => setRegisterData({ ...registerData, password: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="confirm-password">Confirm Password</Label>
                    <Input
                      id="confirm-password"
                      type="password"
                      placeholder="Confirm your password"
                      value={registerData.confirmPassword}
                      onChange={(e) => setRegisterData({ ...registerData, confirmPassword: e.target.value })}
                      required
                    />
                  </div>
                  {error && <p className="text-sm text-destructive">{error}</p>}
                  <Button type="submit" className="w-full">
                    Create Account
                  </Button>
                </form>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
