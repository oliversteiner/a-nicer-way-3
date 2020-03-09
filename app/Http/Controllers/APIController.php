<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class APIController extends Controller
{
  public $boards;

  // For public application
  public function api()
  {
    $boards = [
      0 => [
        'id' => 1,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      1 => [
        'id' => 2,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      2 => [
        'id' => 3,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      3 => [
        'id' => 4,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      4 => [
        'id' => 5,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      5 => [
        'id' => 6,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      6 => [
        'id' => 7,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      7 => [
        'id' => 8,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      8 => [
        'id' => 9,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      9 => [
        'id' => 10,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ],
      10 => [
        'id' => 11,
        'notes' =>
          'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '
      ]
    ];

    return $boards;
  }
}
