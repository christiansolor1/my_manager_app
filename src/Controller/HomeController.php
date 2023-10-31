<?php

namespace App\Controller;

use App\Entity\EstadoCuentaUsuario;
use App\Entity\Genero;
use App\Entity\Usuarios;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private $em;


    public function __construct(EntityManagerInterface $em = null)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {

        $breadcrumb = [
            // '<a href="' . $this->generateUrl('app_home') . '"><i class="material-icons">home</i></a>' => $this->generateUrl('app_home'),
            '<i class="material-icons">home</i>' => $this->generateUrl('app_home'),
        ];
        
        

        $usuarios = $this->em->getRepository(Usuarios::class)->findAll();

        //metodo personalizado
        //$custom_usuario =  $this->em->getRepository(Usuarios::class)->findUsuario(1);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'Dashboard',
            'breadcrumb' => $breadcrumb,
            'usuarios' => $usuarios,
        ]);
    }
}
