<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Code;
use App\Models\Driver;
use App\Models\Jobapplication;
use App\Models\Role_User;
use App\Models\Updatedriverinfoapplication;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;

class DriverController extends Controller
{
    use GeneralTrait;

    public function AllDrivers(): JsonResponse
    {
        $users = User::where('type' ,'=','driver')->with('driver')->paginate(15);
        return $this->returnData("get all driver",$users);
    }

    public function DriverJobApplication(Request $request): JsonResponse
    {
        $request->validate([
            "surname" => "required|regex:/^[a-zA-ZÑñ\s]+$/",
            "age" => "required|int",
            "carmodel" => "string",
            "carcolor" => "string",
            "carnumber" => "string|size:8|unique:jobapplications",
            'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048'
        ]);

        $user = auth()->user();
        if( isset($user) )
        {
            // create data
            $imageName = time().'.'.$request->image->extension();
            $request->image->move(public_path('Images/Cars'), $imageName);

            $jobApplication = new Jobapplication();
            $jobApplication->user_id = $user->id;
            $jobApplication->surname = $request->surname;
            $jobApplication->age = $request->age;
            $jobApplication->carmodel = $request->carmodel;
            $jobApplication->carcolor = $request->carcolor;
            $jobApplication->carnumber = $request->carnumber;
            $jobApplication->image = $imageName;
            $jobApplication->save();

            return $this->returnSuccessMessage();
        }else return $this->returnError();



    }

    public function AllJobApplication(): JsonResponse
    {
        $jobapplication = Jobapplication::paginate(15);
        if(isset($jobapplication))
        {
            return $this->returnData("All JobApplication",$jobapplication);
        }else return $this->returnError("not found ");

    }
    public function AllUpdateDriverInfoApplication()
    {
        $application = Updatedriverinfoapplication::paginate(15);
        if(isset($application))
        {
            return $this->returnData("All Update Driver Application",$application);
        }else return $this->returnError("not found");

    }

    public function GetStatusDriverJobApplication($id): JsonResponse
    {
        $DriverJobApplication = Jobapplication::find($id);
        if(isset($DriverJobApplication))
        {
            return $this->returnData("Success",$DriverJobApplication->status);
        }else return $this->returnError("JobApplication not found");
    }

    public function AcceptOrRejectDriverJobApplication(Request $request): JsonResponse
    {
        $request->validate([
            'AcceptOrReject'=>'boolean|required',
            'id'=>'int|required',
            'category_id'=>'required|int'
        ]);

        $DriverJobApplication = Jobapplication::find($request->id);
        if( isset($DriverJobApplication) )
        {
            if( $DriverJobApplication->status == "waiting" )
            {
                $user = User::find( $DriverJobApplication->user_id );
                if( $request->AcceptOrReject )
                {
                    //////////Here Accept Application


                    ////Update user
                    $user->type = "driver";
                    $user->save();


                    ////Create driver
                    $driver = new Driver();
                    $driver->user_id = $user->id;
                    $driver->surname = $DriverJobApplication->surname;
                    $driver->age = $DriverJobApplication->age;
                    $driver->save();

                    ////Create role
                    $role = new Role_User();
                    $role->user_id = $user->id;
                    $role->role_id = 2;
                    $role->save();

                    ////update status DriverJobApplication
                    $DriverJobApplication->status = "accept";
                    $DriverJobApplication->save();

                    /////Create Car
                    $car = new Car();
                    $car->driver_id = $driver->id;
                    $car->category_id = $request->category_id;
                    $car->model = $DriverJobApplication->carmodel;
                    $car->color = $DriverJobApplication->carcolor;
                    $car->number = $DriverJobApplication->carnumber;
                    $car->image = $DriverJobApplication->image;
                    $car->save();

                    $this->sendnotification($user->fcm_token,"Accept your Job Application","Welcome! we are delighted you've decided to join our company. we are confident that you will bring fresh insights and great work to our team. ");
                    return $this->returnSuccessMessage();


                }else{
                    //////////here reject Application
                    $DriverJobApplication->status = "reject";
                    $DriverJobApplication->save();

                    $this->sendnotification($user->fcm_token,"Reject your Application","you can see reasons rejection in app");
                    return $this->returnSuccessMessage();

                }
            }else return $this->returnError("This JobApplication already done");
        }else return $this->returnError("JobApplication not found");


    }

