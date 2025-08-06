<form 
  action="{{ route('cart.restore', ['id' => $entry->getKey()]) }}" 
  method="POST" 
  style="display:inline-block"
>
    @csrf
    <button type="submit" class="btn btn-sm btn-link">
        <i class="la la-undo mx-1"></i> Recuperar
    </button>
</form>
