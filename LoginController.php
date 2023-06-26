<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helper\ApiSso;
use App\Repositories\UserRepository;
use Cookie;
use Sesion;
use Validator;
use Jenssegers\Agent\Agent;

class LoginController extends FrontEndController
{
    protected $timeExpriredLogin = 24*60;
    protected $UserRepository;
    public function __construct(UserRepository $UserRepository){
        $this->UserRepository = $UserRepository;
    }


    public function index(Request $request){ 
        $loginfalse   = Cookie::get(md5("loginfalse"), 0);
        $data = [
            'stopLogin' => 0,
            'countLoginfalse' => $loginfalse,
        ];

        if($loginfalse >= 5){
            $data['stopLogin'] = 1;
        }

        if($this->UserRepository->isLogged()){
            $info = $this->UserRepository->getUserInfo();

            if($info){
                return redirect()->route('agency.info');
            }else{
                /* Nếu ko get đc data thì cũng return lại login luôn */
                Cookie::queue(Cookie::forget("8HUBTOKEN")); 
                return redirect()->route('user.login')->with('error','Không lấy được thông tin người dùng !');
            }            
        }else{
            return view('login.index')->with($data);
        }
    }

    /*Login */
    public function actlogin(Request $request){
      
        $validator  = Validator::make($request->all(), [
           "username" => "required",
           "password" => "required", 
           "remember" => ''
        ],[ 
           'username.required' => 'Tên đăng nhập là trường bắt buộc!',
           'password.required' => 'Vui lòng nhập mật khẩu!',
        ]);

        if ($validator->fails()) {
           return redirect(route("user.login"))->withInput()->with('error',$validator->messages()->first());
        }
  
        $username = replaceMQ($request->get('username'));
        $password = replaceMQ($request->get('password'));
        $agency   = replaceMQ($request->get('agency'));
        $remember = $request->get("remember", 0);
  
        $info  = array();   
        if($username != "" && $password != ""){         
            $info = $this->UserRepository->requestCheckLogin($username, $password);
            
            if($info){
                if(isset($info['data']) ){
                    if(count($info['data']) <= 0){       
                        $this->checkMultiLoginFalse();             
                        return back()->with('error','Thông tin tài khoản không chính xác!');
                    }
                 
                    $userInfo = $info['data'];
                    $timeExpriredLogin = 0;
                    if($remember == 1){
                        $timeExpriredLogin = $this->timeExpriredLogin;
                    }

                    Cookie::queue("8HUBTOKEN", $userInfo['token'],$timeExpriredLogin);                     
                    if($userInfo['needChangePassword']){
                        Cookie::queue("8HUBCHANGEPASS",'8HUBCHANGEPASS',$timeExpriredLogin);
                    }
                    Cookie::queue(Cookie::forget(md5("loginfalse")));
                    session(['Logged' => true]);
                    return redirect()->route('home.info')->with('success','Đăng nhập thành công!');
                }else{
                    $this->checkMultiLoginFalse();
                    return back()->with('error','Thông tin tài khoản không chính xác!');
                }            
            }else{
                return back()->with('error','Hệ thống đang bận vui lòng thử lại sau!');
            }
        }
    }

    /* Logout */
    public function logout(){
        Cookie::queue(Cookie::forget("8HUBTOKEN"));
        $info = $this->UserRepository->actLogout();
        return redirect()->route('user.login')->with('success','Đăng xuất thành công!');
    }

    /*Forgot password*/
    public function forgotPassword(Request $request){

        $action = $request->get("action");

        if($action == 'forgetpass'){
            $mobile = $request->get("mobile");

            if($mobile == ''){
                return back()->with('error', "Bạn chưa nhập số điện thoại để nhận lại mật khẩu");
            }

            $dataRequest = [
                "username" => $mobile
            ];

            $result = $this->UserRepository->postForgotPassword($dataRequest);
            if($result['status'] == 0){
                return redirect(route("user.login"))->with("success", "Lấy lại mật khẩu thành công");
            }else{
                return back()->with('error', "Lấy lại mật khẩu không thành công");
            }

        }

        return view('login.fpass');
    }

    public function checkQr()
    {
        $agent = new Agent();
        if ($agent->is('Windows') == true) {
            return redirect('https://tripv.vn/');
        } elseif ($agent->isAndroidOS() == true) {
            return redirect('https://play.google.com/store/apps/details?id=com.vnptmedia.tripv');
        } elseif ($agent->isSafari() == true || $agent->is('iPhone') == true) {
            return redirect('https://apps.apple.com/us/app/tripv/id1610659992');
        }
    }

    public function checkMultiLoginFalse()
    {
        $loginfalse   = Cookie::get(md5("loginfalse"), 0);
        if($loginfalse < 5){
            Cookie::queue(md5("loginfalse"), $loginfalse + 1 , 15*60); 
        }
    }
}
