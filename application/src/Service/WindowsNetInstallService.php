<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\PxeResourceService;
use App\Model\PxeTargetModel;

class WindowsNetInstallService
{
    protected $prs = null;
    protected $smbServer = '127.0.0.1';
    protected $smbBasePath = 'pxe';
    protected $repository = 'windows';
    protected $setup = 'setup.exe';

    public function __construct(ContainerInterface $container, PxeResourceService $prs)
    {
        $config = $container->getParameter('windows_netinstall');

        if(isset($config['smbServer'])) $this->smbServer = $config['smbServer'];
        if(isset($config['smbBasePath'])) $this->smbBasePath = $config['smbBasePath'];
        if(isset($config['repository'])) $this->repository = $config['repository'];
        if(isset($config['setup'])) $this->setup = $config['setup'];

        $this->prs = $prs;
    }

    private function getBuilds(string $dist, string $arch): array
    {
        $finder = $this->prs->getRepositoryFinder($this->repository);
        $finder ->path(PxeResourceService::concatPath([$dist, $arch, '']))
                ->depth('<= 3')
                ->files()
                ->name($this->setup)
                ->sortByName()
                ->reverseSorting();

        $builds = [];

        foreach ($finder as $f){
            $builds[] = explode('/'.$arch.'/', $f->getRelativePath())[1];
        }

        if(empty($builds)) throw new \Exception("No builds found for this architecture", 1);

        return $builds;
    }

    public function getCatalog(string $dist, string $arch): array
    {
        return [
            'smbServer' => $this->smbServer,
            'setup' => $this->setup,
            'basePath' => str_replace(
                '/',
                '\\',
                PxeResourceService::concatPath([
                    $this->smbBasePath,
                    $this->repository,
                    $dist,
                    $arch
                ])
            ),
            'builds' => $this->getBuilds($dist, $arch)
        ];
    }
}
