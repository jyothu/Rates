@extends('layouts.master')
@section('body')
<h2>New Service</h2>

<div class="col-sm-12 search-form">
{{ Form::model($service) }}
    <div class="col-sm-3">
        <div class="form-group">
            {{ Form::label('ts_id', 'Service TS ID', array('class' => '')) }}
            {{ Form::text('ts_id', $service->ts_id, array('class' => 'form-control')) }}      
        </div>
        <div class="form-group">
            {{ Form::label('name', 'Service Name', array('class' => '')) }}
            {{ Form::text('name', $service->name, array('class' => 'form-control')) }}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {{ Form::label('short_name', 'Short Name', array('class' => '')) }}
            {{ Form::text('short_name', $service->short_name, array('class' => 'form-control')) }}      
        </div>
        <div class="form-group">
            {{ Form::label('service_type', 'Service Type', array('class' => '')) }}
            {{ Form::select('service_type', ['default' => 'Please select']+$serviceTypes, null, array('class' => 'form-control')) }}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {{ Form::label('region', 'Region', array('class' => '')) }}
            {{ Form::select('region', ['default' => 'Please select']+$regions, null, array('class' => 'form-control')) }}
        </div>
        <div class="form-group">
            {{ Form::label('currency', 'Currency', array('class' => '')) }}
            {{ Form::select('currency', ['default' => 'Please select']+$currencies, null, array('class' => 'form-control')) }}
        </div>
    </div>
{{ Form::close() }}
</div>
@stop