<?php

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\RaceForm;
use Carbon\Carbon;
use App\Models\Dog;

class RaceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dogs = Dog::orderBy('dog_name', 'ASC')
                ->distinct('dog_name')
                ->get();

        $distance = RaceForm::pluck('distance')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
        return view('race.show', compact('dogs', 'distance'));
    }

    public function racing_dog(Request $request)
    {
        $distance = RaceForm::where('dog_id', '=', $request->dog_id)
                ->pluck('distance')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        $dog_name = $request->short_code;
        return view('dog', compact('distance', 'dog_name'));
    }

    public function show($venue, Request $request) 
    {
        $dogs = RaceForm::select('race_forms.*', 'dogs.dog_name')
            ->join('dogs', 'race_forms.dog_id', '=', 'dogs.dog_id')
            ->where('dogs.venue_code', $venue)
            ->where('dogs.race_id', $request->get('raceID'))
            ->whereDate('dogs.date', $request->get('date'))
            ->orderBy('dogs.dog_name', 'ASC')
            ->get();

        $distance = RaceForm::select('race_forms.*', 'dogs.dog_name')
                    ->join('dogs', 'race_forms.dog_id', '=', 'dogs.dog_id')
                    ->where('dogs.venue_code', $venue)
                    ->where('dogs.race_id', $request->get('raceID'))
                    ->whereDate('dogs.date', $request->get('date'))
                    ->pluck('race_forms.distance')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
        return view('race.index', compact('dogs', 'distance', 'venue'));
    }

    public function replaceSpaceWithHyphen($str) {
        // Check if the string contains a space
        if (strpos($str, ' ') !== false) {
            // Replace spaces with hyphens and return the modified string
            return str_replace(' ', '-', $str);
        } else {
            // If no space is found, return the original string
            return $str;
        }
    }

    public function dog_datatables(Request $request)
    {
        if($request->ajax()) {
            $dogsArray = Dog::where('venue_code', $request->get('venue'))
                ->where('race_id', $request->get('raceID'))
                ->whereDate('date', $request->get('date_code'))
                ->pluck('dog_id')
                ->toArray();
            if($request->get('unique_dog') == "on") {
                $racing = RaceForm::select('race_forms.*');

                if (!empty($request->get('distance'))) {
                    $racing->join(
                        \DB::raw('(SELECT dog_id, MAX(CAST(time2 AS DECIMAL(10, 2))) AS highest_time FROM race_forms WHERE distance = \''.$request->get('distance').'\' GROUP BY dog_id) as subq'),
                        function ($join) {
                            $join->on('race_forms.dog_id', '=', 'subq.dog_id')
                                ->on(\DB::raw('CAST(race_forms.time2 AS DECIMAL(10, 2))'), '=', 'subq.highest_time');
                        }
                    );
                }else {
                    $racing->join(
                        \DB::raw('(SELECT dog_id, MAX(CAST(time2 AS DECIMAL(10, 2))) AS highest_time FROM race_forms GROUP BY dog_id) as subq'),
                        function ($join) {
                            $join->on('race_forms.dog_id', '=', 'subq.dog_id')
                                ->on(\DB::raw('CAST(race_forms.time2 AS DECIMAL(10, 2))'), '=', 'subq.highest_time');
                        }
                    );
                }
                
                $racing->orderBy('race_forms.time2', $request->get('time_order'))
                       ->whereIn('race_forms.dog_id', $dogsArray)
                       ->latest();

                return Datatables::of($racing)
                    ->editColumn('dpg_name', function(RaceForm $race) {
                        $dog = Dog::find($race->dog_id);
                        return $dog->dog_name;
                    })
                    ->filter(function ($instance) use ($request) {
                        if(!empty($request->get('dogs'))){
                            $instance->whereIn('race_forms.dog_id', explode(",", $request->get('dogs')));
                        }
    
                        if(!empty($request->get('datepicker'))) {
                            switch ($request->get('datepicker')) {
                                case 'all':
                                    break;
                                
                                default:
                                   $date = explode(" - ", $request->get('datepicker'));
                                   $startDate = Carbon::parse($date[0])->format('Y-m-d');
                                    $endDate = Carbon::parse($date[1])->format('Y-m-d');
                                    $instance->whereBetween("date", [$startDate, $endDate]);
                                    break;
                            }
                        }
    
                        if(!empty($request->get('time_order'))) {
    
                        }
    
                        if(!empty($request->get('orderA'))) {
                            switch ($request->get('orderA')) {
                                case 'ASC':
                                    $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'ASC');
                                    break;
                                
                                default:
                                    $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'DESC');
                                    break;
                            }
                        }
    
                        if ($keyword = $request->input('search.value')) {
                            $instance->whereHas('dogs', function ($query) use ($keyword) {
                                $query->where('dog_name', 'LIKE', '%' . $keyword . '%');
                            });
                        }
                    })
                    ->rawColumns(['dpg_name'])
                    ->toJson();
            }else {
                $racing = RaceForm::whereIn('dog_id', $dogsArray)
                    ->orderBy(\DB::raw('CAST(race_forms.time2 AS DECIMAL(10, 2))'), $request->get('time_order'))
                    ->latest();

                return Datatables::of($racing)
                    ->editColumn('dpg_name', function(RaceForm $race) {
                        $dog = Dog::find($race->dog_id);
                        return $dog->dog_name;
                    })
                    ->filter(function ($instance) use ($request) {
                        if(!empty($request->get('dogs'))){
                            $instance->whereIn('dog_id', explode(",", $request->get('dogs')));
                        }
    
                        if(!empty($request->get('distance'))) {
                            $instance->where('distance', '=', $request->get('distance'));
                        }
    
                        if(!empty($request->get('datepicker'))) {
                            switch ($request->get('datepicker')) {
                                case 'all':
                                    break;
                                
                                default:
                                   $date = explode(" - ", $request->get('datepicker'));
                                   $startDate = Carbon::parse($date[0])->format('Y-m-d');
                                    $endDate = Carbon::parse($date[1])->format('Y-m-d');
                                    $instance->whereBetween("date", [$startDate, $endDate]);
                                    break;
                            }
                        }
    
                        if(!empty($request->get('time_order'))) {
    
                        }
    
                        if(!empty($request->get('orderA'))) {
                            switch ($request->get('orderA')) {
                                case 'ASC':
                                    $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'ASC');
                                    break;
                                
                                default:
                                    $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'DESC');
                                    break;
                            }
                        }
    
                        if ($keyword = $request->input('search.value')) {
                            $instance->whereHas('dogs', function ($query) use ($keyword) {
                                $query->where('dog_name', 'LIKE', '%' . $keyword . '%');
                            });
                        }
                    })
                    ->rawColumns(['dpg_name'])
                    ->toJson();
            }
        }
    }

    public function form_datatables(Request $request)
    {
        if($request->ajax()) { 
            if(!empty($request->get('unique_dog')) && $request->get('unique_dog') == "on")  {
                $racing = RaceForm::select('race_forms.*');

                if (!empty($request->get('distance'))) {
                    $racing->join(
                        \DB::raw('(SELECT dog_id, MAX(CAST(time2 AS DECIMAL(10, 2))) AS highest_time FROM race_forms WHERE distance = \''.$request->get('distance').'\' GROUP BY dog_id) as subq'),
                        function ($join) {
                            $join->on('race_forms.dog_id', '=', 'subq.dog_id')
                                ->on(\DB::raw('CAST(race_forms.time2 AS DECIMAL(10, 2))'), '=', 'subq.highest_time');
                        }
                    );
                }else {
                    $racing->join(
                        \DB::raw('(SELECT dog_id, MAX(CAST(time2 AS DECIMAL(10, 2))) AS highest_time FROM race_forms GROUP BY dog_id) as subq'),
                        function ($join) {
                            $join->on('race_forms.dog_id', '=', 'subq.dog_id')
                                ->on(\DB::raw('CAST(race_forms.time2 AS DECIMAL(10, 2))'), '=', 'subq.highest_time');
                        }
                    );
                }
                
                $racing->orderBy('race_forms.time2', $request->get('time_order'))
                       ->latest();

                return Datatables::of($racing)
                ->editColumn('dpg_name', function(RaceForm $race) {
                    $dog = Dog::find($race->dog_id);
                    return $dog->dog_name;
                })
                ->filter(function ($instance) use ($request) {
                    if(!empty($request->get('dogs'))){
                        $instance->whereIn('race_forms.dog_id', explode(",", $request->get('dogs')));
                    }

                    if(!empty($request->get('distance'))) {
                        $instance->where('distance', '=', $request->get('distance'));
                    }

                    if(!empty($request->get('raceID'))) {
                        $instance->where('race_id', '=', $request->get('raceID'));
                    }

                    if(!empty($request->get('venux'))) {
                        $instance->where('venue', '=', $request->get('venux'));
                    }

                    if(!empty($request->get('datepicker'))) {
                        switch ($request->get('datepicker')) {
                            case 'all':
                                break;
                            
                            default:
                               $date = explode(" - ", $request->get('datepicker'));
                               $startDate = Carbon::parse($date[0])->format('Y-m-d');
                                $endDate = Carbon::parse($date[1])->format('Y-m-d');
                                $instance->whereBetween("date", [$startDate, $endDate]);
                                break;
                        }
                    }

                    if(!empty($request->get('orderA'))) {
                        switch ($request->get('orderA')) {
                            case 'ASC':
                                $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'ASC');
                                break;
                            
                            default:
                                $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'DESC');
                                break;
                        }
                    }

                    if ($keyword = $request->input('search.value')) {
                        $instance->whereHas('dogs', function ($query) use ($keyword) {
                            $query->where('dog_name', 'LIKE', '%' . $keyword . '%');
                        });
                    }
                })
                ->rawColumns(['dpg_name'])
                ->toJson();
            } else {
                $racing = RaceForm::orderBy(\DB::raw('CAST(race_forms.time2 AS DECIMAL(10, 2))'), $request->get('time_order'))
                        ->latest();
                return Datatables::of($racing)
                ->editColumn('dpg_name', function(RaceForm $race) {
                    $dog = Dog::find($race->dog_id);
                    return $dog->dog_name;
                })
                ->filter(function ($instance) use ($request) {
                    if(!empty($request->get('dogs'))){
                        $instance->whereIn('dog_id', explode(",", $request->get('dogs')));
                    }

                    if(!empty($request->get('distance'))) {
                        $instance->where('distance', '=', $request->get('distance'));
                    }

                    if(!empty($request->get('raceID'))) {
                        $instance->where('race_id', '=', $request->get('raceID'));
                    }

                    if(!empty($request->get('venux'))) {
                        $instance->where('venue', '=', $request->get('venux'));
                    }

                    if(!empty($request->get('datepicker'))) {
                        switch ($request->get('datepicker')) {
                            case 'all':
                                break;
                            
                            default:
                               $date = explode(" - ", $request->get('datepicker'));
                               $startDate = Carbon::parse($date[0])->format('Y-m-d');
                                $endDate = Carbon::parse($date[1])->format('Y-m-d');
                                $instance->whereBetween("date", [$startDate, $endDate]);
                                break;
                        }
                    }

                    if(!empty($request->get('orderA'))) {
                        switch ($request->get('orderA')) {
                            case 'ASC':
                                $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'ASC');
                                break;
                            
                            default:
                                $instance->orderBy(\DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), 'DESC');
                                break;
                        }
                    }

                    if ($keyword = $request->input('search.value')) {
                        $instance->whereHas('dogs', function ($query) use ($keyword) {
                            $query->where('dog_name', 'LIKE', '%' . $keyword . '%');
                        });
                    }
                })
                ->rawColumns(['dpg_name'])
                ->toJson();
            }   
        }
    }

    public function datatables(Request $request)
    {        
        if($request->ajax())
        {
            $racing = Race::orderBy('id', 'ASC')->latest();
            return Datatables::of($racing)
                ->addColumn('race', function (Race $race) use ($request) {
                    $data = json_decode($race->race);
                    $output = '';
                    foreach ($data as $key => $raceItem) {
                        $segment = explode("/", $raceItem->link);
                        $timestamp = json_decode($race->time)[$key];
                        $carbonTimestamp = Carbon::parse($timestamp, 'UTC');
                        $carbonTimestamp->setTimezone($request->get('zone_time'));
                        $formattedTime = $carbonTimestamp->format('H.i');
                        $output .= '&nbsp;&nbsp;&nbsp;<a target="_blank" href="https://dogpower.pro/racing/' . strtolower($segment[2]) . '?date=' . $raceItem->date . '&raceID=' . $raceItem->raceID . '&short_code=' . $raceItem->meeting_name . '" class="btn btn-primary btn-sm text-center">' . $formattedTime . '</a>';
                    }
                    return $output;
                })
                ->filter(function ($instance) use ($request) {
                    if(!empty($request->get('datepicker'))){
                        $instance->whereDate("date", '=', Carbon::parse($request->get('datepicker')));
                    }

                    if ($keyword = $request->input('search.value')) {
                        $instance->where('venue', 'LIKE', '%' . $keyword . '%');
                    }
                })
                ->rawColumns(['race'])
                ->toJson();
        }
    }
}
