<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Admin Panel')</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>

  {{-- Common navbar --}}
 

  <div class="d-flex">
    {{-- Sidebar --}}
 @include('admin.layouts.sidebar')


    {{-- Page content --}}
    <main class="flex-grow-1 p-4">
      @yield('content')
    </main>
  </div>

  {{-- Common footer --}}

</body>
</html>
