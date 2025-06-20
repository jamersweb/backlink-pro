    {{-- Sidebar --}}
    <nav class="bg-dark text-white p-3 vh-100" style="width: 220px;">
      <h4 class="text-white mb-4">My App</h4>
      <ul class="nav flex-column">
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="{{ url('/') }}">🏠 Home</a>
        </li>
        @auth
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="{{ url('/dashboard') }}">📊 Dashboard</a>
        </li>
        <li class="nav-item mb-2">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-link nav-link text-white p-0" type="submit">🚪 Logout</button>
          </form>
        </li>
        @else
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="{{ route('login') }}">🔐 Login</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="{{ route('register') }}">📝 Register</a>
        </li>
        @endauth
      </ul>
    </nav>