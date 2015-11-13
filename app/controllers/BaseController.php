<?php

class BaseController extends Controller
{

    public function __construct()
    {
        $this->travelStudioClient = 'initialize travel studio client here';
    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout = View::make($this->layout);
        }
    }

}
