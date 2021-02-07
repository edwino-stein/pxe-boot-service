<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\iPxeRenderService;

/**
 * @Route("/chain")
 */
class PxeController extends AbstractController
{
    /**
    * @Route(
    *   "/{mode}.{_format}",
    *   requirements={"mode": "bios|efi", "_format": "ipxe"}
    * )
    */
    public function chain(string $mode, iPxeRenderService $ipxe)
    {
        $ipxe->setup($mode);
        $response = $this->render('pxe/chain.ipxe.twig', ['ipxe' => $ipxe]);
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    /**
    * @Route("/teste")
    */
    public function teste(iPxeRenderService $prs){
        var_dump($prs);
        return new Response('teste');
    }
}
