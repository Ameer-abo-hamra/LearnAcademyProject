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
        'message' => 'required|string',
    ]);

    $sender = $this->getAuthenticatedUser();

    if (!$sender) {
        return $this->returnError('User not authenticated');
    }

    $admins = Admin::all();

    foreach ($admins as $admin) {
        $support = Support::create([
            'message' => $request->message,
            'sender_type' => get_class($sender),
            'sender_id' => $sender->id,
            'receiver_type' => Admin::class,
            'receiver_id' => $admin->id,
        ]);

        broadcast(new  AdminEvent([
            'message' => $support->message,
            'sender' => [
                'type' => class_basename(get_class($sender)),
                'id' => $sender->id,
            ]
        ], $admin->id));
    }

    return $this->returnSuccess('Message sent to all admins');
}

    public function getSupportMessages()
    {
        $user = currentUser();

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
