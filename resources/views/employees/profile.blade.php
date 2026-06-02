@php
    $person = $employee->person;
    $fullName = $person->full_name ?? trim($person->first_name . ' ' . $person->last_name);
    $initials = strtoupper(
        substr($person->first_name, 0, 1) . substr($person->last_name, 0, 1)
    );
@endphp

@extends('layouts.app')

@section('page-title', 'My Profile')

@section('title', 'My Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 radius-lg">
            <div class="card-body text-center p-5">
                <div class="topbar-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 28px;">
                    {{ $initials }}
                </div>
                <h4 class="mb-1">{{ $fullName }}</h4>
                <p class="text-muted mb-3">{{ $person->title ?? 'Employee' }}</p>

                <hr>

                <div class="text-start">
                    <div class="mb-2"><strong>Username:</strong> {{ $employee->username }}</div>
                    @if ($person->email)
                        <div class="mb-2"><strong>Email:</strong> {{ $person->email }}</div>
                    @endif
                    @if ($person->phone_number)
                        <div class="mb-2"><strong>Phone:</strong> {{ $person->phone_number }}</div>
                    @endif
                    @if ($person->title)
                        <div class="mb-2"><strong>Title:</strong> {{ $person->title }}</div>
                    @endif
                    @if ($employee->employee_number)
                        <div class="mb-2"><strong>Employee #:</strong> {{ $employee->employee_number }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
