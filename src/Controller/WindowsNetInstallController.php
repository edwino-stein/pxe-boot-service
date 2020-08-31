<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\WindowsNetInstallService;

/**
 * @Route(
 *     "/windows/{dist}/{arch}",
 *     requirements={"dist": "10|8", "arch": "amd64|i386|i386_amd64" }
 * )
 */
class WindowsNetInstallController extends AbstractController
{
    /**
    * @Route("/netinstall.json")
    */
    public function netinstall(string $dist, string $arch, WindowsNetInstallService $netInstall)
    {
        try {
            return $this->json($netInstall->getCatalog($dist, $arch));
        }
        catch(\Exception $e){
            throw $this->createNotFoundException($e->getMessage());
        }
    }
}
