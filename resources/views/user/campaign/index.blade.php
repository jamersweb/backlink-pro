@extends('layouts.app')

@section('content')
<div class="container">
  <h1>My Campaigns</h1>
  <a href="{{ route('user-campaign.create') }}" class="btn btn-primary mb-3">
    + New Campaign
  </a>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th><th>Web Name</th><th>Company</th><th>Gmail</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      @forelse($campaigns as $c)
      <tr>
        <td>{{ $c->id }}</td>
        <td>{{ $c->web_name }}</td>
        <td>{{ $c->company_name }}</td>
        <td>{{ $c->gmail }}</td>
   <td>
  <a href="{{ route('user-campaign.show', $c->id) }}" class="btn btn-sm btn-info">Show</a>
  <a href="{{ route('user-campaign.edit', $c->id) }}" class="btn btn-sm btn-warning">Edit</a>
</td>

      </tr>
      @empty
      <tr>
        <td colspan="5" class="text-center">
          No campaigns found. <a href="{{ route('user-campaign.create') }}">Create one</a>.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
