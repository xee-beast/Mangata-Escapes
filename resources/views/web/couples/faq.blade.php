<section id="faq" class="section">
    <div class="faq-surface">
        <div class="container fade-in-slow">
            <div class="header">FREQUENTLY ASKED QUESTIONS</div>
            <div class="faq">
                @foreach ($group->groupFaqs->where('type', 'static') as $faq)
                    <div class="accordion">
                        <a class="accordion-link">
                            <span class="accordion-title">{{ $faq->title }}</span>
                            <span class="accordion-icon for-inactive"><i class="fas fa-plus"></i></span>
                            <span class="accordion-icon for-active"><i class="fas fa-minus"></i></span>
                        </a>
                        <div class="accordion-body">
                            <div class="content">
                                {!! $faq->description !!}
                            </div>
                        </div>
                    </div>
                @endforeach
                @if ($group->groupFaqs->where('type', 'dynamic')->where('title', 'IS TRAVEL INSURANCE AVAILABLE?')->count() > 0)
                    @php
                        $currentInsuranceRate = $group->getInsuranceRate();
                        $rates = $currentInsuranceRate->rates;
                        $chunks = array_chunk($rates, 6);
                    @endphp
                    <div class="accordion">
                        <a class="accordion-link">
                            <span class="accordion-title">IS TRAVEL INSURANCE AVAILABLE?</span>
                            <span class="accordion-icon for-inactive"><i class="fas fa-plus"></i></span>
                            <span class="accordion-icon for-active"><i class="fas fa-minus"></i></span>
                        </a>
                        <div class="accordion-body">
                            <div class="content">
                                <p>{{ $currentInsuranceRate->description }}</p>
                                <p>The rates are as follows:</p>
                                <div class="insurance-rate-grid">
                                    @foreach ($chunks as $chunk)
                                      <table>
                                          <thead>
                                              <tr>
                                                  <th>Rate</th>
                                                  <th>Booking {{ $currentInsuranceRate->type == 'total' ? 'Total' : 'Nights' }}</th>
                                              </tr>
                                          </thead>
                                          <tbody>
                                              @foreach ($chunk as $rate)
                                                  <tr>
                                                      <td class="insurance-rate-rate">${{ number_format($rate['rate'], 2) }}</td>
                                                      <td class="insurance-rate-to">Up to {{ $currentInsuranceRate->type == 'total' ? '$' . number_format($rate['to'], 2) : $rate['to'] . ' nights' }}</td>
                                                  </tr>
                                              @endforeach
                                          </tbody>
                                      </table>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($group->groupFaqs->where('type', 'dynamic')->where('title', 'WHAT IS THE CANCELLATION POLICY?')->count() > 0)
                    <div class="accordion">
                        <a class="accordion-link">
                            <span class="accordion-title">WHAT IS THE CANCELLATION POLICY?</span>
                            <span class="accordion-icon for-inactive"><i class="fas fa-plus"></i></span>
                            <span class="accordion-icon for-active"><i class="fas fa-minus"></i></span>
                        </a>
                        <div class="accordion-body">
                            <div class="content">
                                <p>
                                    Any payments made towards bookings, except for travel insurance, are fully refundable if requested in writing to <a target="_blank" href="mailto:{{ config('emails.groups') }}">{{ config('emails.groups') }}</a> by 5:00pm Eastern the day before {{ $group->cancellation_date->format('F d, Y') }}. If a cancellation request is not made in time, all moneys paid towards travel will be nonrefundable.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="faq-action-row fade-in-slow">
            <div>
                <view-edit-reservation :group='@json($bookingForm->group)' :countries='@json($countries)'/>
            </div>
            <div>
                 <invoice-form :group='@json($invoiceForm->group)' csrf-token="{{ csrf_token() }}" />
            </div>
            @if ($group->is_fit)
                <div>
                    <fit-quote-form :group='@json($group)' csrf-token="{{ csrf_token() }}" groups-email="{{ config('emails.groups') }}"/>
                </div>
            @endif
            @if(\Carbon\Carbon::now()->lessThanOrEqualTo($group->balance_due_date))
                <div>
                    <update-card-form :group='@json($cardForm->group)' :countries='@json($countries)' />
                </div>
            @endif
            <div>
                <payment-form :group='@json($paymentForm->group)' :countries='@json($countries)' />
            </div>
            @if($group->transportation)
                <div>
                    <flight-manifest-form :group='@json($flightManifestForm->group)' :airline='@json($airlines)' :is-group-event-date='@json(!$group->event_date->isBetween(now(), now()->addDays(7)))'/>
                </div>
            @endif
            @if($group->email && $group->password)
                <div>
                    <booking-details :group='@json($group)' />
                </div>
            @endif
        </div>
    </div>
</section>
