<div class="col-sm-12 search-form">

  <form id="form">
    <div class="col-sm-4">
      <div class="form-group">
        <label for="inputEmail3">Service ID</label>      
        <input type='number' name="service_id" min="1" class="form-control" id="service_id" required />
      </div>
      <div class="form-group">
        <label for="inputEmail3">Service Options</label>        
        <select name="option" class="form-control" id="option" required></select> 
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        <label for="inputPassword3">CheckIn</label>        
        <div class='input-group date datetimepicker'>
        <input type='text' name="checkin" class="form-control" id="checkin" required/>
          <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
          </span>
        </div>
      </div>
      <div class="form-group">
        <label for="inputPassword3">CheckOut</label>        
        <div class='input-group date datetimepicker'>
          <input type='text' name="checkout" class="form-control" id="checkout" required/>
          <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
          </span>
        </div>
      </div>
    </div>

    <div class="col-sm-12">
      <div class="form-group">
        <button type="submit" class="btn btn-default" id="submit">Get Rates</button>
        <button type="reset" class="btn btn-default">Reset Results</button>
      </div>
    </div>
  </form>
</div>

<div class="col-sm-12 search-result">
  <div id="result">
    <h5>No Results Found!!</h5>
  </div>
</div>

