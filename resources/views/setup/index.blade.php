@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="mb-3">First-Time Setup</h4>
                    <p class="text-muted">Complete the fields below to initialize this device.</p>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('setup.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">App Name</label>
                            <input type="text" name="app_name" class="form-control" value="{{ old('app_name', config('app.name')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data Storage Path (optional)</label>
                            <input type="text" name="storage_path" class="form-control" value="{{ old('storage_path') }}" placeholder="C:\\LaravelPos\\storage">
                            <div class="form-text">Leave empty to use the default storage directory.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Node Name</label>
                            <input type="text" name="node_name" class="form-control" value="{{ old('node_name', gethostname()) }}" required>
                            <div class="form-text">This label is shown in the LAN Locations module for this device.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Store Location Name</label>
                            <input type="text" name="store_location_name" class="form-control" value="{{ old('store_location_name', 'Main Store') }}" required>
                            <div class="form-text">This is the location name used in POS data (sales, inventory, reports).</div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Default User</h6>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Complete Setup</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
