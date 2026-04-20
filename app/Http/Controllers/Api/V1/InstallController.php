<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;


class InstallController extends Controller
{
    

    public function step0()
    {
    

        return view('installation.step0');
    }

    public function step1()
    {
        $permission['curl_enabled'] = function_exists('curl_version');
        $permission['db_file_write_perm'] = is_writable(base_path('.env'));
        $permission['routes_file_write_perm'] = is_writable(base_path('app/Providers/RouteServiceProvider.php'));
        return view('installation.step1', compact('permission'));
    }

    public function step2()
    {
        return view('installation.step2');
    }

    public function step3()
    {
        return view('installation.step3');
    }

    public function step4()
    {
        return view('installation.step4');
    }

    public function step5()
    {
        return view('installation.step5');
    }

    public function purchase_code(Request $request)
    {

       
        Helpers::setEnvironmentValue('SOFTWARE_ID', 'NTYwMzU4ODU=');
        Helpers::setEnvironmentValue('BUYER_USERNAME', $request['username']);
        Helpers::setEnvironmentValue('PURCHASE_CODE', $request['purchase_key']);
        return Redirect::route('dmvf', ['purchase_key' => $request['purchase_key'], 'username' => $request['username']]);
      //  return redirect()->route('dmvf', ['purchase_key' => $request['purchase_key'], 'username' => $request['username']]);
    }

    public function system_settings(Request $request)
    {
        DB::table('users')->insertOrIgnore([
            'id' => 1,
            'f_name' => $request['f_name'],
            'l_name' => "Admin",
            'email' => $request['email'],
            'password' => Hash::Make($request['password']),
            'phone' => $request['phone'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
            DB::table('users_role_assign')->insertOrIgnore([
            'id' => 1,
            'user_id' =>1,
            'role_id' =>14,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('configurations')->where(['id' => '6'])->update([
            'value' => $request['business_name']
        ]);

        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);
        //sleep(5);
        return view('installation.step6');
    }

    public function database_installation(Request $request)
    {
        if (self::check_database_connection($request->DB_HOST, $request->DB_DATABASE, $request->DB_USERNAME, $request->DB_PASSWORD)) {

            $key = base64_encode(random_bytes(32));
            //genare api key
            $apiKey = base64_encode(random_bytes(32));
            $output = 'APP_NAME=medicare'.time().
                'APP_ENV=live
                     APP_KEY=base64:' . $key . '
                   API_KEY=' . $apiKey . '
                    APP_DEBUG=false
                    APP_INSTALL=true
                    APP_LOG_LEVEL=debug
                    APP_MODE=live
                    APP_URL=' . URL::to('/') . '

                    DB_CONNECTION=mysql
                    DB_HOST=' . $request->DB_HOST . '
                    DB_PORT=3306
                    DB_DATABASE=' . $request->DB_DATABASE . '
                    DB_USERNAME=' . $request->DB_USERNAME . '
                    DB_PASSWORD=' . $request->DB_PASSWORD . '

                    
                    MAIL_MAILER=smtp
                    MAIL_HOST=
                    MAIL_PORT=587 
                    MAIL_USERNAME=
                    MAIL_PASSWORD=
                    MAIL_ENCRYPTION=ssl
                    MAIL_FROM_ADDRESS=
                    MAIL_FROM_NAME="${APP_NAME}"

                    BROADCAST_DRIVER=log
                    CACHE_DRIVER=file
                    SESSION_DRIVER=file
                    SESSION_LIFETIME=120
                    QUEUE_DRIVER=sync

                    REDIS_HOST=127.0.0.1
                    REDIS_PASSWORD=null
                    REDIS_PORT=6379

                    PUSHER_APP_ID=
                    PUSHER_APP_KEY=
                    PUSHER_APP_SECRET=
                    PUSHER_APP_CLUSTER=mt1

                    PURCHASE_CODE=' . session('purchase_key') . '
                    BUYER_USERNAME=' . session('username') . '
                    SOFTWARE_ID=NTYwMzU4ODU=

                    SOFTWARE_VERSION=1.0
                    ';
            $file = fopen(base_path('.env'), 'w');
            fwrite($file, $output);
            fclose($file);

            $path = base_path('.env');
            if (file_exists($path)) {
                return redirect('step4');
            } else {
                session()->flash('error', 'Database error!');
                return redirect('step3');
            }
        } else {
            session()->flash('error', 'Database error!');
            return redirect('step3');
        }
    }

    public function import_sql()
    {
      
        try {
            $sql_path = base_path('installation/backup/database.sql');
            DB::unprepared(file_get_contents($sql_path));
            return redirect('step5');
        } catch (\Exception $exception) {
            
           session()->flash('error', 'Your database is not clean, do you want to clean database then import?');
           return back();
        }
    }

    public function force_import_sql()
    {
        try {
            Artisan::call('db:wipe', ['--force' => true]);
            $sql_path = base_path('installation/backup/database.sql');
            DB::unprepared(file_get_contents($sql_path));
            return redirect('step5');
        } catch (\Exception $exception) {
            session()->flash('error', 'Check your database permission!');
            return back();
        }
    }

    function check_database_connection($db_host = "", $db_name = "", $db_user = "", $db_pass = "")
    {

        if (@mysqli_connect($db_host, $db_user, $db_pass, $db_name)) {
            return true;
        } else {
            return false;
        }
    }
}
