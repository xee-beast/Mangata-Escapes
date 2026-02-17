@extends('web.layouts.layout')

@section('title')
Start planning your dream wedding - Contact Us | Barefoot Bridal
@endsection

@section('content')
<section id="contact">
    <div class="hero is-large has-glass">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-size-2-mobile is-uppercase">Contact Us</h1>
            </div>
        </div>
        <img src="{{ asset('img/contact-hero.jpg') }}" class="parallax-background" data-parallax-position="1">
    </div>
    <div class="section is-medium">
        <div class="container">
            <h2 class="subtitle is-4 is-uppercase has-text-weight-normal">Barefoot Bridal Destination Wedding Contact Form</h2>
            <p>
                Start planning your dream wedding or honeymoon (or both!) today and enjoy
                our complimentary consultation with one of our best destination wedding travel specialists.
            </p>
            @if (session('formSubmitted', false))
            <br>
            <p class="has-text-success">Thank you for your submission, our Destination Wedding Travel Specialist will contact you as soon as possible.</p>
            @endif
            <form id="contact-form" method="POST" action="{{ route('newLead') }}">
                @csrf
                <div class="form-seperator">
                    <p>Wedding Couple:</p>
                </div>

                <div class="field is-horizontal">
                    <div class="field-body">
                        <div class="field">
                            <div class="control is-expanded">
                                <input class="input @error('bride.firstName') is-danger @enderror" type="text" name="bride[firstName]" value="{{ old('bride.firstName') }}">
                            </div>
                            @if ($errors->has('bride.firstName'))
                            <p class="help is-danger">{{ $errors->first('bride.firstName') }}</p> 
                            @else
                            <p class="help">First Name</p>
                            @endif
                        </div>
                        <div class="field">
                            <div class="control is-expanded">
                                <input class="input @error('bride.lastName') is-danger @enderror" type="text" name="bride[lastName]" value="{{ old('bride.lastName') }}">
                            </div>
                            @if ($errors->has('bride.lastName'))
                            <p class="help is-danger">{{ $errors->first('bride.lastName') }}</p> 
                            @else
                            <p class="help">Last Name</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="field is-horizontal">
                    <div class="field-body">
                        <div class="field">
                            <div class="control is-expanded">
                                <input class="input @error('groom.firstName') is-danger @enderror" type="text" name="groom[firstName]" value="{{ old('groom.firstName') }}">
                            </div>
                            @if ($errors->has('groom.firstName'))
                            <p class="help is-danger">{{ $errors->first('groom.firstName') }}</p> 
                            @else
                            <p class="help">First Name</p>
                            @endif
                        </div>
                        <div class="field">
                            <div class="control is-expanded">
                                <input class="input @error('groom.lastName') is-danger @enderror" type="text" name="groom[lastName]" value="{{ old('groom.lastName') }}">
                            </div>
                            @if ($errors->has('groom.lastName'))
                            <p class="help is-danger">{{ $errors->first('groom.lastName') }}</p> 
                            @else
                            <p class="help">Last Name</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label class="label">Where is your group departing from? *</label>
                    <div class="control">
                        <label class="radio">
                            <input type="radio" name="departure" value="USA" @if(old('departure') == 'USA') checked @endif>
                            USA
                        </label>
                        <label class="radio">
                            <input type="radio" name="departure" value="Canada" @if(old('departure') == 'Canada') checked @endif>
                            Canada
                        </label>
                        <label class="radio">
                            <input type="radio" name="departure" value="Other" @if(old('departure') == 'Other') checked @endif>
                            Other
                        </label>
                    </div>
                    @if ($errors->has('departure'))
                        <p class="help is-danger">{{ $errors->first('departure') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">Check here if you need a Spanish-Speaking Destination Wedding Specialist.</label>
                    <div class="control">
                        <label class="checkbox">
                            <input type="checkbox" name="spanish" value="1" @if(old('spanish')) checked @endif>
                            Yes
                        </label>
                    </div>
                    @if ($errors->has('spanish'))
                        <p class="help is-danger">{{ $errors->first('spanish') }}</p>
                    @endif
                </div>

                <div class="form-seperator">
                    <p>Contact Information:</p>
                </div>

                <div class="field">
                    <label class="label">Best Contact Number *</label>
                    <div class="control">
                        <input type="text" class="input @error('phone') is-danger @enderror" placeholder="(###) ###-####" name="phone" value="{{ old('phone') }}">
                    </div>
                    @if ($errors->has('phone'))
                        <p class="help is-danger">{{ $errors->first('phone') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">Email *</label>
                    <div class="control">
                        <input type="text" class="input @error('email') is-danger @enderror" name="email" value="{{ old('email') }}">
                    </div>
                    @if ($errors->has('email'))
                        <p class="help is-danger">{{ $errors->first('email') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">Destination(s)</label>
                    <div class="control">
                        <input type="text" class="input @error('destinations') is-danger @enderror" name="destinations" value="{{ old('destinations') }}">
                    </div>
                    @if ($errors->has('destinations'))
                        <p class="help is-danger">{{ $errors->first('destinations') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">Wedding Date *</label>
                    <p class="help">Tentative Wedding Date</p>
                    <div class="control">
                        <input type="text" class="input @error('weddingDate') is-danger @enderror" placeholder="MM/DD/YYYY" name="weddingDate" value="{{ old('weddingDate') }}">
                    </div>
                    @if ($errors->has('weddingDate'))
                        <p class="help is-danger">{{ $errors->first('weddingDate') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">Please Choose a Specialist</label>
                    <div class="control">
                        <div class="select @error('agent') is-danger @enderror">
                            <select name="agent">
                                <option value="0" @if(is_null(old('agent')) || old('agent') == 0) selected @endif>First Available</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" @if(old('agent') == $agent->id) selected @endif>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if ($errors->has('agent'))
                        <p class="help is-danger">{{ $errors->first('agent') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">How Did You Hear About Us?</label>
                    <div class="control">
                        <input type="text @error('source') is-danger @enderror" class="input" name="source" value="{{ old('source') }}">
                    </div>
                    @if ($errors->has('source'))
                        <p class="help is-danger">{{ $errors->first('source') }}</p>
                    @endif
                </div>

                <div class="field">
                    <label class="label">Leave Us a Message</label>
                    <div class="control">
                        <textarea class="textarea @error('message') is-danger @enderror" name="message">{{ old('message') }}</textarea>
                    </div>
                    @if ($errors->has('message'))
                        <p class="help is-danger">{{ $errors->first('message') }}</p>
                    @endif
                </div>

                <div class="field">
                    <div class="control">
                        <button type="submit" class="button is-rounded is-outlined is-black has-text-weight-normal">SUBMIT</button>
                    </div>
                </div>
            </form>


        </div>
    </div>
</section>
@endsection

@section('scripts')
@parent
<script>
    var allowSubmit = true;

    document.querySelector('#contact-form').addEventListener('submit', function (event) {
        event.target.querySelector('[type="submit"]').classList.add('is-loading');

        if (allowSubmit) {
            allowSubmit = false;
        } else {
            return false;
        }
    })
</script>
@endsection
