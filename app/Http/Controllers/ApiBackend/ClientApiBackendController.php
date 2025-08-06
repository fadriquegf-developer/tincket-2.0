<?php

namespace App\Http\Controllers\ApiBackend;

use Carbon\Carbon;
use App\Models\Event;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\EventApiCollection;


class ClientApiBackendController extends \App\Http\Controllers\Controller
{

    public function subscribed(Request $request)
    {
       /*  $newsletter_field = \App\FormField::ownedByBrand()->where('config', 'like', '%newsletter%')->limit(1)->get()->first();

        if(!is_null($newsletter_field))
        {
            $list = \App\Client::ownedByBrand()->join('form_field_answers', 'form_field_answers.client_id', 'clients.id')
            ->whereNull('form_field_answers.deleted_at')
            ->where('form_field_answers.field_id', '=', $newsletter_field->id)
            ->where('form_field_answers.answer', 'like', '%newsletter%')
            ->select(['clients.email'])
            ->get();
    
            return $list;
        }  */

        return Client::ownedByBrand()->where('newsletter', 1)->select(['clients.email'])->get();

        // return response()->json(["example@example.com", "foobar@example.com"]);
        
        // this will load all clients if the fiels doesnt exists.
        /* return \App\Client::ownedByBrand()->select(['clients.email'])->get(); */
    }

    public function subscribedTo(Request $request)
    {

        //$newsletter_field = \App\FormField::ownedByBrand()->where('config', 'like', '%newsletter%')->limit(1)->get()->first();

        $interests_field = Setting::where('brand_id', get_current_brand()->id)->where('key', 'ywt.mail_interests_field_id')->first();

        if(!is_null($interests_field))
        {

            $interests = $request->input('interests');

            /* $conditions = [
                ['form_field_answers.field_id', '=', $newsletter_field->id],
                ['form_field_answers.answer', 'like', '%newsletter%'],
                ['form_field_answers.deleted_at', '=', NULL],
            ]; */

            $field = [
                ['form_field_answers.field_id', '=', $interests_field->value],
            ];
   
            $interested = [];

            foreach($interests as $value)
            {
                array_push($interested, ['form_field_answers.answer', 'like', '%'.$value.'%']);
            }

            $list = Client::ownedByBrand()
            ->where('newsletter', 1)
            ->whereHas('answers', function ($query) use ($interested, $field) {
                
                $query = $query->where($field);

                foreach($interested as $key => $interest){
                    if($key == 0){
                        $query = $query->where($interest[0], $interest[1], $interest[2]);
                    } else {
                        $query = $query->orWhere($interest[0], $interest[1], $interest[2]);
                    }
                }
            }) 
            ->select(['clients.email'])
            ->get();
    
            return $list;
        } 

        // this will load all clients if the fiels doesnt exists.
        return Client::ownedByBrand()->select(['clients.email'])->get();
        
    }

    public function getAllClientsByDate($date_start = null, $date_end = null)
    {
        $clients = Client::ownedByBrand();
        if($date_start != null){
            $clients = $clients->where('created_at', '>=' , Carbon::createFromFormat('Y-m-d', $date_start));
        }
        if($date_end != null){
            $clients = $clients->where('created_at', '<=' , Carbon::createFromFormat('Y-m-d', $date_end));
        }

        return response()->json([
            'data' => [
                'num_clients' => $clients->get()->count(),
                'desde' => $date_start,
                'hasta' => $date_end
            ]
        ]);
    }

    public function getAllEventsInfoByDate($event = null, $date_start = null, $date_end = null)
    {
        $events = Event::ownedByBrand();

        if($event != 'all'){
            $events = $events->where('id', $event);
        }

        if($date_start != null){
            $events = $events->whereHas('sessions.inscriptions', function ($q) use ($date_start) {
                    $q->where('inscriptions.created_at', '>=' , Carbon::createFromFormat('Y-m-d', $date_start));
            });
        }

        if($date_end != null){
            $events = $events->whereHas('sessions.inscriptions', function ($q) use ($date_end) {
                $q->where('inscriptions.created_at', '<=' , Carbon::createFromFormat('Y-m-d', $date_end));
            });
        }
        
        return response()->json(new EventApiCollection($events->get()));
    }

}
