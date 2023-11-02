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

    //=========================================== template ===================================================
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

    //================================================= data ===========================================================================

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

    //Prueba de lo que recibe el controlador
    // #[Route('/usuarios/new_user', name: 'app_new_user')]
    // public function new_user(Request $request): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);

    //     // Verificar si los datos están llegando al controlador
    //     var_dump($data);

    //     // Resto de tu lógica para procesar los datos...

    //     // Devuelve una respuesta JsonResponse, por ejemplo:
    //     return new JsonResponse(['success' => true]);
    // }

    //============================================== Desarrollo ===================================================================

    //==================================== paginacion data table usuarios    
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
                'nacimiento' => $usuario->getFechaDeNacimiento()->format('d-m-Y'),
                'registro' => $usuario->getFechaDeRegistro()->format('d-m-Y'),
                'acceso' => $usuario->getFechaDeAcceso()->format('d-m-Y H:i:s'),
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













































    //////////////////////////////////////////////    DESARROLLO     ///////////////////////////////////////////////////////////////
    // #[Route('/usuarios/data', name: 'app_datatable_usuarios')]
    // public function datatable_usuarios(Request $request): JsonResponse
    // {
    //     $start = $request->query->getInt('start', 0);
    //     $length = $request->query->getInt('length', 10);

    //     $usuarios = $this->em->getRepository(Usuarios::class)
    //         ->createQueryBuilder('u')
    //         ->setFirstResult($start)
    //         ->setMaxResults($length)
    //         ->getQuery()
    //         ->getResult();

    //     // Obtener el número total de registros en la base de datos
    //     $totalRecords = $this->em->getRepository(Usuarios::class)->createQueryBuilder('u')
    //         ->select('count(u.id)')
    //         ->getQuery()
    //         ->getSingleScalarResult();

    //     $serializedUsuarios = [];

    //     foreach ($usuarios as $usuario) {
    //         $serializedUsuarios[] = [
    //             'id' => $usuario->getId(),
    //             'correo' => $usuario->getEmail(),
    //             'rol' => $usuario->getRoles(),
    //             'usuario' => $usuario->getUsername(),
    //             'nombres' => $usuario->getNombres(),
    //             'apellidos' => $usuario->getApellidos(),
    //             'estado' => $usuario->getEstadoCuenta()->getEstadoCuenta(),
    //             'genero' => $usuario->getGenero()->getGenero(),
    //             'nacimiento' => $usuario->getFechaDeNacimiento()->format('d-m-Y'),
    //             'registro' => $usuario->getFechaDeRegistro()->format('d-m-Y'),
    //             'acceso' => $usuario->getFechaDeAcceso()->format('d-m-Y H:i:s'),
    //         ];
    //     }

    //     $response = [
    //         'recordsTotal' => $totalRecords, // Total de registros sin filtrar
    //         'recordsFiltered' => $totalRecords, // Total de registros después del filtrado (en este caso, sin filtrar)
    //         'data' => $serializedUsuarios, // Datos a mostrar
    //     ];

    //     return new JsonResponse($response);
    // }
    // //==================================== Cargar usuarios

    // #[Route('/usuarios/data', name: 'app_data_usuarios')]
    // public function data_usuarios(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    // {
    //     $usuarios = new Usuarios();

    //     $usuarios = $this->em->getRepository(Usuarios::class)->findAll();

    //     $serializedUsuarios = [];

    //     foreach ($usuarios as $usuario) {

    //         $serializedUsuarios[] = [
    //             'id' => $usuario->getId(),
    //             'correo' => $usuario->getEmail(),
    //             'rol' => $usuario->getRoles(),
    //             'usuario' => $usuario->getUsername(),
    //             'nombres' => $usuario->getNombres(),
    //             'apellidos' => $usuario->getApellidos(),
    //             'estado' => $usuario->getEstadoCuenta()->getEstadoCuenta(),
    //             'genero' => $usuario->getGenero()->getGenero(),
    //             'nacimiento' => $usuario->getFechaDeNacimiento()->format('d-m-Y'),
    //             'registro' => $usuario->getFechaDeRegistro()->format('d-m-Y'),
    //             'acceso' => $usuario->getFechaDeAcceso()->format('d-m-Y H:i:s'),
    //         ];
    //     }
    //     return new JsonResponse($serializedUsuarios);
    // }

    //==================================== paginacion data table con search
    //127.0.0.1:8000/usuarios/data?start=10&length=10&searchValue=grant0@adventure-works.com
    // #[Route('/usuarios/data', name: 'app_datatable_usuarios')]
    // public function datatable_usuarios(Request $request): JsonResponse
    // {
    //     $start = $request->query->getInt('start', 0);
    //     $length = $request->query->getInt('length', 10);
    //     $search = $request->query->get('search');

    //     $searchValue = isset($search['value']) ? $search['value'] : null; // Verificar y definir $searchValue

    //     // Obtener el repositorio a través del EntityManager
    //     $repository = $this->em->getRepository(Usuarios::class);

    //     $queryBuilder = $repository->createQueryBuilder('u');

    //     // Aplicar la búsqueda si hay un término de búsqueda
    //     if ($searchValue) {
    //         $queryBuilder
    //             ->andWhere('u.id LIKE :searchValue')
    //             ->orWhere('u.correo LIKE :searchValue')
    //             ->orWhere('u.roles LIKE :searchValue')
    //             ->orWhere('u.estado_cuenta_id LIKE :searchValue')
    //             ->orWhere('u.genero_id LIKE :searchValue')
    //             ->orWhere('u.username LIKE :searchValue')
    //             ->orWhere('u.nombres LIKE :searchValue')
    //             ->orWhere('u.apellidos LIKE :searchValue')
    //             ->orWhere('u.fecha_de_nacimiento LIKE :searchValue')
    //             ->orWhere('u.fecha_de_registro LIKE :searchValue')
    //             ->orWhere('u.fecha_de_acceso LIKE :searchValue')
    //             ->setParameter('searchValue', '%' . $searchValue . '%');
    //         // Puedes agregar más condiciones para otros campos si es necesario
    //     }

    //     // Clonar la consulta para contar los resultados sin límite de longitud
    //     $countQueryBuilder = clone $queryBuilder;
    //     $count = $countQueryBuilder
    //         ->select('COUNT(u.id)')
    //         ->getQuery()
    //         ->getSingleScalarResult();

    //     $usuarios = $queryBuilder
    //         ->setFirstResult($start)
    //         ->setMaxResults($length)
    //         ->getQuery()
    //         ->getResult();

    //     // Obtener el número total de registros en la base de datos (sin tener en cuenta el filtrado)
    //     $totalRecords = $repository->createQueryBuilder('u')
    //         ->select('COUNT(u.id)')
    //         ->getQuery()
    //         ->getSingleScalarResult();

    //     $response = [
    //         'recordsTotal' => $totalRecords,
    //         'recordsFiltered' => $count, // Utilizar el conteo de registros filtrados obtenido
    //         'data' => [], // Inicializar para evitar errores si no hay datos
    //     ];

    //     $serializedUsuarios = [];

    //     foreach ($usuarios as $usuario) {
    //         $serializedUsuarios[] = [
    //             'id' => $usuario->getId(),
    //             'correo' => $usuario->getEmail(),
    //             'rol' => $usuario->getRoles(),
    //             'usuario' => $usuario->getUsername(),
    //             'nombres' => $usuario->getNombres(),
    //             'apellidos' => $usuario->getApellidos(),
    //             'estado' => $usuario->getEstadoCuenta()->getEstadoCuenta(),
    //             'genero' => $usuario->getGenero()->getGenero(),
    //             'nacimiento' => $usuario->getFechaDeNacimiento()->format('d-m-Y'),
    //             'registro' => $usuario->getFechaDeRegistro()->format('d-m-Y'),
    //             'acceso' => $usuario->getFechaDeAcceso()->format('d-m-Y H:i:s'),
    //         ];
    //     }

    //     $response['data'] = $serializedUsuarios; // Asignar los datos al final

    //     return new JsonResponse($response);
    // }


    //==================================== Actualizar usuario
    #[Route('/usuarios/update/{id}', name: 'app_update_usuario')]
    public function updateUsuario($id, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $usuario = $this->em->getRepository(Usuarios::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $username = $request->request->get('username');
        $nombre = $request->request->get('nombres');
        $apellidos = $request->request->get('apellidos');
        $email = $request->request->get('email');
        $plaintextPassword = $request->request->get('pass');
        $role = $request->request->get('role');
        $gen = $request->request->get('gen');
        $state_user = $request->request->get('state_user');
        $fecha_nacimiento = \DateTime::createFromFormat('d-m-Y', $request->request->get('fecha_nacimiento'));
        $fecha_acceso = new \DateTime();

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword($usuario, $plaintextPassword);

        $usuario->setNombres($nombre);
        $usuario->setApellidos($apellidos);
        $usuario->setUsername($username);
        $usuario->setEmail($email);
        $usuario->setPassword($hashedPassword);
        $usuario->setRoles(json_decode($role));
        $usuario->setGenero($gen);
        $usuario->setEstadoCuenta($state_user);
        $usuario->setFechaDeNacimiento($fecha_nacimiento);
        $usuario->setFechaDeAcceso($fecha_acceso);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/usuarios/update_pas/{id}/{pass}', name: 'app_update_pas_usuario')]
    public function update_pas_usuario($id, $pass, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $usuario = $this->em->getRepository(Usuarios::class)->find($id);

        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $plaintextPassword = $request->request->get($pass);
        $fecha_acceso = new \DateTime();

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword($usuario, $plaintextPassword);

        $usuario->setPassword($hashedPassword);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }




    // #[Route('/usuarios', name: 'app_usuarios')]
    // public function index(): Response
    // {

    //     $breadcrumb = [
    //         '<i class="material-icons">home</i>' => $this->generateUrl('app_home'),
    //     ];

    //     // $usuarios_lol = $this->em->getRepository(Usuarios::class)->find(['id'=> 1, ]);
    //     // $usuarios_lol = $this->em->getRepository(Usuarios::class)->findBy(['id'=> 1, 'username'=>'csolorzano']);

    //     //$usuarios = $this->em->getRepository(Usuarios::class)->findOneBy(['id'=> 1, 'username'=>'csolorzano']);

    //     $usuarios = $this->em->getRepository(Usuarios::class)->findAll();

    //     //metodo personalizado
    //     $custom_usuario =  $this->em->getRepository(Usuarios::class)->findUsuario(1);

    //     // dump($usu);
    //     return $this->render('usuarios/index.html.twig', [
    //         'breadcrumb' => $breadcrumb,
    //         'controller_name' => 'Gestión de usuarios',
    //         'breadcrumb_name' => 'Gestión de usuarios',
    //         'usuarios' => $usuarios,
    //         'custom_usuario' => $custom_usuario,
    //         // 'usuarios_lol'=>$usuarios_lol,

    //     ]);
    // }



    // #[Route('/usuarios/insert', name: 'insert2_usuarios')]
    // public function insert()
    // {
    //     $gener_user = $this->em->getRepository(Genero::class)->find(id:1);
    //     $estado_user = $this->em->getRepository(EstadoCuentaUsuario::class)->find(id:1);
    //     $nacimiento_user = new \DateTime('1993-09-24');
    //     $dateRegistre_user = new \DateTime();


    //     $usuario = new Usuarios(
    //         email:'vjosuesolor1@mail.com',
    //         password:'1324',
    //         username:'victorjos1',
    //         nombres:'Victor Josue',
    //         apellidos:'Solorzano Giron'
    //     );
    //     $usuario->setRoles(json_decode('["ROLE_USER"]'));
    //     $usuario->setGenero($gener_user);
    //     $usuario->setEstadoCuenta($estado_user);
    //     $usuario->setFechaDeNacimiento($nacimiento_user);
    //     $usuario->setFechaDeRegistro($dateRegistre_user);

    //     $this->em->persist($usuario);
    //     $this->em->flush();

    //     return new JsonResponse(['succes'=> true]);
    // }


    // #[Route('/usuarios/insert/{user}', name: 'app_insert_usuarios')]
    // public function insert_usuarios($user)
    // {
    //     $usuario = new Usuarios();

    //     $gener_user = $this->em->getRepository(Genero::class)->find(id:1);
    //     $estado_user = $this->em->getRepository(EstadoCuentaUsuario::class)->find(id:1);
    //     $nacimiento_user = new \DateTime('1993-09-24');
    //     $dateRegistre_user = new \DateTime();
    //     $dateUltimateAcces_user = new \DateTime();

    //     // $usuario->setNombres('Victor Manuel');
    //     // $usuario->setApellidos('Solorzano Alvarez');
    //     // $usuario->setUsername('victormsolor1');
    //     // $usuario->setEmail('manuelsolor1@gmail.com');
    //     // $usuario->setPassword('1234');
    //     // $usuario->setRoles(json_decode('["ROLE_USER"]'));
    //     // $usuario->setGenero($gener_user);
    //     // $usuario->setEstadoCuenta($estado_user);
    //     // $usuario->setFechaDeNacimiento($nacimiento_user);
    //     // $usuario->setFechaDeRegistro($dateRegistre_user);
    //     // $usuario->setFechaAcceso($dateUltimateAcces_user);


    //     $this->em->persist($usuario);
    //     $this->em->flush();

    //     return new JsonResponse(['succes' => true]);
    // }

}
