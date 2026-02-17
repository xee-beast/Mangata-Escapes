<?php

namespace App\Http\Controllers\Couples;

use App\Http\Controllers\Controller;
use App\Http\Requests\Couples\StreamInvoice;
use App\Models\Group;
use App\Models\BookingClient;
use App\Notifications\InvoiceMail;
use PDF;

class InvoiceController extends Controller
{
    /**
     * Stream the booking's invoice to the browser.
     *
     * @param \App\Models\Group $group
     * @param  \App\Http\Requests\Couples\StreamInvoice  $request
     * @return PDF
     */
    public function streamInvoice(Group $group, StreamInvoice $request)
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

        $hasChanges = $bookingClient->booking->trackedChanges()->whereNull('confirmed_at')->exists();

        if ($request->boolean('sendEmail') == true) {
            if ($bookingClient->client && !$hasChanges) {
                $bookingClient->client->notify(new InvoiceMail($bookingClient->booking));
                return response()->json(['message' => 'The invoice has been sent to your email address.'], 423);
            }

            return response()->json(['message' => 'A copy of this invoice cannot be sent until all changes have been confirmed. Please reach out to <a class="has-text-link" href="mailto:'.config('emails.groups').'">'.config('emails.groups').'</a> with any questions.'], 423);
        }

        if ($request->input('validate', false)) {
            return response()->json();
        }

        $invoice = PDF::loadView('pdf.invoice', ['invoice' => $bookingClient->booking->invoice, 'hasChanges' => $hasChanges])->stream('R' . $bookingClient->booking->order . ' BB Invoice - ' . $group->name . '.pdf');

        $invoice->headers->set('X-Vapor-Base64-Encode', 'True');

        return $invoice;
    }
}
