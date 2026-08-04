<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class ContactPage extends Component
{
    public function render()
    {
        return view('livewire.storefront.contact');
    }
}
