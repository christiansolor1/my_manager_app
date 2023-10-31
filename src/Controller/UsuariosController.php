<?php

namespace App\Controller;

use App\Entity\EstadoCuentaUsuario;
use App\Entity\Genero;
use App\Entity\Usuarios;
use App\Form\UsuariosType;
use DateTime;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    //==================================== Crear Usuario

    #[Route('/usuarios/new_user', name: 'app_new_user')]
    public function new_user(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $username = $request->request->get('username');
        $nombres = $request->request->get('nombres');
        $apellidos = $request->request->get('apellidos');
        $email = $request->request->get('email');
        $plaintextPassword = $request->request->get('password'); // Corregido: usar 'password' en lugar de 'pass'
        $rol = $request->request->get('rol');
        $fechaNacimiento = \DateTime::createFromFormat('Y-m-d', $request->request->get('fechaNacimiento'));
        $fecha_registro = new \DateTime();
        $fecha_acceso = new \DateTime();

        $generoId = $request->request->get('genero');
        $estadoUsuarioId = $request->request->get('estado_usuario');

        $genero = $this->em->getRepository(Genero::class)->find($generoId);
        $estado_usuario = $this->em->getRepository(EstadoCuentaUsuario::class)->find($estadoUsuarioId);

        // Verificar si los datos no son nulos
        if (!$username || !$nombres || !$apellidos || !$email || !$plaintextPassword || !$rol || !$genero || !$estado_usuario || !$fechaNacimiento) {
            return new JsonResponse(['success' => false, 'message' => 'Falta información obligatoria.'], 400);
        }
        if ($rol !== 'ROLE_ADMIN' && $rol !== 'ROLE_INVITED' && $rol !== '') {
            return new JsonResponse(['success' => false, 'message' => 'Rol no válido.'], 400);
        }

        $usuario = new Usuarios();
        $roles = [$rol];
        // Hash de la contraseña (basado en la configuración de security.yaml para la clase $user)
        $hashedPassword = $passwordHasher->hashPassword($usuario, $plaintextPassword);

        $usuario->setNombres($nombres);
        $usuario->setApellidos($apellidos);
        $usuario->setUsername($username);
        $usuario->setEmail($email);
        $usuario->setPassword($hashedPassword);
        $usuario->setRoles($roles);
        $usuario->setGenero($genero); // Establecer el objeto Genero, no solo el ID
        $usuario->setEstadoCuenta($estado_usuario); // Establecer el objeto EstadoCuentaUsuario, no solo el ID
        $usuario->setFechaDeNacimiento($fechaNacimiento);
        $usuario->setFechaDeRegistro($fecha_registro);
        $usuario->setFechaDeAcceso($fecha_acceso); // Corregido: usar 'setFechaDeAcceso' en lugar de 'getFechaDeAcceso'

        $this->em->persist($usuario);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    //==================================== paginacion data table usuariosin search

    #[Route('/usuarios/data', name: 'app_datatable_usuarios')]
    public function datatable_usuarios(Request $request): JsonResponse
    {
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);

        $usuarios = $this->em->getRepository(Usuarios::class)
            ->createQueryBuilder('u')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getResult();

        // Obtener el número total de registros en la base de datos
        $totalRecords = $this->em->getRepository(Usuarios::class)->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $serializedUsuarios = [];

        foreach ($usuarios as $usuario) {
            $serializedUsuarios[] = [
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
            ];
        }

        $response = [
            'recordsTotal' => $totalRecords, // Total de registros sin filtrar
            'recordsFiltered' => $totalRecords, // Total de registros después del filtrado (en este caso, sin filtrar)
            'data' => $serializedUsuarios, // Datos a mostrar
        ];

        return new JsonResponse($response);
    }
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

    //==================================== Cargar usuarios

    #[Route('/usuarios/data', name: 'app_data_usuarios')]
    public function data_usuarios(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $usuarios = new Usuarios();

        $usuarios = $this->em->getRepository(Usuarios::class)->findAll();

        $serializedUsuarios = [];

        foreach ($usuarios as $usuario) {

            $serializedUsuarios[] = [
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
            ];
        }
        return new JsonResponse($serializedUsuarios);
    }

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

    //==================================== Actualizar estado usuario
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

    //==================================== Eliminar un usuario

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
