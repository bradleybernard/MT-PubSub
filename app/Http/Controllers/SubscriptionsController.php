<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Site;
use App\User;
use App\Subscription;
use Facebook;
use Log;
use Cookie;

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
        $json = (object) [
            'loggedIn' => false,
            'subscribed' => false,
        ];

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
        $json->loggedIn = true;

        $exists = Subscription::where('user_id', $user->id)->where('page_id', $request->page)->count();
        $json->subscribed = $exists == 1;

        $response = response()->json($json)->cookie($site->cookie, $user->oauth_id, $site->expr_mins);
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

    public function toggleSubscription(Request $request)
    {
        $json = (object) ['subscribed' => false];

        if(!$site = Site::where('endpoint', $request->site)->first()) {
            return $this->withOrigin(response()->json($json), '*');
        }

        if($request->action != "subscribe" && $request->action != "unsubscribe") {
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$id = $request->cookie($site->cookie)) {
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$user = User::where('site_id', $site->id)->where('oauth_id', $id)->first()) {
            return $this->withOrigin(response()->json($json), '*');
        }

        $subscription = Subscription::where('user_id', $user->id)->where('page_id', $request->page)->first();
        if($request->action == "subscribe") {

            if($subscription) {
                return $this->withOrigin(response()->json($json), '*');
            }

            Subscription::create([
                'user_id' => $user->id,
                'page_id' => $request->page,
            ]);

            $json->subscribed = true;

        } else if($request->action == "unsubscribe") {

            if(!$subscription) {
                return $this->withOrigin(response()->json($json), '*');
            }
            
            $subscription->delete();
            $json->subscribed = false;
        }

        return $this->withOrigin(response()->json($json), $site);
    }

    public function logoutUser(Request $request)
    {
        $json = (object) ['loggedIn' => true];

        if(!$site = Site::where('endpoint', $request->site)->first()) {
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$id = $request->cookie($site->cookie)) {
            return $this->withOrigin(response()->json($json), '*');
        }

        if(!$user = User::where('site_id', $site->id)->where('oauth_id', $id)->first()) {
            return $this->withOrigin(response()->json($json), '*');
        }

        Cookie::queue(Cookie::forget($site->cookie));
        $json->loggedIn = false;

        return $this->withOrigin(response()->json($json), $site);
    }
}
