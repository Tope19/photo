@extends('layouts.app')
@section('content')
<div class="row">
    <div class='col-md-12'>
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <div class="row">
                    <div class="col-sm-8">
                        <h4>
                            Edit {{$model->id}}
                        </h4>
                    </div>
                    <div class="col-sm-4 text-right">
                        <a href="{{route('photo::photos.create')}}">
                            <span class="fa fa-plus"></span> photo_photos
                        </a>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                @include('photo::forms.photo',[
                'route'=>route('photo::photos.update',$model->id),
                'method'=>'PUT'
                ])
            </div>
        </div>
    </div>
</div>
@endSection