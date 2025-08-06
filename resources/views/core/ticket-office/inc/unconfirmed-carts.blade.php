<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">
            Carts 
        </h3>        
    </div>    
    <div class="box-body table-responsive no-padding">
        <div class="array-container form-group">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ trans('tincket/backend.ticket.sessions') }}</th>
                        <th>{{ trans('tincket/backend.ticket.packs') }}</th>
                        <th>{{ trans('tincket/backend.ticket.price') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($carts as $cart)
                    <tr>
                        <td>
                            <div class="radio">
                                <label>
                                    <input type="radio" value="{{ $cart->id }}" name="cart_id">
                                    {{ $cart->id }}
                                </label>
                            </div>
                        </td>
                        <td>
                            @foreach($cart->inscriptions as $inscription)
                            {{ $inscription->session->event->name }} {!! format_date_with_time($inscription->session->starts_on) !!}<br/>
                            @endforeach
                        </td>
                        <td>
                            @foreach($cart->groupPacks as $groupPack)
                            {{ $groupPack->pack->name }} <br/>
                            @endforeach
                        </td>
                        <td>{{ sprintf("%s€", number_format($cart->priceSold, 2)) }}</td>                        
                        <td>
                            <a href="{{ route('crud.cart.destroy', compact('cart')) }}" class="btn btn-xs btn-default" data-button-type="delete"><i class="fa fa-trash"></i> {{ trans('tincket/backend.ticket.delete') }}</a>
                        </td>                        
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>    
    </div>            
</div>