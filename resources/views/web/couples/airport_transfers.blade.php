@if ($group->transportation)
    <section id="airport-transfers" class="section">
        <div class="airport-transfers-surface">
            <div class="airport-transfers-layout">
                <div class="airport-transfers-copy">
                    <div class="header fade-in">Airport Transfers</div>
                    <div class="airport-transfers-content fade-in-slow">
                        <p class="airport-transfers-intro">
                            {{ ucfirst($group->transportation_type) }} round trip group transfers will be coordinated for you! Flights must be submitted by {{ $group->transportation_submit_before->format('F d, Y') }}.
                        </p>

                        @php $singular_rate = true @endphp
                        <ul class="airport-transfers-list">
                            @foreach ($group->airports as $airport)
                                @if ($airport->transportation_rate === $airport->single_transportation_rate)
                                    <li>If you are flying into {{ $airport->airport->airport_code }}, transfers are ${{ $airport->transportation_rate }} per person.</li>
                                @else
                                    @php $singular_rate = false @endphp
                                    <li>If you are flying into {{ $airport->airport->airport_code }}, transfers are ${{ $airport->transportation_rate }} per person for those flying together and ${{ $airport->single_transportation_rate }} for those flying separately.</li>
                                @endif
                            @endforeach
                        </ul>

                        @if (!$singular_rate)
                            <p class="airport-transfers-note">
                                Please note, single occupants and occupants in a shared room with separate invoices will automatically be charged the solo rate for transfers, but will receive a refund if they are on the same flights as anyone else in the group once the flight itineraries are all collected.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="airport-transfers-media fade-in-slow">
                    <img src="{{ asset('img/sydney_wedding_kukua_2_-brides.png') }}" alt="Scenic beach with ceremony seating" class="airport-transfers-photo">
                    <img src="{{ asset('img/bb_gold-splatter-1.png') }}" class="airport-transfers-gold" >
                </div>
            </div>
        </div>
    </section>
@endif
