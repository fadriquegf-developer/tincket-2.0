@inject('helper', 'Backpack\LanguageSwitcher\Helpers\LanguageSwitcherHelper')

@php
    // Define which flags to override
    $flagOverrides = [
        'gl' => 'language-gl.svg',
    ];
@endphp

<li class="nav-item me-2 dropdown language-switcher">
    <a class="nav-link dropdown-toggle text-decoration-none" data-bs-toggle="dropdown" data-toggle="dropdown"
        data-bs-auto-close="outside" role="button" aria-expanded="false" style="cursor: pointer">
        @if ($flags ?? true)
            <span class="nav-link-icon" style="width: fit-content">
                @if (isset($flagOverrides[$helper->getCurrentLocale()]))
                    <img src="{{ asset('vendor/blade-flags/' . $flagOverrides[$helper->getCurrentLocale()]) }}"
                        style="width: 1.5rem" alt="{{ $helper->getCurrentLocale() }}" />
                @else
                    <x-dynamic-component component="flag-{{ $helper->getFlagOrFallback($helper->getCurrentLocale()) }}"
                        style="width: 1.5rem" />
                @endif
            </span>
        @endif
        @if ($main_label ?? false || (($flags ?? true) === false && !isset($main_label)))
            <span class="nav-link-title">
                {{ is_string($main_label ?? false) ? $main_label : $helper->getLocaleName($helper->getCurrentLocale()) }}
            </span>
        @endif
    </a>
    <ul class="dropdown-menu dropdown-menu-right dropdown-menu-end" style="right: 0">
        @php
            $useAdminPrefix = config('backpack.language-switcher.use_backpack_route_prefix');
        @endphp
        @foreach (config('backpack.crud.locales', []) as $locale => $name)
            <li>
                <a class="dropdown-item {{ $locale === $helper->getCurrentLocale() ? 'active disabled' : '' }}"
                    href="{{ route('language-switcher.locale', [
                        'locale' => $useAdminPrefix ? $locale : null,
                        'backpack_prefix' => $useAdminPrefix ? config('backpack.base.route_prefix') : 'set-locale',
                        'setLocale' => $useAdminPrefix ? 'set-locale' : $locale,
                    ]) }}">
                    @if ($flags ?? true)
                        <span class="nav-link-icon" style="width: fit-content">
                            @if (isset($flagOverrides[$locale]))
                                <img src="{{ asset('vendor/blade-flags/' . $flagOverrides[$locale]) }}"
                                    style="width: 1.5rem" alt="{{ $locale }}" />
                            @else
                                <x-dynamic-component component="flag-{{ $helper->getFlagOrFallback($locale) }}"
                                    style="width: 1.5rem" />
                            @endif
                        </span>
                    @endif
                    <span class="nav-link-title">
                        {{ $name }}
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</li>
