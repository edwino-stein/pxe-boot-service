<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;

use App\Exception\ToolOrRepositoryNotDefined;

class PxeResourceService
{
    protected $publicPath = 'public';
    protected $rootDir = '';

    protected $tools = [];

    public function __construct(ContainerInterface $container)
    {
        $config = $container->getParameter('pxe_boot');
        $this->rootDir = $config['dirs']['root'];
        $this->publicPath = self::concatPath([
            $container->getParameter('kernel.project_dir'),
            $this->publicPath
        ]);

        $basePath = self::concatPath([$this->publicPath, $this->rootDir]);

        $this->tools = self::scanForTools(
            $config['tools'],
            $config['dirs']['tools'],
            $basePath
        );
    }

    public function getTool(string $tool) : string
    {
        if(!isset($this->tools[$tool])){
            throw new ToolOrRepositoryNotDefined($tool);
        }

        return $this->tools[$tool];
    }

    public static function scanForTools(array $toolsList, string $toolsDir, string $basePath): array
    {
        $path = self::concatPath([$basePath, $toolsDir]);
        $fs = new Filesystem();

        if(!$fs->exists($path)){
            throw new FileNotFoundException(null, 0, null, $path);
        }

        $tools = [];
        foreach ($toolsList as $t) {
            $finder = new Finder();
            foreach ($finder->in($path)->files()->name($t) as $file) {
                $tools[$t] = self::concatPath(['', $toolsDir, $file->getRelativePathname()]);
            }
        }

        return $tools;
    }

    public static function concatPath(array $paths): string
    { return implode('/', $paths); }
}
