<nav id="navbar" class="navbar is-fixed-top is-transparent is-size-6" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
        <div class="navbar-item fade-in">
            <a href="{{ route('web.home') }}" class="nav-logo">
                <img src="{{ asset('img/logo-white.png') }}" alt="Barefoot Bridal">
            </a>
        </div>

        <a role="button" id="menu-toggle" class="navbar-burger burger" aria-label="menu" aria-expanded="false" data-target="menu">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </a>
    </div>

    <div id="menu" class="navbar-menu">
        <div class="navbar-end">
            {{ $slot }}
        </div>
    </div>
</nav>
