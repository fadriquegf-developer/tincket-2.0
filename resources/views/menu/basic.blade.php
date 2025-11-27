<!-- Avançat: solo superadmin principal -->
@if (in_array(backpack_user()->id, config('superusers.ids')))
    <x-backpack::menu-dropdown title="{{ __('menu.configuration') }}" icon="la la-tools">
        <x-backpack::menu-dropdown-item title="{{ __('menu.configuration_general') }}" icon="la la-cogs"
            :link="backpack_url('custom-settings/brand')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.configuration_personalized') }}" icon="la la-wrench"
            :link="backpack_url('custom-settings/advanced')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.setting_tpv') }}" icon="la la-terminal" :link="backpack_url('custom-settings/tpv')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.codes') }}" icon="la la-key" :link="backpack_url('code')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.inputs') }}" icon="la la-check-square-o" :link="backpack_url('register-input')" />
    </x-backpack::menu-dropdown>
@endif

<!-- Administració -->
@canany(['users.index', 'roles.index'])
    <x-backpack::menu-dropdown title="{{ __('menu.user_administration') }}" icon="la la-cog">
        @can('users.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.users') }}" icon="la la-users" :link="backpack_url('user')" />
        @endcan
        @can('roles.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.roles') }}" icon="la la-user-tag" :link="backpack_url('role')" />
        @endcan
        <!-- @if (in_array(backpack_user()->id, config('superusers.ids')))
    <x-backpack::menu-dropdown-item title="{{ __('menu.recent_activity') }}" icon="la la-stream"
                    :link="backpack_url('activity-log')" />
    @endif -->
    </x-backpack::menu-dropdown>
@endcanany

<!-- CRM -->
@canany(['clients.index', 'events.index', 'sessions.index', 'carts.index', 'packs.index', 'gift_cards.index',
    'census.index', 'locations.index', 'spaces.index', 'zones.index', 'rates.index', 'forms.index', 'form_fields.index'])
    <x-backpack::menu-dropdown title="CRM" icon="la la-address-book">
        {{-- 4) CRM (núcleo) --}}
        @can('clients.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.clients') }}" icon="la la-address-book" :link="backpack_url('client')" />
        @endcan
        @can('events.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.events') }}" icon="la la-calendar" :link="backpack_url('event')" />
        @endcan
        @can('sessions.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.sessions') }}" icon="la la-clock" :link="backpack_url('session')" />
        @endcan
        @can('rates.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.rates') }}" icon="la la-money-bill-wave" :link="backpack_url('rate')" />
        @endcan
        @can('carts.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.inscriptions') }}" icon="la la-ticket" :link="backpack_url('inscription')" />
            <x-backpack::menu-dropdown-item title="{{ __('menu.carts') }}" icon="la la-shopping-cart" :link="backpack_url('cart')" />
        @endcan
        @can('packs.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.packs') }}" icon="la la-box" :link="backpack_url('pack')" />
        @endcan
        @can('gift_cards.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.gift_cards') }}" icon="la la-gift" :link="backpack_url('gift-card')" />
        @endcan
        @can('census.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.census') }}" icon="la la-home" :link="backpack_url('censu')" />
        @endcan

        {{-- 5) Espais i Localitzacions (submenú DENTRO de CRM) --}}
        <x-backpack::menu-dropdown title="{{ __('menu.spaces_locations') }}" icon="la la-map" nested="true">
            @can('locations.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.locations') }}" icon="la la-map-marker"
                    :link="backpack_url('location')" />
            @endcan
            @can('spaces.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.spaces') }}" icon="la la-building" :link="backpack_url('space')" />
            @endcan
            @can('zones.index')
                <x-backpack::menu-dropdown-item title="{{ __('menu.zones') }}" icon="la la-map-pin" :link="backpack_url('zone')" />
            @endcan
        </x-backpack::menu-dropdown>

        {{-- 6) Formularis i Personalització (submenú DENTRO de CRM) --}}
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

<!-- CMS -->
@canany(['menu_items.index', 'taxonomies.index', 'pages.index', 'posts.index', 'validation.index'])
    <x-backpack::menu-dropdown title="CMS" icon="la la-file-alt">
        @can('menu_items.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.navigation_menu') }}" icon="la la-list" :link="backpack_url('menu-item')" />
        @endcan
        @can('taxonomies.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.taxonomies') }}" icon="la la-code-fork" :link="backpack_url('taxonomy')" />
        @endcan
        @can('pages.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.pages') }}" icon="la la-copy" :link="backpack_url('page')" />
        @endcan
        @can('posts.index')
            <x-backpack::menu-dropdown-item title="{{ __('menu.posts') }}" icon="la la-newspaper" :link="backpack_url('post')" />
        @endcan
        <x-backpack::menu-dropdown-item title="{{ __('menu.mails') }}" icon="la la-at" :link="backpack_url('mailing')" />
        <x-backpack::menu-dropdown-item :title="trans('backpack::crud.file_manager')" icon="la la-files-o" :link="backpack_url('elfinder')" />
    </x-backpack::menu-dropdown>
@endcanany

<!-- Taquilla -->
<x-backpack::menu-dropdown title="{{ __('menu.box_office') }}" icon="la la-ticket-alt">
    <x-backpack::menu-dropdown-item title="{{ __('menu.ticket_sales') }}" icon="la la-cash-register"
        :link="backpack_url('ticket-office/create')" />
    @can('validations.index')
        <x-backpack::menu-dropdown-item title="{{ __('menu.validation') }}" icon="la la-barcode" :link="backpack_url('validation')" />
    @endcan
</x-backpack::menu-dropdown>

<!-- Estadístiques i Informes -->
@can('statistics.index')
    <x-backpack::menu-dropdown title="{{ __('menu.statistics') }}" icon="la la-chart-bar">
        <x-backpack::menu-dropdown-item title="{{ __('menu.sales') }}" icon="la la-ticket-alt" :link="backpack_url('statistics/sales')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.balance') }}" icon="la la-euro" :link="backpack_url('statistics/balance')" />
        <x-backpack::menu-dropdown-item title="{{ __('menu.client_sales') }}" icon="la la-euro" :link="backpack_url('statistics/client-sales')" />
    </x-backpack::menu-dropdown>
@endcan
