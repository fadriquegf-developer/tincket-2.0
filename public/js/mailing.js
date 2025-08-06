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

        $(".interest-checkbox").each(function () {
            if ($(this).is(":checked")) {
                interests.push($(this).data("interest"));
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
            emails_target.val(data.map((user) => user.email));
            ready(button.find(".la"));
        });
    });
});
