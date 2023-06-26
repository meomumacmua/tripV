<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Cookie;
use Validator; 
use App\Repositories\ConfigRepository;
use App\Repositories\NotifyRepository;

class NotifyController extends Controller
{	
	protected $NotifyRepository;
	protected $ConfigRepository;
	function __construct(NotifyRepository $NotifyRepository, ConfigRepository $ConfigRepository)
	{
		$this->NotifyRepository = $NotifyRepository;
		$this->ConfigRepository = $ConfigRepository;
	}
	
	function getListObjectType(){
		$return = [
			1 => 'Topic',
			0 => 'Customer',
		];

		return $return;
	}

	function getListNotifyType(){
		$result = $this->NotifyRepository->getListNotifyType();
		$return = [];
		if($result['status'] == 0 && $result['data'] != null){
			foreach ($result['data']  as $value) {
				$return[$value['notifyTypeCode']] = $value['info'];
			}
			
		}
		return $return;
	}

	function index(Request $request){
		// check quyền
		$arrPermision = app()->USER_PERMISION;
			if(!in_array('notify_manager_view', $arrPermision)){
			return redirect(route("access_denied"));
		}
		
		$params = [];

		$page  	= $request->get('page', 1);
		$num    = $request->get('start', 20);
		$object = $request->get('object', -1);
		$params =[
			'object' => $object,
			'num'    => $num,
			'page'   => $page,
		];

		$arrType   = $this->getListNotifyType();
		$arrObject = $this->getListObjectType();		
		$listNotify = [];
		$data_request = [
			'type'  => $object >= 0 ? intval($object)  : null,
			'num'   => intval($num),
			'start' => intval(($page - 1) * $num),
		];
		
		$result = $this->NotifyRepository->getListNotify($data_request);

		if($result['status'] == 0 && $result['data'] != null){
			$listNotify = $result['data'];
		}


		$data = [
			'params' => $params,
			'listNotify' => $listNotify,
			'arrType' => $arrType,
			'arrObject' => $arrObject,
		];
		
		return view('notify.index')->with($data);
	}

	function add(Request $request){
		// check quyền
		$arrPermision = app()->USER_PERMISION;
			if(!in_array('notify_manager_edit', $arrPermision)){
			return redirect(route("access_denied"));
		}
		$object     = $request->get('object');
		$customerId = $request->get('customerId'); 
		$validator  = Validator::make($request->all(), [
			"type"    => "required",  
			"title"   => "required",  
			"content" => "required",  
		],[
			'type.required' => 'Bạn chưa chọn dịch vụ!',
			'title.required'   => 'Bạn chưa điền tiêu đề!',
			'content.required' => 'Bạn chưa điền nội dung!', 
		]);

		if ($validator->fails()) {
		  return redirect(route("notify.index"))->withInput()->with(['show' => true,'error' => $validator->messages()->first()]);
		}

		if($object == 0 && !$customerId){
			return redirect(route("notify.index"))->withInput()->with(['show' => true,'error' => "Bạn chưa nhập khách hàng"]);	
		}

		$data_request = [
			'type' => $request->get('type', 'All'),
			'title' => $request->get('title', ''),
			'content' => $request->get('content', ''),
		];

		// Gửi cho customer thì datarequest thêm customerId
		if($object == 0){
			$data_request['customer_id'] = $customerId;
			$result = $this->NotifyRepository->createNotifyCustomer($data_request);
		}elseif ($object == 1) {
			$result = $this->NotifyRepository->createNotifyTopic($data_request);
		}

		//dd($result);
		if($result['status'] == 0){
			return redirect(route("notify.index"))->with(['success' => 'Thêm mới thành công']);
		}else{
			return redirect(route("notify.index"))->with(['error' => $result['errorMessage'] ?? "Thêm mới không thành công"]);
		}
	}

	public function detail(Request $request)
	{	
		$arrType   = $this->getListNotifyType();
		$arrObject = $this->getListObjectType();
		$notify_id = $request->get('notify_id');

		$result = $this->NotifyRepository->getDetailNotify($notify_id);
		$html = '';
		if($result['status'] == 0 && $result['data'] != null){
			$html = '<div class="item">
							<label>Tiêu đề:</label>
							<p>'. $result['data']['title'] .'</p>
						</div>
						<div class="item">
							<label>Nội dung:</label>
							<p>'. $result['data']['content'] .'</p>
						</div>
						<div class="item">
							<label>Thời gian:</label>
							<p>'. $result['data']['datetime'] .'</p>
						</div>	 
						<div class="item">
							<label>Loại:</label>
							<p>'. $result['data']['notifyType']['info'].'</p>
						</div>
						<div class="item">
							<label>Danh mục:</label>
							<p>'. $result['data']['notifyCategory']['name'] .'</p>
						</div>
						<div class="item">
							<label>Đối tượng:</label>
							<p>'. $arrObject[$result['data']['type']] .'</p>
						</div>
						<div class="item">
							<label>Mã đơn hàng:</label>
							<p>'. $result['data']['orderCode'] .'</p>
						</div>';
		}		

		return $html;
	}
}