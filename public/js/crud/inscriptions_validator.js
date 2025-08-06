(function ($, window, document, undefined) {
    $.fn.tincketInscriptionValidator = function (options) {
        var opts = $.extend({}, $.fn.tincketInscriptionValidator.defaults, options);
        init(this, opts);
        return this;
    };

    $.fn.tincketInscriptionValidator.defaults = {
        input: 'barcode',
        modalWrapper: 'div.modal'
    };

    // PRIVATE VARIABLES
    var form, modal, modalInstance;

    function init(object, opts) {
        form = object;
        modal = document.querySelector(opts.modalWrapper);

        if (!modal) {
            console.error("Modal wrapper not found: " + opts.modalWrapper);
            return;
        }

        // For Bootstrap 5: get or create instance
        modalInstance = bootstrap.Modal.getOrCreateInstance(modal);

        form.on('submit', validateInscription);

        modal.addEventListener('hidden.bs.modal', function () {
            var input = form.find('[name="' + opts.input + '"]');
            input.val('');
            input.focus();
        });
    }

    function validateInscription(e) {
        e.preventDefault();

        $.post($(this).attr('action'), $(this).serialize(), function (res) {
            displayResponse(res.data);
        });

        return false;
    }

    function openModal() {
        if (!modalInstance) modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
        modalInstance.show();
    }

    function closeModal() {
        if (!modalInstance) modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
        modalInstance.hide();
    }

    function prepareSuccess() {
        $(modal).find('div.result-icon').html(
            '<span class="la la-check-circle" style="font-size: 5em; color: green;"></span>'
        );
    }

    function prepareError() {
        $(modal).find('div.result-icon').html(
            '<span class="la la-times-circle" style="font-size: 5em; color: red;"></span>'
        );
    }

    function prepareDetails(response) {
        $(modal).find('div.details').html(response.details);
    }

    function updateCount(response) {
        $('#n_validated').text(response.n_validated);
    }

    function displayResponse(response) {
        if (response.success) {
            prepareSuccess();
            setTimeout(closeModal, 1000); // Este es el que falla si no hay instancia
        } else {
            prepareError();
        }

        prepareDetails(response);
        updateCount(response);
        openModal();
    }
})(jQuery, window, document);
