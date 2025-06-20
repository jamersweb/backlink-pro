<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','User Panel')</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>

  {{-- Common navbar --}}


  <div class="d-flex">
    {{-- Sidebar --}}
   @include('user.layouts.sidebar')

    {{-- Page content --}}
    <main class="flex-grow-1 p-4">
      @yield('content')
    </main>
  </div>

  {{-- Common footer --}}
 

</body>
</html>
