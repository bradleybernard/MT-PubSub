<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Site;

class SubscriptionsController extends Controller
{
    public function checkUser(Request $request)
    {
        if(!$siteExists = Site::where('endpoint', $request->site)->first()) {
            return response()->json(['loggedIn' => false]);
        }

        return response()->json(['loggedIn' => true]);
    }
}
