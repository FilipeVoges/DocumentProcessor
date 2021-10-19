<?php


namespace App\Controllers;

use App\Modules\Configuration\View;
use Exception;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Class AppController
 * @package App\Controllers
 */
class AppController extends Controller
{

    /**
     * @return mixed
     * @throws Exception
     */
    public static function home() {
        $view = new View('home.twig');
        return $view->render();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public static function process()
    {
        $file = $_FILES['fileToProcess'] ?: NULL;
        if(is_null($file)) {
            throw new Exception("Nenhum arquivo para ser processado");
        }

        $filename = $file['name'];

        $directory = appDir(env('APP_CACHE_PATH')) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
        $filePath = $directory . $filename;
        if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $filePath)) {
            $view = new View('process.twig');
            $fileToProcess = new TemplateProcessor($filePath);

            $fileVars = $fileToProcess->getVariables();
            $view->assign('fileVars', $fileVars);
            $view->assign('fileName', $filename);

            return $view->render();
        }
    }

    public static function download() {
        $filename = $_POST['filename'] ?: NULL;
        if(is_null($filename)) {
            throw new Exception("Arquivo para ser processado não encontrado");
        }

        unset($_POST['filepath']);
        $directory = appDir(env('APP_CACHE_PATH')) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
        $filePath = $directory . $filename;
        $fileProcess = new TemplateProcessor($filePath);

        foreach ($_POST as $varFile => $newVal) {
            $fileProcess->setValue($varFile,$newVal);
        }

        $newFileName = 'PROCESSED_' . $filename;
        $newFilePath = $directory . $newFileName;
        $fileProcess->saveAs($newFilePath);
        downloadFile($newFileName);
    }
}