@extends('main')

@section('pagecss')	
	<link rel="stylesheet" type="text/css" href=" {{ asset('assets/css/post.css') }}">
	<link rel="stylesheet" type="text/css" href=" {{ asset('assets/css/daterangepicker.css') }}">
@endsection
@section('pagejs')
	<script src="{{ asset('assets/js/moment.js') }}"></script>
	<script src="{{ asset('assets/js/daterangepicker.js') }}"></script>
	<script src="{{ asset('/assets/ckeditor/ckeditor.js') }}"></script>
@endsection	
@section('content')
<div class="content"> 
<form role="form" method="post" action="{{route('location.add')}}" enctype="multipart/form-data" class="editPost" style="margin-top: 20px;">
	@csrf
	{!! Form::hidden("action", "insert", []) !!}
	<div class="right" style="width: 50%;">
		<h3>Tạo mới địa điểm</h3>
		<div>
			<p>Thành phố</p> 	
			{!! Form::select('cit_id', $arrCity, old('cit_id') ? old('cit_id') : null, ['class="form-control"']); !!}
		</div>
		<label style="padding: 21px 0px 10px;display: block;">Tên</label>
		{!! Form::text('name', old('name') ? old('name') : null, ['class' => 'form-control','placeholder' => 'Tên','style'=>'line-height: 40px; font-size: 15px;']) !!}
		<div>
			<p>Ảnh đại diện</p> 
			{!! Form::file("image", ['style' => 'padding-top: 9px;']) !!}
		</div>    
		<div>
			<p>Giới thiệu</p>
			{!! Form::textarea('teaser', old('teaser') ? old('teaser') : null, ['class' => 'form-control', 'style'=> 'height:80px']) !!}
		</div>
		<div>
			<p>Mô tả</p>
			{!! Form::textarea('description', old('description') ? old('description') : null, ['class' => 'form-control']) !!}
		</div>
		<div>
			<p>Thông tin trợ giúp <i>(EX: Tiêu đề|Địa chỉ|SDT|Ghi chú)</i></p>
			{!! Form::textarea('info_help', old('info_help') ? old('info_help') : null, ['class' => 'form-control',  'placeholder' => '', 'style'=> 'height:80px']) !!}
		</div>
		<div>
			<p>Trọng số</p>
			{!! Form::text('order', old('order') ? old('order') : null, ['class' => 'form-control']) !!}
		</div>
		<div>
			<p>Active</p>
			{!! Form::checkbox('active', '1', old('active') ? true : false, ['class' => '', 'id' => 'active','style' => 'width:15px;']) !!}
		</div>
		<div> 
			<button onclick="showLoading()" class="btn btn-submit" style="padding: 10px 20px;">Xác nhận</button>
		</div>
		<br>
	</div> 
</form>
</div>
@component('component.loading-dots')
@endcomponent
<script type="text/javascript">
	
	function showLoading(){
		$('.loading-dots').show();
	}

	 
	$(document).ready(function(){
		CKEDITOR.replace('description',{
			extraPlugins : 'uicolor',
			height: '400px',
			width:'100%',  
		}); 
	})
</script>
@endsection	