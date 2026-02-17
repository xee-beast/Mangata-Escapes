<div id="welcome">
      <header class="hero">
        <div class="hero-content">
            <div class="hero-image-container">
                <img src="{{ is_null($group->image) ? asset('img/couple-1.jpg') : Storage::url($group->image->path) }}" alt="{{ $group->name . '\'s Wedding' }}" alt="Barefoot Bridal" class="hero-image" loading="lazy">
            </div>
            
            <div class="hero-text">
                <div class="hero-text-wrapper">
                    <h1>{!! str_replace('&amp;', '<span class="hero-ampersand">&amp;</span><br/>', e(strtoupper($group->name))) !!}</h1>
                    <p>{{ strtoupper($group->event_date->format('F d, Y')) }}<br>{{ strtoupper($group->destination->name) }}, {{ strtoupper($group->destination->country->name) }}</p>
                </div>
            </div>
        </div>
    </header>
    <section class="Welcome-section fade-in">
        <h2>Welcome, family & friends!</h2>
        <p> We are so excited to spend an extended weekend with you in {{ $group->destination->name }}, {{ $group->destination->country->name }}.
                Our travel agent, {{ $group->travel_agent->first_name }}, @if ($group->is_fit) is waiting to help you quote and book your reservation with @else has secured group rates with @endif
                {{ $group->hotels->filter(fn($hotel_block) => $hotel_block->rooms->where('is_active', true)->isNotEmpty())
                    ->values()
                    ->reduce(function ($hotels, $hotel, $index) use ($group) {
                        $activeHotels = $group->hotels->filter(fn($hb) => $hb->rooms->where('is_active', true)->isNotEmpty());
                        $count = $activeHotels->count();

                        return $hotels . ($hotels == '' ? '' : (($index + 1) == $count ? ' and ' : ', ')) . $hotel->hotel->name;
                    }, '')
                }}. All the information for your travel and accommodations is below.
        </p>
        
        <h3>Questions?</h3>
        <div class="contact">
            <p>Email: <a href="mailto:{{ config('emails.groups') }}">{{ config('emails.groups') }}</a></p>
            <p>Call or text: <a href="tel:8668227336">866-822-7336</a></p>
        </div>
    </section>
    @if (!is_null($group->message))
        <section class="couple-message-section fade-in">
            <div class="couple-message-container">
                <h2 class="couple-message-heading">A MESSAGE FROM THE COUPLE:</h2>
                <p class="couple-message-text">{!! nl2br(e($group->message)) !!}</p>
                <div class="couple-message-signature">&mdash; {{ strtoupper($group->name) }}</div>
            </div>
            <div class="couple-message-bottom-edge" aria-hidden="true">
                <img src="{{ asset('img/edge-light.png') }}" alt="" loading="lazy">
            </div>
        </section>
    @endif
</div>
