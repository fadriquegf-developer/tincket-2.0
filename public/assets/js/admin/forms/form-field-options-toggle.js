document.addEventListener("DOMContentLoaded", function () {
    const select = document.getElementById("field_type");
    const wrapper = document.querySelector('[bp-field-name="options"]');

    if (!select || !wrapper) {
        console.warn('Select o wrapper no encontrado');
        return;
    }

    const visibleTypes = ["select", "radio", "checkbox"];

    function toggleOptionsVisibility() {
        wrapper.style.display = visibleTypes.includes(select.value) ? 'block' : 'none';
    }

    toggleOptionsVisibility();
    select.addEventListener("change", toggleOptionsVisibility);
});
