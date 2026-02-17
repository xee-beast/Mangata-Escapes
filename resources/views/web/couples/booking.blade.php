<section id="booking" class="section">
  <div class="container">
      <div class="header fade-in">booking</div>

      <div class="booking-layout">
          <div class="booking-image-column fade-in-slow">
              <img src="{{ asset('img/diana_recommended_hotel.png') }}" alt="Background image" class="booking-image">
          </div>

          <div class="booking-content-column">
              <div class="booking-section fade-in-slow">
                  <div class="booking-section-title">How to Book Your Stay</div>
                  <p class="booking-text">
                      @if ($group->is_fit)
                          To participate in all of the group-related activities, secure your booking by submitting a request for a quote using the Search Accommodation feature below. Once you receive a quote via email, you will have the ability to accept the quote and confirm your reservation with the deposit amount reflected on your quote.
                      @else
                          To participate in all of the group-related activities, secure your booking with a
                        @if ($group->deposit_type == 'fixed') ${{ $group->deposit == intval($group->deposit) ? (int)$group->deposit : $group->deposit }} @elseif ($group->deposit_type == 'nights') {{ $group->deposit == intval($group->deposit) ? (int)$group->deposit : $group->deposit  }} {{ $group->deposit > 1 ? 'nights' : 'night' }} @elseif ($group->deposit_type == 'percentage') {{ $group->deposit == intval($group->deposit) ? (int)$group->deposit : $group->deposit }}% @else ${{ $group->deposit == intval($group->deposit) ? (int)$group->deposit : $group->deposit  }}/person @endif
                          deposit using the <strong>Book Now</strong> button that appears next to the room category of your choice once you've entered your travel dates and occupants below.
                      @endif
                  </p>
              </div>

              <div class="booking-section fade-in-slow">
                  <div class="booking-section-title">Terms &amp; Conditions</div>
                  <ul class="booking-list">
                      @if ($group->is_fit)
                          <li>The deposit is refundable with an emailed cancellation prior to {{ $group->cancellation_date->format('F d, Y') }}.</li>
                          <li>Travel Insurance is available and highly recommended, but fully non-refundable.</li>
                          <li>The balance will automatically be drafted to the card on file on {{ $group->balance_due_date->format('F d, Y') }}.</li>
                          <li>Changes to parties, travel dates, and room categories will always result in an entirely new price quote and may not reflect the same nightly rate you initially received. Changes must be made prior to {{ $group->change_fee_date->format('F d, Y') }}. Any changes made thereafter may be non-refundable, rejected by the hotel, and/or subjected to a ${{ $group->change_fee_amount }} change fee.</li>
                          <li>The hotel reserves the right to cancel any bookings not paid for according to the due dates outlined above.</li>
                      @else
                          <li>The deposit is refundable with an e-mailed cancellation prior to {{ $group->cancellation_date->format('F d, Y') }}. Travel insurance, which is discounted and highly recommended, is always non-refundable.</li>
                          @foreach ($group->due_dates as $dueDate)
                              @switch($dueDate->type)
                                @case('nights')
                                    <li>A payment in the amount of {{ $dueDate->amount == intval($dueDate->amount) ? (int)$dueDate->amount : $dueDate->amount}}-nights per booking is due on {{ $dueDate->date->format('F d, Y') }}.</li>
                                    @break
                                @case('percentage')
                                    <li>A payment in the amount of {{ $dueDate->amount == intval($dueDate->amount) ? (int)$dueDate->amount : $dueDate->amount }}% per booking is due on {{ $dueDate->date->format('F d, Y') }}.</li>
                                    @break
                                @case('price')
                                    <li>A payment in the amount of ${{ $dueDate->amount == intval($dueDate->amount) ? (int)$dueDate->amount : $dueDate->amount }} per booking is due on {{ $dueDate->date->format('F d, Y') }}.</li>
                                    @break
                              @endswitch
                          @endforeach
                          <li>The balance will automatically be drafted to the card on file on {{ $group->balance_due_date->format('F d, Y') }}.</li>
                          <li>Changes to parties, travel dates, and room categories may be made prior to {{ $group->change_fee_date->format('F d, Y') }}. After this date, all reductions are non-refundable and all change requests will incur a ${{ $group->change_fee_amount }} change fee. Any changes requested after the balance due date must be paid for in full upfront even before they are confirmed. The hotel reserves the right to cancel any bookings not paid for according to the due dates outlined above.</li>
                      @endif
                  </ul>
              </div>
          </div>
      </div>
  </div>
</section>
<section id="acccommodations-search" class="section">
  <div class="container">
        <accomodations-search :group='@json($bookingForm->group)' :hotels='@json($bookingForm->hotels)' :countries='@json($countries)' />  </div>
</section>
