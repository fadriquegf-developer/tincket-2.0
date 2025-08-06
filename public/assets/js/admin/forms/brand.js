crud.field('capability_id').onChange(function (field) {
    crud.field('parent_id').show(field.value == 3);
}).change();
