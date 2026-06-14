<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class Navbar extends Component
{
    public $isLogged;
    public $user;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->isLogged = Auth::check();
        $this->user = $this->isLogged ? Auth::user() : null;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.navbar');
    }
}
