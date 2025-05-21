<?php

namespace App\Http\Controllers;

use App\Events\AdminEvent;
use App\Events\StudentEvent;
use App\Events\TeacherEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    use ResoinseT
    public function sendSupportMessage(Request $request)
    {
        $request->validate([
            'receiver_type' => 'required|string', // eg: App\Models\Teacher
            'receiver_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $sender = auth()->user(); // أو u('admin') حسب السياق

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
}
