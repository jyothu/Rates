@extends('layouts.master')
@section('body')
<a href="{{ route('services.create') }}" class="btn btn-primary">Create a Service</a>
<h3>All Services </h3>

<div class="col-sm-12 search-form">
  <table class="table table-striped">
    <tr>
      <th>#</th>
      <th>TS ID</th>
      <th>Service Name</th>
      <th>Service Type</th>
      <th>Region</th>
      <th>Supplier</th>
      <th>Currency</th>
      <th>Actions</th>
    </tr>
    @foreach ($services as $key=>$service)
        <tr>
            <td><a href="{{ route('services.show', $service->id) }}" class="">{{$service->id}}</a></td>
            <td>{{$service->ts_id}}</td>
            <td>{{$service->name}}</td>
            <td>{{$service->serviceType->name}}</td>
            <td>{{$service->region->name}}</td>
            <td>{{$service->supplier->name}}</td>
            <td>{{$service->currency->symbol}}</td>
            <td><a href="{{ route('services.edit', $service->id) }}" class="btn btn-primary btn-mini">Edit</a></td>
        </tr>
    @endforeach
  </table>
  <?php echo $services->links(); ?>
</div>
@stop