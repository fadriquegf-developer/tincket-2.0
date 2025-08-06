@if ($crud->hasAccess('list'))
	<a href="{{ url('/space/create?location='.$entry->getKey()) }}" class="btn btn-sm btn-link" data-style="zoom-in"><span class="ladda-label"><i class="la la-braille" aria-hidden="true"></i> {{ __('backend.location.add_space') }}</span></a>
@endif