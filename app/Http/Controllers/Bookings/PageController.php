<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Booking;
use App\Models\Country;
use PDF;

class PageController extends Controller
{
    public function index()
    {
        $countries = Country::whereHas('states')->with('states')->get();
        $airlines = Airline::orderBy('name', 'asc')->get();

        return view('web.bookings.index', compact('countries', 'airlines'));
    }

    public function termsConditions(Booking $individual_booking)
    {
        if ($individual_booking->group) abort(404);

        $pdf = PDF::loadView('pdf.termsConditions', ['booking' => $individual_booking])->stream('Terms-Conditions.pdf');
        $pdf->headers->set('X-Vapor-Base64-Encode', 'True');

        return $pdf;
    }
}
