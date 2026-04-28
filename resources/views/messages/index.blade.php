@extends('layouts.app')

@section('title', 'Messages')
@section('page-title', 'Messages')

@push('styles')
    <style>
        .page-content-inner {
            max-width: 1400px;
            margin: 0 auto;
        }

        .messages-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn-inbox {
            background: #22c55e;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 20px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-inbox:hover {
            background: #16a34a;
            color: #fff;
        }

        .btn-new-message {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 20px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-new-message:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        .messages-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }

        .message-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.2s;
            cursor: pointer;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item:hover {
            background: #f8fafc;
        }

        .message-item.unread {
            background: #eff6ff;
        }

        .message-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .message-avatar-wrap {
            position: relative;
            width: 44px;
            height: 44px;
            background: var(--primary-soft);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: 700;
        }

        .message-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-status-dot {
            position: absolute;
            top: 0;
            left: -2px;
            width: 12px;
            height: 12px;
            background: #ef4444;
            border: 2px solid #fff;
            border-radius: 50%;
        }

        .message-details {
            display: flex;
            flex-direction: column;
        }

        .message-sender {
            font-weight: 700;
            color: var(--gray-800);
            font-size: 13.5px;
            margin-bottom: 2px;
        }

        .message-subject {
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 2px;
        }

        .message-date {
            font-size: 11.5px;
            color: var(--gray-500);
        }

        .action-delete {
            color: #ef4444;
            cursor: pointer;
            font-size: 18px;
            transition: color 0.2s;
            padding: 8px;
            border: none;
            background: transparent;
        }

        .action-delete:hover {
            color: #dc2626;
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .form-label-custom {
            width: 120px;
            text-align: right;
            color: #ef4444;
            font-weight: 500;
            font-size: 14px;
            margin-right: 15px;
        }

        .form-input-wrap {
            flex: 1;
            border: 1px solid var(--gray-200);
            border-radius: 4px;
            display: flex;
            height: 38px;
            overflow: hidden;
        }

        .checkbox-label {
            background: #3b82f6;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 14px;
            cursor: pointer;
            margin: 0;
            font-size: 13.5px;
        }

        .form-control-custom {
            flex: 1;
            padding: 0 12px;
            font-size: 13.5px;
        }

        textarea.form-control-custom {
            height: 120px;
            padding: 12px;
        }

        .btn-send {
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 9px 24px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-send:hover {
            background: #2563eb;
        }

        .recipient-list {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid var(--gray-200);
            border-radius: 4px;
            margin-top: 5px;
            display: none;
        }

        .recipient-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
            font-size: 13px;
        }
    </style>
@endpush

@section('content')
    <div class="page-content-inner">

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Top Actions -->
        <div class="messages-actions">
            <button class="btn-inbox" id="btn-inbox-trigger"><i class="bi bi-inbox-fill"></i> Inbox</button>
            <button class="btn-new-message" id="btn-new-trigger"><i class="bi bi-pencil-square"></i> New Message</button>
        </div>

        <!-- Messages List -->
        <div class="messages-card" id="messages-list-view">
            @forelse($inbox as $receiver)
                <div class="message-item {{ !$receiver->is_read ? 'unread' : '' }}" data-id="{{ $receiver->id }}"
                    data-subject="{{ $receiver->message->subject }}"
                    data-sender="{{ $receiver->message->sender->person->first_name }} {{ $receiver->message->sender->person->last_name }}"
                    data-date="{{ $receiver->message->sent_at->format('m/d/Y h:i a') }}"
                    data-content="{{ $receiver->message->message }}" data-read="{{ $receiver->is_read ? '1' : '0' }}">
                    <div class="message-info">
                        <div class="message-avatar-wrap">
                            @if($receiver->message->sender->person->first_name)
                                {{ substr($receiver->message->sender->person->first_name, 0, 1) }}{{ substr($receiver->message->sender->person->last_name, 0, 1) }}
                            @else
                                <i class="bi bi-person"></i>
                            @endif
                            @if(!$receiver->is_read)
                                <div class="message-status-dot"></div>
                            @endif
                        </div>
                        <div class="message-details">
                            <div class="message-sender">{{ $receiver->message->sender->person->first_name }}
                                {{ $receiver->message->sender->person->last_name }}
                            </div>
                            <div class="message-subject">{{ $receiver->message->subject }}</div>
                            <div class="message-date">{{ $receiver->message->sent_at->format('m/d/Y h:i a') }}</div>
                        </div>
                    </div>
                    <form action="{{ route('messages.destroy', $receiver->message_id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-delete"
                            onclick="event.stopPropagation(); return confirm('Delete this message?')">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            @empty
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-envelope-open" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                    No messages found in your inbox.
                </div>
            @endforelse
        </div>

        <!-- New Message Form -->
        <div class="messages-card" id="new-message-view" style="display: none; padding: 40px;">
            <form action="{{ route('messages.store') }}" method="POST">
                @csrf

                <!-- Locations -->
                <div class="form-row">
                    <div class="form-label-custom">Locations :</div>
                    <div style="flex: 1;">
                        <div class="form-input-wrap">
                            <label class="checkbox-label">
                                <input type="checkbox" name="all_locations" value="all" id="all-locations-cb" checked> All
                            </label>
                            <div class="form-control-custom d-flex align-items-center text-muted"
                                id="selected-locations-text">
                                All Locations Selected
                            </div>
                        </div>
                        <div class="recipient-list" id="locations-list">
                            @foreach($locations as $location)
                                <div class="recipient-item">
                                    <input type="checkbox" name="location_ids[]" value="{{ $location->location_id }}"
                                        class="location-cb">
                                    {{ $location->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Employees -->
                <div class="form-row">
                    <div class="form-label-custom">Employees :</div>
                    <div style="flex: 1;">
                        <div class="form-input-wrap">
                            <label class="checkbox-label">
                                <input type="checkbox" name="all_employees" value="all" id="all-employees-cb" checked> All
                            </label>
                            <div class="form-control-custom d-flex align-items-center text-muted"
                                id="selected-employees-text">
                                All Employees Selected
                            </div>
                        </div>
                        <div class="recipient-list" id="employees-list">
                            @foreach($employees as $employee)
                                <div class="recipient-item">
                                    <input type="checkbox" name="receiver_ids[]" value="{{ $employee->person_id }}"
                                        class="employee-cb">
                                    {{ $employee->person->first_name }} {{ $employee->person->last_name }}
                                    (#{{ $employee->person_id }})
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Subject -->
                <div class="form-row">
                    <div class="form-label-custom">Subject :</div>
                    <div class="form-input-wrap">
                        <input type="text" name="subject" class="form-control-custom" placeholder="Enter subject">
                    </div>
                </div>

                <!-- Message -->
                <div class="form-row" style="align-items: flex-start;">
                    <div class="form-label-custom" style="padding-top: 10px;">Message :</div>
                    <div style="flex: 1;">
                        <textarea name="message" class="form-control-custom w-100" placeholder="Type your message here..."
                            style="height: 150px; resize: vertical;"></textarea>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                    <button type="submit" class="btn-send">Send</button>
                </div>
            </form>
        </div>

        <!-- Message Detail View -->
        <div class="messages-card" id="message-detail-view" style="display: none; padding: 24px; background: #fff;">
            <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                <button class="btn-new-message" id="btn-reply"><i class="bi bi-pencil-square"></i> Reply</button>
            </div>

            <div style="border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; background: #fff;">
                <div style="display: flex; border-bottom: 1px solid var(--gray-200);">
                    <div style="width: 50px; display: flex; align-items: center; justify-content: center; border-right: 2px solid var(--primary); cursor: pointer; transition: background 0.2s;"
                        id="btn-back-to-inbox">
                        <i class="bi bi-arrow-left-short" style="color: var(--primary); font-size: 24px;"></i>
                    </div>
                    <div style="padding: 16px 20px; display: flex; align-items: center; gap: 16px;">
                        <div id="detail-avatar" class="message-avatar-wrap"
                            style="width: 44px; height: 44px; font-size: 14px;"></div>
                        <div style="display: flex; flex-direction: column;">
                            <span id="detail-sender"
                                style="font-weight: 700; color: var(--gray-800); font-size: 14px; margin-bottom: 2px;"></span>
                            <span id="detail-date" style="font-size: 12px; color: var(--gray-500);"></span>
                        </div>
                    </div>
                </div>
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--gray-100);">
                    <h5 id="detail-subject" style="margin: 0; font-weight: 600; font-size: 16px; color: var(--gray-800);">
                    </h5>
                </div>
                <div id="detail-content"
                    style="padding: 24px; font-size: 14px; color: var(--gray-800); min-height: 120px; white-space: pre-wrap;">
                </div>
            </div>

            <form id="mark-read-form" method="POST" style="display: none;">
                @csrf
            </form>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const listView = document.getElementById('messages-list-view');
            const newView = document.getElementById('new-message-view');
            const detailView = document.getElementById('message-detail-view');

            const btnInbox = document.getElementById('btn-inbox-trigger');
            const btnNew = document.getElementById('btn-new-trigger');
            const btnBack = document.getElementById('btn-back-to-inbox');
            const btnReply = document.getElementById('btn-reply');

            // View Switching
            function showView(view) {
                listView.style.display = 'none';
                newView.style.display = 'none';
                detailView.style.display = 'none';
                view.style.display = view === newView ? 'block' : (view === listView ? 'flex' : 'block');
            }

            btnInbox.addEventListener('click', () => showView(listView));
            btnNew.addEventListener('click', () => {
                showView(newView);
                document.querySelector('[name="subject"]').value = '';
                document.querySelector('[name="message"]').value = '';
            });

            // Inbox Item Click
            document.querySelectorAll('.message-item').forEach(item => {
                item.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const sender = this.dataset.sender;
                    const subject = this.dataset.subject;
                    const date = this.dataset.date;
                    const content = this.dataset.content;
                    const isRead = this.dataset.read === '1';

                    document.getElementById('detail-sender').textContent = sender;
                    document.getElementById('detail-subject').textContent = subject;
                    document.getElementById('detail-date').textContent = date;
                    document.getElementById('detail-content').textContent = content;
                    document.getElementById('detail-avatar').textContent = sender.split(' ').map(n => n[0]).join('');

                    showView(detailView);

                    // Mark as read if unread
                    if (!isRead) {
                        const form = document.getElementById('mark-read-form');
                        form.action = `/messages/${id}/read`;
                        form.submit();
                    }
                });
            });

            btnBack.addEventListener('click', () => showView(listView));

            btnReply.addEventListener('click', () => {
                const sender = document.getElementById('detail-sender').textContent;
                const subject = document.getElementById('detail-subject').textContent;
                showView(newView);
                document.querySelector('[name="subject"]').value = 'Re: ' + subject;

                // Uncheck "All" for employees and find the sender
                document.getElementById('all-employees-cb').checked = false;
                document.getElementById('employees-list').style.display = 'block';
                document.getElementById('selected-employees-text').textContent = 'Select Employees Below';

                document.querySelectorAll('.employee-cb').forEach(cb => {
                    const label = cb.parentElement.textContent.trim();
                    if (label.startsWith(sender)) {
                        cb.checked = true;
                    } else {
                        cb.checked = false;
                    }
                });
            });

            // "All" Checkbox Logic
            const allLocsCb = document.getElementById('all-locations-cb');
            const locsList = document.getElementById('locations-list');
            const locsText = document.getElementById('selected-locations-text');

            allLocsCb.addEventListener('change', function () {
                locsList.style.display = this.checked ? 'none' : 'block';
                locsText.textContent = this.checked ? 'All Locations Selected' : 'Select Locations Below';
                if (this.checked) {
                    document.querySelectorAll('.location-cb').forEach(cb => cb.checked = false);
                }
            });

            const allEmpsCb = document.getElementById('all-employees-cb');
            const empsList = document.getElementById('employees-list');
            const empsText = document.getElementById('selected-employees-text');

            allEmpsCb.addEventListener('change', function () {
                empsList.style.display = this.checked ? 'none' : 'block';
                empsText.textContent = this.checked ? 'All Employees Selected' : 'Select Employees Below';
                if (this.checked) {
                    document.querySelectorAll('.employee-cb').forEach(cb => cb.checked = false);
                }
            });

            // Initialize display
            if (allLocsCb.checked) locsList.style.display = 'none';
            if (allEmpsCb.checked) empsList.style.display = 'none';
        });
    </script>
@endpush