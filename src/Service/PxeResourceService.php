<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;

use App\Exception\ToolOrRepositoryNotDefined;

use App\Repository\AbstractPxeTargetRepository;

class PxeResourceService
{
    protected $publicPath = 'public';
    protected $rootDir = '';
    protected $mediaDir = '';

    protected $tools = [];
    protected $repositories = [];

    public function __construct(ContainerInterface $container)
    {
        $config = $container->getParameter('pxe_boot');
        $this->rootDir = $config['dirs']['root'];
        $this->publicPath = self::concatPath([
            $container->getParameter('kernel.project_dir'),
            $this->publicPath
        ]);

        $this->tools = self::scanForTools(
            $config['tools'],
            self::concatPath([$this->rootDir, $config['dirs']['tools']]),
            $this->publicPath
        );

        $this->mediaDir = $config['dirs']['media'];
        $this->repositories = self::scanForRepositories(
            $config['repositories'],
            self::concatPath([$this->rootDir, $this->mediaDir]),
            $this->publicPath
        );
    }

    public function getRepository(string $repository) : AbstractPxeTargetRepository
    {
        if(!$this->hasRepostory($repository)){
            throw new ToolOrRepositoryNotDefined($repository);
        }

        return AbstractPxeTargetRepository::create(
            $repository,
            $this->repositories[$repository]['class'],
            $this->repositories[$repository]['config'],
            $this
        );
    }

    public function hasRepostory(string $repository): bool
    { return isset($this->repositories[$repository]); }

    public function getRepositoryFinder(string $repository): Finder
    {
        if(!$this->hasRepostory($repository)){
            throw new ToolOrRepositoryNotDefined($repository);
        }

        $path = $this->getRepositoryPath($repository, true);
        $finder = new Finder();
        return $finder->in($path);
    }

    public function fileExistsInRepository(string $file, string $repository) : bool
    {
        if(!$this->hasRepostory($repository)){
            throw new ToolOrRepositoryNotDefined($repository);
        }

        $fs = new Filesystem();
        $path = self::concatPath([$this->getRepositoryPath($repository), $file]);

        return $fs->exists($path);
    }

    public function getTool(string $tool) : string
    {
        if(!isset($this->tools[$tool])){
            throw new ToolOrRepositoryNotDefined($tool);
        }

        return $this->tools[$tool];
    }

    public function getRepositoryPath(string $repository, bool $absolute = false): string
    {
        if(!$this->hasRepostory($repository)) return '';

        if($absolute){
            return self::concatPath([
                $this->publicPath,
                $this->rootDir,
                $this->mediaDir,
                $repository
            ]);
        }
        else {
            return self::concatPath([
                $this->rootDir,
                $this->mediaDir,
                $repository
            ]);
        }
    }

    public static function scanForRepositories(array $repoList, string $mediaDir, string $basePath): array
    {
        $path = self::concatPath([$basePath, $mediaDir]);
        $fs = new Filesystem();

        if(!$fs->exists($path)){
            throw new FileNotFoundException(null, 0, null, $path);
        }

        $repositories = [];
        foreach ($repoList as $r => $v) {
            try{

                $rp = self::concatPath([$path, $r]);
                if(!$fs->exists($rp)){
                    throw new ToolOrRepositoryNotDefined(
                        $r,
                        new FileNotFoundException(null, 0, null, $rp)
                    );
                }

                if(is_string($v)) $v = ['class' => $v];

                if(!is_array($v)){
                    throw new \InvalidArgumentException(
                        'Expected an array type in "'.$r.'" repository definition'
                    );
                }

                if(!isset($v['class']) || !is_string($v['class'])){
                    throw new \InvalidArgumentException(
                        'Class parameter must be a string with a valid repository'
                    );
                }

                if(!class_exists($v['class'])){
                    throw new \InvalidArgumentException(
                        'Invalid repository "'.$v['class'].'" class',
                        0,
                        new \Exception('"'.$v['class'].'" class not defined')
                    );
                }

                if(!is_subclass_of($v['class'],  AbstractPxeTargetRepository::class)){
                    throw new \InvalidArgumentException(
                        'Invalid repository "'.$v['class'].'" class',
                        0,
                        new \Exception(
                            '"'.$v['class'].'" class is not a subclass of "'.
                            AbstractPxeTargetRepository::class.'"'
                        )
                    );
                }

                if(!isset($v['config'])) $v['config'] = [];
                if(!is_array($v['config'])){
                    throw new \InvalidArgumentException(
                        'Config parameter must be a array'
                    );
                }

                $repositories[$r] = $v;
            }
            catch(\Exception $e){
                throw new \RuntimeException(
                    'Invalid definition for "'. $r. '" repository',
                    0,
                    $e
                );
            }
        }

        return $repositories;
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

    public static function validArchs(): array
    { return ['i386', 'amd64', 'i386_amd64']; }

    public static function validBootModes(): array
    { return ['bios', 'efi']; }
}
