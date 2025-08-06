@if(request()->old())
<?php
    $old_data = [
        "client" => [
            "email" => request()->old('client_email'),
            "firstname" => request()->old('client_firstname'),
            "lastname" => request()->old('client_lastname')
        ],
        "inscriptions" => []
    ];
    
    foreach(request()->old('session_id', []) as $key => $value)
    {
        $old_data['inscriptions'][] = [
            'session_id' => (request()->old('session_id')[$key]) ?? '',
            'rate_id' => (request()->old('rate_id')[$key]) ?? '',
            'quantity' => (request()->old('quantity')[$key]) ?? 0,
        ];
    }
    
    $this->old_data = $old_data;
?>
@endif