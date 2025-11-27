<?php

namespace App\Http\Controllers\ApiBackend;

use Illuminate\Http\Request;
use App\Models\GiftCard;

/**
 * Description of GiftCardApiBackendController
 *
 */
class GiftCardApiBackendController extends \App\Http\Controllers\Controller
{

    public function validateCode(Request $request)
    {
        $card = GiftCard::ownedByPartneship()
            ->with('event.next_sessions.space.location')
            ->where('code', $request->input('code'))
            ->doesntHave('inscription')
            ->first();

        $response = ["success" => false, "data" => []];
        if ($card) {
            $response["success"] = true;
            $response["event"] = $card->event;
        }

        return response()->json($response);
    }
}
