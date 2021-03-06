<?php

namespace App\Http\Controllers;

use App\LockerKey;
use App\Models\Client;
use App\Models\Locker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\LockerForgotKeyMail;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\LockerKeyException;
use App\Http\Resources\LockerResource;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\LockerStoreRequest;
use App\Http\Requests\LockerUnlockRequest;
use App\Http\Resources\LockerClaimResource;
use App\Exceptions\NotYetImplementedException;

class LockerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lockers = Locker::with('claims.client')->get();
        return LockerResource::collection($lockers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LockerStoreRequest $request)
    {
        $guid = $request->input('guid', Str::random(8));
        $locker = Locker::create(['guid' => $guid]);
        return new LockerResource($locker);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $lockerGuid
     * @return \Illuminate\Http\Response
     */
    public function show(string $lockerGuid)
    {
        $locker = Locker::where('guid', $lockerGuid)
            ->with('claims.client')
            ->firstOrFail()
        ;
        return new LockerResource($locker);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $lockerGuid
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(string $lockerGuid, LockerStoreRequest $request)
    {
        $locker = Locker::where('guid', $lockerGuid)->firstOrFail();
        $locker->update($request->only('guid'));

        return new LockerResource($locker);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $lockerGuid
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $lockerGuid)
    {
        $locker = Locker::where('guid', $lockerGuid)->firstOrFail();
        $locker->delete();

        return response()->json([
            'message' => 'OK.',
        ]);
    }

    public function unlock(string $lockerGuid, LockerUnlockRequest $request)
    {
        $locker = Locker::where('guid', $lockerGuid)->firstOrFail();

        $lockerClaim = $locker->activeClaim();

        $lockerKey = new LockerKey($request->get('key'));

        try {
            $lockerKey->attempt($lockerClaim);
        } catch (LockerKeyException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        $exitCode = Artisan::call('locker:unlock', [
            'lockerGuid' => $locker->guid,
        ]);

        if ($exitCode !== 0) {
            return response()->json([
                'message' => 'Oops. Something wrong happened at our side.',
            ], 500);
        }

        return response()->json([
            'message' => 'OK.',
            'data' => [
                'claim' => new LockerClaimResource($lockerClaim),
            ],
        ]);
    }

    public function forgotKey(string $lockerGuid)
    {
        $locker = Locker::where('guid', $lockerGuid)->firstOrFail();
        $activeClaim = $locker->activeClaim();

        $activeClaim->setup_token = Str::random();
        $activeClaim->save();

        $client = $activeClaim->client;
        $mail = new LockerForgotKeyMail($activeClaim);
        Mail::to($client->email)->send($mail);

        return response()->json([
            'message' => 'OK.',
        ]);
    }
}
