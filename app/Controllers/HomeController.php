<?php


namespace App\Controllers;

use App\Modules\Configuration\View;
use Exception;

/**
 * Class HomeController
 * @package App\Controllers
 */
class HomeController extends Controller
{
    /**
     * @throws Exception
     */
    public static function home() {
        $view = new View('home.twig');
        try {
            return $view->render();
        } catch (Exception $e) {
            dd($e);
        }
    }

}