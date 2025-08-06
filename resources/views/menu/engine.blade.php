
<!-- Avanzado -->
<x-backpack::menu-dropdown title="{{ __('backend.menu.advanced') }}" icon="la la-microchip">
    <x-backpack::menu-dropdown-item title="Activity" icon="la la-stream" :link="backpack_url('activity-log')" />
    <x-backpack::menu-dropdown-item title="Logs" icon="la la-terminal" :link="backpack_url('log')" />
</x-backpack::menu-dropdown>

<!-- Motor -->
<x-backpack::menu-dropdown title="{{ __('backend.menu.engine') }}" icon="la la-server"> 
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.brands') }}" icon="la la-building" :link="backpack_url('brand')" />
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.applications') }}" icon="la la-key" :link="backpack_url('application')" />
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.capability') }}" icon="la la-puzzle-piece" :link="backpack_url('capability')" />
    <!-- Esdeveniments -->
    <!-- Sessions -->
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.update_notifications') }}" icon="la la-bell" :link="backpack_url('update-notification')" />
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.jobs') }}" icon="la la-tasks" :link="backpack_url('job')" />
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.failed_jobs') }}" icon="la la-exclamation-triangle" :link="backpack_url('failed-job')" />
    <!-- Desenvolupament -->
    
</x-backpack::menu-dropdown>

<!-- Administración -->
<x-backpack::menu-dropdown title="{{ __('backend.menu.administration') }}" icon="la la-cog">
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.users') }}" icon="la la-users" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="{{ __('backend.menu.clients') }}" icon="la la-address-book" :link="backpack_url('client')" />
    <x-backpack::menu-dropdown-item title="Permisos" icon="la la-lock" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>


