@extends('user.layouts.app')

@section('title','My Dashboard')

@section('content')
  <h1 class="test">Welcome, {{ auth()->user()->name }}</h1>
  <p>Yeh aapki user dashboard hai.</p>
@endsection
