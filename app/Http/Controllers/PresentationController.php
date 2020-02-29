<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PresentationController extends Controller
{

  // For public application
  public function show()
  {
    return view('presentation');
  }
}
