<table class='table table-striped'>
  <th>Season Period</th>
  <th>Buying Price</th>
  <th>Selling Price</th>
  <th>Total Buying Price</th>
  <th>Total Selling Price</th>
  @foreach ($prices as $key => $price)
    <tr>
      <td>
        <span class='display-block'>START : {{ $price->start }}</span>
        <span class='display-block'>END : {{ $price->end }}</span>
      </td>
      <td>{{ $price->buy_price }}</td>
      <td>{{ $price->sell_price }}</td>
      @if ($key == 0)
        <td rowspan="{{ count($prices) }}">{{ $totalBuyingPrice }}</td><td rowspan="{{ count($prices) }}">{{ $totalSellingPrice }}</td>
      @endif
    </tr>
  @endforeach
</table>