@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>{{ isset($campaign) ? 'Edit' : 'Create' }} Campaign</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($campaign) ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}" method="POST">
        @csrf
        @if(isset($campaign)) @method('PUT') @endif

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="{{ old('title', $campaign->title ?? '') }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $campaign->description ?? '') }}</textarea>
        </div>

        <!-- Add other fields here -->

        <button type="submit" class="btn btn-success">{{ isset($campaign) ? 'Update' : 'Publish' }}</button>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection