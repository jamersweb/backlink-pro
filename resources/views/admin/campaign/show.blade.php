@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>{{ $campaign->title }}</h1>
    <p>{{ $campaign->description }}</p>
    <!-- Display other fields here -->

    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-warning">Edit</a>
    <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection