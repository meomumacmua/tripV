<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Cookie;
use Validator; 
use App\Repositories\PromotionRepository;
use App\Repositories\StatisticRepository;
use Excel;
use App\Helper\ComboPriceExport;
use App\Repositories\AgencyRepository;
use App\Helper\formDoisoatExport;

class StatisticalController extends Controller
{	
	protected $StatisticRepository;
	protected $promotionRepository;
	function __construct(StatisticRepository $StatisticRepository, PromotionRepository $promotionRepository, AgencyRepository $AgencyRepository)
	{
		$this->StatisticRepository = $StatisticRepository;
		$this->promotionRepository = $promotionRepository;
		$this->AgencyRepository = $AgencyRepository;
	}
	
 	public function StatisticTotal(Request $Request)
 	{
		// check quyền
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
 		// code...
		$start_time  = time() - 30*86400;
		$end_time    = time();
		$date_search = $Request->get('date_search');
		$orderCode   = $Request->get('orderCode', null);
		$statusCode  = $Request->get('statusCode', null);

		if($date_search){
         $arrDate    = explode('-', str_replace(' ', '', $date_search));
         
         $start_time = convertDateTime($arrDate[0], '00:00:00') ;
         $end_time   = convertDateTime($arrDate[1], ' 23:59:59');
      }

 		$params = [ 
 			"act"	=> "total",
			'dateStart'	=> date('d/m/Y', $start_time),
			'dateEnd'	=> date('d/m/Y', $end_time),
			'title' => 'Doanh thu tổng',
			'orderCode' => $orderCode,
			'statusCode' => $statusCode,
			'date_search' => $date_search,
		];

		/*Số liệu*/
		$dataRequest = [
			"act"        => "total",
			"search"     => ($orderCode != null) ? $orderCode : $orderCode ?? '',
			"start_date" => date('d/m/Y', $start_time),
			"end_date"   => date('d/m/Y', $end_time),
		];
		
		$result  = $this->StatisticRepository->getStatistic($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}

		// Loop để tính tổng số của các trạng thái		
		$Total = [];
		foreach ($listData as $key => $val) {
			if(isset($Total[$val['rcReconcile']])){
				$Total[$val['rcReconcile']]++;
			}else{
				$Total[$val['rcReconcile']] = 1;
			}
		}

		//Arr Trạng thái
		$ReconcileDes = [
			"00" => "Giao dịch khớp",
			"01" => "Đối tác không có giao dịch, VNPT Pay có giao dịch",
			"02" => "Đối tác có giao dịch, VNPT Pay không có giao dịch",
			"03" => "Dữ liệu sai lệch",
			"99" => "Chưa xử lý",
		];
		$data = [ 
			'params'   => $params,
			'listData' => $listData,
			'Total'    => $Total,
			'ReconcileDes' => $ReconcileDes,
		];

 		return view('statistic.total')->with($data);
 	}

