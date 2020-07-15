<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PxeController
{
    /**
    * @Route("/chain")
    */
    public function index()
    {
        return new Response(
            '<html><body>PXE</body></html>'
        );
    }
}
