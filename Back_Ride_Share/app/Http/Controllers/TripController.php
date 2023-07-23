<?php

namespace App\Http\Controllers;

use App\Events\TripAccepted;
use App\Events\TripEnded;
use App\Events\TripLocationUpdated;
use App\Events\TripStarted;
use App\Models\Trip;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function store(Request $request)
    {

        //validation
        $request->validate([
            'orgin' => 'required',
            'destination' => 'required',
            'destination_name' => 'required'
        ]);

        // create the trip
        return $request->user()->trips()->create($request->only([
            'orgin',
            'destination',
            'destination_name'
        ]));
    }

    public function show(Request $request, Trip $trip)
    {

        // is the trip is associated with authenticated of this user

        if ($trip->user->id == $request->user()->id) {

            return $trip;
        }

        if ($trip->driver() && $request->user()->driver) {

            if ($trip->driver->id == $request->user()->driver->id) {

                return $trip;
            }
        }


        return response()->json(['massege' => "Can't find this trip"], 404);
    }

    public function accept(Request $request, Trip $trip)
    {
        // driver start the trip

        $request->validate([
            'driver_location' => 'required'

        ]);

        $trip->update([
            'driver_id' => $request->user()->id,
            'driver_location' => $request->driver_location

        ]);

        $trip->load('driver.user');

        TripAccepted::dispatch($trip, $request->user());

        return $trip;
    }

    public function start(Request $request, Trip $trip)
    {
        // driver has taking a passenger to their destination

        $trip->update([
            'is_started' => true
        ]);

        $trip->load('driver.user');

        TripStarted::dispatch($trip, $request->user());


        return $trip;
    }

    public function end(Request $request, Trip $trip)
    {
        // driver end the trip

        $trip->update([
            'is_complete' => true
        ]);

        $trip->load('driver.user');

        TripEnded::dispatch($trip, $request->user());


        return $trip;
    }

    public function location(Request $request, Trip $trip)
    {
        // updated the driver location

        $request->validate([
            'driver_location' => 'required'

        ]);


        $trip->update([
            'driver_location' => $request->driver_location

        ]);

        $trip->load('driver.user');

        TripLocationUpdated::dispatch($trip, $request->user());


        return $trip;
    }
}
