<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $employeeId = (int) auth('employee')->id();
        $q = trim((string) $request->query('q', ''));

        $inbox = \App\Models\PhpposMessageReceiver::with(['message.sender.person'])
            ->where('receiver_id', $employeeId)
            ->whereHas('message', function ($query) use ($q) {
                if ($q !== '') {
                    $query->where('subject', 'like', '%'.$q.'%')
                        ->orWhere('message', 'like', '%'.$q.'%');
                }
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $unreadCount = \App\Models\PhpposMessageReceiver::where('receiver_id', $employeeId)
            ->where('is_read', false)
            ->count();

        $employees = \App\Models\PhpposEmployee::with('person')
            ->where('deleted', 0)
            ->where('inactive', 0)
            ->where('person_id', '!=', $employeeId)
            ->get();

        $locations = \App\Models\PhpposLocation::where('deleted', 0)->get();

        return view('messages.index', compact('inbox', 'employees', 'locations', 'unreadCount', 'q'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'all_employees' => ['nullable', 'string'],
            'all_locations' => ['nullable', 'string'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:phppos_locations,location_id'],
            'receiver_ids' => ['nullable', 'array'],
            'receiver_ids.*' => ['integer', 'exists:phppos_employees,person_id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $senderId = (int) auth('employee')->id();
        $finalReceiverIds = [];

        if (($data['all_employees'] ?? '') === 'all') {
            if (($data['all_locations'] ?? '') === 'all') {
                $finalReceiverIds = \App\Models\PhpposEmployee::where('deleted', 0)
                    ->where('inactive', 0)
                    ->where('person_id', '!=', $senderId)
                    ->pluck('person_id')
                    ->toArray();
            } elseif (!empty($data['location_ids'])) {
                $finalReceiverIds = \App\Models\PhpposEmployee::whereHas('locations', function($q) use ($data) {
                        $q->whereIn('phppos_locations.location_id', $data['location_ids']);
                    })
                    ->where('deleted', 0)
                    ->where('inactive', 0)
                    ->where('person_id', '!=', $senderId)
                    ->pluck('person_id')
                    ->toArray();
            } else {
                // Fallback to all if no locations specified but all_employees is checked? 
                // Or maybe just do nothing. Let's follow CI3: if all_employees is checked, 
                // it checks all_locations.
                $finalReceiverIds = \App\Models\PhpposEmployee::where('deleted', 0)
                    ->where('inactive', 0)
                    ->where('person_id', '!=', $senderId)
                    ->pluck('person_id')
                    ->toArray();
            }
        } elseif (!empty($data['receiver_ids'])) {
            $finalReceiverIds = array_filter($data['receiver_ids'], fn($id) => (int)$id !== $senderId);
        }

        if (empty($finalReceiverIds)) {
            return back()->withErrors(['receiver_ids' => 'Please select at least one recipient.'])->withInput();
        }

        $message = \App\Models\PhpposMessage::create([
            'sender_id' => $senderId,
            'subject' => $data['subject'] ?? 'No Subject',
            'message' => $data['message'],
            'sent_at' => now(),
        ]);

        foreach (array_unique($finalReceiverIds) as $receiverId) {
            \App\Models\PhpposMessageReceiver::create([
                'message_id' => $message->id,
                'receiver_id' => $receiverId,
                'is_read' => false,
            ]);
        }

        return redirect()->route('messages.index')->with('status', 'Message sent.');
    }

    public function markRead(int $receiverRowId): RedirectResponse
    {
        $employeeId = (int) auth('employee')->id();

        \App\Models\PhpposMessageReceiver::where('id', $receiverRowId)
            ->where('receiver_id', $employeeId)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->route('messages.index');
    }

    public function destroy(int $messageId): RedirectResponse
    {
        $employeeId = (int) auth('employee')->id();
        
        // Soft delete the message. In CI3, it sets deleted=1 in messages table.
        // We use SoftDeletes trait in Laravel.
        $message = \App\Models\PhpposMessage::where('id', $messageId)
            ->where(function($q) use ($employeeId) {
                $q->where('sender_id', $employeeId)
                  ->orWhereHas('receivers', function($rq) use ($employeeId) {
                      $rq->where('receiver_id', $employeeId);
                  });
            })
            ->first();

        if ($message) {
            $message->delete();
        }

        return redirect()->route('messages.index')->with('status', 'Message deleted.');
    }
}
