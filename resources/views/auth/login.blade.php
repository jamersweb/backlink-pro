 
 @vite(['resources/css/app.css', 'resources/js/app.js'])
 <link rel="stylesheet" href="{{ asset('css/style.css') }}">  
 <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<body class="text-light">

{{-- Common navbar --}}
<div class="row justify-content-center align-items-center vh-100">
  <div class="col-md-6 login_form p-4">
    <h2 class="mb-4 text-center">Login</h2>
    <form action="{{ route('login') }}" method="POST">
      @csrf

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input
          type="email"
          name="email"
          id="email"
          value="{{ old('email') }}"
          class="form-control @error('email') is-invalid @enderror"
        >
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
  <label for="password" class="form-label">Password</label>
  <div class="input-group">
    <input
      type="password"
      name="password"
      id="password"
      class="form-control @error('password') is-invalid @enderror"
    >
    <button
      type="button"
      class="btn btn-outline-secondary"
      id="togglePassword"
      tabindex="-1"
    >
      <i class="bi bi-eye-slash"></i>
    </button>
    @error('password')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>
    

      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</div>
</body>

{{-- JavaScript to toggle password visibility --}}
<script>
  document.addEventListener('DOMContentLoaded', function(){
    const toggle = document.getElementById('togglePassword');
    const pwd    = document.getElementById('password');
    toggle.addEventListener('click', () => {
      // type toggle
      const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
      pwd.setAttribute('type', type);
      // icon toggle
      toggle.querySelector('i').classList.toggle('bi-eye');
      toggle.querySelector('i').classList.toggle('bi-eye-slash');
    });
  });
</script>