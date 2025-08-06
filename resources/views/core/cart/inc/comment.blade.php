<div class="card h-100 ">
    <div class="card-header">
        <h3 class="card-title">
            {{ __('backend.cart.inc.comment') }}
        </h3>
    </div>
    <div class="card-body d-flex flex-column">
        <textarea name="comment" class="form-control flex-grow-1" maxlength="255">{{ $entry->comment }}</textarea>
    </div>
</div>
