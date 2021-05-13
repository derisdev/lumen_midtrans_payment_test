<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function make(){
        return Hash::make('!zharletclassicimplements');
    }
}
