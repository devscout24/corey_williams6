@extends('layouts.app')

@section('title', 'ZKTeco Device Monitor')
@section('page-title', 'ZKTeco Device Monitor')

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Device Configuration</h5>
                    <div>
                        <button type="button" class="btn btn-success me-2" id="btnConnect">
                            <i class="bi bi-plug-fill me-1"></i>Test Connection
                        </button>
                        <button type="button" class="btn btn-primary" id="btnRefreshAttendance">
                            <i class="bi bi-arrow-repeat me-1"></i>Fetch Attendance
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="configForm" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label for="deviceIp" class="form-label">Device IP</label>
                            <input type="text" name="ip" id="deviceIp" class="form-control"
                                value="{{ $ip }}" placeholder="e.g. 192.168.1.201">
                        </div>
                        <div class="col-md-2">
                            <label for="devicePort" class="form-label">Port</label>
                            <input type="number" name="port" id="devicePort" class="form-control"
                                value="{{ $port }}" min="1" max="65535">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-save me-1"></i>Save
                            </button>
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-end">
                            <div id="connectionStatus" class="d-flex align-items-center gap-2">
                                <span class="status-indicator bg-secondary" id="statusDot"></span>
                                <span class="text-muted" id="statusLabel">Not tested</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Device Info</h5>
                </div>
                <div class="card-body" id="deviceInfoBody">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-hdd-stack" style="font-size: 3rem;"></i>
                        <p class="mt-2">Connect to device to see info</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Attendance Summary</h5>
                    <span class="badge bg-secondary" id="attendanceCount">0 records</span>
                </div>
                <div class="card-body" id="attendanceSummaryBody">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clock" style="font-size: 3rem;"></i>
                        <p class="mt-2">Fetch attendance data to see summary</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Attendance Records</h5>
                    <span class="text-muted small" id="lastFetchTime"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User ID</th>
                                <th>Timestamp</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No data yet. Click "Fetch Attendance" to load records.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-indicator {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    flex-shrink: 0;
}
.status-indicator.connected {
    background: #198754;
    box-shadow: 0 0 8px rgba(25, 135, 84, 0.6);
}
.status-indicator.disconnected {
    background: #dc3545;
    box-shadow: 0 0 8px rgba(220, 53, 69, 0.6);
}
.status-indicator.connecting {
    background: #ffc107;
    animation: pulse 1s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.device-info-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid var(--gray-200);
}
.device-info-item:last-child {
    border-bottom: none;
}
.device-info-label {
    color: var(--gray-600);
    font-weight: 500;
}
.device-info-value {
    font-family: monospace;
    word-break: break-all;
}
[data-theme='dark'] .device-info-item {
    border-bottom-color: var(--gray-700);
}
[data-theme='dark'] .device-info-label {
    color: var(--gray-400);
}
</style>
@endpush

@push('scripts')
<script>
const statusDot = document.getElementById('statusDot');
const statusLabel = document.getElementById('statusLabel');
const deviceInfoBody = document.getElementById('deviceInfoBody');
const attendanceSummaryBody = document.getElementById('attendanceSummaryBody');
const attendanceCount = document.getElementById('attendanceCount');
const attendanceTableBody = document.getElementById('attendanceTableBody');
const lastFetchTime = document.getElementById('lastFetchTime');
const configForm = document.getElementById('configForm');

function showToast(title, message, type = 'info') {
    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;
    const header = document.querySelector('#liveToast .toast-header');
    header.className = 'toast-header';
    if (type === 'success') header.classList.add('text-success');
    else if (type === 'error') header.classList.add('text-danger');
    toast.show();
}

function setStatus(state, label) {
    statusDot.className = 'status-indicator ' + state;
    statusLabel.textContent = label;
}

