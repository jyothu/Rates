@if (empty($serviceOptions))
  Invalid Service ID
@else
  <option>Select Service Options</option>
  @foreach ($serviceOptions as $serviceOption)
  	<option value="{{ $serviceOption->id }}">{{ $serviceOption->name }}</option>
	@endforeach
@endif
