<?php 

namespace App\Models;

class ExchangeRate extends \Illuminate\Database\Eloquent\Model {

	protected $fillable = array('from_currency', 'to_currency', 'rate', 'status', 'id');

}

?>