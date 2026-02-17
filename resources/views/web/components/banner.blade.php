
    @if(!empty($group->banner_message))
        <div class="top-notification is-font-family-montserrat">
            <span id="close">X</span>
            <span>{{ $group->banner_message }}</span>
        </div>
    @endif
