<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class CouplesPasswordController extends Controller
{
    public function show(Group $group)
    {
        return view('web.couples.password', compact('group'));
    }

    public function verify(Request $request, Group $group)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if ($group->couples_site_password !== $request->password) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session()->put("group_password_verified_{$group->id}", now());

        return redirect()->route('couples', ['group' => $group->slug]);
    }
}
