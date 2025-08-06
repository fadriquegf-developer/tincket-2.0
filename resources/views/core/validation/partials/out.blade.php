<?php
/*
 * Chech method is a JSON response. This response contains some HTML to be
 * rendered to provide information about validating process.
 * 
 * This file contains this HTML that will be returned through JSON object
 */
?>

@if(isset($data['success']) && $data['success'])
@include('core.validation.inc._session_info', ['session' => $data['inscription']->session])
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-success">                        
                {{ trans('tincket/backend.validation.inscription_is_valid_out') }}
        </div>
    </div>                    
</div>
@else
    @if(isset($data['inscription']))    
        @include('core.validation.inc._session_info', ['session' => $data['inscription']->session])
        <?php
            $session = $data['inscription']->session;                    
            if($session->id != request()->get('session_id'))
                $message = trans('tincket/backend.validation.errors.incorrect_session');
            if($data['inscription']->checked_at)
                $message =  trans(
                    'tincket/backend.validation.errors.already_scanned_out',
                    [
                        'date' => $data['inscription']->checked_at->format('d/m/Y'),
                        'time' => $data['inscription']->checked_at->format('H:i')
                    ]
                );
        ?>
    @else
        <?php $message = trans('tincket/backend.validation.errors.not_found'); ?>
    @endif
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-danger">                        
                {{ $message ?? "" }}
            </div>
        </div>
    </div>           
@endif


