@extends('layouts.app')

@section('title', 'Employees')
@section('page-title', 'Employees')

@section('content')
<div class="container-fluid">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Active Employees</h2>
        <a class="btn btn-primary" href="{{ route('employees.create') }}">Add Employee</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Inactive</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>{{ $employee->person_id }}</td>
                            <td>{{ $employee->person?->first_name }} {{ $employee->person?->last_name }}</td>
                            <td>{{ $employee->username }}</td>
                            <td>{{ $employee->person?->email }}</td>
                            <td>{{ $employee->inactive ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('employees.edit', $employee->person_id) }}">Edit</a>
                                <form method="post" action="{{ route('employees.destroy', $employee->person_id) }}" class="d-inline">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this employee?')">Archive</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $employees->links() }}
    </div>
</div>
@endsection
