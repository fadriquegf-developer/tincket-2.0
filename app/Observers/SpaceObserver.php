<?php

namespace App\Observers;

use App\Models\Space;
use App\Models\Zone;

class SpaceObserver
{
    public function saved(Space $space)
    {
        // Si tiene SVG pero no tiene zonas, crear una zona "General"
        if ($space->svg_path && !$space->zones()->exists()) {
            Zone::create([
                'space_id' => $space->id,
                'name' => 'General',
                'color' => '#3498db'
            ]);
        }
    }
}
