<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Http;
use App\Models\Dog;
use App\Models\RaceForm;
use App\Models\ImportedDog;
use Carbon\Carbon;
use DB;

use Illuminate\Console\Command;

class FormCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'form:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function __get_offset($val) {
        if ($val >= 800 && $val <= 899) {
            return 0.77;
        } elseif ($val >= 900 && $val <= 999) {
            return 0.87;
        } elseif ($val >= 1000 && $val <= 1099) {
            return 0.97;
        } elseif ($val >= 1100 && $val <= 1199) {
            return 1.02;
        }
        return 0;
    }
    
    private function __process_time2($record, $time) {
        $distance = $record;
    
        // First round up distance to the nearest 10
        if ($distance % 10 >= 5) {
            $distance += 10 - ($distance % 10);
        } else {
            $distance -= $distance % 10;
        }
    
        // Then round off again to the nearest 100
        if ($distance % 100 >= 50) {
            $reminder_distance = 100 - ($distance % 100);
            $distance += $reminder_distance;
            $sign = -1.0;
        } else {
            $reminder_distance = $distance % 100;
            $distance -= $reminder_distance;
            $sign = 1.0;
        }
    
        $offset = $this->__get_offset($distance);
        $recordArray = [];
        $recordArray['distance'] = $distance;
        return $recordArray;
    }    

    public function getTImeTwo($dist, $distance, $time)
    {
        if ($dist > $distance) {
            return  floatval($time) - ((floatval($dist) - floatval($distance)) * 0.05);
        } else {
            return  floatval($time) + ((floatval($distance) - floatval($dist)) * 0.05);
        }
    }

    public function scrapeForm()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        $forms = Dog::where('imported', 0)
            // ->where(function($query) use ($today, $yesterday) {
            //     $query->whereDate('created_at', $today)
            //           ->orWhereDate('created_at', $yesterday);
            // })
            ->limit(15)
            ->get();
        
        $batchLink = [];
        foreach ($forms as $key => $form) {
            $batchLink[] = 'https://www.thedogs.com.au'.$form->link;
            $this->comment('https://www.thedogs.com.au'.$form->link);
        }

        $queryParams = [
            'raceLink' => $batchLink,
        ];
        $apiUrl = 'https://api.dogpower.pro/api/get/form';
        $response = Http::post($apiUrl, $queryParams);
        $data = $response->json();
        // dd($data["data"]);
        if($data["success"] == true) {
            foreach ($data["data"] as $key => $value) {
                if(!empty($value["dateLink"]) && !empty($value["plc"])) {
                    $segments = explode("/", $value["dateLink"]);
                    $segment3 = isset($segments[2]) ? $segments[2] : null;
                    $segment4 = isset($segments[3]) ? $segments[3] : null;
                    $segment5 = isset($segments[4]) ? $segments[4] : null;
                    $segment6 = isset($segments[5]) ? $segments[5] : null;

                    if (preg_match("/\d{2}\/\d{2}\/\d{4}\d{2}:\d{2}\s\d{2}\s\w+\s\d{4}/", $value["date"])) {
                        // Parse the date using the first format
                        $date = \Carbon\Carbon::createFromFormat("d/m/Y H:i j F Y", $value["date"]);
                    } else {
                        // Parse the date using the second format
                        $date = \Carbon\Carbon::createFromFormat("d/m/Y", $value["date"]);
                    }
                    $exists = RaceForm::whereDate("date", '=', Carbon::parse($date))
                                ->where('dog_id', '=', $value['dog_id'])
                                ->where('sex', '=', !empty($value["sexData"][2]) ? substr($value["sexData"][2], 0, 1) : "")
                                ->where('plc', '=', $value["plc"])
                                ->where("box", "=", $this->getBox($value['box']))
                                ->where('dist', '=', $value['dist'])
                                ->where("track", "=", $value["track"])
                                ->where('race_id', '=', $segment5)
                                ->where('venue', '=', $segment3)
                                ->where('wgt', '=', str_replace("kg", "", $value['wgt']))
                                ->where('race_code', '=', $segment6)
                                ->where('track', '=', $value['track'])
                                ->where('G', '=', $value['grade'])
                                ->where('Win', '=', $value['recd'][1])
                                ->exists();
                    // dd(Carbon::createFromFormat('d/m/Y', $value["date"])->format('Y-m-d'));
                    // "2023-10-20"
                    switch ($exists) {
                        case TRUE:
                            $this->comment('Race Exists');
                            break;
                        
                        default:
                            $formData = new RaceForm();
                            $formData->dog_id = $value["dog_id"];
                            $formData->race_code = $segment6;
                            $formData->venue = $segment3;
                            $formData->race_id = $segment5;
                            $formData->sex = !empty($value["sexData"][2]) ? substr($value["sexData"][2], 0, 1) : "";
                            $formData->plc = $value["plc"];
                            $formData->box = $this->getBox($value['box']);
                            $formData->wgt = str_replace("kg", "", $value['wgt']);
                            $formData->dist = $value['dist'];
                            $formData->distance = $this->__process_time2($value['dist'], $value['recd'][0])['distance'];
                            $formData->time2 = $this->getTImeTwo($value['dist'], $this->__process_time2($value['dist'], $value['recd'][0])['distance'], $value['recd'][0]);
                            $formData->date = Carbon::parse($date);
                            $formData->track = $value['track'];
                            $formData->G = $value['grade'];
                            $formData->Time = $value['recd'][0];
                            $formData->Win = $value['recd'][1];
                            $formData->Bon = $value['recd'][2];
                            $formData->_Sec = $value['recd'][3];
                            $formData->MGN = $value['mgn'];
                            $formData->W_2G = $value['winner'];
                            $formData->PIR = $value['pir'];
                            $formData->SP = str_replace("$", "", $value['sp']);
                            $formData->save();
                            $this->comment("inserted");
                            break;
                    }
                }
            }

            if(count($data["data"]) != 0) {
                foreach ($forms as $key => $form) {
                    Dog::where('dog_id', '=', $form->dog_id)
                    // ->where(function($query) use ($today, $yesterday) {
                    //     $query->whereDate('created_at', $today)
                    //           ->orWhereDate('created_at', $yesterday);
                    // })
                    ->update([
                        "imported" => 1
                    ]);
                }
            }
        }
    }

    public function getBox($key) {
        switch ($key) {
            case 'rug_8':
                return 8;
                break;
            case 'rug_7':
                return 7;
                break;
            case 'rug_6':
                return 6;
                break;
            case 'rug_5':
                return 5;
                break;
            case 'rug_4':
                return 4;
                break;
            case 'rug_3':
                return 3;
            case 'rug_2':
                return 2;
                break;
            case 'rug_1':
                return 1;
                break;
            default:
                return NULL;
                break;
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->scrapeForm();
    }
}
