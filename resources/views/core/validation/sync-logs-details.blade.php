@php
    $response = json_decode($entry->response);
@endphp
<div class="mt-10 mb-10 ps-10 pe-10 pt-10 pb-10">
    <div class="row text-left">
        <div class="col-xs-12">
            @foreach ($response as $sessions)
                @foreach ($sessions as $id => $inscriptions)
                    @php
                        $session = \App\Models\Session::ownedByBrand()->find($id);
                    @endphp

                    <b class="mb-2"">{{ $session->name ?? $id }}</b>
                    {{ isset($session->starts_on) ? $session->starts_on->format('d-m-Y H:i') : '???' }}
                    <div class="row">
                        @foreach ($inscriptions as $inscription)
                            <div class="col-xs-2 mb-1">
                                {{ $inscription->barcode }}
                            </div>
                            <div class="col-xs-10">
                                <span class="badge {{ $entry->getColorInscription($inscription) }} mb-2">
                                    {{ $entry->getTextInscription($inscription) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <hr style="text-align:left;margin-left:0">
                @endforeach
            @endforeach
        </div>
    </div>
</div>
<div class="clearfix"></div>
