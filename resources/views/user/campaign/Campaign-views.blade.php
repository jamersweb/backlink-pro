@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Campaign Details</h1>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">{{ $campaign->web_name }}</h5>
      <p><strong>URL:</strong> <a href="{{ $campaign->web_url }}" target="_blank">{{ $campaign->web_url }}</a></p>
      <p><strong>Keywords:</strong> {{ $campaign->web_keyword }}</p>
      <p><strong>About:</strong> {{ $campaign->web_about }}</p>
      <p><strong>Target:</strong> {{ ucfirst(str_replace('_', ' ', $campaign->web_target)) }}@if($campaign->web_target=='specific_country') ({{ $campaign->country_name }})@endif</p>
      <hr>
      <p><strong>Company:</strong> {{ $campaign->company_name }}</p>
      @if($campaign->company_logo)
        <p><strong>Logo:</strong><br>
           <img src="{{ asset($campaign->company_logo) }}" alt="Logo" width="200" height="200">
        </p>
      @endif
      <p><strong>Email:</strong> {{ $campaign->company_email_address }}</p>
      <p><strong>Address:</strong> {{ $campaign->company_address }}</p>
      <p><strong>Phone:</strong> {{ $campaign->company_number }}</p>
<p><strong>Location:</strong>
  {{ optional($campaign->country)->name }}
  {{ optional($campaign->state)->name }},
  {{ optional($campaign->city)->name }},
</p>
      <hr>
      <p><strong>Gmail:</strong> {{ $campaign->gmail }}</p>
      <p><strong>Password:</strong> {{ $campaign->password }}</p>
    </div>
  </div>

  <a href="{{ route('user-campaign.index') }}" class="btn btn-secondary">Back to list</a>
  <a href="{{ route('user-campaign.edit', $campaign->id) }}" class="btn btn-warning">Edit</a>
</div>
@endsection
