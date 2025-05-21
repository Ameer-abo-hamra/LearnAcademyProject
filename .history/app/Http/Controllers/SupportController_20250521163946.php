<?php

namespace App\Http\Controllers;

use App\Events\AdminEvent;
use App\Events\StudentEvent;
use App\Events\TeacherEvent;
use App\Http\Controllers\Controller;
use App\Models\Support;
use App\Traits\ResponseTrait;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    use ResponseTrait;
    public function sendSupportMessage(Request $request)
    {
        $request->validate([
            'receiver_type' => 'required|string', // eg: App\Models\Teacher
            'receiver_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $sender = CurrentUser(); // أو u('admin') حسب السياق

        $support = Support::create([
            'message' => $request->message,
            'sender_type' => get_class($sender),
            'sender_id' => $sender->id,
            'receiver_type' => $request->receiver_type,
            'receiver_id' => $request->receiver_id,
        ]);

        $eventMessage = [
            'message' => $request->message,
            'sender' => [
                'type' => class_basename(get_class($sender)),
                'id' => $sender->id,
            ]
        ];

        // بث حسب نوع المستقبل
        switch ($request->receiver_type) {
            case \App\Models\Admin::class:
                broadcast(new AdminEvent($eventMessage, $request->receiver_id));
                break;

            case \App\Models\Teacher::class:
                broadcast(new TeacherEvent($eventMessage, $request->receiver_id));
                break;

            case \App\Models\Student::class:
                broadcast(new StudentEvent($eventMessage, $request->receiver_id));
                break;

            default:
                return $this->returnError('Invalid receiver type');
        }

        return $this->returnSuccess('Message sent and broadcasted');
    }

    public function getSupportMessages()
    {
        $user = cu;

        if (!$user) {
            return $this->returnError('User not authenticated');
        }

        $messages = $user->sentSupports()
            ->with(['sender', 'receiver'])
            ->get()
            ->merge(
                $user->receivedSupports()->with(['sender', 'receiver'])->get()
            )
            ->sortBy('created_at')
            ->values(); // لضبط الترتيب كمجموعة جديدة

        return $this->returnSuccess('Support messages retrieved successfully.', $messages);
    }

}
