<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PxeResourceService
{
    protected $publicPath = 'public';
    protected $rootDir = '';

    public function __construct(ContainerInterface $container)
    {
        $config = $container->getParameter('pxe_boot');
        $this->publicPath = $container->getParameter('kernel.project_dir') . '/' . $this->publicPath;
        $this->rootDir = $config['dirs']['root'];
    }
}