function renderDeviceInfo(info) {
    const rows = [
        { label: 'Device Name', value: info.name || '-' },
        { label: 'Serial Number', value: info.serial || '-' },
        { label: 'Version', value: info.version || '-' },
        { label: 'Firmware', value: info.firmware || '-' },
        { label: 'Platform', value: info.platform || '-' },
        { label: 'OS', value: info.os || '-' },
        { label: 'Device Time', value: info.device_time || '-' },
    ];

    deviceInfoBody.innerHTML = rows.map(r => `
        <div class="device-info-item px-2">
            <span class="device-info-label">${r.label}</span>
            <span class="device-info-value">${r.value}</span>
        </div>
    `).join('');
}

function renderAttendance(records) {
    if (!records || records.length === 0) {
        attendanceTableBody.innerHTML = `
            <tr><td colspan="4" class="text-center text-muted py-4">No attendance records found.</td></tr>
        `;
        attendanceSummaryBody.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-clock" style="font-size: 3rem;"></i>
                <p class="mt-2">No records available</p>
            </div>
        `;
        attendanceCount.textContent = '0 records';
        return;
    }

    const stateMap = { 0: 'Check In', 1: 'Fingerprint', 4: 'RF Card', 255: 'Unknown' };

    const rows = records.map((r, i) => {
        const stateLabel = stateMap[r.state] || r.state || '-';
        return `<tr>
            <td>${records.length - i}</td>
            <td>${r.id || '-'}</td>
            <td>${r.timestamp || '-'}</td>
            <td><span class="badge bg-info">${stateLabel}</span></td>
        </tr>`;
    }).join('');

    attendanceTableBody.innerHTML = rows;

    const today = new Date().toISOString().slice(0, 10);
    const todayRecords = records.filter(r => r.timestamp && r.timestamp.startsWith(today));
    const uniqueUsers = new Set(records.map(r => r.id)).size;

    attendanceSummaryBody.innerHTML = `
        <div class="row text-center g-3">
            <div class="col-4">
                <div class="display-6 fw-bold text-primary">${records.length}</div>
                <div class="text-muted small">Total Records</div>
            </div>
            <div class="col-4">
                <div class="display-6 fw-bold text-success">${todayRecords.length}</div>
                <div class="text-muted small">Today</div>
            </div>
            <div class="col-4">
                <div class="display-6 fw-bold text-info">${uniqueUsers}</div>
                <div class="text-muted small">Unique Users</div>
            </div>
        </div>
    `;

    attendanceCount.textContent = records.length + ' records';
    const now = new Date();
    lastFetchTime.textContent = 'Last fetch: ' + now.toLocaleString();
}

document.getElementById('btnConnect').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Connecting...';
    setStatus('connecting', 'Connecting...');

    fetch('{{ route("zkteco.connect") }}')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setStatus('connected', 'Connected');
                renderDeviceInfo(data.info);
                showToast('Connected', 'Device reached successfully', 'success');
            } else {
                setStatus('disconnected', 'Disconnected');
                deviceInfoBody.innerHTML = `
                    <div class="text-center text-danger py-5">
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                        <p class="mt-2">${data.error || 'Connection failed'}</p>
                    </div>
                `;
                showToast('Connection Failed', data.error || 'Could not reach device', 'error');
            }
        })
        .catch(err => {
            setStatus('disconnected', 'Error');
            showToast('Error', err.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug-fill me-1"></i>Test Connection';
        });
});

document.getElementById('btnRefreshAttendance').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Fetching...';

    fetch('{{ route("zkteco.attendance") }}')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderAttendance(data.records);
                showToast('Attendance', 'Loaded ' + (data.records?.length || 0) + ' records', 'success');
            } else {
                showToast('Error', data.error || 'Failed to fetch attendance', 'error');
            }
        })
        .catch(err => {
            showToast('Error', err.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Fetch Attendance';
        });
});

configForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = {
        ip: formData.get('ip'),
        port: parseInt(formData.get('port')),
    };

    fetch('{{ route("zkteco.save-config") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
        body: JSON.stringify(data),
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Saved', 'Device configuration saved', 'success');
            } else {
                showToast('Error', 'Failed to save configuration', 'error');
            }
        })
        .catch(err => showToast('Error', err.message, 'error'));
});
</script>
@endpush
