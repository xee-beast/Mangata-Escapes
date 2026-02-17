<?php

 namespace App\Http\Controllers\Traits;

 use Illuminate\Support\Facades\Auth;

 trait RedirectTo
 {
     public function redirectTo()
     {
         return route('dashboard', ['uri' => '/']);
     }
 }
