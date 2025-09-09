// Authentication JavaScript
class AuthManager {
  constructor() {
    this.init()
  }

  init() {
    this.setupEventListeners()
    this.setupTabSwitching()
  }

  setupEventListeners() {
    document.getElementById("loginForm").addEventListener("submit", this.handleLogin.bind(this))
    document.getElementById("registerForm").addEventListener("submit", this.handleRegister.bind(this))
  }

  setupTabSwitching() {
    document.getElementById("loginTab").addEventListener("click", () => {
      this.switchTab("login")
    })

    document.getElementById("registerTab").addEventListener("click", () => {
      this.switchTab("register")
    })
  }

  switchTab(tab) {
    const loginTab = document.getElementById("loginTab")
    const registerTab = document.getElementById("registerTab")
    const loginForm = document.getElementById("loginForm")
    const registerForm = document.getElementById("registerForm")

    if (tab === "login") {
      loginTab.classList.add("active")
      registerTab.classList.remove("active")
      loginForm.classList.remove("hidden")
      registerForm.classList.add("hidden")
    } else {
      registerTab.classList.add("active")
      loginTab.classList.remove("active")
      registerForm.classList.remove("hidden")
      loginForm.classList.add("hidden")
    }

    this.clearMessage()
  }

  async handleLogin(e) {
    e.preventDefault()

    const formData = new FormData(e.target)

    try {
      const response = await fetch("api/login.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        window.location.href = "index.html"
      } else {
        this.showMessage(data.message || "Login failed", "error")
      }
    } catch (error) {
      console.error("Login error:", error)
      this.showMessage("An error occurred during login", "error")
    }
  }

  async handleRegister(e) {
    e.preventDefault()

    const formData = new FormData(e.target)

    // Validate password confirmation
    const password = formData.get("password")
    const confirmPassword = formData.get("confirm_password")

    if (password !== confirmPassword) {
      this.showMessage("Passwords do not match", "error")
      return
    }

    try {
      const response = await fetch("api/register.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        this.showMessage("Registration successful! Please login.", "success")
        setTimeout(() => {
          this.switchTab("login")
        }, 2000)
      } else {
        this.showMessage(data.message || "Registration failed", "error")
      }
    } catch (error) {
      console.error("Registration error:", error)
      this.showMessage("An error occurred during registration", "error")
    }
  }

  showMessage(message, type) {
    const messageDiv = document.getElementById("message")
    messageDiv.className = `mt-4 p-3 rounded-md ${type === "success" ? "message-success" : "message-error"}`
    messageDiv.textContent = message
    messageDiv.classList.remove("hidden")
  }

  clearMessage() {
    const messageDiv = document.getElementById("message")
    messageDiv.classList.add("hidden")
  }
}

// Initialize auth manager when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new AuthManager()
})
