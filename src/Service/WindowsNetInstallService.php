<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\PxeResourceService;
use App\Model\PxeTargetModel;

class WindowsNetInstallService
{
    protected $pes = null;
    protected $smbServer = '127.0.0.1';
    protected $smbBasePath = 'pxe';
    protected $repository = 'windows';
    protected $setup = 'setup.exe';

    public function __construct(ContainerInterface $container, PxeResourceService $pes)
    {
        $config = $container->getParameter('windows_netinstall');

        if(isset($config['smbServer'])) $this->smbServer = $config['smbServer'];
        elseif(isset($_ENV['NEXT_SERVER'])) $this->smbServer = $_ENV['NEXT_SERVER'];

        if(isset($config['smbBasePath'])) $this->smbBasePath = $config['smbBasePath'];
        if(isset($config['repository'])) $this->repository = $config['repository'];
        if(isset($config['setup'])) $this->setup = $config['setup'];

        $this->pes = $pes;
    }

    private function getBuilds(string $dist, string $arch): array
    {
        $finder = $this->pes->getRepositoryFinder($this->repository);
        $finder ->path(PxeResourceService::concatPath([$dist, $arch, '']))
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
