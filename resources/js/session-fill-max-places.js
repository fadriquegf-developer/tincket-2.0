jQuery(document).ready(function ($) {

    var $inputMaxPlaces = $('input[name="max_places"]');
    var $selectSpace = $('select[name="space"]');

    if ($selectSpace.length && $inputMaxPlaces.length) {
        $selectSpace.on('change', function () {
            var spaceId = $(this).val();

            if (!spaceId) return;

            fetch('/space-capacity/' + spaceId)
                .then(response => response.json())
                .then(data => {
                    console.log("📦 Capacidad recibida:", data);
                    if (data && data.capacity !== undefined) {
                        $inputMaxPlaces.val(data.capacity);
                    }
                })
        });
    }
});
