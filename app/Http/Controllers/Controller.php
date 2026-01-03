<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

abstract class Controller
{
    public function loadAccount()
    {
        return Auth::user()->load(['profile:id,account_id,name,age,address,avatar,phone,cover_photo,bio,social_link']);
    }
}
