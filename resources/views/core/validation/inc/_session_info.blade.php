<div class="row">              
    <div class="col-xs-6">                        
        <label>{{ __('backend.validation.session') }}</label>
    </div>                    
    <div class="col-xs-6">                        
        {{ $session->event->name }}
        @if($session->name)                        
        {{ $session->name }}
        @endif
    </div>  
</div>
<div class="row">
    <div class="col-xs-6">                        
        <label>{{ __('backend.validation.starts_on') }}</label>
    </div>                    
    <div class="col-xs-6">                        
        {{ $session->starts_on->format('d/m/Y H:i') }}
    </div>                    
</div>
<div class="row">
    <div class="col-xs-6">                        
        <label>{{ __('backend.validation.space') }}</label>
    </div>                    
    <div class="col-xs-6">                        
        {{ $session->space->name }}
    </div>                    
</div>