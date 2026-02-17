<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\NewLead;
use App\Mail\NewLead as LeadMail;
use App\Models\Group;
use App\Models\TravelAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class WebController extends Controller
{
    /**
     * Show the home page.
     *
     * @return View
     */
    public function index()
    {
        return view('web.index');
    }

    /**
     * Show the about page.
     *
     * @return View
     */
    public function about()
    {
        return view('web.about');
    }

    /**
     * Show the team page.
     *
     * @return View
     */
    public function team()
    {
        return view('web.team');
    }

    /**
     * Show the brides page.
     *
     * @return View
     */
    public function brides()
    {
        $groups = Group::orderByRaw('RAND()')->where('show_as_past_bride', true)->take(10)->get();

        return view('web.brides', ['groups' => $groups]);
    }

    /**
     * Show the services page.
     *
     * @return View
     */
    public function services()
    {
        return view('web.services');
    }

    /**
     * Show the contact page.
     *
     * @param Request $request
     * @return View
     */
    public function contact()
    {
        return view('web.contact', ['agents' => TravelAgent::all(['id', 'first_name', 'last_name'])]);
    }

    /**
     * Show the blog page.
     *
     * @param Request $request
     * @return View
     */
    public function blog()
    {
        return view('web.blog');
    }

    /**
     * Send contact form email notification.
     *
     * @param App\Http\Requests\NewContact $request
     * @return View
     */
    public function submit(NewLead $request)
    {
        $agent = TravelAgent::find($request->input('agent'));

        $data = [
            'bride' => $request->input('bride.firstName', '') . ' ' . $request->input('bride.lastName', ''),
            'groom' => $request->input('groom.firstName', '') . ' ' .  $request->input('groom.lastName', ''),
            'departure' => $request->input('departure'),
            'spanish' => $request->input('spanish', false),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'destinations' => $request->input('destinations', 'Not specified'),
            'weddingDate' => $request->input('weddingDate'),
            'source' => $request->input('source', 'Not specified'),
            'message' => $request->input('message', '')
        ];

        Mail::to(is_null($agent) ? config('emails.contact_us') : $agent->email)->send(new LeadMail($data));

        return redirect('/contact')->with('formSubmitted', true);
    }
}
