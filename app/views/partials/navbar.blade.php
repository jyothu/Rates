<nav class="navbar navbar-inverse navbar-fluid-top">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="{{ URL::route('landing') }}">Service Rates</a>
    </div>
    <div id="navbar" class="collapse navbar-collapse">
      <ul class="nav navbar-nav">
        <li class="{{ Route::currentRouteName() == 'rates' ? 'active' : '' }}"><a href="{{ URL::route('rates') }}">Rates</a></li>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
</nav>