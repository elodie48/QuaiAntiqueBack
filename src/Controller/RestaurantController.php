<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/restaurant', name: 'app_api_restaurant_')]
final class RestaurantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager, 
        private RestaurantRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(name: 'new', methods: 'POST')]
    #[OA\Post(
        path:"/api/restaurant",
        summary:"Create a restaurant",
        requestBody: new OA\RequestBody(
            required: true,
            description:"Restaurant data to be registered",
            content: new OA\JsonContent(
                type:"object",
                properties: [
                    new OA\Property(property:"name", type:"string", example:"Quai Antique"),
                    new OA\Property(property:"description", type:"string", example:"Le Chef Arnaud Michant vous invite à découvrir un voyage culinaire mémorable dans son restaurant gastronomique."),
                    new OA\Property(property:"amOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["11:00", "13:00"])),
                    new OA\Property(property:"pmOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["19:00", "22:00"])),
                    new OA\Property(property:"maxGuest", type:"smallint", example:"10"),
                    new OA\Property(property:"createdAt", type:"string", format: "date-time")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Restaurant successfully added",
                content: new OA\JsonContent(
                    type:"object",
                    properties: [
                        new OA\Property(property:"id", type:"integer", example: 1),
                        new OA\Property(property:"name", type:"string", example:"Quai Antique"),
                        new OA\Property(property:"description", type:"string", example:"Le Chef Arnaud Michant vous invite à découvrir un voyage culinaire mémorable dans son restaurant gastronomique."),
                        new OA\Property(property:"amOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["11:00", "13:00"])),
                        new OA\Property(property:"pmOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["19:00", "22:00"])),
                        new OA\Property(property:"maxGuest", type:"smallint", example:"10"),
                        new OA\Property(property:"createdAt", type:"string", format: "date-time")
                    ]
                )
            )
        ]
    )]

    public function new(Request $request): JsonResponse
    {
        $restaurant = $this->serializer->deserialize($request->getContent(), Restaurant::class, 'json');
        $restaurant->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($restaurant); //waiting list
        $this->manager->flush(); //send to database

        $responseData = $this->serializer->serialize($restaurant, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

       return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'show', methods:'GET')]
    #[OA\Get(
        path:"/api/restaurant/{id}",
        summary:"Show a restaurant by its ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Restaurant ID to show",
                schema: new OA\Schema(type:"integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Restaurant successfully found",
                content: new OA\JsonContent(
                    type:"object",
                    properties: [
                        new OA\Property(property:"id", type:"integer", example: 1),
                        new OA\Property(property:"name", type:"string", example:"Quai Antiquet"),
                        new OA\Property(property:"description", type:"string", example:"Le Chef Arnaud Michant vous invite à découvrir un voyage culinaire mémorable dans son restaurant gastronomique."),
                        new OA\Property(property:"amOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["11:00", "13:00"])),
                        new OA\Property(property:"pmOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["19:00", "22:00"])),
                        new OA\Property(property:"maxGuest", type:"smallint", example:"10"),
                        new OA\Property(property:"createdAt", type:"string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant not found")
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if($restaurant) {
            $responseData = $this->serializer->serialize($restaurant, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    #[OA\Put(
        path:"/api/restaurant/{id}",
        summary:"Edit a restaurant by its ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: " restaurant ID to edit",
                schema: new OA\Schema(type:"integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description:"Restaurant data to modify",
            content: new OA\JsonContent(
                type:"object",
                properties: [
                    new OA\Property(property:"id", type:"integer", example: 1),
                    new OA\Property(property:"name", type:"string", example:"Restaurant Quai Antique"),
                    new OA\Property(property:"description", type:"string", example:"Le Chef Arnaud Michant vous invite à découvrir un voyage culinaire mémorable."),
                    new OA\Property(property:"amOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["11:30", "13:30"])),
                    new OA\Property(property:"pmOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["18:45", "22:15"])),
                    new OA\Property(property:"maxGuest", type:"smallint", example:"60"),
                    new OA\Property(property:"updatedAt", type:"string", format: "date-time")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Restaurant successfully modified",
                content: new OA\JsonContent(
                    type:"object",
                    properties: [
                        new OA\Property(property:"id", type:"integer", example: 1),
                        new OA\Property(property:"name", type:"string", example:"Restaurant Quai Antique"),
                        new OA\Property(property:"description", type:"string", example:"Le Chef Arnaud Michant vous invite à découvrir un voyage culinaire mémorable"),
                        new OA\Property(property:"amOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["11:30", "13:30"])),
                        new OA\Property(property:"pmOpeningTime", type:"array", items: new OA\Items(type:"string", example: ["18:45", "22:15"])),
                        new OA\Property(property:"createdAt", type:"string", format: "date-time"),
                        new OA\Property(property:"updatedAt", type:"string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant not found")
        ]
    )]

    public function edit(int $id, Request $request): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if($restaurant) {
            $restaurant = $this->serializer->deserialize(
                $request->getContent(),
                Restaurant::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $restaurant]
            );
            $restaurant->setUpdatedAt(new DateTimeImmutable());

            $this->manager->flush();

            $json = $this->serializer->serialize($restaurant, 'json');

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'delete',methods: 'DELETE')]
    #[OA\Delete(
        path:"/api/restaurant/{id}",
        summary:"Delete a restaurant by its ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: " Restaurant ID to delete",
                schema: new OA\Schema(type:"integer")
            )
        ],
        
        responses: [
            new OA\Response(
                response: 204,
                description: "Restaurant successfully deleted",
           ),
            new OA\Response(
                response: 404,
                description: "Restaurant not found")
        ]
    )]

    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->repository->findOneBy(['id' => $id]);
        if($restaurant) {
            $this->manager->remove($restaurant);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}