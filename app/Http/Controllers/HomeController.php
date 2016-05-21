<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    // include guzzle laravel module for curl

    $client = new \GuzzleHttp\Client();

        try {
            $res = $client->request('GET', 'http://ipinfo.io/');
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();

            $responseBodyAsString = $response->getBody()->getContents();
        }

    // check for response ok status

    if ($res->getStatusCode() == 200) {
        $coordinates = json_decode($res->getBody());

        $coordinates = explode(',', $coordinates->loc);

        $lat = $coordinates[0];

        $lng = $coordinates[1];

    // framing a raw query as it needs haversine algorithm ( referred from internet) logic to get the nearest geo spatial from table

    $busstop = DB::select(DB::raw("SELECT a.id, ( 3959 * acos( cos( radians($lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( lat ) ) ) ) AS distance,b.busno,b.timing FROM bus_stopslist_address a join bus_stops_routes b on a.id=b.id HAVING distance < 25 ORDER BY distance,b.timing LIMIT 0 , 20"));

    // convert array object to normalized array

    $busstop = array_map(function ($object) {
        return (array) $object;
    }, $busstop);

    // flatten the multi dimensioal array to single with dot sepreration pre built fucntion of laravel

    $busstop = array_dot($busstop);

        return view('home', compact('busstop'));
    } else {
        // not a valid url to culr
    return abort(403, 'Unauthorized action.');
    }
    }
}
