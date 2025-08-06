@php($modalId = 'importCodes')
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
    <i class="la la-file-import"></i> {{ __('backend.session.import_codes') }}
</button>

@push('after_scripts')
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('backend.session.import_codes') }}</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST" action="{{ route('censu.import-codes') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="alert alert-info">{{ __('backend.session.import_codes_info') }}
                            <p>Exemple:</p>
                            <img src="/images/codis.png" style="margin-top: 10px;">
                        </div>
                    <div class="modal-body">
                        <input type="file" name="csv" class="form-control" accept=".csv" required>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">
                            <span>{{ __('Importar') }}</span>
                            <span class="spinner-border spinner-border-sm d-none" id="spinner-import"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush