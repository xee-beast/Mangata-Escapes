<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\StreamInvoice;
use App\Models\BookingClient;
use App\Notifications\InvoiceMail;
use PDF;

class InvoiceController extends Controller
{
    public function streamInvoice(StreamInvoice $request)
    {
        $bookingClient = BookingClient::where('reservation_code', $request->input('booking.code'))
            ->whereHas('client', function ($query) use ($request) {
                $query->where('email', $request->input('booking.email'));
            })
            ->with(['booking', 'client'])
            ->first();

        if (is_null($bookingClient->booking->confirmed_at)) {
            return response()->json([
                'message' => 'The booking has not been confirmed yet.'
            ], 403);
        }

        if ($request->boolean('sendEmail') == true) {
            $bookingClient->client->notify(new InvoiceMail($bookingClient->booking));
            return response()->json(['message' => 'The invoice has been sent to your email address.'], 423);
        }

        if ($request->input('validate', false)) {
            return response()->json();
        }

        $invoice = PDF::loadView('pdf.invoice', ['invoice' => $bookingClient->booking->invoice])->stream('R' . $bookingClient->booking->order . ' BB Invoice - ' . $bookingClient->booking->full_name . '.pdf');

        $invoice->headers->set('X-Vapor-Base64-Encode', 'True');

        return $invoice;
    }
}
