<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use MyApp\ReqDataService;
use App\Entity\Users;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;
use Firebase\JWT\JWT;
use Throwable;

class UsersController extends AbstractController
{
    #[Route('/api/user/registration', name: 'api_user_registration_options', methods: ['OPTIONS'])]
    public function userRegistrationOptions(Request $request): Response
    {
        $response = new Response(
            '',
            Response::HTTP_OK,
            [
                'content-type' => 'text/html, application/json',
                'Access-Control-Allow-Origin' => 'http://localhost:4200',
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
                // 'Access-Control-Max-Age' => '86400' X-PINGOTHER,
            ]
        );

        return $response;
    }
    
    #[Route('/api/user/registration', name: 'api_user_registration', methods: ['POST'])]
    public function userRegistration(Request $request, ManagerRegistry $doctrine): Response
    {   
        $data = new ReqDataService($request);
        $reqUserLogin = $data->getReqUserLogin();
        $repository = $doctrine->getRepository(Users::class);
        try
        {
            $users = $repository->findOneBy(['login' => "$reqUserLogin"]);
        }
        catch(Throwable $ex)
        {
            // echo 'Database error, please try later!';
            // return $this->json(['error' => 'Database not response, try later!'],
            //     $status = 404, 
            //     $headers = ['Access-Control-Allow-Origin' => 'http://localhost:4200'], 
            //     $context = []);

            $response = new Response(
                '',
                Response::HTTP_SERVICE_UNAVAILABLE,
                [
                    'content-type' => 'text/html',
                    'Access-Control-Allow-Origin' => 'http://localhost:4200',
                    'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type',
                ]
            );
    
            return $response;
        }
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
            $respArrUser = [
                'hello' => "User ID: $userId",
                'userLogin' => "$userLogin Registration complete!"
            ];
            
            return $this->json($respArrUser, $status = 200,
                $headers = ['Access-Control-Allow-Origin' => 'http://localhost:4200'],
                $context = []);
        }
        $respArray = ['error' => "Name {$reqUserLogin} is alredy use! "];
        return $this->json($respArray, $status = 200, $headers = ['Access-Control-Allow-Origin' => 'http://localhost:4200'], $context = []);
    }

    #[Route('/api/user/authorization', name: 'api_user_authorization_options', methods: ['OPTIONS'])]
    public function userAuthorizationOptions(Request $request, ManagerRegistry $doctrine): Response
    {
        $response = new Response(
            '',
            Response::HTTP_OK,
            [
                'content-type' => 'text/html, application/json',
                'Access-Control-Allow-Origin' => 'http://localhost:4200',
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
            ]
        );

        return $response;
    }

    #[Route('/api/user/authorization', name: 'api_user_authorization', methods: ['POST'])]
    public function userAuthorization(Request $request, ManagerRegistry $doctrine): Response
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
                // return $this->json(['hello' => "{$reqLogin} authorization OK!",
                //                     'jwt' => "$jwt"
                //                     ]);
                return $this->json(
                    [
                        'hello' => "{$reqLogin} authorization OK!",
                        'jwt' => "$jwt",
                        'exp' => $expirationTime
                    ],
                    $status = 200, 
                    $headers = ['Access-Control-Allow-Origin' => 'http://localhost:4200'], 
                    $context = []);
            }
        }
        // return $this->json(['error' => " {$reqLogin} wrong login or password!"]);
        return $this->json(['error' => " {$reqLogin} wrong login or password!"],
                                    $status = 200, 
                                    $headers = ['Access-Control-Allow-Origin' => 'http://localhost:4200'], 
                                    $context = []);
    }

    #[Route('/api/user/edit', name: 'api_user_edit', methods: ['PUT'])]
    public function userEdit(Request $request, ManagerRegistry $doctrine): Response
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
    public function userDelete(Request $request, ManagerRegistry $doctrine): Response
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
}
