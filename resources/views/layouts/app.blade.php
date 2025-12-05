<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Admin Panel')</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link
    href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css"
    rel="stylesheet"
  />
 @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>

  {{-- Common navbar --}}
 

  <div class="d-flex">
    {{-- Sidebar --}}
 @include('layouts.sidebar')


    {{-- Page content --}}
    <main class="flex-grow-1 p-4">
      @yield('content')
    </main>
  </div>

  {{-- Common footer --}}
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    @stack('scripts')
</body>

</html>
