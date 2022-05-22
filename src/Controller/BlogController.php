<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use MyApp\ReqDataService;
use App\Entity\Users;
use App\Entity\Articles;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MyApp\ServiceJWT;

class BlogController extends AbstractController
{
    #[Route('/api/user/registration', name: 'api_user_registration', methods: ['POST'])]
    public function user_registration(Request $request, ManagerRegistry $doctrine): Response
    {   
        $data = new ReqDataService($request);
        $reqUserLogin = $data->getReqUserLogin();
        $repository = $doctrine->getRepository(Users::class);
        $users = $repository->findOneBy(['login' => "$reqUserLogin"]);
        $entityManager = $doctrine->getManager();

        if (!$users){
            $users = new Users;
            $data->passwordHesh();
            $users
                ->setLogin($data->getReqUserLogin())
                ->setPassword($data->getReqUserPassword())
                ->setEmail($data->getReqUserEmail())
                ->setRole($data->getReqUserRole())
                ->setDateCreate(new DateTime())
                ->setDateChange(new DateTime());
            $entityManager->persist($users);
            $entityManager->flush();
            $userId = $users->getId();
            $userLogin = $users->getLogin();
            return $this->json([
                'Hello' => "User ID: $userId",
                'User Login:' => "$userLogin Registration complete!"
            ]);
        }
        return $this->json(['Error' => "Name {$reqUserLogin} is alredy use! "]);
    }

    #[Route('/api/user/authorization', name: 'api_user_authorization', methods: ['POST'])]
    public function user_authorization(Request $request, ManagerRegistry $doctrine): Response
    {   
        $data = new ReqDataService($request);
        $reqLogin = $data->getReqUserLogin();
        $repository = $doctrine->getRepository(Users::class);
        $users = $repository->findOneBy(['login' => "$reqLogin"]);
        $secretKey  = "Secret_Key";
        $serverName = "localhost";

        if($users){
            $result = $data->autorization($users->getLogin(), $users->getPassword());
            if($result){
                $dateTime = new DateTime();
                $issuedAt = $dateTime->getTimestamp();
                $expirationTime = $dateTime->modify('+10 minutes')->getTimestamp();
                $dataJWT = [
                    'iss' => $serverName,
                    'iat' => $issuedAt,
                    'nbf' => $issuedAt,
                    'exp' => $expirationTime,
                    'userLogin' => $users->getLogin(),
                    'userRole' => $users->getRole(),
                ];
                
                $jwt = JWT::encode($dataJWT, $secretKey, 'HS256');
                return $this->json(['Hello ' => "{$reqLogin} authorization OK!",
                                    'Your jwt ' => "$jwt"
                                    ]);
            }
        }
        return $this->json(['Error' => " {$reqLogin} wrong login or password!"]);
    }

    #[Route('/api/user/edit', name: 'api_user_edit', methods: ['PUT'])]
    public function user_edit(Request $request, ManagerRegistry $doctrine): Response
    {   
        $data = new ReqDataService($request);
        $reqUserLogin = $data->getReqUserLogin();
        $repository = $doctrine->getRepository(Users::class);
        $users = $repository->findOneBy(['login' => "$reqUserLogin"]);
        $entityManager = $doctrine->getManager();
        
        if($users){
            if( $data->checkPassword($users->getPassword()) ){
                $data->passwordHesh();
                $users->setPassword($data->getReqUserPassword())
                    ->setDateChange(new DateTime());
                $entityManager->flush();
            }
            if( $data->checkEmail($users->getEmail()) ){
                $users->setEmail($data->getReqUserEmail())
                    ->setDateChange(new DateTime());
                $entityManager->flush();
            }
            return $this->json(['Hello' => " {$reqUserLogin} EDIT DONE!"]);
        }
        return $this->json(['Hello' => " {$reqUserLogin} EDIT DON'T need!"]);
    }

    #[Route('/api/user/delete', name: 'api_user_delete', methods: ['DELETE'])]
    public function user_delete(Request $request, ManagerRegistry $doctrine): Response
    {   
        $entityManager = $doctrine->getManager();
        $repository = $doctrine->getRepository(Users::class);
        $reqLogin = $request->query->get('login');
        $users = $repository->findOneBy(['login' => "$reqLogin"]);

        if($users){
            $entityManager->remove($users);
            $entityManager->flush();
            return $this->json(['Goodbye' => "{$reqLogin} DELETE is OK!"]);
        }
        return $this->json(['Error' => " {$reqLogin} DELETE FALSE!"]);
    }

    #[Route('/api/article/add', name: 'api_article_add', methods: ['POST'])]
    public function article_add(Request $request, ManagerRegistry $doctrine): Response
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
    public function article_edit(Request $request, ManagerRegistry $doctrine): Response
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
    public function article_delete(Request $request, ManagerRegistry $doctrine): Response
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
