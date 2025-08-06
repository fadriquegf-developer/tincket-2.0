<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('[name="enable_gift_card"]');

        if (!toggle) return;

        const toggleGiftFields = function (enabled) {
            const names = [
                'price_gift_card',
                'gift_card_text',
                'gift_card_footer_text',
                'gift_card_email_text',
                'gift_card_legal_text'
            ];

            names.forEach(function (name) {
                const wrapper = document.querySelector(`[bp-field-name="${name}"]`);
                if (wrapper) {
                    wrapper.style.display = enabled ? 'block' : 'none';
                }
            });
        };

        // Primera carga
        toggleGiftFields(parseInt(toggle.value) === 1);

        // Al cambiar
        toggle.addEventListener('change', function () {
            toggleGiftFields(parseInt(this.value) === 1);
        });
    });
</script>
