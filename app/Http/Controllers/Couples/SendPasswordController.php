<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Notifications\GroupPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SendPasswordController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'group_id' => 'required'
        ]);

        $group = Group::where('id', $request->group_id)->where('email', $request->email)->first();

        if (!$group) {
            throw ValidationException::withMessages([
                'email' => ['The email that you entered is incorrect.']
            ]);
        } else {
            $group->notify(new GroupPasswordNotification($group));
        }

        return response()->json();
    }
}
