<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\FormField;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientsExtraExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize
{
    use Exportable;

    public function query()
    {
        return Client::query();
    }

    public function headings(): array
    {

        $headings = [
            '#',
            __('backend.client.name'),
            __('backend.client.surname'),
            __('backend.client.email'),
            __('backend.client.phone'),
            __('backend.client.mobile_phone'),
            __('backend.client.created_at'),
            __('backend.client.locale'),
            'Token',
            __('backend.client.num_session'),
            __('backend.client.newsletter'),
        ];

        foreach (FormField::get() as $label_answer) {
            if (!in_array($label_answer->label, $headings)) {
                array_push($headings, $label_answer->label);
            }
        }
        //dd(FormField::get());
        return $headings;
    }

    /**
     * @var Client $client
     */
    public function map($client): array
    {
        $headings = [
            '#',
            __('backend.client.name'),
            __('backend.client.surname'),
            __('backend.client.email'),
            __('backend.client.phone'),
            __('backend.client.mobile_phone'),
            __('backend.client.created_at'),
            __('backend.client.locale'),
            'Token',
            __('backend.client.num_session'),
            __('backend.client.newsletter'),
        ];

        $extra_headings = [];
        foreach (FormField::get() as $label_answer) {
            if (!in_array($label_answer->label, $headings)) {
                array_push($extra_headings, $label_answer->label);
            }
        }

        $datos_cliente = [
            $client->id,
            $client->name,
            $client->surname,
            $client->email,
            $client->phone,
            $client->mobile_phone,
            $client->created_at,
            $client->locale,
            $client->token_confirm_newsletter,
            $client->getNumSessions(),
            $client->newsletter
        ];

        foreach ($extra_headings as $head) {
            $attr = false;
        
            foreach ($client->answers as $answer) {
                if ($answer->form_field && $answer->form_field->label == $head) {
                    $attr = true;
                    array_push($datos_cliente, $answer->answer);
                    break;
                }
            }
        
            if (!$attr) {
                array_push($datos_cliente, '');
            }
        }
        

        return $datos_cliente;
    }

}
