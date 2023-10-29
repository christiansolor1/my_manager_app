<?php

// src/Controller/RegistrationController.php
namespace App\Controller;

// ...
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// class RegistrationController extends AbstractController
// {
//     public function index(UserPasswordHasherInterface $passwordHasher): Response
//     {
//         // ... e.g. get the user data from a registration form
//         //$user = new User(...);
//         $plaintextPassword = ...;

//         // hash the password (based on the security.yaml config for the $user class)
//         $hashedPassword = $passwordHasher->hashPassword(
//             $user,
//             $plaintextPassword
//         );
//         $user->setPassword($hashedPassword);

//         // ...
//     }
// }
