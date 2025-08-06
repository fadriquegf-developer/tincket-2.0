@if ($entry->gift_cards->isNotEmpty())
    <div class="box">
        <div class="box-header">
            <i class="fa fa-gift" aria-hidden="true"></i>
            <h3 class="box-title">{{ __('backend.gift_card.gift_cards') }}</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover">

                <tbody>
                    <tr>
                        <th>{{ __('backend.cart.inc.event') }}</th>
                        <th>{{ __('backend.gift_card.code') }}</th>
                    </tr>
                    @foreach ($entry->gift_cards as $gift_card)
                        <tr>
                            <td>{{ $gift_card->event->name }}</td>
                            <td>{{ $gift_card->code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
