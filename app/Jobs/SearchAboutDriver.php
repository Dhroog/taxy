<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\Trip;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SearchAboutDriver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,GeneralTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $trip_id,$category_id;
    public function __construct($trip_id,$category_id)
    {
        $this->trip_id = $trip_id;
        $this->category_id = $category_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $trip = Trip::find($this->trip_id);
        if(isset($trip))
        {
            $accepted = false;
            $now = Carbon::now();
            $after3minutes = $now->addMinutes(3);
            while( $now->diffInMinutes($after3minutes) < 3 || $accepted	 == false )
            {
                ///////get all available drivers
                $drivers = Driver::where('available',true)->get();
                foreach ($drivers as $driver)
                {
                    //check if driver Belongs To Circle customer
                    if( $this->BelongsToCircle(100,$trip->s_lat,$trip->s_long,$driver->lat,$driver->long) && $driver->car->category->id == $this->category_id )
                    {
                        ///check if driver not reject this trip before
                        $rejections = $driver->rejection;
                        $re = true;
                        if(isset($reject))
                        {
                            foreach ($rejections as $value)
                            {
                                if( $value->trip_id == $this->trip_id )
                                {
                                    $re = false;
                                    break;
                                }
                            }
                        }
                        ////send notification to driver
                        if($re)
                        {
                            $this->sendnotification($driver->user->fcm_token,'Trip Available ','there new trip can accept it');
                        }
                    }
                }

                $trip = Trip::find($this->trip_id);
                $accepted = $trip->accepted;

                sleep(30);
                $now = Carbon::now();
            }
        }

    }
}
