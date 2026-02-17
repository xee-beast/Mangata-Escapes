@extends('web.mail.layouts.email')

@section('title', 'NEW LEAD')

@section('content')
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Wedding Couple:</span> {{ $contact['bride'] }} - {{ $contact['groom'] }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Group departing from:</span> {{ $contact['departure'] }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Requires spanish speaking destination wedding specialist:</span> {{ $contact['spanish'] ? 'Yes' : 'No' }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Phone:</span> {{ $contact['phone'] }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Email:</span> {{ $contact['email'] }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Destination(s):</span> {{ $contact['destinations'] }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Wedding Date:</span> {{ $contact['weddingDate'] }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Acquisition Source:</span> {{ $contact['source'] }}</p>

<p style="font-weight:600; color:#3C3B3B; margin-bottom:8px">Message:</p>
<p style="margin-bottom:8px">{{ $contact['message'] }}</p>
@endsection
