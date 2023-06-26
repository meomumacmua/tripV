<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminUser;
use App\Models\Location;
use App\Models\Area;
use App\Models\City;
use Illuminate\Http\Request;
use Session;
use Validator;
use Lang;
use App\Helper\ImageManager;
use DB;
use App\Helper\HtmlCleanup;

class AdminLocationController extends AdminController
{

	public function __construct(){}

    public function index(Request $request)
    {
        $arrPermision = app()->USER_PERMISION;
		if(!in_array('news_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}

        $admin_id = 0;
        $params   = [];
        $where    = array();
        $data     = [];
        $name     = replaceMQ($request->input('name'));
        $status   = $request->get('status', -1);
        $hot      = $request->get("hot", -1);
        $cityId   = $request->get("cityId", 0);
        $per_page = 30;
        $page     = $request->get('page', 1);

        $query    = Location::query();

        if ($name){
            $searchTitle = removeAccent(strtolower($name));
            $searchTitle = cleanKeywordSearch($searchTitle);
            $query->where('TITLE_SEARCH', 'like', '%' . $searchTitle . '%'); 
        }  

        $params = [
            'name'   => $name,
            'hot'    => $hot,
            'status' => $status,
            'cityId' => $cityId
        ];

        if($status >= 0){
            $query->where('ACTIVE', $status); 
        }

        if($hot >= 0){
            $query->where('HOT', $hot);
        }

        if($cityId > 0){
            $query->where('CIT_ID', $cityId);
        }

        $data['arrArea'] = $this->getArea();
        $CityInfo = City::where('ACTIVE',1)->get();
        $arrCity = [];
        foreach($CityInfo as $city){
            $arrCity[$city['cit_id']] = $city['name'];
        }
        asort($arrCity);
        $data['arrCity'] = $arrCity;
        $data['arrHot']      = [
            -1 => "-Chọn-", 
            1 => "Đã chọn",
            0 => "Chưa chọn",
        ]; 
        $data['arrStatus']      = [
            -1 => "-Chọn trạng thái-", 
            1 => "Kích hoạt",
            0 => "Khóa",
        ]; 
        $totalItem = $query->count(); 
        $panigate  = ['totalItem' => $totalItem, 'per' => $per_page, 'page' => $page ];

        $data['panigate'] = $panigate;

        $data['location']        = $query->orderBy('LOCATION.ORDER', 'ASC')->paginate($per_page);
        $data['params']          = $params;

        $data['page_filter_url'] = '/admin/location';
        return view("location.index", $data);
    }

    public function create(Request $request)
    {
        $arrPermision = app()->USER_PERMISION;
		if(!in_array('news_manager_edit', $arrPermision)){
			 return redirect(route("access_denied"));
		}
        $admin_id     =  0;
        $data         = []; 
        $data['arrCity'] = $this->getCity();
        if($request->action == "insert"){
            $validator = Validator::make($request->all(), [
                'name'         => 'max:255|required',
                'teaser'       => 'max:255|required',
                'description'  => 'required',
                'picture_data' => 'required',
                'are_id'       => 'required|numeric|not_in:0',
                'cit_id'       => 'required|numeric|not_in:0',
                'order'        => 'numeric',
            ],[
                'name.required'         => 'Bạn nhập tiêu đề.',
                'description.required'  => 'Bạn nhập mô tả.',
                'picture_data.required' => 'Bạn chưa chọn ảnh đại diện.',
                'are_id.required'       => 'Bạn chưa chọn khu vực.',
                'are_id.not_in'         => 'Bạn chưa chọn khu vực.',
                'cit_id.required'       => 'Bạn chưa chọn thành phố.',
                'cit_id.not_in'         => 'Bạn chưa chọn thành phố.',
            ]);

            if ($validator->fails()) {
                return redirect('/admin/location/create')->withErrors($validator)->withInput();
            }

            $description    = $request->description;
            $description    = stripslashes($description);
            $my_HtmlCleanup = new HtmlCleanup($description);
            $my_HtmlCleanup->setIgnoreCheckProtocol();
            $my_HtmlCleanup->clean();
            $description    = $my_HtmlCleanup->output_html;

            /*Upload file*/
            $dataUpload = $this->upload($request);
            if ($dataUpload['status'] == true)
            {
                $image = $dataUpload['url'];
            };

            $dataInsert = [];

            $dataInsert['IMAGE']       = $image ?? '';
            $dataInsert['DESCRIPTION'] = $description;
            $dataInsert['ACTIVE']      = replaceMQ($request->active);
            $dataInsert['NAME']        = replaceMQ($request->name);
            $dataInsert['ARE_ID']      = replaceMQ($request->are_id);
            $dataInsert['CIT_ID']      = replaceMQ($request->cit_id);
            $dataInsert['TEASER']      = replaceMQ($request->teaser);
            $dataInsert['INFO_HELP']   = replaceMQ($request->info_help);
            $dataInsert['ORDER']       = replaceMQ($request->order);
            $dataInsert['VIDEO_URL']   = replaceMQ($request->video_url);

            $searchTitle                = removeAccent(strtolower($request->name));
            $searchTitle                = cleanKeywordSearch($searchTitle);
            $dataInsert['TITLE_SEARCH'] = $searchTitle;

            $location = Location::create($dataInsert);

            if(isset($location) && $location->ARE_ID > 0){
                Session::flash('success', 'Thêm mới địa điểm thành công!');
            }
        }
        return view("location.add", $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AdminUser  $adminUser
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {   
        $arrPermision = app()->USER_PERMISION;
		if(!in_array('news_manager_edit', $arrPermision)){
			 return redirect(route("access_denied"));
		}
        $actual_link = 'http://'.$_SERVER['HTTP_HOST'].'/location';
        $admin_id    =  0;
        $data['image'] = [];
        $data['arrCity'] = $this->getCity();
        $data['location'] = Location::find($id);
        if($data['location']){
            $data['location'] = $data['location']->toArray();
        }else{
            return redirect('/admin/location/');
        }
        return view('location.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AdminUser  $adminUser
     * @return \Illuminate\Http\Response
     */
    public function update($id,Request $request)
    {
        $arrPermision = app()->USER_PERMISION;
		if(!in_array('news_manager_edit', $arrPermision)){
			 return redirect(route("access_denied"));
		}
        $admin_id    =  0;
        $locationInfo = Location::find($id);
        if($request->action == "update"){
            $validator = Validator::make($request->all(), [
                'name'         => 'max:255|required',
                'teaser'       => 'max:255|required',
                'description'  => 'required',
                'cit_id'       => 'required|numeric|not_in:0',
                'order'        => 'numeric',
            ],[
                'name.required'         => 'Bạn nhập tiêu đề.',
                'description.required'  => 'Bạn nhập mô tả.',
                'cit_id.required'       => 'Bạn chưa chọn thành phố.',
                'cit_id.not_in'         => 'Bạn chưa chọn thành phố.',
            ]);

            if ($validator->fails()) {
                return redirect(route('location.edit', ['id' => $id]))->withErrors($validator)->withInput();
            }

            $dataInsert = []; 

            $description    = $request->description;
            $description    = stripslashes($description);
            $my_HtmlCleanup = new HtmlCleanup($description);
            $my_HtmlCleanup->setIgnoreCheckProtocol();
            $my_HtmlCleanup->clean();
            $description    = $my_HtmlCleanup->output_html;

            /*Upload file*/
            $dataUpload = $this->upload($request);
            if ($dataUpload['status'] == true)
            {
                $image = $dataUpload['url'];
            };

            $dataInsert['IMAGE']       = $image ?? $locationInfo->image;
            $dataInsert['DESCRIPTION'] = $description;
            $dataInsert['ACTIVE']      = replaceMQ($request->active);
            $dataInsert['NAME']        = replaceMQ($request->name);
            $dataInsert['ARE_ID']      = replaceMQ($request->are_id);
            $dataInsert['CIT_ID']      = replaceMQ($request->cit_id);
            $dataInsert['TEASER']      = replaceMQ($request->teaser);
            $dataInsert['INFO_HELP']   = replaceMQ($request->info_help);
            $dataInsert['ORDER']       = replaceMQ($request->order);
            $dataInsert['VIDEO_URL']   = replaceMQ($request->video_url);

            $searchTitle                = removeAccent(strtolower($request->name));
            $searchTitle                = cleanKeywordSearch($searchTitle);
            $dataInsert['TITLE_SEARCH'] = $searchTitle;
            $location = Location::where("loc_id","=",$id)->update($dataInsert);
            if($location){
                Session::flash('success', 'Sửa địa điểm thành công!');
            }else{
                return redirect('/admin/location/');
            }

        }
        return redirect(route('location.edit', ['id' => $id]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AdminUser  $adminUser
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $arrPermision = app()->USER_PERMISION;
		if(!in_array('news_manager_edit', $arrPermision)){
			 return redirect(route("access_denied"));
		}
        try {
            $news = Location::where("loc_id","=",$id)->delete();
            Session::flash('success', 'Gỡ bài thành công!');
        } catch (\Throwable $th) {
            Session::flash('error', 'Bản ghi này đang có dữ liệu liên quan!');
        }

        return redirect('/admin/location/');
    }

    public function getArea(){
        $arrReturn = [0 => 'Chọn'];
        $data = Area::where('ACTIVE',1)->get();
        foreach ($data as $key => $value) {
            $arrReturn[$value->are_id] = $value->name;
        }
        return $arrReturn;
    }

    public function getCity(){
        $return = [];
        $data = City::where('ACTIVE',1)->orderBy('name', 'ASC')->get();
        foreach($data as $city){
           $return[$city->cit_id] = $city->name; 
        }
        return $return;
    }

    public function upload(Request $request){
        $dataReturn = ['status' => false, 'msg' => '', 'filename' => '', 'url' => '', 'filesize' => 0, 'width' => 0, 'height' => 0];
        $arrPermision = app()->USER_PERMISION;
		if(!in_array('news_manager_edit', $arrPermision)){
            $dataReturn['msg'] = 'Bạn không có quyền thực thi!';
            return json_encode($dataReturn);
		}
        // Update thông tin ảnh
        $actual_link = env('DATA_IMAGE_URL');
        if ($request->hasFile('image'))
        {
         $image = $request->file('image');

         $validator = Validator::make($request->all() , ['image' => 'mimes:jpeg,jpg,png,gif'], ['image.mimes' => 'Ảnh tải lên không đúng định dạng']);

         if ($validator->fails())
         {
            $dataReturn['msg'] = 'File upload không tồn tại!';
         }
         //upload images
         $dataReturn['filename'] = $actual_link . 'location/' . ImageManager::upload($image, 'location');
         $dataReturn['status'] = true;
         $dataReturn['url'] = $dataReturn['filename'];
        }
        else
        {
         $dataReturn['msg'] = 'File upload không tồn tại!';
        }

        return $dataReturn;
    }

    public function getSequence($key){
        $sequence = DB::getSequence();
        // create a sequence
        $check = $sequence->exists($key);
        if($check == null){
            $sequence->create($key);
        }
        return $sequence->nextValue($key);
    }

    public function getLocation(Request $request){
        $arrReturn['status']  = 0;
        $arrReturn['message'] = '';
        $arrReturn['result']  = [];
        $location = Location::where('ACTIVE',1)->get();
        return $location;
    }

    public function changeOrder(Request $request){
        $id     = $request->get('id');
        $value  = $request->get('value', 0);
        $return = ['status'=> 0, 'msg' => ''];

        if($id){
            try {
                $update = Location::where('LOC_ID', $id)->update([
                    'ORDER' => intval($value),
                ]);
                $return['msg'] = 'Update Thành Công';
            } catch (Exception $e) {
                $return['msg'] = 'Update Thất bại';    
                $return['status'] = 1;
            }            
        }
        return $return;
    }

    public function quickChangeActive($id, $page, Request $request)
    {    
        $page     = intval($page);
        $admin_id = 0;
        $news     = Location::where("loc_id","=",$id);
        $cur      = $request->get('status', 0);
        if ($news)
        {   
            $news->update(['ACTIVE' => $cur]);
            Session::flash('success', 'Update thành công!');
        }
        return redirect('/location');
    }
 
}
