<section id="accommodations" class="section">
    <div class="container">
        <div class="header fade-in " style="margin-top:0% !important">accommodations</div>
    </div>

    <div class="hotels">
        @foreach ($group->hotels->filter(fn($hotel_block) => $hotel_block->rooms->where('is_active', true)->isNotEmpty()) as $hotel_block)
            <div class="hotel {{ $loop->odd ? 'is-even' : 'is-odd' }}">
                <div class="container">
                    <div class="hotel-content">
                        @if ($hotel_block->hotel->images->isNotEmpty())
                            <div class="hotel-images">
                                <div class="glide hotel-main-carousel" data-hotel-id="{{ $hotel_block->id }}">
                                    <div class="glide__track" data-glide-el="track">
                                        <ul class="glide__slides">
                                            @foreach ($hotel_block->hotel->images as $image)
                                                <li class="glide__slide">
                                                    <img src="{{ Storage::url($image->path) }}" loading="lazy" alt="{{ $hotel_block->hotel->name }}" class="hotel-image">
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>

                                @if ($hotel_block->hotel->images->count() > 1)
                                    <div class="hotel-thumbnails-wrapper">
                                        <div class="glide hotel-thumb-carousel" data-hotel-id="{{ $hotel_block->id }}">
                                            <div class="glide__track" data-glide-el="track">
                                                <ul class="glide__slides">
                                                    @foreach ($hotel_block->hotel->images as $thumbIndex => $image)
                                                        <li class="glide__slide">
                                                            <img src="{{ Storage::url($image->path) }}" loading="lazy" alt="{{ $hotel_block->hotel->name }}" class="hotel-thumbnail" data-index="{{ $thumbIndex }}">
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="hotel-description">
                          <div class="hotel-name">{{ $hotel_block->hotel->name }}</div>
                          <div class="hotel-description-content">
                              {!! $hotel_block->hotel->description !!}
                          </div>
                        </div>

                        <div class="hotel-content-clear"></div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="hotels-bottom-border"></div>
    </div>

    <section class="hotel-note-section">
        <div class="container">
            <div class="hotel-note-title fade-in">Group Rates</div>
            <p class="hotel-note fade-in">
                @if ($group->is_fit)
                  The following room categories are our suggestions based on what is most popular at this resort!
                @else
                  Special Group Rates have been secured with a {{ $group->min_nights }}-night minimum stay and rooms are in inventory until {{ $group->cancellation_date->format('F d, Y') }}, ready for you to book your stay!
                @endif
            </p>
            <p class="hotel-note-text fade-in">
              @if ($group->is_fit)
                Please note that rooms may not be available depending on your travel dates. Additional categories may also be available; email <a href="mailto:{{ config('emails.groups') }}" class="is-black-color">{{ config('emails.groups') }}</a> if you're interested in a room category not listed below.
              @else
                Please note that inventory is not live and is subject to availability. Additional categories may also be available; email <a href="mailto:{{ config('emails.groups') }}" class="is-black-color">{{ config('emails.groups') }}</a> if you're interested in a room category not listed below.
              @endif
            </p>
        </div>
    </section>

    <section class="rooms-section">
        <div class="container hotels-search-results">
            <div class="room-carousel">
                <div class="glide room-carousel-glide">
                    <div class="glide__track" data-glide-el="track">
                        <ul class="glide__slides">
                            @foreach ($sortedRoomBlocks as $room_block)
                                <li class="glide__slide">
                                    <div class="search-result-room">
                                        <div class="room-card">
                                            <div class="room-card-layout">
                                                <div class="room-card-image-column fade-in-slow">
                                                    @if (!is_null($room_block->room->image))
                                                        <img
                                                            src="{{ Storage::url($room_block->room->image->path) }}"
                                                            alt="{{ $room_block->room->name }}, {{ $room_block->hotel_block->hotel->name }}"
                                                            loading="lazy"
                                                            class="room-card-image"
                                                        >
                                                    @endif
                                                </div>
                                                <div class="room-card-details-column">
                                                    <h1 class="room-card-hotel-name">{{ strtoupper($room_block->hotel_block->hotel->name) }}</h1>
                                                    <h3 class="room-card-room-name">{{ $room_block->room->name }}</h3>
                                                    <div class="room-card-info-table">
                                                        <div class="room-card-info-row">
                                                            <span class="room-card-info-label">Room Size</span>
                                                            <span class="room-card-info-value">{{ $room_block->room->size ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="room-card-info-row">
                                                            <span class="room-card-info-label">Room View</span>
                                                            <span class="room-card-info-value">{{ $room_block->room->view ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="room-card-info-row">
                                                            <span class="room-card-info-label">Bedding Type</span>
                                                            <span class="room-card-info-value">{{ $room_block->room->beds ? implode(' or ', $room_block->room->beds) : 'N/A' }}</span>
                                                        </div>
                                                        <div class="room-card-info-row">
                                                            <span class="room-card-info-label">Max Occupancy</span>
                                                            <span class="room-card-info-value">{{ $room_block->room->formatted_max_occupancy }}</span>
                                                        </div>
                                                    </div>

                                                    @if ($room_block->sold_out)
                                                        <div class="room-card-sold-out">
                                                            <p class="has-text-weight-bold has-text-danger">Sold Out</p>
                                                        </div>
                                                    @else
                                                        @if (!$group->is_fit)
                                                            <div class="room-card-pricing">
                                                                <span class="room-card-dates">{{ $room_block->start_date->format('F d') }} - {{ is_null($room_block->split_date) ? $room_block->end_date->format('F d, Y') : $room_block->split_date->format('F d, Y') }}:</span>
                                                                <ul class="room-card-rate-list">
                                                                    @foreach ($room_block->rates as $rate)
                                                                        <li class="room-card-rate-item">${{ $rate->rate }}/night per adult for {{ $rate->occupancy }} in a room.</li>
                                                                    @endforeach
                                                                    @foreach ($room_block->child_rates as $child_rate)
                                                                        @if ($child_rate->rate != 0)
                                                                            <li class="room-card-rate-item">${{ $child_rate->rate }}/night per child ({{ $child_rate->from }} - {{ $child_rate->to }})</li>
                                                                        @else
                                                                            <li class="room-card-rate-item">Free for child ({{ $child_rate->from }} - {{ $child_rate->to }})</li>
                                                                        @endif
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                            @if (!is_null($room_block->split_date))
                                                                <div class="room-card-pricing room-card-pricing--secondary">
                                                                    <span class="room-card-dates">{{ $room_block->split_date->format('F d') }} - {{ $room_block->end_date->format('F d, Y') }}:</span>
                                                                    <ul class="room-card-rate-list">
                                                                        @foreach ($room_block->rates as $rate)
                                                                            <li class="room-card-rate-item">${{ $rate->split_rate }}/night per adult for {{ $rate->occupancy }} in a room.</li>
                                                                        @endforeach
                                                                        @foreach ($room_block->child_rates as $child_rate)
                                                                            @if ($child_rate->split_rate != 0)
                                                                                <li class="room-card-rate-item">${{ $child_rate->split_rate }}/night per child ({{ $child_rate->from }} - {{ $child_rate->to }})</li>
                                                                            @else
                                                                                <li class="room-card-rate-item">Free for child ({{ $child_rate->from }} - {{ $child_rate->to }})</li>
                                                                            @endif
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @if (count($sortedRoomBlocks) > 1)
                        <div class="glide__arrows" data-glide-el="controls">
                            <button class="glide__arrow glide__arrow--left" data-glide-dir="<"><span>&lt;</span></button>
                            <button class="glide__arrow glide__arrow--right" data-glide-dir=">"><span>&gt;</span></button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</section>
