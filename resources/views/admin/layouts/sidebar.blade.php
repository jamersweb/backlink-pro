  <aside class="sidebar">
  <!-- Sidebar Header -->
  <header class="sidebar-header">
    <a href="#" class="header-logo">
      <img src="{{ asset('images/logo.png') }}" alt="Logo" />
    </a>
<button class="sidebar-toggler">
  <i class="bi bi-chevron-left"></i>
</button>
  </header>

  <nav class="sidebar-nav">
    <!-- Primary Nav -->
    <ul class="nav-list primary-nav">
      <!-- Admin Dashboard -->
      <li class="nav-item">
        <a href="/admin/dashboard" class="nav-link">
          <i class="bi bi-speedometer2"></i>
          <span class="nav-label">Admin Dashboard</span>
        </a>
      </li>

      <!-- Login -->
      <li class="nav-item">
        <a href="/login" class="nav-link">
      <i class="bi bi-box-arrow-in-right"></i>
          <span class="nav-label">Login</span>
        </a>
      </li>

      <!-- Register -->
      <li class="nav-item">
        <a href="/register" class="nav-link">
          <i class="bi bi-person-plus"></i>
          <span class="nav-label">Register</span>
        </a>
      </li>

      <!-- Logout -->
      <li class="nav-item">
        <a href="#" class="nav-link">
          <form action="/logout" method="POST">
            <!-- Laravel CSRF token agar use ho to -->
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <button type="submit" class="nav-link btn btn-link p-0 text-center" style="border:none;">
              <i class="bi bi-box-arrow-right"></i>
              <span class="nav-label">Logout</span>
            </button>
          </form>
        </a>
      </li>
    </ul>
  </nav>
</aside>

     <script>
  // Toggle the visibility of a dropdown menu
const toggleDropdown = (dropdown, menu, isOpen) => {
  dropdown.classList.toggle("open", isOpen);
  menu.style.height = isOpen ? `${menu.scrollHeight}px` : 0;
};
// Close all open dropdowns
const closeAllDropdowns = () => {
  document.querySelectorAll(".dropdown-container.open").forEach((openDropdown) => {
    toggleDropdown(openDropdown, openDropdown.querySelector(".dropdown-menu"), false);
  });
};
// Attach click event to all dropdown toggles
document.querySelectorAll(".dropdown-toggle").forEach((dropdownToggle) => {
  dropdownToggle.addEventListener("click", (e) => {
    e.preventDefault();
    const dropdown = dropdownToggle.closest(".dropdown-container");
    const menu = dropdown.querySelector(".dropdown-menu");
    const isOpen = dropdown.classList.contains("open");
    closeAllDropdowns(); // Close all open dropdowns
    toggleDropdown(dropdown, menu, !isOpen); // Toggle current dropdown visibility
  });
});
document.querySelectorAll(".sidebar-toggler, .sidebar-menu-button").forEach((button) => {
  button.addEventListener("click", () => {
    document.querySelector(".sidebar").classList.toggle("collapsed");
  });
});
  </script>