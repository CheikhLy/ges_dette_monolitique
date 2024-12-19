<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Client;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends AbstractController
{
    #[Route('/client', name: 'client.index')]
    public function index(): Response
    {
        // return $this->json([
        //     'message' => 'Welcome to your new controller!',
        //     'path' => 'src/Controller/ClientController.php',
        // ]);
        $html = file_get_contents("../src/views/client/listerClient.html");
        return new Response($html);

    }
    #[Route('/client/store', name: 'client_store')]
    public function create(): Response
    {
        // return $this->json([
        //     'message' => 'Welcome to your new controller!',
        //     'path' => 'src/Controller/ArticleController.php',
        // ]);
        $html = file_get_contents("../src/views/client/creerClient.html");
        return new Response($html);
    }
    #[Route('api/client/store', name: 'client.store', methods: ['POST','GET'])]
    public function store(Request $request, EntityManagerInterface $em): Response
    {
        // Décoder les données JSON reçues
        $data = json_decode($request->getContent(), true);

        // Vérifier si toutes les données requises sont présentes
        if (!isset($data['surname'], $data['adresse'], $data['telephone'])) {
            return new JsonResponse(['message' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'article
        $client = new Client();
        $client->setSurname($data['surname']);
        $client->setAdresse($data['adresse']);
        $client->setTelephone($data['telephone']);
            // Gestion de l'utilisateur (optionnel)
            if (isset($data['userId'])) {
                $user = $this->entityManager->getRepository(Users::class)->find($data['userId']);
                if ($user) {
                    $client->setUsers($user);
                }
            }
    
        try {
            // Persister et sauvegarder dans la base de données
            $em->persist($client);
            $em->flush();

            return new JsonResponse([
                'message' => 'Article créé avec succès',
                'client' => [
                    'id' => $client->getId(),
                    'surname' => $client->getSurname(),
                    'adresse' => $client->getAdresse(),
                    'telephone' => $client->getTelephone(),
                    // 'user' => $client->getUser()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la création du \'client : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/api/client', name: 'api_clients', methods: ['GET'])]
    public function apiList(ClientRepository $clientRepository, Request $request): JsonResponse
    {
        // Récupération des paramètres de requête
        $telephone = $request->query->get('telephone', ''); // Recherche par libellé
        $page = max(1, (int) $request->query->get('page', 1)); // Page par défaut 1
        $limit = 6; // Nombre d'articles par page

        // Construire une requête personnalisée
        $queryBuilder = $clientRepository->createQueryBuilder('a');

        // Filtrer par libellé si fourni
        if (!empty($telephone)) {
            $queryBuilder->andWhere('a.telephone LIKE :telephone')
                         ->setParameter('telephone', '%' . $telephone . '%');
        }

        // Obtenir le nombre total d'articles correspondant à la requête
        $totalClients = (clone $queryBuilder)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        // Pagination : définir les résultats de la page actuelle
        $queryBuilder->setFirstResult(($page - 1) * $limit)
                     ->setMaxResults($limit);

        $client = $queryBuilder->getQuery()->getResult();

        // Calculer le nombre total de pages
        $totalPages = (int) ceil($totalClients / $limit);

        // Construire la réponse
        $data = [
            'client' => array_map(function (Client $article) {
                return [
                    'id' => $article->getId(),
                    'surname' => $article->getSurname(),
                    'adresse' => $article->getAdresse(),
                    'telephone' => $article->getTelephone(),
                   
                ];
            }, $client),
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_client' => $totalClients,
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }


}
