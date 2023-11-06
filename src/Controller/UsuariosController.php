<?php

namespace App\Controller;

use App\Entity\EstadoCuentaUsuario;
use App\Entity\Genero;
use App\Entity\Usuarios;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Routing\Annotation\Route;

class UsuariosController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em = null)
    {
        $this->em = $em;
    }

    //=========================================== TEMPLATE ===================================================

    #[Route('/usuarios', name: 'app_usuarios')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $breadcrumb = [
            '<a href="' . $this->generateUrl('app_home') . '"><i class="material-icons">home</i></a>' => $this->generateUrl('app_home'),
            // '<a href="' . $this->generateUrl('app_home') . '"></a>' => $this->generateUrl('app_home'),
        ];

        $usuario = new Usuarios();

        return $this->render('usuarios/index.html.twig', [
            'breadcrumb' => $breadcrumb,
            // 'custom_usuario' => $custom_usuario,
            'breadcrumb_name' => 'Gestión de usuarios',
        ]);
    }

    //================================================= DATA ===========================================================================

    //==================================== INSERTAR UN USUARIO

    #[Route('/usuarios/new_user', name: 'app_new_user')]
    public function new_user(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['username', 'password', 'nombres', 'apellidos', 'email', 'genero', 'estado_usuario', 'fechaNacimiento', 'rol'];
        $missingData = [];
        $fechaNacimiento = new \DateTime($data['fechaNacimiento']);
        $fechaRegistro = new \DateTime();
        $fechaAcceso = new \DateTime();

        $generoId = $data['genero'];

        $estadoUsuarioId = $data['estado_usuario'];

        $genero = $this->em->getRepository(Genero::class)->find($generoId);

        $estadoUsuario = $this->em->getRepository(EstadoCuentaUsuario::class)->find($estadoUsuarioId);

        $usuario = new Usuarios();

        $hashedPassword = $passwordHasher->hashPassword($usuario, $data['password']);


        if (!$genero || !$estadoUsuario) {
            return new JsonResponse(['error' => 'El género o estado de usuario no son válidos.'], 400);
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingData[] = $field;
            }
        }

        if (!empty($missingData)) {
            return new JsonResponse(['error' => 'Faltan datos obligatorios: ' . implode(', ', $missingData)], 400);
        }

        $allowedRoles = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_INVITED'];
        // if (!in_array($rol, $allowedRoles) && $rol !== '') {
        //     return new JsonResponse(['error' => 'Rol no válido.'], 400);
        // }
        $rol = $data['rol'];
        if ($rol === 'ROLE_USER' || $rol === '') {
            //$rol = ''; // Asignar un rol vacío

        } else if (!in_array($rol, $allowedRoles)) {
            return new JsonResponse(['error' => 'Rol no válido.'], 400);
        } else {
            // $usuario->setRoles($rol);
            $usuario->setRoles([$rol]);
        }

        $usuario->setUsername($data['username']);
        $usuario->setNombres($data['nombres']);
        $usuario->setApellidos($data['apellidos']);
        $usuario->setEmail($data['email']);
        $usuario->setPassword($hashedPassword);
        $usuario->setGenero($genero);
        $usuario->setEstadoCuenta($estadoUsuario);
        $usuario->setFechaDeNacimiento($fechaNacimiento);
        $usuario->setFechaDeRegistro($fechaRegistro);
        $usuario->setFechaDeAcceso($fechaAcceso);
        $entityManager->persist($usuario);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Usuario creado con éxito'], 200);
        //return new JsonResponse(['error' => 'El mensaje de error específico'], 400);
    }

    //==================================== CARGA DE USUARIOS EN DATATABLE CON PAGINACION    
    #[Route('/usuarios/data', name: 'app_datatable_usuarios')]
    public function datatable_usuarios(Request $request)
    {
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $queryBuilder = $this->em->getRepository(Usuarios::class)
            ->createQueryBuilder('u')
            ->setFirstResult($start)
            ->setMaxResults($length);

        if (!empty($searchValue)) {
            $queryBuilder
                ->andWhere('u.nombres LIKE :searchValue')
                ->orWhere('u.apellidos LIKE :searchValue')
                ->orWhere('u.email LIKE :searchValue')
                ->orWhere('u.username LIKE :searchValue')
                ->orWhere('u.roles LIKE :searchValue')
                ->setParameter('searchValue', '%' . $searchValue . '%');
        }

        $usuarios = $queryBuilder->getQuery()->getResult();

        $totalRecords = $this->em->getRepository(Usuarios::class)
            ->createQueryBuilder('u')
            ->select('count(u.id)');

        if (!empty($searchValue)) {
            $totalRecords
                ->andWhere('u.nombres LIKE :searchValue')
                ->orWhere('u.apellidos LIKE :searchValue')
                ->orWhere('u.email LIKE :searchValue')
                ->orWhere('u.username LIKE :searchValue')
                ->orWhere('u.roles LIKE :searchValue')
                ->setParameter('searchValue', '%' . $searchValue . '%');
        }

        $totalRecords = $totalRecords->getQuery()->getSingleScalarResult();

        $data = [];
        foreach ($usuarios as $usuario) {
            $data[] = [
                'id' => $usuario->getId(),
                'correo' => $usuario->getEmail(),
                'rol' => $usuario->getRoles(),
                'usuario' => $usuario->getUsername(),
                'nombres' => $usuario->getNombres(),
                'apellidos' => $usuario->getApellidos(),
                'estado' => $usuario->getEstadoCuenta()->getEstadoCuenta(),
                'genero' => $usuario->getGenero()->getGenero(),
                'nacimiento' => $usuario->getFechaDeNacimiento()->format('d/m/Y'),
                'registro' => $usuario->getFechaDeRegistro()->format('d/m/Y'),
                'acceso' => $usuario->getFechaDeAcceso()->format('d/m/Y H:i:s'),
                // Otros campos de usuario que desees mostrar
            ];
        }

        $response = [
            'draw' => (int) $request->get('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords, // En este caso, no hay filtrado, podrías ajustarlo si aplicas filtros
            'data' => $data,
        ];

        return new JsonResponse($response);
    }

    //==================================== ACTUALIZAR USUARIO
    #[Route('/usuarios/update_user', name: 'app_update_usuario')]
    public function update_user(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $requiredFields = ['id','username', 'password', 'nombres', 'apellidos', 'email', 'genero', 'estado_usuario', 'fechaNacimiento', 'rol'];
        $missingData = [];
        $fechaNacimiento = new \DateTime($data['fechaNacimiento']);
        $fechaAcceso = new \DateTime();

        $generoId = $data['genero'];

        $estadoUsuarioId = $data['estado_usuario'];

        $genero = $this->em->getRepository(Genero::class)->find($generoId);

        $estadoUsuario = $this->em->getRepository(EstadoCuentaUsuario::class)->find($estadoUsuarioId);


        $usuario = $this->em->getRepository(Usuarios::class)->find($data['id']);


      // Validar si 'password' existe en $data antes de intentar acceder
        if (!isset($data['password'])) {
            return new JsonResponse(['error' => 'El campo de contraseña es requerido.'], 400);
        }    

        if (!$genero || !$estadoUsuario) {
            return new JsonResponse(['error' => 'El género o estado de usuario no son válidos.'], 400);
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingData[] = $field;
            }
        }

        if (!empty($missingData)) {
            return new JsonResponse(['error' => 'Faltan datos obligatorios: ' . implode(', ', $missingData)], 400);
        }

        $allowedRoles = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_INVITED'];
        // if (!in_array($rol, $allowedRoles) && $rol !== '') {
        //     return new JsonResponse(['error' => 'Rol no válido.'], 400);
        // }
        $rol = $data['rol'];
        if ($rol === 'ROLE_USER' || $rol === '') {
            //$rol = ''; // Asignar un rol vacío
        } else if (!in_array($rol, $allowedRoles)) {
            return new JsonResponse(['error' => 'Rol no válido.'], 400);
        } else {
            // $usuario->setRoles($rol);
            $usuario->setRoles([$rol]);
        }

        $hashedPassword = $passwordHasher->hashPassword($usuario, $data['password']);

        $usuario->setUsername($data['username']);
        $usuario->setNombres($data['nombres']);
        $usuario->setApellidos($data['apellidos']);
        $usuario->setEmail($data['email']);
        $usuario->setPassword($hashedPassword);
        $usuario->setGenero($genero);
        $usuario->setEstadoCuenta($estadoUsuario);
        $usuario->setFechaDeNacimiento($fechaNacimiento);
        $usuario->setFechaDeAcceso($fechaAcceso);
        $this->em->flush();

        return new JsonResponse(['message' => 'Usuario actualizado con éxito'], 200);
    }
    
    //==================================== ACTUALIZAR EL ESTADO DEL USUARIO

    #[Route('/usuarios/update/estado/{id}/{id_estado}', name: 'app_update_estado_usuario')]
    public function update_estado_usuario($id, $id_estado)
    {
        $usuario = $this->em->getRepository(Usuarios::class)->find($id);

        $estado_user = $this->em->getRepository(EstadoCuentaUsuario::class)->find($id_estado);

        if (!$usuario || !$estado_user) {
            return new JsonResponse(['error' => 'Usuario o estado no encontrado'], 404);
        }

        $usuario->setEstadoCuenta($estado_user);

        $this->em->persist($usuario);
        $this->em->flush();

        $usuarioData = $this->em->getRepository(Usuarios::class)->find($id); // Obtener los datos actualizados del usuario

        return new JsonResponse(['success' => 'Usuario actualizado exitosamente']);
    }

    //==================================== ACTUALIZAR PASSWORD USUARIO
    
    #[Route('/usuarios/update/password/{id}/{pass}', name: 'app_update_pas_usuario')]
    public function update_pas_usuario($id, $pass, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        if(!isset($pass)){
            return new JsonResponse(['error' => 'El campo de contraseña es requerido.'], 400);
        }
        $usuario = $this->em->getRepository(Usuarios::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        
        $fecha_acceso = new \DateTime();

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword($usuario, $pass);

        $usuario->setPassword($hashedPassword);
        $usuario->setFechaDeAcceso($fecha_acceso);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }
    
    //==================================== ELIMINAR UN USUARIO

    #[Route('/usuarios/remove/{id}', name: 'remove_usuarios')]
    public function removeUsuario($id)
    {
        $usuario = $this->em->getRepository(Usuarios::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }

        $this->em->remove($usuario);
        $this->em->flush();

        return new JsonResponse(['message' => 'Usuario eliminado exitosamente']);
    }

    //================================================  TEST   ===============================================================

    //============================================== Desarrollo ===================================================================

    



    


}