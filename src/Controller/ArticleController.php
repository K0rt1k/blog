<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use App\Entity\Users;
use App\Entity\Articles;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MyApp\ServiceJWT;

class ArticleController extends AbstractController
{
    #[Route('/api/article/add', name: 'api_article_add', methods: ['POST'])]
    public function articleAdd(Request $request, ManagerRegistry $doctrine): Response
    {   
        $serviceJWT = new ServiceJWT($request);
        $serviceJWT->checkJWT();
        $userLogin = $serviceJWT->getLogin();
        
        if($userLogin){
            $entityManager = $doctrine->getManager();
            $repository = $doctrine->getRepository(Users::class);
            $articleRepository = $doctrine->getRepository(Articles::class);
            $reqArticle = $request->toArray();
            $reqTitle = $reqArticle['title'];
            $reqText = $reqArticle['text'];
            $users = $repository->findOneBy(['login' => "$userLogin"]);
            $dbarticle = $articleRepository->findOneBy(['title' => "$reqTitle"]);
        }
        
        if($users && $reqArticle && !$dbarticle){
            $articles = new Articles;
            $articles->setTitle($reqTitle)
                    ->setText($reqText)
                    ->setDateCreate(new DateTime())
                    ->setDateChange(new DateTime())
                    ->setFkUsers($users);

            $entityManager->persist($articles);
            $entityManager->flush();

            return new Response(
                'To user with id: '.$users->getId()
                .' added new article with id: '.$articles->getId()
            );
        }
        return $this->json(['Error' => 'article don\'t save!']);
    }

    #[Route('/api/article/edit', name: 'api_article_edit', methods: ['PUT'])]
    public function articleEdit(Request $request, ManagerRegistry $doctrine): Response
    {
        $serviceJWT = new ServiceJWT($request);
        $serviceJWT->checkJWT();
        $userLogin = $serviceJWT->getLogin();

        if($userLogin){
            $reqArticle = $request->toArray();
            $reqTitle = $reqArticle['title'];
            $reqText = $reqArticle['text'];
            $entityManager = $doctrine->getManager();
            $repository = $doctrine->getRepository(Users::class);
            $users = $repository->findOneByTitleJoinedToArticle($userLogin, $reqTitle);

            if($users){
                $article = $users->getFkArticleId();
                $articleTitle = $article[0]->getTitle();
                $acticleText = $article[0]->getText();

                if($reqText != $acticleText){
                    $article[0]->setText($reqText)
                        ->setDateChange(new DateTime());
                    $entityManager->flush();
                    return $this->json(['Edit' => 'DONE!']);
                }
            }
        }
        
        return $this->json(['Edit' => 'Don\'t need', 'Title' => "$articleTitle"]);
    }

    #[Route('/api/article/delete', name: 'api_article_delete', methods: ['DELETE'])]
    public function articleDelete(Request $request, ManagerRegistry $doctrine): Response
    {
        $serviceJWT = new ServiceJWT($request);
        $serviceJWT->checkJWT();

        $reqLogin = $serviceJWT->getLogin();
        $reqTitle = $request->query->get('title');

        if($reqTitle){
            $entityManager = $doctrine->getManager();
            $repository = $doctrine->getRepository(Users::class);
            $users = $repository->findOneByTitleJoinedToArticle($reqLogin, $reqTitle);
        }

        if($users){
            $article = $users->getFkArticleId();
            if($article[0]){
                $entityManager->remove($article[0]);
                $entityManager->flush();

                return $this->json(['Article' => "$reqTitle", 'Delete is' => 'OK!']);
            }
        }

        return $this->json(['Error' => 'Delete is false!']);
    }
}