    public function UpdateDriverInfoApplication(Request $request): JsonResponse
    {
        $request->validate([
            'name'=> "regex:/^[a-zA-ZÑñ\s]+$/",
            'surname'=> "regex:/^[a-zA-ZÑñ\s]+$/",
            "age" => "int",
            'image_driver' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'image_car' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'carmodel'=>'string',
            'carcolor'=>'string',
            'carnumber'=> "string|size:8|unique:updatedriverinfoapplications"
        ]);

        $driver = auth()->user()->driver;
        if(isset($request->name) || isset($request->surname)|| isset($request->age)|| isset($request->image_driver)|| isset($request->image_car)|| isset($request->carmodel)|| isset($request->carcolor)|| isset($request->carnumber))
        {
            if(isset($driver))
            {
                $info = new Updatedriverinfoapplication();
                $info->driver_id = $driver->id;
                if(isset($request->name))
                    $info->name = $request->name;
                if(isset($request->surname))
                    $info->surname = $request->surname;
                if(isset($request->age))
                    $info->age = $request->age;
                if(isset($request->carmodel ))
                    $info->carmodel = $request->carmodel;
                if(isset($request->carcolor ))
                    $info->carcolor = $request->carcolor;
                if(isset($request->carnumber))
                    $info->carnumber = $request->carnumber;
                if(isset($request->image_car))
                {
                    $imageName = time().'.'.$request->image_car->extension();
                    $request->image_car->move(public_path('Images/Cars'), $imageName);
                    $info->image_car = $imageName;
                }

                if(isset($request->image_driver))
                {
                    $imageName = time().'.'.$request->image_driver->extension();
                    $request->image_driver->move(public_path('Images/User'), $imageName);
                    $info->image_driver = $imageName;
                }

                $info->save();

                return $this->returnSuccessMessage();

            }else return $this->returnError("driver not found");
        }else return $this->returnError("application is empty");

    }

    public function AcceptOrRejectUpdateDriverInfoApplication(Request $request): JsonResponse
    {
        $request->validate([
            'AcceptOrReject'=>'boolean|required',
            'id'=>'int|required',
        ]);

        $InfoApplication = Updatedriverinfoapplication::find($request->id);
        if( isset($InfoApplication) ){
            if( $InfoApplication->status == "waiting" ){
                $driver = Driver::find($InfoApplication->driver_id);
                $user = $driver->user;
                if( $request->AcceptOrReject )
                {
                    //////////Here Accept Application
                    /// Update Driver Table
                    if(isset($InfoApplication->surname))
                    $driver->surname = $InfoApplication->surname;
                    if(isset($InfoApplication->age))
                    $driver->age = $InfoApplication->age;
                    $driver->save();
                    /////// Update User Table
                    if(isset($InfoApplication->name))
                    $user->name = $InfoApplication->name;
                    if( isset( $info->image_driver ) )
                        $user->image = $info->image_driver;
                    $user->save();

                    ////update car
                    $car = $driver->car;
                    if(isset($InfoApplication->carmodel))
                        $car->model  = $InfoApplication->carmodel;
                    if(isset($InfoApplication->carcolor))
                        $car->color = $InfoApplication->carcolor;
                    if(isset($InfoApplication->carnumber))
                        $car->number = $InfoApplication->carnumber;
                    if(isset($InfoApplication->image_car))
                        $car->image = $InfoApplication->image_car;
                    $car->save();
                    ///////Update Status InfoApplication
                    $InfoApplication->status = "accept";
                    $InfoApplication->save();
                    $this->sendnotification($user->fcm_token," Update Information Application","Accept your Update Information Application,Your Information is update ");
                    return $this->returnSuccessMessage();

                }else
                {
                    //////////Here Reject Application
                    $InfoApplication->status = "reject";
                    $InfoApplication->save();
                    $this->sendnotification($user->fcm_token,"Update Information Application","Reject your Update Information Application,you can see reasons rejection in app");
                    return $this->returnSuccessMessage();
                }
            }else return $this->returnError("This UpdateInfoApplication already done");
        }else return $this->returnError("Update Information Application not found");
    }
}