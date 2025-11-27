{{-- $entry = App\Models\Session --}}

@php($modalId = 'importCodes-' . $entry->getKey())
<button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
    <i class="la la-file-import"></i>
    {{ __('tincket/backend.session.import_codes') }}
</button>

@push('after_scripts')
    {{-- Modal Bootstrap 5 --}}
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('backend.session.import_codes') }}</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST" action="{{ route('session.import-codes', $entry->getKey()) }}"
                    enctype="multipart/form-data"
                    onsubmit="document.getElementById('spin-{{ $modalId }}').classList.remove('d-none')">
                    @csrf
                    <div class="modal-body">
                        <p>{!! __('backend.session.import_codes_info') !!}</p>
                        <p class="mb-2">Exemple CSV:</p>
                        <img src="/images/codis.png" class="mb-4 img-fluid">
                        <input type="file" name="csv" class="form-control" accept=".csv" required>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">
                            <span>{{ __('Importar') }}</span>
                            <span id="spin-{{ $modalId }}" class="spinner-border spinner-border-sm d-none"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endpush
