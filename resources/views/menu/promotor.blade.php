<!-- Avanzado: solo superadmin principal -->
@if (in_array(backpack_user()->id, config('superusers.ids')))
    <x-backpack::menu-dropdown title="{{ __('backend.menu.advanced') }}" icon="la la-microchip">
        <x-backpack::menu-dropdown-item title="{{ __('backend.menu.brand_settings') }}" icon="la la-cogs"
            :link="backpack_url('custom-settings/brand')" />
        <x-backpack::menu-dropdown-item title="{{ __('backend.menu.setting_advanced') }}" icon="la la-tools"
            :link="backpack_url('custom-settings/advanced')" />
        <x-backpack::menu-dropdown-item title="{{ __('backend.menu.setting_tpv') }}" icon="la la-terminal"
            :link="backpack_url('custom-settings/tpv')" />
        <x-backpack::menu-dropdown-item title="Activity" icon="la la-stream"
            :link="backpack_url('activity-log')" />
    </x-backpack::menu-dropdown>
@endif

<!-- Administración -->
@canany(['users.index', 'roles.index'])
    <x-backpack::menu-dropdown title="{{ __('backend.menu.administration') }}" icon="la la-cog">
        @can('users.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.users') }}" icon="la la-users"
                :link="backpack_url('user')" />
        @endcan

        @can('roles.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.roles') }}" icon="la la-user-tag"
                :link="backpack_url('role')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany

<!-- CRM (solo lo necesario para promotor) -->
@canany(['events.index', 'sessions.index', 'locations.index', 'spaces.index', 'zones.index', 'rates.index', 'forms.index', 'form_fields.index'])
    <x-backpack::menu-dropdown title="CRM" icon="la la-address-book">
        @can('events.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.events') }}" icon="la la-calendar"
                :link="backpack_url('event')" />
        @endcan

        @can('sessions.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.sessions') }}" icon="la la-clock"
                :link="backpack_url('session')" />
        @endcan

        @can('locations.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.locations') }}" icon="la la-wpforms"
                :link="backpack_url('location')" />
        @endcan

        @can('spaces.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.spaces') }}" icon="la la-building"
                :link="backpack_url('space')" />
        @endcan

        @can('zones.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.zone') }}" icon="la la-map-pin"
                :link="backpack_url('zone')" />
        @endcan

        @can('rates.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.rates') }}" icon="la la-money-bill-wave"
                :link="backpack_url('rate')" />
        @endcan

        @can('forms.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.forms') }}" icon="la la-map"
                :link="backpack_url('form')" />
        @endcan

        @can('form_fields.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.form_fields') }}" icon="la la-plus"
                :link="backpack_url('form-field')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany

<!-- Taquilla (promotor puede vender, ver inscripciones y, opcionalmente, clientes) -->
@canany(['clients.index', 'carts.index', 'validations.index'])
    <x-backpack::menu-dropdown title="{{ __('backend.menu.box_office') }}" icon="la la-cash-register">
        @can('carts.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.box_office') }}" icon="la la-cash-register"
                :link="backpack_url('ticket-office/create')" />
        @endcan

        @can('clients.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.clients') }}" icon="la la-address-book"
                :link="backpack_url('client')" />
        @endcan

        @can('carts.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.inscriptions') }}" icon="la la-ticket"
                :link="backpack_url('inscription')" />
        @endcan

        @can('validations.index')
            <x-backpack::menu-dropdown-item title="{{ __('backend.menu.validation') }}" icon="la la-barcode"
                :link="backpack_url('validation')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany

<!-- Estadísticas (si el promotor tiene permiso, mostramos igual que el basic) -->
@can('statistics.index')
    <x-backpack::menu-dropdown title="{{ __('backend.menu.statistics') }}" icon="la la-chart-bar">
        <x-backpack::menu-dropdown-item title="{{ __('backend.menu.sales') }}" icon="la la-ticket-alt"
            :link="backpack_url('statistics/sales')" />
        <x-backpack::menu-dropdown-item title="{{ __('backend.menu.balance') }}" icon="la la-euro"
            :link="backpack_url('statistics/balance')" />
        <x-backpack::menu-dropdown-item title="{{ __('backend.menu.client_sales') }}" icon="la la-euro"
            :link="backpack_url('statistics/client-sales')" />
    </x-backpack::menu-dropdown>
@endcan
