@if ($crud->hasAccess('list') && $entry->promotor)
	<a href="/code/info-promotor/{{$entry->promotor->id}}" class="btn btn-sm btn-link" data-style="zoom-in"><span class="ladda-label"><i class="la la-info-circle"></i> {{ __('backend.code.info_promotor') }}</span></a>
@endif