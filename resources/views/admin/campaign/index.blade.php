@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>Campaigns</h1>
    <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary mb-3">+ New Campaign</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table nftmax-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($campaigns as $campaign)
            <tr>
                <td>{{ $loop->iteration + ($campaigns->currentPage()-1)*$campaigns->perPage() }}</td>
                <td>{{ $campaign->title }}</td>
                <td>{{ $campaign->created_at->format('Y-m-d') }}</td>
                <td>
                    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-sm btn-warning">Edit</a>
                    <button onclick="deleteCampaign({{ $campaign->id }})" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $campaigns->links() }}
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
function deleteCampaign(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/campaigns/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Deleted!', 'Campaign has been deleted.', 'success')
                      .then(() => location.reload());
                }
            });
        }
    });
}
</script>
@endsection