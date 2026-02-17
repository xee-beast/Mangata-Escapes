<div class="navbar-item has-dropdown is-hoverable">
    <a class="navbar-link is-arrowless {{ request()->is('about*') ? 'is-active' : '' }}">ABOUT US</a>
    <div class="navbar-dropdown">
        <a href="{{ route('web.about') }}" class="navbar-item {{ request()->is('about') ? 'is-active' : '' }}">WHY GO BAREFOOT</a>
        <a href="{{ route('web.about.team') }}" class="navbar-item {{ request()->is('about/team') ? 'is-active' : '' }}">OUR TEAM</a>
        <a href="{{ route('web.about.brides') }}" class="navbar-item {{ request()->is('about/brides') ? 'is-active' : '' }}">OUR BRIDES</a>
    </div>
</div>
<a href="{{ route('web.services') }}" class="navbar-item {{ request()->is('services') ? 'is-active' : '' }}">SERVICES</a>
<a href="{{ route('web.contact') }}" class="navbar-item {{ request()->is('contact') ? 'is-active' : '' }}">CONTACT</a>
{{-- <a href="{{ route('web.blog') }} class="navbar-item" {{ request()->is('blog') ? 'is-active' : '' }}>BLOG</a> --}}