{{-- client_import_button.blade.php --}}
@if (get_brand_capability() === 'basic')
    {{-- Botón que abre el modal --}}
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientImportModal">
        <i class="la la-download"></i> {{ app()->getLocale() === 'en' ? 'Import CSV' : 'Importar CSV' }}
    </button>

    @push('after_scripts')
        {{-- Modal Bootstrap 5: colocado al final de <body> --}}
        <div class="modal fade" id="clientImportModal" tabindex="-1" aria-labelledby="clientImportModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form action="{{ route('client.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title" id="clientImportModalLabel">
                                {{ __('backend.modal.tittle') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            {!! __('modal.modal.instructions') !!}

                            <div class="form-group mt-4 mb-3">
                                <label for="file_csv">{{ __('backend.modal.select_file') }}</label>
                                <input type="file" name="csv" class="form-control" id="file_csv" required>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ __('backend.modal.close') }}
                            </button>

                            <button type="submit" class="btn btn-primary">
                                <i class="la la-upload"></i> {{ app()->getLocale() === 'en' ? 'Import' : 'Importar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush
@endif
