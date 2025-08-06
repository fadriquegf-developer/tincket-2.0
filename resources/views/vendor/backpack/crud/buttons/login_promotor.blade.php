@if ($crud->hasAccess('list'))
	<a href="{{ $entry->getPromotorURL() }}" class="btn btn-sm btn-link" data-style="zoom-in" target="_blank"><span class="ladda-label"><i class="la la-sign-in"></i> {{ __('backend.code.login_promoter') }}</span></a>
@endif