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
                <div class="mb-3 text-center">
                    <button id="edit-profile" class="btn btn-sm btn-outline-primary">Edit</button>
                    <button id="save-profile" class="btn btn-sm btn-primary d-none">Save</button>
                    <button id="cancel-profile" class="btn btn-sm btn-secondary d-none">Cancel</button>
                </div>

                <hr>

                <div class="text-start">
                    <div class="mb-2"><strong>Firstname:</strong> <span class="editable" data-field="first_name">{{ $person->first_name }}</span></div>
                    <div class="mb-2"><strong>Lastname:</strong> <span class="editable" data-field="last_name">{{ $person->last_name }}</span></div>

                    @if ($person->email)
                        <div class="mb-2"><strong>Email:</strong> <span class="editable" data-field="email">{{ $person->email }}</span></div>
                    @endif
                    @if ($person->phone_number)
                        <div class="mb-2"><strong>Phone:</strong> <span class="editable" data-field="phone_number">{{ $person->phone_number }}</span></div>
                    @endif
                    @if ($person->title)
                        <div class="mb-2"><strong>Title:</strong> <span class="editable" data-field="title">{{ $person->title }}</span></div>
                    @endif
                    @if ($employee->employee_number)
                        <div class="mb-2"><strong>Employee #:</strong> <span class="editable" data-field="employee_number">{{ $employee->employee_number }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 radius-lg mt-4">
            <div class="card-body p-5">
                <h5 class="mb-4"><i class="bi bi-key me-2"></i>Change Password</h5>
                <form id="change-password-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required minlength="6">
                    </div>
                    <div id="password-error" class="alert alert-danger d-none"></div>
                    <div id="password-success" class="alert alert-success d-none"></div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
    (function($){
        $(function(){
            var $card = $('.card-body');
            var updateUrl = '{{ url("/profile") }}';
            var csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

            function toInputs(){
                $card.find('.editable').each(function(){
                    var $span = $(this);
                    var val = $span.text().trim();
                    var name = $span.data('field');
                    var $input = $('<input>').attr('type','text').addClass('form-control form-control-sm editable-input').attr('data-field', name).val(val);
                    $span.replaceWith($input);
                });
            }

            function toSpans(){
                $card.find('.editable-input').each(function(){
                    var $input = $(this);
                    var val = $input.val();
                    var name = $input.data('field');
                    var $span = $('<span>').addClass('editable').attr('data-field', name).text(val);
                    $input.replaceWith($span);
                });
            }

            $('#edit-profile').on('click', function(){
                toInputs();
                $(this).addClass('d-none');
                $('#save-profile, #cancel-profile').removeClass('d-none');
            });

            $('#cancel-profile').on('click', function(){
                location.reload();
            });

            $('#save-profile').on('click', function(){
                var data = {};
                $card.find('.editable-input').each(function(){
                    var $i = $(this);
                    data[$i.data('field')] = $i.val();
                });
                if($.isEmptyObject(data)) return;

                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function(resp){
                        var $firstNameInput = $card.find('input[data-field="first_name"]');
                        var $lastNameInput = $card.find('input[data-field="last_name"]');
                        var firstName = $firstNameInput.length ? $firstNameInput.val() : 'Unknown';
                        var lastName = $lastNameInput.length ? $lastNameInput.val() : 'User';
                        var newInitials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
                        var newFullName = firstName + ' ' + lastName;

                        $card.find('.topbar-avatar').text(newInitials);
                        $card.find('h4.mb-1:first').text(newFullName);

                        toSpans();
                        $('#save-profile, #cancel-profile').addClass('d-none');
                        $('#edit-profile').removeClass('d-none');
                    },
                    error: function(xhr){
                        var msg = 'Save failed';
                        if(xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            $('#change-password-form').on('submit', function(e){
                e.preventDefault();
                var $form = $(this);
                var $error = $('#password-error');
                var $success = $('#password-success');
                $error.addClass('d-none');
                $success.addClass('d-none');

                var data = {
                    _token: csrfToken,
                    current_password: $('#current_password').val(),
                    new_password: $('#new_password').val(),
                    new_password_confirmation: $('#new_password_confirmation').val(),
                    change_password: true
                };

                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function(resp){
                        $success.text(resp.message || 'Password updated successfully.').removeClass('d-none');
                        $form[0].reset();
                    },
                    error: function(xhr){
                        var msg = 'Failed to update password.';
                        if(xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        $error.text(msg).removeClass('d-none');
                    }
                });
            });
        });
    })(jQuery);
</script>
@endpush
