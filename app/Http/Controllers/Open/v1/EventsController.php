<?php

namespace App\Http\Controllers\Open\v1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;

class EventsController extends Controller
{
    public function __construct()
    {
        // only
    }

    public function index(Request $request)
    {
        $query = Event::whereHas('next_sessions')->with(['next_sessions', 'next_sessions.space', 'next_sessions.space.location', 'taxonomies']);

        $taxonomies = $request->input('taxonomies');
        if ($taxonomies) {
            $query->whereHas('taxonomies', function (Builder $query) use ($taxonomies) {
                $query->whereIn('taxonomies.id', $taxonomies);
            })->get();
        }

        $events = $query->get();

        $events->transform(function ($item, $key) {
            // prepare sessions
            $sessions = $item->next_sessions->transform(function ($item, $key) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'max_places' => $item->max_places,
                    'count_available' => $item->getAvailableWebPositions(),
                    'starts_on' => $item->starts_on->toDateTimeString(),
                    'ends_on' => $item->ends_on->toDateTimeString(),
                    'space' => $item->space->name ?? '',
                    'location' => $item->space->location->name ?? '',
                    'rates' => collect($item->public_rates)->map(function ($rate) {
                        return [
                            'name' => $rate['name'],
                            'price' => $rate['pivot']['price'] ?? 0
                        ];
                    })
                ];
            });

            // prepare event info
            return [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'lead' =>  $item->lead,
                'description' => $item->description,
                'metadata' => $item->metadata,
                'taxonomies' => $item->taxonomies->transform(function ($rate, $key) {
                    // prepare taxonomies
                    return [
                        'name' => $rate->name,
                    ];
                }),
                'redirect_to' => $item->getRedirectTo(),
                'image' => $item->image_url,
                'publish_on' => $item->publish_on,
                'sessions' => $sessions,
            ];
        });


        return response()->json(['data' => $events], 200);
    }
}
