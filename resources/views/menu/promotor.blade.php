<!-- Avançat: solo superadmin principal -->

<x-backpack::menu-dropdown title="{{ __('menu.configuration') }}" icon="la la-tools">
    @if (in_array(backpack_user()->id, config('superusers.ids')))
        <x-backpack::menu-dropdown-item title="{{ __('menu.configuration_general') }}" icon="la la-cogs"
            :link="backpack_url('custom-settings/brand')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.configuration_personalized') }}" icon="la la-wrench"
            :link="backpack_url('custom-settings/advanced')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.setting_tpv') }}" icon="la la-terminal" :link="backpack_url('custom-settings/tpv')" />
    @else
        <x-backpack::menu-dropdown-item title="{{ __('menu.setting_promotor') }}" icon="la la-cogs" :link="backpack_url('custom-settings/promotor')" />
    @endif
</x-backpack::menu-dropdown>


<!-- Administració -->
@canany(['users.index', 'roles.index'])
    <x-backpack::menu-dropdown title="{{ __('menu.user_administration') }}" icon="la la-cog">
        @can('users.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.users') }}" icon="la la-users" :link="backpack_url('user')" />
        @endcan
        <!-- @if (in_array(backpack_user()->id, config('superusers.ids')))
    <x-backpack::menu-dropdown-item title="{{ __('menu.recent_activity') }}" icon="la la-stream"
                    :link="backpack_url('activity-log')" />
    @endif -->
    </x-backpack::menu-dropdown>
@endcanany

<!-- CRM -->
@canany(['events.index', 'sessions.index', 'locations.index', 'spaces.index', 'zones.index', 'rates.index',
    'forms.index', 'form_fields.index'])
    <x-backpack::menu-dropdown title="CRM" icon="la la-address-book">
        {{-- Núcleo CRM del promotor --}}
        @can('events.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.events') }}" icon="la la-calendar" :link="backpack_url('event')" />
        @endcan
        @can('sessions.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.sessions') }}" icon="la la-clock" :link="backpack_url('session')" />
        @endcan
        @can('rates.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.rates') }}" icon="la la-money-bill-wave" :link="backpack_url('rate')" />
        @endcan

        {{-- 5) Espais i Localitzacions (submenú dentro de CRM) --}}
        <x-backpack::menu-dropdown title="{{ __('menu.spaces_locations') }}" icon="la la-map" nested="true">
            @can('locations.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.locations') }}" icon="la la-map-marker" :link="backpack_url('location')" />
            @endcan
            @can('spaces.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.spaces') }}" icon="la la-building" :link="backpack_url('space')" />
            @endcan
            @can('zones.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.zones') }}" icon="la la-map-pin" :link="backpack_url('zone')" />
            @endcan
        </x-backpack::menu-dropdown>

        {{-- 6) Formularis i Personalització (submenú dentro de CRM) --}}
        <x-backpack::menu-dropdown title="{{ __('menu.forms_customization') }}" icon="la la-sliders-h" nested="true">
            @can('forms.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.forms') }}" icon="la la-map" :link="backpack_url('form')" />
            @endcan
            @can('form_fields.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.form_fields') }}" icon="la la-plus" :link="backpack_url('form-field')" />
            @endcan
        </x-backpack::menu-dropdown>
    </x-backpack::menu-dropdown>
@endcanany

<!-- Taquilla (promotor puede vender, ver inscripciones/validar si corresponde) -->
@canany(['carts.index', 'clients.index', 'validations.index'])
    <x-backpack::menu-dropdown title="{{ __('menu.box_office') }}" icon="la la-ticket-alt">
        @can('carts.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.ticket_sales') }}" icon="la la-cash-register"
                :link="backpack_url('ticket-office/create')" />
        @endcan
        @can('clients.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.clients') }}" icon="la la-address-book" :link="backpack_url('client')" />
        @endcan
        @can('carts.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.carts') }}" icon="la la-shopping-cart" :link="backpack_url('cart')" />
        @endcan
        @can('validations.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.validation') }}" icon="la la-barcode" :link="backpack_url('validation')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany
