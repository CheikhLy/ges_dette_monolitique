<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ArticleRepository;
use App\Entity\Article;


class ArticleController extends AbstractController
{
    #[Route('/article', name: 'article.index')]
    public function index(): Response
    {
        // return $this->json([
        //     'message' => 'Welcome to your new controller!',
        //     'path' => 'src/Controller/ArticleController.php',
        // ]);
        $html = file_get_contents("../src/views/article/listerArticle.html");
        return new Response($html);
    }
    
    
    #[Route('/article/store', name: 'app_article')]
    public function create(): Response
    {
        // return $this->json([
        //     'message' => 'Welcome to your new controller!',
        //     'path' => 'src/Controller/ArticleController.php',
        // ]);
        $html = file_get_contents("../src/views/article/creerArticle.html");
        return new Response($html);
    }
 
    #[Route('api/article/store', name: 'article.store', methods: ['POST','GET'])]
    public function store(Request $request, EntityManagerInterface $em): Response
    {
        // Décoder les données JSON reçues
        $data = json_decode($request->getContent(), true);

        // Vérifier si toutes les données requises sont présentes
        if (!isset($data['libelle'], $data['reference'], $data['prix'], $data['qteStock'])) {
            return new JsonResponse(['message' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'article
        $article = new Article();
        $article->setLibelle($data['libelle']);
        $article->setReference($data['reference']);
        $article->setPrix($data['prix']);
        $article->setQteStock($data['qteStock']);

        try {
            // Persister et sauvegarder dans la base de données
            $em->persist($article);
            $em->flush();

            return new JsonResponse([
                'message' => 'Article créé avec succès',
                'article' => [
                    'id' => $article->getId(),
                    'libelle' => $article->getLibelle(),
                    'reference' => $article->getReference(),
                    'prix' => $article->getPrix(),
                    'qteStock' => $article->getQteStock()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors de la création de l\'article : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/api/articles', name: 'api_articles', methods: ['GET'])]
    public function apiList(ArticleRepository $articleRepository, Request $request): JsonResponse
    {
        // Récupération des paramètres de requête
        $libelle = $request->query->get('libelle', ''); // Recherche par libellé
        $page = max(1, (int) $request->query->get('page', 1)); // Page par défaut 1
        $limit = 6; // Nombre d'articles par page

        // Construire une requête personnalisée
        $queryBuilder = $articleRepository->createQueryBuilder('a');

        // Filtrer par libellé si fourni
        if (!empty($libelle)) {
            $queryBuilder->andWhere('a.libelle LIKE :libelle')
                         ->setParameter('libelle', '%' . $libelle . '%');
        }

        // Obtenir le nombre total d'articles correspondant à la requête
        $totalArticles = (clone $queryBuilder)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        // Pagination : définir les résultats de la page actuelle
        $queryBuilder->setFirstResult(($page - 1) * $limit)
                     ->setMaxResults($limit);

        $articles = $queryBuilder->getQuery()->getResult();

        // Calculer le nombre total de pages
        $totalPages = (int) ceil($totalArticles / $limit);

        // Construire la réponse
        $data = [
            'articles' => array_map(function (Article $article) {
                return [
                    'id' => $article->getId(),
                    'libelle' => $article->getLibelle(),
                    'reference' => $article->getReference(),
                    'prix' => $article->getPrix(),
                    'qteStock' => $article->getQteStock(),
                ];
            }, $articles),
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_articles' => $totalArticles,
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
