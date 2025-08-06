@push('after_scripts')
    <div class="modal fade" id="cloneSessionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clonar múltiples sesiones</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="cloneSessionForm">
                        <input type="hidden" name="session_id" id="cloneSessionId">

                        <div class="form-group">
                            <label>Número de sesiones a crear</label>
                            <input type="number" id="numSessions" min="1" class="form-control" required>
                        </div>

                        <div id="sessionDatesContainer"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="submitCloneSessions()">Clonar sesiones</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCloneModal(sessionId) {
            $('#cloneSessionId').val(sessionId);
            $('#numSessions').val(1);
            generateSessionInputs(1);
            $('#cloneSessionModal').modal('show');
        }

        $(document).on('input', '#numSessions', function () {
            let count = $(this).val();
            generateSessionInputs(count);
        });

        function generateSessionInputs(count) {
            let container = $('#sessionDatesContainer');
            container.empty();

            for (let i = 1; i <= count; i++) {
                container.append(`
                    <div class="card mb-3 p-3 bg-light">
                        <h6>Sesión #${i}</h6>
                        <div class="form-row mb-2">
                            <div class="col-md-6">
                                <label>Inicio de la sesión</label>
                                <input type="datetime-local" name="sessions[${i}][start]" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Fin de la sesión</label>
                                <input type="datetime-local" name="sessions[${i}][end]" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <label>Inicio de inscripciones</label>
                                <input type="datetime-local" name="sessions[${i}][inscription_starts_on]" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Fin de inscripciones</label>
                                <input type="datetime-local" name="sessions[${i}][inscription_ends_on]" class="form-control" required>
                            </div>
                        </div>
                    </div>
                `);
            }
        }

        function submitCloneSessions() {
            const form = $('#cloneSessionForm')[0];
            const sessions = [];
            const count = $('#numSessions').val();

            let isValid = true;
            let errorMessage = '';

            for (let i = 1; i <= count; i++) {
                const start = form[`sessions[${i}][start]`].value;
                const end = form[`sessions[${i}][end]`].value;
                const inscriptionStart = form[`sessions[${i}][inscription_starts_on]`].value;
                const inscriptionEnd = form[`sessions[${i}][inscription_ends_on]`].value;

                if (!start || !end || !inscriptionStart || !inscriptionEnd) {
                    isValid = false;
                    errorMessage = `Todos los campos de la sesión #${i} son obligatorios.`;
                    break;
                }

                if (new Date(start) >= new Date(end)) {
                    isValid = false;
                    errorMessage = `En la sesión #${i}, la fecha de fin debe ser posterior a la de inicio.`;
                    break;
                }

                if (new Date(inscriptionStart) >= new Date(inscriptionEnd)) {
                    isValid = false;
                    errorMessage = `En la sesión #${i}, el fin de inscripciones debe ser posterior al inicio de inscripciones.`;
                    break;
                }

                if (new Date(inscriptionEnd) > new Date(start)) {
                    isValid = false;
                    errorMessage = `En la sesión #${i}, el fin de inscripciones debe ser antes del inicio de la sesión.`;
                    break;
                }

                sessions.push({
                    start,
                    end,
                    inscription_starts_on: inscriptionStart,
                    inscription_ends_on: inscriptionEnd
                });
            }

            if (!isValid) {
                new Noty({ text: errorMessage, type: 'error' }).show();
                return;
            }

            const sessionId = $('#cloneSessionId').val();

            $.ajax({
                url: '{{ route('session.clone') }}',
                method: 'POST',
                data: {
                    session_id: sessionId,
                    sessions: sessions,
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    $('#cloneSessionModal').modal('hide');
                    new Noty({ text: 'Sesiones clonadas correctamente', type: 'success' }).show();
                    setTimeout(() => location.reload(), 1000);
                },
                error: function () {
                    new Noty({ text: 'Error al clonar las sesiones.', type: 'error' }).show();
                }
            });
        }

    </script>
@endpush