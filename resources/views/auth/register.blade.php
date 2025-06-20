 @vite(['resources/css/app.css', 'resources/js/app.js'])
<div class="row justify-content-center">
  <div class="col-md-6">
    <h2 class="mb-4 text-center">Register</h2>
    <form action="{{ route('register') }}" method="POST">
      @csrf

      <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input
          type="text"
          name="name"
          id="name"
          value="{{ old('name') }}"
          class="form-control @error('name') is-invalid @enderror"
        >
        @error('name')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
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
        <label class="form-label" for="password">Password</label>
        <input
          type="password"
          name="password"
          id="password"
          class="form-control @error('password') is-invalid @enderror"
        >
        @error('password')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input
          type="password"
          name="password_confirmation"
          id="password_confirmation"
          class="form-control"
        >
      </div>

      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
  </div>
</div>

