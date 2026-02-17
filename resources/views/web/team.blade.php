@extends('web.layouts.layout')

@section('title')
DESTINATION WEDDING SPECIALISTS - Our Team | Barefoot Bridal
@endsection

@section('content')
<article id="team">
    <div class="hero is-large has-glass">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-size-2-mobile is-uppercase">Our team</h1>
            </div>
        </div>
        <img src="{{ asset('img/team-hero.jpg') }}" class="parallax-background" data-parallax-position="0">
    </div>
    <div class="section is-medium">
        <div class="container">
            <h2 class="section-title subtitle is-2 is-size-3-mobile is-uppercase has-text-secondary has-text-weight-normal has-text-centered">Destination Wedding Specialists</h2>
            <br>
            <div class="columns">
                <div class="column is-6">
                    <img src="{{ asset('img/team-1.jpeg') }}">
                </div>
                <div class="column is-6">
                    <p>
                        <strong>Sharon Kopp</strong>
                        <br>
                        <em>Founder and Owner, Destination Wedding Specialist</em>
                        <br>
                        <a href="mailto:sharon@barefootbridal.com" target="_blank" class="has-text-primary">{{ 'sharon@barefootbridal.com' }}</a>
                    </p>
                    <br>
                    <p>
                        After returning from her own destination wedding in Punta Cana, Sharon started Barefoot Bridal because destination wedding brides know other destination wedding brides best.
                        Knowing how important not only the wedding day, but the entire trip is to the couple and their guests is what sets Sharon and Barefoot Bridal apart from other travel agents.
                    </p>
                    <br>
                    <p>
                        Sharon got married with a sunrise ceremony at Huracan Cafe decorated by the famous Mayte Mari herself.
                        Her guests stayed at Paradisus Palma Real.
                        She minimooned at Breathless Punta Cana and Honeymooned in Maui, Kauai, and Oahu in Hawaii.
                    </p>
                    <div class="team-badges columns is-centered is-vcentered is-multiline">
                        <div class="column is-6">
                            <img src="{{ asset('img/team-badge-wow.jpg') }}">
                        </div>
                        <div class="column is-6">
                            <img src="{{ asset('img/team-badge-am.jpg') }}">
                        </div>
                    </div>
                    <br>
                </div>
            </div>

            <div class="columns">
                <div class="column is-6">
                    <img src="{{ asset('img/team-2.jpg') }}">
                </div>
                <div class="column is-6">
                    <p>
                        <strong>Carol Ebua</strong>
                        <br>
                        <em>Destination Wedding Specialist </em>
                        <br>
                        <a href="mailto:carol@barefootbridal.com" target="_blank" class="has-text-primary">{{ 'carol@barefootbridal.com' }}</a>
                    </p>
                    <br>
                    <p>
                        Having been a Barefoot Bride and experienced the exceptional level of service herself,
                        Carol decided she wanted to be a destination wedding specialist for future brides upon her return from her own destination wedding in Punta Cana, which is exactly what she did!
                        Loving weddings and brides makes giving exceptional service easy and natural for Carol. In addition, Carol is fluent in Spanish.
                    </p>
                    <br>
                    <p>
                        Carol got married at the ever so popular and stunning venue Jellyfish Restaurant.
                        Carol and her guests stayed at Now Larimar Punta Cana for the wedding week.
                    </p>
                    <br>
                    <div class="team-badges columns is-centered is-vcentered">
                        <div class="column is-6">
                            <img src="{{ asset('img/team-badge-am.jpg') }}">
                        </div>
                    </div>
                    <br>
                </div>
            </div>

            <div class="columns">
                <div class="column is-6">
                    <img src="{{ asset('img/team-3.jpg') }}">
                </div>
                <div class="column is-6">
                    <p>
                        <strong>Carolisa Selmo</strong>
                        <br>
                        <em>Destination Wedding Specialist </em>
                        <br>
                        <a href="mailto:carolisa@barefootbridal.com" target="_blank" class="has-text-primary">{{ 'carolisa@barefootbridal.com' }}</a>
                    </p>
                    <br>
                    <p>
                        Having attended her sister’s destination wedding in Punta Cana, Carolisa knew there wasn’t any other option for her.
                        After a few weeks of planning in Boston, Carolisa knew she was a Destination Wedding Bride at heart.
                        After being a Barefoot Bridal guest and then a Barefoot Bridal bride,
                        she was ready and determined to walk other brides through this amazing experience.
                    </p>
                    <br>
                    <p>
                        Carolisa was married at Pearl Beach Club and hosted her guest’s at Hard Rock Punta Cana.
                        She closed her week of festivities with an amazing hineymoon at the one and only Zoetry Punta Cana.
                    </p>
                    <br>
                    <div class="team-badges columns is-centered is-vcentered">
                        <div class="column is-6">
                            <img src="{{ asset('img/team-badge-am.jpg') }}">
                        </div>
                    </div>
                    <br>
                </div>
            </div>

            <h2 class="section-title subtitle is-2 is-size-3-mobile is-uppercase has-text-secondary has-text-weight-normal has-text-centered">Group Coordinator</h2>
            <br>

            <div class="columns">
                <div class="column is-6">
                    <img src="{{ asset('img/team-4.jpg') }}" class="is-pulled-right">
                </div>
                <div class="column is-6">
                    <p>
                        <strong>Carrie</strong>
                        <br>
                        <em>Groups Specialist</em>
                        <br>
                        <a href="mailto:{{ config('emails.groups') }}" target="_blank" class="has-text-primary">{{ config('emails.groups') }}</a>
                    </p>
                    <br>
                    <p>
                        Carrie had her own little destination wedding in Kona, Hawaii in 2008, and while it was small and relatively easy to "throw together",
                        she understands how stressful any wedding planning can be and loves to be able to take some of that stress away from Barefoot Brides.
                        She has an extensive background in customer service from 15 years in the retail industry and Human Resources,
                        making her an excellent addition to the Barefoot Bridal family, and a favorite among brides and their guests.
                    </p>
                    <br>
                </div>
            </div>

        </div>
    </div>
</article>
@endsection
