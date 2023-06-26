<?php

namespace App\Repositories;

use App\Helper\ApiSso;
use Cookie;

class UserRepository
{   

   protected $URL_LOGIN       = '/authen/login';
   protected $URL_CHANGEPWD   = '/authen/pwd';
   protected $URL_FORGOT_PWD  = '/authen/pwd_forget';
   protected $URL_GETINFO     = '/authen/info';
   protected $URL_GETPER      = '/authen/rulepermision';
   protected $URL_AGENCY_LIST = '/info/agency/list';
   protected $URL_ACC_GROUP   = '/info/agency/account_group';
   protected $URL_LOGOUT      = '/authen/logout';

   /* Hàm gọi check login */
   public function requestCheckLogin($username, $password){
      $url  = ApiSso::getHostDB() . $this->URL_LOGIN;
      $data =  [
         'username' => $username,
         'password' => $password
      ];
      $result = ApiSso::curlPost($url, $data);    

      return $result;
   }

   // check login user
   public function isLogged(){
      $hubToken   = Cookie::get("8HUBTOKEN");
    
      if($hubToken !== null){         
         return true;
      }else{
         return false;
      }
      return false;      
   }

   // đổi mật khẩu
   public function changeUserPassword($data){
      $hubToken = Cookie::get("8HUBTOKEN");
      $url      = ApiSso::getHostDB() . $this->URL_CHANGEPWD;
      $result   = ApiSso::curlPost($url, $data, $hubToken);    
      return $result;
   }
   
   // lấy thông tin user
   public function getUserInfo(){
      $hubToken = Cookie::get("8HUBTOKEN");
      $url      = ApiSso::getHostDB() . $this->URL_GETINFO;
      $result   = ApiSso::curlGet($url, $hubToken);   
      
      if(isset($result['status']) && $result['status'] == 203){
     
      }else{
         $data                = isset($result['data']['account']) ? $result['data']['account'] : [];
         $data['agency_id']   = isset($result['data']['agency_id']) ? $result['data']['agency_id'] : 0;
         $data['agency_code'] = isset($result['data']['agency_code']) ? $result['data']['agency_code'] : '';
         $result['data']      = $data ;
      }
      return $result;
   }

   // Lấy danh sách quyền
   public function getUserPermision(){
      $arrReturn = [];
      $hubToken  = Cookie::get("8HUBTOKEN");
      $url       = ApiSso::getHostDB() . $this->URL_GETPER;
      $result    = ApiSso::curlGet($url, $hubToken);
      if(isset($result['data'])){
         foreach ($result['data'] as $key => $value) {
            $arrReturn[] = $value['rule'] . "_" . $value['permision'];
         }
      } 
      return $arrReturn;
   }

   // lấy danh sách agency
   public function getListAgency(){
      $hubToken = Cookie::get("8HUBTOKEN");
      $url      = ApiSso::getHostDB() . $this->URL_AGENCY_LIST;
      $result   = ApiSso::curlGet($url, $hubToken);    
      return $result;
   }

   // lấy danh sách group user
   public function getAccGroup(){
      $hubToken = Cookie::get("8HUBTOKEN");
      $url      = ApiSso::getHostDB() . $this->URL_ACC_GROUP;
      $result   = ApiSso::curlGet($url, $hubToken);    
      return $result;
   }

   // Lấy lại mật khẩu (fogotpassword)
   public function postForgotPassword($data){ 
      $url     = ApiSso::getHostDB() . $this->URL_FORGOT_PWD;
      $result  = ApiSso::curlPost($url, $data);   
      return $result;
   }

   // Logout     
   public function actLogout(){
      $hubToken = Cookie::get("8HUBTOKEN");
      $url      = ApiSso::getHostDB() . $this->URL_LOGOUT;
      $result   = ApiSso::curlGet($url, $hubToken);    
      return $result;
   }
}