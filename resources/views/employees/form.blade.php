@extends('layouts.app')

@section('title', $employee ? 'Edit Employee' : 'New Employee')
@section('page-title', $employee ? 'Edit Employee' : 'New Employee')

@section('content')
<div class="container-fluid">
    <form method="post" action="{{ $employee ? route('employees.update', $employee->person_id) : route('employees.store') }}" class="card p-4 shadow-sm">
        @csrf
        @if($employee)
            @method('put')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $person?->first_name) }}" required />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $person?->last_name) }}" required />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $person?->email) }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone_number">Phone Number</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $person?->phone_number) }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $employee?->username) }}" required />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">Password {{ $employee ? '(leave blank to keep current)' : '' }}</label>
                <input type="password" class="form-control" id="password" name="password" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employee_number">Employee Number</label>
                <input type="text" class="form-control" id="employee_number" name="employee_number" value="{{ old('employee_number', $employee?->employee_number) }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="hire_date">Hire Date</label>
                <input type="date" class="form-control" id="hire_date" name="hire_date" value="{{ old('hire_date', $employee?->hire_date) }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="commission_percent">Commission Percent</label>
                <input type="number" step="0.001" class="form-control" id="commission_percent" name="commission_percent" value="{{ old('commission_percent', $employee?->commission_percent) }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="hourly_pay_rate">Hourly Pay Rate</label>
                <input type="number" step="0.001" class="form-control" id="hourly_pay_rate" name="hourly_pay_rate" value="{{ old('hourly_pay_rate', $employee?->hourly_pay_rate) }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="allowed_ip_address">Allowed IPs (comma separated)</label>
                <input type="text" class="form-control" id="allowed_ip_address" name="allowed_ip_address" value="{{ old('allowed_ip_address') }}" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="inactive">Inactive</label>
                <select class="form-select" id="inactive" name="inactive">
                    <option value="0" @selected(old('inactive', $employee?->inactive ?? 0) == 0)>No</option>
                    <option value="1" @selected(old('inactive', $employee?->inactive ?? 0) == 1)>Yes</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="force_password_change">Force Password Change</label>
                <select class="form-select" id="force_password_change" name="force_password_change">
                    <option value="0" @selected(old('force_password_change', $employee?->force_password_change ?? 0) == 0)>No</option>
                    <option value="1" @selected(old('force_password_change', $employee?->force_password_change ?? 0) == 1)>Yes</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="always_require_password">Always Require Password</label>
                <select class="form-select" id="always_require_password" name="always_require_password">
                    <option value="0" @selected(old('always_require_password', $employee?->always_require_password ?? 0) == 0)>No</option>
                    <option value="1" @selected(old('always_require_password', $employee?->always_require_password ?? 0) == 1)>Yes</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="not_required_to_clock_in">Not Required To Clock In</label>
                <select class="form-select" id="not_required_to_clock_in" name="not_required_to_clock_in">
                    <option value="0" @selected(old('not_required_to_clock_in', $employee?->not_required_to_clock_in ?? 0) == 0)>No</option>
                    <option value="1" @selected(old('not_required_to_clock_in', $employee?->not_required_to_clock_in ?? 0) == 1)>Yes</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label" for="locations">Locations</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($locations as $location)
                        <label class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="locations[]"
                                value="{{ $location->location_id }}"
                                @checked(in_array($location->location_id, old('locations', $selectedLocations)))
                            />
                            <span class="form-check-label">{{ $location->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Permission Templates -->
            <div class="col-md-6">
                <label class="form-label" for="permission_templates">Permission Template</label>
                <select class="form-select" id="permission_templates" name="permission_templates">
                    <option value="">— None —</option>
                    @foreach($permissionTemplates as $template)
                        <option value="{{ $template->id }}" @selected(old('permission_templates', $employee?->template_id) == $template->id)>{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Permissions Section -->
        <div class="mt-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-shield-lock fs-4"></i>
                <h4 class="m-0">Module Permissions</h4>
            </div>
            <div class="row g-3">
                @foreach($modules as $module)
                    @php
                        $moduleId = $module->module_id;
                        $isChecked = in_array($moduleId, old('permissions', $selectedPermissions));
                        $hasActions = isset($moduleActions[$moduleId]);
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100 module-card">
                            <div class="card-header d-flex align-items-center gap-2 py-3 px-3 {{ $isChecked ? 'bg-primary text-white' : 'bg-light' }}" id="header-{{ $moduleId }}">
                                <input class="form-check-input module-checkbox fs-5 mt-0" type="checkbox" 
                                       id="permissions-{{ $moduleId }}"
                                       name="permissions[]" 
                                       value="{{ $moduleId }}"
                                       @checked($isChecked)>
                                <label class="form-check-label fw-semibold flex-grow-1 cursor-pointer" for="permissions-{{ $moduleId }}">
                                    {{ $module->name_lang_key }}
                                </label>
                                <i class="bi bi-{{ $module->icon ?? 'gear' }} fs-5 opacity-75"></i>
                            </div>
                            @if($hasActions)
                                <div class="card-body py-2 px-3">
                                    <small class="text-muted fw-medium text-uppercase tracking-wide">Actions</small>
                                    @foreach($moduleActions[$moduleId] as $action)
                                        @php
                                            $actionKey = $moduleId . '|' . $action->action_id;
                                            $actionChecked = in_array($actionKey, old('permissions_actions', $selectedActionPermissions));
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 mt-2 py-1 px-2 rounded hover-bg">
                                            <input class="form-check-input action-checkbox mt-0" type="checkbox"
                                                   id="permissions_actions-{{ $moduleId }}-{{ $action->action_id }}"
                                                   name="permissions_actions[]"
                                                   value="{{ $moduleId }}|{{ $action->action_id }}"
                                                   data-module-checkbox-id="permissions-{{ $moduleId }}"
                                                   @checked($actionChecked)>
                                            <label class="form-check-label small flex-grow-1 cursor-pointer" for="permissions_actions-{{ $moduleId }}-{{ $action->action_id }}">
                                                {{ $action->action_name_key }}
                                            </label>
                                            @if(count($locations) > 1)
                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 py-0 px-1" 
                                                        onclick="toggleLocationOverride('action-{{ $moduleId }}-{{ $action->action_id }}')"
                                                        title="Location overrides">
                                                    <i class="bi bi-geo-alt"></i>
                                                </button>
                                            @endif
                                        </div>
                                        @if(count($locations) > 1)
                                            <div class="ms-4 mt-1 ps-3 border-start border-2 d-none" id="action-{{ $moduleId }}-{{ $action->action_id }}-locations">
                                                @foreach($locations as $loc)
                                                    <div class="form-check py-1">
                                                        <input class="form-check-input small-checkbox" type="checkbox"
                                                               id="action-location-{{ $moduleId }}-{{ $action->action_id }}-{{ $loc->location_id }}"
                                                               name="action-location[]"
                                                               value="{{ $moduleId }}|{{ $action->action_id }}|{{ $loc->location_id }}"
                                                               @checked(in_array($moduleId . '|' . $action->action_id . '|' . $loc->location_id, old('action-location', $selectedActionLocations)))>
                                                        <label class="form-check-label small" for="action-location-{{ $moduleId }}-{{ $action->action_id }}-{{ $loc->location_id }}">
                                                            {{ $loc->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @if(count($locations) > 1)
                                <div class="card-footer bg-white px-3 py-2 border-top">
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" 
                                            onclick="toggleLocationOverride('module-{{ $moduleId }}')">
                                        <i class="bi bi-geo-alt-fill"></i> Location Overrides
                                    </button>
                                    <div class="mt-2 ps-3 border-start border-2 d-none" id="module-{{ $moduleId }}-locations">
                                        @foreach($locations as $loc)
                                            <div class="form-check py-1">
                                                <input class="form-check-input small-checkbox" type="checkbox"
                                                       id="module-location-{{ $moduleId }}-{{ $loc->location_id }}"
                                                       name="module_location[]"
                                                       value="{{ $moduleId }}|{{ $loc->location_id }}"
                                                       @checked(in_array($moduleId . '|' . $loc->location_id, old('module_location', $selectedModuleLocations)))>
                                                <label class="form-check-label small" for="module-location-{{ $moduleId }}-{{ $loc->location_id }}">
                                                    {{ $loc->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('employees.index') }}">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Employee</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .module-card { transition: box-shadow .2s, border-color .2s; }
    .module-card:hover { box-shadow: 0 .25rem .75rem rgba(0,0,0,.08); border-color: #86b7fe; }
    .module-card .card-header { transition: background .2s, color .2s; }
    .hover-bg { transition: background .15s; }
    .hover-bg:hover { background: rgba(13,110,253,.05); }
    .cursor-pointer { cursor: pointer; }
    .tracking-wide { letter-spacing: .04em; }
    .small-checkbox { transform: scale(.85); }
    .module-checkbox:checked ~ .bi { color: inherit; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Module checkbox: check/uncheck all action checkboxes + toggle header styling
    document.querySelectorAll('.module-checkbox').forEach(function(moduleCheckbox) {
        moduleCheckbox.addEventListener('change', function() {
            const moduleId = this.id.replace('permissions-', '');
            const header = document.getElementById('header-' + moduleId);
            const actionCheckboxes = document.querySelectorAll('.action-checkbox[data-module-checkbox-id="permissions-' + moduleId + '"]');
            actionCheckboxes.forEach(function(actionCb) {
                if (!actionCb.disabled) {
                    actionCb.checked = moduleCheckbox.checked;
                }
            });
            if (header) {
                header.classList.toggle('bg-primary', moduleCheckbox.checked);
                header.classList.toggle('text-white', moduleCheckbox.checked);
                header.classList.toggle('bg-light', !moduleCheckbox.checked);
            }
        });
    });

    // Action checkbox: if checked, ensure parent module is checked
    document.querySelectorAll('.action-checkbox').forEach(function(actionCheckbox) {
        actionCheckbox.addEventListener('change', function() {
            if (this.checked) {
                const moduleId = this.getAttribute('data-module-checkbox-id');
                const moduleCheckbox = document.getElementById(moduleId);
                if (moduleCheckbox) {
                    moduleCheckbox.checked = true;
                }
            }
        });
    });

    // Toggle location override visibility
    window.toggleLocationOverride = function(id) {
        const el = document.getElementById(id + '-locations');
        if (el) {
            el.classList.toggle('d-none');
        }
    };

    // Select all locations for a module
    window.selectAllLocation = function(selectAllId) {
        const selectAll = document.getElementById(selectAllId);
        if (!selectAll) return;
        const isChecked = selectAll.checked;
        const checkboxes = document.querySelectorAll('input[data-emp-name="' + selectAllId + '"]');
        checkboxes.forEach(function(cb) {
            cb.checked = isChecked;
        });
    };
});
</script>
@endpush
