<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Site;
use App\User;
use App\Subscription;
use Facebook;
use Log;

class SubscriptionsController extends Controller
{
    private function withOrigin($response, $site)
    {
        if($site === '*') {
            return $response->withHeaders([
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        return $response->withHeaders([
            'Access-Control-Allow-Origin' => rtrim($site->endpoint, '/'),
            'Access-Control-Allow-Credentials' => 'true',
        ]);
    }

    public function loginUser(Request $request, \SammyK\LaravelFacebookSdk\LaravelFacebookSdk $fb)
    {
        $json = (object) ['success' => false];

        if(!$site = Site::where('endpoint', $request->site)->first()) {
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$request->has('token')) {
            return $this->withOrigin(response()->json($json), '*');
        }

        try {
            $response = $fb->get('/me?fields=id,name,email', $request->token);
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            Log::error($e->getMessage());
            return $this->withOrigin(response()->json($json), '*');
        }

        $node = $response->getGraphUser();
        if(!$node->getEmail() || !$node->getName()) {
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$user = User::where('site_id', $site->id)->where('oauth_id', $node->getId())->first()) {
            $user = new User;
        }

        $user->fill([
            'site_id' => $site->id, 
            'oauth_token' => $request->token,
            'oauth_id'  => $node->getId(),
            'name'  => $node->getName(),
            'email' => $node->getEmail(),
        ])->save();

        $response = response()->json($json)->cookie($site->cookie, $user->oauth_id, $site->expr_mins);
        $json->success = true;

        return $this->withOrigin($response, $site);
    }

    public function checkUser(Request $request)
    {
        $json = (object) [
            'loggedIn' => false,
            'subscribed' => false,
        ];

        if(!$site = Site::where('endpoint', $request->site)->first()) {
            // Wont work in browser since CORS disabled for this response
            // !! Can go back to just response()->json($json);
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$request->cookie($site->cookie)) {
            return $this->withOrigin(response()->json($json), $site);
        }

        Log::info("Got here");

        $token = $request->cookie($site->cookie);
        if(!$user = User::where('oauth_id', $token)->where('site_id', $site->id)->first()) {
            return $this->withOrigin(response()->json($json), $site);
        } else {
            $json->loggedIn = true;
        }

        if(!$subscription = Subscription::where('user_id', $user->id)->where('page_id', $request->page)->first()) {
            return $this->withOrigin(response()->json($json), $site);
        } else {
            $json->subscribed = true;
        }

        return $this->withOrigin(response()->json($json), $site);
    }
}
