<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\PxeResourceService;
use App\Model\PxeTargetModel;

class iPxeRenderService
{
    protected $pes = null;
    protected $menuTitle = 'iPXE Boot Menu';
    protected $defines = [];
    protected $menu = [];
    protected $mode = '';
    protected $baseUrl = '';

    protected $targets = [];

    public function __construct(ContainerInterface $container, PxeResourceService $pes)
    {
        $config = $container->getParameter('ipxe_menu');

        if(isset($config['title'])) $this->menuTitle = $config['title'];
        if(is_array($config['defines'])) $this->defines = $config['defines'];
        if(is_array($config['menu'])) $this->menu = $config['menu'];

        $this->pes = $pes;
    }

    public function setup($mode): void
    {
        $this->mode = $mode;
        $targets = [];
        $gaps = 0;

        foreach ($this->menu as $m) {

            if($m['type'] == 'repository'){

                $targets = array_merge(
                    $targets,
                    $this->fetchRepositoryTargets($m)
                );

                continue;
            }

            if($m['type'] == 'gap'){
                $targets['gap'.$gaps++] = $m;
                continue;
            }
        }

        $this->targets = $targets;
    }

    public function getMenuItens(): array
    {
        $menu = [];

        $this->eachMenuTarget(
            function($key, $target) use (&$menu)
            {
                if($target instanceof PxeTargetModel){
                    $menu[] = implode(' ', ['item', $key, $target->getLabel()]);
                    return;
                }

                if($target['type'] == 'gap'){

                    $visibility = isset($target['visibility']) ? $target['visibility'] : $this->mode;

                    if($visibility === $this->mode){
                        $menu[] = implode(
                            ' ',
                            array_merge(
                                ['item', '--gap'],
                                isset($target['label']) ? [$target['label']] : []
                            )
                        );
                    }

                    return;
                }
            }
        );

        return $menu;
    }

    public function getMenuCallbacks(): array
    {
        $callbacks = [];

        $this->eachMenuTarget(
            function($key, $target) use (&$callbacks)
            {
                if(!($target instanceof PxeTargetModel)) return;

                $bp = $target->getBasePath();

                $script = [
                    $this->parseScriptLine([
                        'echo',
                        'Booting',
                        $target->getLabel(),
                        '...'
                    ], false),
                    $this->parseScriptLine([
                        'kernel',
                        self::resolveFilePath($target->getKernel(), $bp)
                    ])
                ];

                foreach ($target->getInitrd() as $k => $v) {
                    $line = ['initrd', self::resolveFilePath($k, $bp)];
                    foreach ($v as $o => $a){
                        if(is_string($o)) $line[] = '--' . $o;
                        $line[] = $a;
                    }
                    $script[] = $this->parseScriptLine($line);
                }

                $imgargs = $target->getImgargs();
                if(!empty($imgargs)){
                    $script[] = $this->parseScriptLine(
                        array_merge(['imgargs'], $imgargs)
                    );
                }

                $script[] = $this->parseScriptLine(['echo', 'Please wait...'], false);
                $script[] = $this->parseScriptLine(['boot']);

                $callbacks[$key] = $script;
            }
        );

        return $callbacks;
    }

    protected function fetchRepositoryTargets(array $options): array
    {
        $repository = $this->pes->getRepository($options['target']);

        if(!isset($options['dist'])) $dist = $repository->getDists();
        elseif (!is_array($options['dist'])) $dist = [$options['dist']];
        else $dist = $options['dist'];

        $result = [];
        foreach ($dist as $d) {
            $targets = $repository->findByDistAndMode($d, $this->mode);
            foreach ($targets as $t) $result[$this->parseTargetId($t)] = $t;
        }

        return $result;
    }

    protected function eachMenuTarget(\Closure $handle): void
    { foreach ($this->targets as $k => $v) $handle($k, $v); }

    protected function parseTargetId(PxeTargetModel $model): string
    {
        return implode(
            '_',
            [
                self::removeSpecial($model->getRepositoryName()),
                self::removeSpecial($model->getDist()),
                self::removeSpecial($model->getArch())
            ]
        );
    }

    protected function parseScriptLine(array $args, bool $withFail = true): string
    {
        return implode(
            ' ',
            array_merge(
                $args,
                $withFail ? ['||', 'goto', 'failed'] : []
            )
        );
    }

    public function getMenuTitle(): string
    { return $this->menuTitle; }

    public function getDefines(): array
    {
        $defines = [
            'set _mode_ ' . $this->mode,
            'set _baseurl_ ' . $this->baseUrl,
            "cpuid --ext 29 && set _arch_ amd64 || set _arch_ i386",
            'set _default_timeout_ 2000'
        ];

        return array_merge($defines, $this->defines);
    }

    protected static function removeSpecial(string $str): string
    {
        return str_replace(
            ['.', '_', '-'],
            '',
            $str
        );
    }

    protected static function resolveFilePath(string $file, string $basePath): string
    { return '${_baseurl_}' . ($file[0] !== '/' ? $basePath.'/' : '') . $file; }
}