 	public function StatisticByFee(Request $Request)
 	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}

 		// code...
 		$start_time = time() - 30*86400;
		$end_time   = time();
      $date_search = $Request->get('date_search');
		$orderCode   = $Request->get('orderCode', null);
		$statusCode  = $Request->get('statusCode', null);
		if($date_search){
         $arrDate    = explode('-', str_replace(' ', '', $date_search));
         
         $start_time = convertDateTime($arrDate[0], '00:00:00') ;
         $end_time   = convertDateTime($arrDate[1], ' 23:59:59');
      }
 		$params = [ 
 			"act"        => "fee",
			'dateStart'  => date('d/m/Y', $start_time),
			'dateEnd'    => date('d/m/Y', $end_time),
			'title'      => 'Doanh thu phí dịch vụ',
			'orderCode'  => $orderCode,
			'statusCode' => $statusCode,
			'date_search' => $date_search,
		];

		/*Số liệu*/
		$dataRequest = [
			"act"        => "fee",
			"search"     => ($orderCode != null) ? $orderCode : $orderCode ?? '',
			"start_date" => date('d/m/Y', $start_time),
			"end_date"   => date('d/m/Y', $end_time),
		];
		$result  = $this->StatisticRepository->getStatistic($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}

		$data = [ 
			'params'		=> $params,
			'listData'  => $listData,
		];
 		return view('statistic.fee')->with($data);
 	}

	 public function doExportTotal(Request $Request)
	 { 
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}

		$date_search  = $Request->get('date_search');
		$start_time   = time() - 30*86400;
		$end_time     = time();
		$orderCode   = $Request->get('orderCode', null);
		$statusCode  = $Request->get('statusCode', null);
		if($date_search){
         $arrDate    = explode('-', str_replace(' ', '', $date_search));         
         $start_time = convertDateTime($arrDate[0], '00:00:00') ;
         $end_time   = convertDateTime($arrDate[1], ' 23:59:59');
      }
      /*Số liệu*/
		$dataRequest = [
			"act"        => "total",
			"search"     => ($orderCode != null) ? $orderCode : $statusCode ?? "",
			"start_date" => date('d/m/Y', $start_time),
			"end_date"   => date('d/m/Y', $end_time),
		];

		$result   = $this->StatisticRepository->getStatistic($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}
		
		$title = 'Doanh thu tổng hợp';
		$file_name  = 'Statistic_total_' . date('Ymd', time()).'.xlsx';

 		/*Export*/
 		$arr_export = [];
		$arr_export[] = [
			$title ?? "Doanh thu tổng hợp", '', '', '', '', '', ''
		];
		$arr_export[] = [
			"Stt", 'Ngày giao dịch', 'Mã đơn hàng','Khách hàng', 'Điện thoại', 'Số tiền', 'Trạng thái ĐS', 
		];

		$ReconcileDes = [
			"00" => "Giao dịch khớp",
			"01" => "Đối tác không có giao dịch, VNPT Pay có giao dịch",
			"02" => "Đối tác có giao dịch, VNPT Pay không có giao dịch",
			"03" => "Dữ liệu sai lệch",
			"99" => "Chưa xử lý",
		];

		if($listData){
			foreach ($listData as $key => $item) {
				$arr_export[] = [
					$key + 1,
				   $item['tranDate']	,
					$item['orderId'] ?? "",
					$item['customerId'] ?? "",
					$item['customerMobile'] ?? "",
					number_format($item['amount']), 
					($item['rcReconcile'] ?? '') .'-'. ($ReconcileDes[$item['rcReconcile']] ?? ""),
				];	
			}
		}
		$export = new ComboPriceExport($arr_export);		
		return Excel::download($export, $file_name);
 	}

 	public function StatisticByService(Request $Request)
 	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
 		// code...
		$start_time  = time() - 30*86400;
		$end_time    = time();
		$date_search = $Request->get('date_search');
		$orderCode   = $Request->get('orderCode', null);
		$ServiceProId   = $Request->get('ServiceProId ', null);
		if($date_search){
         $arrDate    = explode('-', str_replace(' ', '', $date_search));
         
         $start_time = convertDateTime($arrDate[0], '00:00:00') ;
         $end_time   = convertDateTime($arrDate[1], ' 23:59:59');
      }

 		$params = [ 
 			"act"					 => "product",
			'dateStart'        => date('d/m/Y', $start_time),
			'dateEnd'          => date('d/m/Y', $end_time),
			'title'            => 'Doanh thu dịch vụ',
			'orderCode'        => $orderCode,
			'ServiceProId '    => $ServiceProId,
			'date_search' => $date_search,
		];

		/*Số liệu*/
		$dataRequest = [
			"act"        => "product",
			"search"     => ($orderCode != null) ? $orderCode : $orderCode ?? '',
			"start_date" => date('d/m/Y', $start_time),
			"end_date"   => date('d/m/Y', $end_time),
		];

		$result   = $this->StatisticRepository->getStatistic($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}

		$data = [ 
			'params'		=> $params,
			'listData'  => $listData,
		];

 		return view('statistic.service')->with($data);
 	}

 	public function doExport(Request $Request){
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		$act          = $Request->get('act');
		$date_search  = $Request->get('date_search');
		$start_time   = time() - 30*86400;
		$end_time     = time();
		$orderCode    = $Request->get('orderCode', null);
		$ServiceProId = $Request->get('ServiceProId ', null);
		if($date_search){
         $arrDate    = explode('-', str_replace(' ', '', $date_search));         
         $start_time = convertDateTime($arrDate[0], '00:00:00') ;
         $end_time   = convertDateTime($arrDate[1], ' 23:59:59');
      }

      /*Số liệu*/
		$dataRequest = [
			"act"        => $act,
			"search"     => ($orderCode != null) ? $orderCode : $ServiceProId ?? '',
			"start_date" => date('d/m/Y', $start_time),
			"end_date"   => date('d/m/Y', $end_time),
		];

		$result   = $this->StatisticRepository->getStatistic($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}

 		switch ($act) {
			case 'total': 
				$title = 'Doanh thu tổng hợp';
				$file_name  = 'Statistic_total_' . date('Ymd', time()).'.xlsx';
				break;
 			case 'product': 
 				$title = 'Doanh thu dịch vụ';
 				$file_name  = 'Statistic_product_' . date('Ymd', time()).'.xlsx';
 				break;
 			case 'fee': 
 				$title = 'Doanh thu phí dịch vụ';
 				$file_name  = 'Statistic_fee_' . date('Ymd', time()).'.xlsx';
 				break;
 		}

 		/*Export*/
 		$arr_export = [];
		$arr_export[] = [
			$title ?? "Doanh thu dịch vụ", '', '', '', '', '', ''
		];
		$arr_export[] = [
			"Stt", 'Ngày giao dịch', 'Mã đơn hàng','Số tiền', 'Đơn vị cung cấp', 'Khách hàng', 'Số điện thoại', 
		];

		if($listData){
			foreach ($listData as $key => $item) {
				$amount = 0;
				switch ($act) {
					case 'product':
						$amount = isset($item['price']) ? $item['price'] : 0;
						break;
					
					case 'fee':
						$amount = isset($item['fee']) ? $item['fee'] : 0;
						break;
				}

				$arr_export[] = [
					$key + 1,
				   $item['tranDate']	,
					$item['orderId'] ?? "",
					(string) $amount,
					$item['partnerId'] ?? "",
					$item['customerId'] ?? "",
					$item['customerMobile'] ?? "",
				];	
			}
		}

		$export = new ComboPriceExport($arr_export);		
		return Excel::download($export, $file_name);
 	}

	public function dailypriceExelUpdate(Request $request, $id){
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		$tourid = intval($id);
		$return 	  = [
			'msg' => '', 'status' => 0, 'data' => []
		];
 

		if ($request->hasFile('updatePriceExel')) {
			$d = [];
			$d = Excel::toArray($d, request()->file('updatePriceExel'));
			
			$times = [];

			foreach ($d[0] as $key => $data) {
				if($key < 2) continue;
				if($data[1] == '' || $data[1] == null) continue;

				$times[] = [
					'id' => $data[0] ?? null,
					'start'=> $data[1],					
					'prices' => [ 
						'AD' => $data[2],
						'CH' => $data[3],
						'BB' => $data[4],
					],
					'guest' => $data[5],
					'disable' => $data[6],
				];
			}
 		
		}
	}
 
	public function StatisticPaymentGate(Request $request)
	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		// code...
		$start_time  = time() - 30 * 86400;
		$end_time    = time();
		$date_search = $request->get('date_search'); 

		if ($date_search) {
			$arrDate    = explode('-', str_replace(' ', '', $date_search));

			$start_time = convertDateTime($arrDate[0], '00:00:00');
			$end_time   = convertDateTime($arrDate[1], ' 23:59:59');
		}
		$checkResult = $request->get('checkResult', null);
		$isFc = $request->get('isFc', null);
		$transId = $request->get('transId', null);
		$orderCode = $request->get('orderCode', null);
		$params = [
			"act"	=> "total",
			'dateStart'	=> date('d/m/Y', $start_time),
			'dateEnd'	=> date('d/m/Y', $end_time),
			'title' => 'Báo cáo cổng thanh toán',
			'orderCode' => $orderCode,
			'transId' => $transId,
			'checkResult' => $checkResult,
			'isFc' => $isFc,
		];		
		
		/*Số liệu*/
		$dataRequest = [
			"transDate" => date('d/m/Y', $start_time) . ' ' . date('d/m/Y', $end_time),
			"transId"   => $transId,
			"orderCode" => $orderCode,
			"result"    => $checkResult,
			"isFc"      => $isFc,
		];
		// dd($dataRequest);
		$result  = $this->StatisticRepository->getPaymentGateReport($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}
		$Total = 0;

		// data
		$arr_checkresult = [
			'00' => "Giao dịch khớp",
			'01' => "Đối tác không có giao dịch, VNPT Pay có giao dịch",
			'02' => "Đối tác có giao dịch, VNPT Pay không có giao dịch",
			'03' => "Dữ liệu sai lệch",
		];
		$arr_isFc = [
			0 => "Đang chờ",
			1 => "Đã đối soát"
		];
		$arr_rcReconcile = [
			'00' => 'Giao dịch khớp (đã đối soát)',
			'01' => 'Đối tác không có giao dịch, VNPT Pay có giao dịch (đã đối soát)',
			'02' => 'Đối tác có giao dịch, VNPT Pay không có giao dịch (đã đối soát)',
			'03' => 'Dữ liệu sai lệch (đã đối soát)',
			'99' => 'Chưa đối soát ',
		];

		$data = [
			'params'   => $params,
			'listData' => $listData,
			'Total'    => $Total,
			'arr_checkresult' => $arr_checkresult,
			'arr_isFc' => $arr_isFc,
			'arr_rcReconcile' => $arr_rcReconcile,
		];

		return view('statistic.payment_gate')->with($data);
	}

	public function StatisticrefundMoney(Request $request)
	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		// code...
		$start_time  = time() - 30 * 86400;
		$end_time    = time();
		$date_search = $request->get('date_search', date('d/m/Y', time()));

		 
		$params = [
			"act"	=> "total",
			'dateStart'	=> $date_search, 
			'title' => 'Báo cáo hoàn tiền', 
		];

		/*Số liệu*/
		$dataRequest = [
			"date" => $date_search, 
		];
		
		$result  = $this->StatisticRepository->RefundReport($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}
		$Total = 0; 

		$data = [
			'params'   => $params,
			'listData' => $listData,
			'Total'    => $Total, 
		];

		return view('statistic.payment_refund')->with($data);
	}

	public function StatisticrevanueMoney(Request $request)
	{	
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		// code...
		$start_time  = time() - 30 * 86400;
		$end_time    = time();
		$date_search = $request->get('date_search'); 

		if ($date_search) {
			$arrDate    = explode('-', str_replace(' ', '', $date_search));

			$start_time = convertDateTime($arrDate[0], '00:00:00');
			$end_time   = convertDateTime($arrDate[1], ' 23:59:59');
		}

		$transId 	= $request->get('transId', null);
		$orderCode 	= $request->get('orderCode', null);
		$partnerCode = $request->get('partnerCode', null);
		$params = [
			"act"	      => "total",
			'dateStart'	=> date('d/m/Y', $start_time),
			'dateEnd'	=> date('d/m/Y', $end_time),
			'title'     => 'Báo cáo phân chia doanh thu',
			'orderCode' => $orderCode,
			'transId'   => $transId,
			'partnerCode' => $partnerCode,
		]; 
		/*Số liệu*/
		$dataRequest = [
			"transDate" => date('d/m/Y', $start_time) . ' ' . date('d/m/Y', $end_time),
			"transId"   => $transId,
			"orderCode" => $orderCode,
			'partner'   => $partnerCode,
		];
		// dd($dataRequest);
		$result  = $this->StatisticRepository->RevenueReport($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}
		$Total = 0; 
		/*Đối tác*/
		$partners = [];	
		$requestData = ["act" => "list"];
		$data = $this->AgencyRepository->managePartner($requestData);
		if($data['status'] == 0 && isset($data['data']['list'])){
			foreach ($data['data']['list'] as $key => $value) {
				$partners[$value['code']]	 = $value['name'];
			}
		}

		$data = [
			'params'   => $params,
			'listData' => $listData,
			'Total'    => $Total, 
			'partners' => $partners,
		];

		return view('statistic.payment_ravenue')->with($data);
	}

	public function Statisticrevanue_export(Request $request )
	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		$dateStart  = $request->get('dateStart', '');
		$dateEnd    = $request->get('dateEnd', '');
		$transId 	= $request->get('transId', null);
		$orderCode 	= $request->get('orderCode', null);
		$partnerCode = $request->get('partnerCode', null);
		/*Số liệu*/
		$dataRequest = [
			"transDate" => $dateStart . ' ' . $dateEnd,
			"transId"   => $transId,
			"orderCode" => $orderCode,
			'partner'   => $partnerCode,
		];
		// dd($dataRequest);
		$result  = $this->StatisticRepository->RevenueReport($dataRequest);
		$listData = [];
		if ($result['status'] == 0) {
			$listData = $result['data'] ?? [];
		}
		
		/*Export*/
		$arr_export = [];
		$arr_export[] = [
			$title ?? "Báo cáo phân chia doanh thu", '', '', '', '', '', ''
		];
		$arr_export[] = ["", '','', '','', '', '', '', '', ''];
		$arr_export[] = [
			"Stt", 'Ngày giao dịch','Mã giao dịch', 'Mã đơn hàng','Doanh thu', 'Nhà cung cấp', 'Đai lý', 'Giá trị ĐH', 'Giá gốc', 'Khuyến mãi'
		];

		if($listData){
			foreach ($listData as $key => $item) {				
				$arr_export[] = [
					$key + 1,
				   $item['tranDate']	,
					(string) $item['tranId'],
					(string) $item['orderId'] ?? "",
					(string) $item['amount'],
					$item['partner'] ?? "",
					$item['agency'] ?? "",
					(string) $item['total_price'] ?? "",
					(string) $item['price'] ?? "",
					(string) $item['discount'] ?? "",
				];	
			}
		}
		$file_name  = 'Bao_cao_doanh_thu_' . date('Ymd', time()).'.xlsx';

		$export = new ComboPriceExport($arr_export);		
		return Excel::download($export, $file_name);
	}

	public function Statistic_payment_form(Request $request)
	{		
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		/*Đối tác*/
		$partners = [];	
		$requestData = ["act" => "list"];
		$data = $this->AgencyRepository->managePartner($requestData);
		if($data['status'] == 0 && isset($data['data']['list'])){
			foreach ($data['data']['list'] as $key => $value) {
				$partners[$value['code']]	 = $value['name'];
			}
		}

		// code...
		$start_time  = time() - 30 * 86400;
		$end_time    = time();
		$date_search = $request->get('date_search'); 
		$type        = $request->get('type','product_services');
		

		if ($date_search) {
			$arrDate    = explode('-', str_replace(' ', '', $date_search));
			$start_time = convertDateTime($arrDate[0], '00:00:00');
			$end_time   = convertDateTime($arrDate[1], ' 23:59:59');
		}

		$params = [
			'title' => 'Biểu mẫu đối soát',
			'type' => $type,
			'dateStart'	=> date('d/m/Y', $start_time),
			'dateEnd'	=> date('d/m/Y', $end_time),
		];

		$listData = [];
		/*Số liệu*/
		$dataRequest = [
			"transDate" => date('d/m/Y', $start_time) . ' ' . date('d/m/Y', $end_time),
			"transId"   => null,
			"orderCode" => null,
			'partner'   => null,
		];
		// dd($dataRequest);
		$result  = $this->StatisticRepository->RevenueReport($dataRequest);
		$listData          = [];
		$total_amount_show = 0;
		$total_amount      = 0;
		$total_order_price = 0;
		$total_price_all   = 0;
		$total_count       = 0;

		if ($result['status'] == 0) {						
			foreach ($result['data'] as $key => $value) {
				$total_amount      += $value['amount'];
            $total_order_price += $value['total_price'];
            $total_price_all   += $value['price'];
            $total_count++;
			}
		}

		// Switch template biểu mẫu

		switch ($type) {
			case 'product_services':
				$total_amount_show      = $total_price_all;
				$txt_company_name       = 'CÔNG TY CỔ PHẦN TRUYỀN THÔNG SÔNG SÁNG';
				$txt_company_short_name = 'SSMedia';
				$txt_product_fee        = 'Doanh thu sản phẩm';
				$txt_title_fee          = 'Sản lượng và chi phí phải trả';
				$txt_date               = 'NGÀY '. date('d', $start_time) .' THÁNG '. date('m', $start_time) .' NĂM '. date('Y', $start_time);
				$txt_payment_to = 'Chi phí trả SS Media';
				$txt_percent = 100;
				$html_note = '<p>- Căn cứ Hợp đồng hợp tác cung cấp sản phẩm trên nền tảng dịch vụ TripV số 02/2022/HĐHT/MEDIA-VAS-SSMEDIA ký ngày 08/03/2022 giữa Công ty Phát triển Dịch vụ Giá trị gia tăng - Chi nhánh Tổng công ty Truyền thông và Công ty Cổ phần Truyền thông Sông sáng (SS Media);</p>
				<p>-Căn cứ Bảng thông báo sản lượng/doanh thu dịch vụ TripV ngày '. date('d/m/Y', $start_time) .' số <font color="red">[Số biểu đối soát]</font> ký ngày <font color="red">[Ngày ký biểu]</font> của Trung tâm Dịch vụ Tài chính số VNPT (VNPT FINTECH); Công ty Phát triển Dịch vụ Giá trị gia tăng (VNPT VAS) và Công ty Cổ phần Truyền thông Sông Sáng (SS Media) xác nhận sản lượng/doanh thu dịch vụ TripV như sau:</p> ';
				break;			
			case 'processing':
				$total_amount_show      = $total_amount;
				$txt_company_name       = 'CÔNG TY CỔ PHẦN CÔNG NGHỆ VÀ TRUYỀN THÔNG VNLINK';
				$txt_company_short_name = 'VNLINK';
				$txt_product_fee        = 'Doanh thu phí dịch vụ';
				$txt_title_fee          = 'Sản lượng và phân chia doanh thu';
				$txt_date               = 'THÁNG '. date('m', $start_time) .' NĂM '. date('Y', $start_time);
				$txt_payment_to = 'Doanh thu phân chia bên B được hưởng';
				$txt_percent = 10;
				$html_note = '<p>- Căn cứ Hợp đồng hợp tác cung cấp giải pháp hệ thống cho dịch vụ TripV trên mạng di động VinaPhone số 02/2022/HĐHT/MEDIA-VAS-VNLINK ký ngày 03/03/2022 giữa Công ty Phát triển Dịch vụ Giá trị gia tăng - Chi nhánh Tổng công ty Truyền thông và Công ty Cổ phần Công nghệ và Truyền thông VNLink;</p>
				<p>- Căn cứ Bảng thông báo sản lượng/doanh thu dịch vụ TripV tháng '. date('m/Y', $start_time) .' số <font color="red">[Số biểu đối soát]</font> ký ngày <font color="red">[Ngày ký biểu]</font> của Trung tâm Dịch vụ Tài chính số VNPT (VNPT FINTECH); Công ty Phát triển Dịch vụ Giá trị gia tăng (VNPT VAS) và Công ty Cổ phần Công nghệ và Truyền thông VNLink (VNLink) xác nhận sản lượng/doanh thu dịch vụ TripV như sau:</p>';
				break;
			case 'customer_care':
				$total_amount_show      = $total_amount;
				$txt_company_name       = 'CÔNG TY CỔ PHẦN TRUYỀN THÔNG SÔNG SÁNG';
				$txt_company_short_name = 'SSMedia';
				$txt_product_fee        = 'Doanh thu phí dịch vụ';
				$txt_title_fee          = 'Sản lượng và phân chia doanh thu';
				$txt_payment_to = 'Doanh thu phân chia bên B được hưởng';
				$txt_date               = 'THÁNG '. date('m', $start_time) .' NĂM '. date('Y', $start_time);
				$txt_percent = 12;
				$html_note = '<p>- Căn cứ Hợp đồng hợp tác cung cấp sản phẩm trên nền tảng dịch vụ TripV số 02/2022/HĐHT/MEDIA-VAS-SSMEDIA ký ngày 08/03/2022 giữa Công ty Phát triển Dịch vụ Giá trị gia tăng - Chi nhánh Tổng công ty Truyền thông và Công ty Cổ phần Truyền thông Sông sáng (SS Media);</p>
				<p>- Căn cứ Bảng thông báo sản lượng/doanh thu dịch vụ TripV ngày '. date('d/m/Y', $start_time) .' số <font color="red">[Số biểu đối soát]</font> ký ngày <font color="red">[Ngày ký biểu]</font> của Trung tâm Dịch vụ Tài chính số VNPT (VNPT FINTECH); Công ty Phát triển Dịch vụ Giá trị gia tăng (VNPT VAS) và Công ty Cổ phần Truyền thông Sông Sáng (SS Media) xác nhận sản lượng/doanh thu dịch vụ TripV như sau:</p>';
				break;					
		}

		$listData = [
			'numb_order' 	           => $total_count,
			'total_amount'           => $total_amount_show,
			'txt_date' 		            => $txt_date,
			'txt_title_fee'			       => $txt_title_fee,
			'txt_company_name'       => $txt_company_name,
			'txt_product_fee'        => $txt_product_fee,
			'txt_company_short_name' => $txt_company_short_name,
			'html_note'              => $html_note,
			'txt_payment_to'         => $txt_payment_to,
			'txt_percent' => $txt_percent,
		];

		$data = [
			'params'	 => $params,
			'partners' => $partners,
			'listData' => $listData,
		];
		return view('statistic.payment_form_doisoat')->with($data);
	}

	public function export_form_doisoat(Request $request) 
	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		return Excel::download(new formDoisoatExport($request->all()), 'doisoat.xlsx');
	}

	public function export_invoice_customer(Request $request)
	{	
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		// code...
		$start_time  = time() - 30 * 86400;
		$end_time    = time();
		$date_search = $request->get('date_search');  
		

		if ($date_search) {
			$arrDate    = explode('-', str_replace(' ', '', $date_search));
			$start_time = convertDateTime($arrDate[0], '00:00:00');
			$end_time   = convertDateTime($arrDate[1], ' 23:59:59');
		}
		$params = [
			'title' 	=> 'BIỂU MẪU THỐNG KÊ DSKH XUẤT HOÁ ĐƠN DỊCH VỤ TRIPV',
			'dateStart'	=> date('d/m/Y', $start_time),
			'dateEnd'	=> date('d/m/Y', $end_time),
			'start' => date('Ymd', $start_time),
			'end'  => date('Ymd', $end_time),
		];

		// Request data 
		$requestData = [
			'start_date' => date('Ymd', $start_time),
			'end_date' => date('Ymd', $end_time),
		];
		
		$result 		= $this->StatisticRepository->CustomerInvoiceReport($requestData);
		$listData 	=  [];
		if($result['status'] == 0 && $result['data'] != null){
			$listData = $result['data'];			
		}
		
		$data = [
			'params' => $params,
			'listData' => $listData,
		];

		return view("statistic.InvoiceExportList")->with($data);
	}

	public function invoice_customer_excel(Request $request)
	{
		$arrPermision = app()->USER_PERMISION;
		if(!in_array('report_manager_view', $arrPermision)){
			 return redirect(route("access_denied"));
		}
		$start_date = $request->get('start_date');
		$end_date = $request->get('end_date');
		// Request data 
		$requestData = [
			'start_date' => $start_date,
			'end_date' => $start_date,
		];
		
		$result 		= $this->StatisticRepository->CustomerInvoiceReport($requestData);
		$listData 	=  [];
		if($result['status'] == 0){
			$listData = $result['data'];			
		}
		
		$arr_export = [];
		$arr_export[] = [
			"Danh sách khách hàng xuất hóa đơn", 
		];
		$arr_export[] = [
			"Từ ngày: " . $start_date , 
		];
		$arr_export[] = [
			"Đến ngày: ". $end_date, 
		];
		$arr_export[] = [
			"TT",	"Ngày đặt hàng", "Tên tài khoản", "Email", "Tên doanh nghiệp", "MST", "Nội dung mua hàng", "ĐVT", "Số lượng", "Số tiền trước thuế", "Thuê VAT",	"Số tiền sau thuế",
		];
		$i = 0;
      foreach ($listData as $data) {
			$i++;
         $arr_export[] = [
				$i, 
				$data['date'],
				$data['identification'],
				$data['email'],
				$data['company_name'],
				$data['tax_code'],
				$data['content'],
				$data['unit'],
				$data['pax_num'],
				number_format($data['price']),
				number_format($data['vat']),
				number_format($data['total']),
			];
		}

		// dd($arr_export);
		$export = new ComboPriceExport($arr_export);
		$file_name  = 'Danh_sach_khach_hang_xuat_hoa_don_' . $start_date . '_' .$end_date .'.xlsx';
		return Excel::download($export, $file_name);		
	}
}