<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        ) {
    }

    #[Route('/registration', name: 'registration', methods: 'POST')]
    #[OA\Post(
        path:"/api/registration",
        summary:"New user registration",
        requestBody: new OA\RequestBody(
            required: true,
            description:"User data to be registered",
            content: new OA\JsonContent(
                type:"object",
                properties: [
                    new OA\Property(property:"email", type:"string", example:"adresse@email.com"),
                    new OA\Property(property:"password", type:"string", example:"Mot de passe"),
                    new OA\Property(property:"firstName", type:"string", example:"Elodie"),
                    new OA\Property(property:"lastName", type:"string", example:"Test"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User successfully registered",
                content: new OA\JsonContent(
                    type:"object",
                    properties: [
                        new OA\Property(property:"user", type:"string", example:"adresse@email.com"),
                        new OA\Property(property:"apiToken", type:"string", example:"12azerty3456uiopmlkjhgf789"),
                        new OA\Property(property:"roles", type:"array", items: new OA\Items(type:"string", example: "ROLE_USER")),
                    ]
                )
            )
        ]
    )]

    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            ['user' => $user->getUserIdentifier(), 
            'apiToken' => $user->getApiToken(), 
            'roles' => $user->getRoles()], 
            Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    #[OA\Post(
        path:"/api/login",
        summary:"Login a user",
        requestBody: new OA\RequestBody(
            required: true,
            description:"User data for login",
            content: new OA\JsonContent(
                type:"object",
                properties: [
                    new OA\Property(property:"username", type:"string", example:"adresse@email.com"),
                    new OA\Property(property:"password", type:"string", example:"Mot de passe"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful connection",
                content: new OA\JsonContent(
                    type:"object",
                    properties: [
                        new OA\Property(property:"user", type:"string", example:"Nom d'utilisateur"),
                        new OA\Property(property:"apiToken", type:"string", example:"31a0123212f4457fea31a0123212f4457fea"),
                        new OA\Property(property:"roles", type:"array", items: new OA\Items(type:"string", example: "ROLE_USER")),
                    ]
                )
            )
        ]
    )]

    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if(null === $user) {
            return new JsonResponse(
                ['message' => 'missing credentials'], 
                Response::HTTP_UNAUTHORIZED);
            }

        return new JsonResponse(
            ['user' => $user->getUserIdentifier(), 
            'apiToken' => $user->getApiToken(), 
            'roles' => $user->getRoles()], 
        );
    }
}
