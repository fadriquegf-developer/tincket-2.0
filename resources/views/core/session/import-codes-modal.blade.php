@push('after_scripts')
    {{-- Genero UN modal por cada fila --}}
        <div class="modal fade"
             id="importCodes-{{ $entry->id }}"
             tabindex="-1"
             aria-labelledby="importCodesLabel-{{ $entry->id }}"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">

                    {{-- Cabecera --}}
                    <div class="modal-header">
                        <h5 class="modal-title" id="importCodesLabel-{{ $entry->id }}">
                            {{ __('backend.session.import_codes') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    {{-- Formulario --}}
                    <form id="form-import-{{ $entry->id }}"
                          action="{{ route('session.import-codes', $entry->id) }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="modal-body">
                            <div class="alert alert-info mb-3">
                                {{ __('backend.session.import_codes_info') }}
                                <p class="mb-0 mt-2">Exemple:</p>
                                <img src="/images/codis.png" class="mt-1" alt="codis">
                            </div>

                            <input  id="csv-{{ $entry->id }}"
                                    name="csv"
                                    type="file"
                                    class="form-control mb-2" required>
                            <small id="csv-label-{{ $entry->id }}" class="form-text"></small>
                        </div>

                        <div class="modal-footer">
                            <button id="btn-import-{{ $entry->id }}" type="submit" class="btn btn-primary">
                                <span class="txt">{{ __('backend.session.import') }}</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    {{-- JS mínimo para todos los modales (se ejecuta una sola vez) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[id^="csv-"]').forEach(input => {
                const id  = input.id.replace('csv-','');
                const lbl = document.getElementById('csv-label-'+id);
                const frm = document.getElementById('form-import-'+id);
                const btn = document.getElementById('btn-import-'+id);
                const spn = btn.querySelector('.spinner-border');
                const txt = btn.querySelector('.txt');

                input.addEventListener('change', () => {
                    if (input.files.length) lbl.textContent = input.files[0].name;
                });

                frm.addEventListener('submit', () => {
                    txt.classList.add('d-none');
                    spn.classList.remove('d-none');
                });
            });
        });
    </script>
@endpush
