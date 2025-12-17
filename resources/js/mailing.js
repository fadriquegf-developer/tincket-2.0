jQuery(document).ready(function ($) {
    var loading = function (target) {
        target.removeClass("la-cloud-upload").addClass("la la-spin la-spinner");
    };

    var ready = function (target) {
        target.removeClass("la-spin la-spinner").addClass("la la-cloud-upload");
    };

    $(".btn-load-emails").click(function () {

        var button = $(this);
        var emails_target = $($(this).data("emails-target"));
        var interests = [];

        loading(button.find(".la"));

        // Buscar todos los checkboxes de intereses
        var checkboxes = $(".interest-checkbox, input[name^='interests[']");

        checkboxes.each(function (index) {
            if ($(this).is(":checked")) {
                var interest = $(this).data("interest");

                // Si no tiene data-interest, intentar extraer del name
                if (!interest) {
                    var name = $(this).attr("name");
                    var match = name.match(/interests\[([^\]]+)\]/);
                    if (match) {
                        interest = match[1];
                    }
                }

                if (interest) {
                    interests.push(interest);
                }
            }
        });

        var endpoint = $(this).data("emails-api");

        if (interests.length > 0) {
            endpoint =
                $(this).data("emails-for-api") +
                "?interests[]=" +
                interests.join("&interests[]=");
        }

        $.get(endpoint, function (data) {
            var emails = data.map((user) => user.email).join(",");
            emails_target.val(emails);

            ready(button.find(".la"));
        }).fail(function (xhr, status, error) {
            console.error("[MAILING DEBUG] Error en petición:", {
                status: status,
                error: error,
                response: xhr.responseText,
            });
            ready(button.find(".la"));
            alert("Error al cargar emails: " + error);
        });
    });
});
