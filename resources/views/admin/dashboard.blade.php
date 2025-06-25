@extends('layouts.app')

@section('title','Admin Dashboard')

@section('content')
  <h1 class="test">Admin: {{ auth()->user()->name }}</h1>
  <p>Yeh aapka admin dashboard hai.</p>

@endsection
