<?php


namespace App\Modules\Configuration;

use App\Model;
use Exception;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class View extends Model
{
    /**
     * @var bool
     */
    protected bool $hasConn = false;

    /**
     * @var string
     */
    protected string $template;

    /**
     * @var array
     */
    protected array $vars = [];

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * View constructor.
     * @param string $template
     * @param array $vars
     * @throws Exception
     */
    public function __construct(string $template, array $vars = [])
    {
        parent::__construct();

        $this->set('template', $template);

        $title = APP_NAME;
        if(isset($vars['title'])) {
            $title .= ' - ' . $vars['title'];
        }
        $vars['title'] = $title;

        if (!empty($vars)) {
            $this->set('vars', $vars);
        }


        $loader = new FilesystemLoader(APP_VIEWS_PATH);
        $twig = new Environment($loader, [
            'cache' => APP_CACHE_PATH . '\framework\views',
            'debug' => true,
        ]);
        $twig->addExtension(new DebugExtension());

        $this->set('twig', $twig);
    }

    /**
     * @param string $key
     * @param $value
     * @throws Exception
     */
    public function assign(string $key, $value)
    {
        $vars = $this->get('vars');
        $vars[$key] = $value;
        $this->set('vars', $vars);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function render()
    {
        $twig = $this->get('twig');
        $template = $this->get('template');
        $vars = $this->get('vars');

        return $twig->render($template, $vars);
    }
}