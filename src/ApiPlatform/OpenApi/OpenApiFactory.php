<?php

namespace App\ApiPlatform\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

class OpenApiFactory implements OpenApiFactoryInterface { // services.yaml

    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        /** @var PathItem $path */
        foreach($openApi->getPaths()->getPaths() as $key => $path) {
            if($path->getGet() && $path->getGet()->getSummary() === 'hidden') {
                $openApi->getPaths()->addPath($key, $path->withGet(null));
            }
        } // -- Va cacher les ressources qui ne sont pas utilisé à condition qu'il est dans son 'openapi' 'summary: 'hidden''

        // Authentification via jwt --
        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['bearerAuth'] = new ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer', // On utilise le schema 'bearer' qui est reconnu par 'openApi'
            'bearerFormat' => 'JWT' // On peut définir le format
        ]);

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Credentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'username' => [
                    'type' => 'string',
                    'example' => 'admin@gmail.com'
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'admin'
                ]
            ]
        ]);

        $schemas = $openApi->getComponents()->getSchemas(); // On crée un nouveau schema
        $schemas['Token'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true // Permet de spécifier que ça ne sera jamais modifiable
                ]
            ]
        ]);
        // --

        /*
                    /*
            $openApi = $openApi->withSecurity([['bearerAuth' => []]]);
👉 Ça dit à Swagger : toutes les routes utilisent bearerAuth

OU seulement pour /me :

            $meOperation = $meOperation->withSecurity([['bearerAuth' => []]]); -- Pour indiquer la protection ou le faire au niveau des attribut
            - Permet de caché les paramètre dans la documentation
                $meOperation = $openApi->getPaths()->getPath('/api/plateforme/me')->getGet()->withParameters([]);
                $mePathItem = $openApi->getPaths()->getPath('/api/plateforme/me')->withGet($meOperation);
                $openApi->getPaths()->addPath('/api/plateforme/me', $mePathItem);
        *

        // Pour enlever les paramètre dans la documentation -- Par défaut sur la doc il a été gérer grâce au 'GetCollection'
        $meOperation = $openApi->getPaths()->getPath('/fr/api/plateforme/me')->getGet()->withParameters([]);
        $mePathItem = $openApi->getPaths()->getPath('/fr/api/plateforme/me')->withGet($meOperation);
        $openApi->getPaths()->addPath('/fr/api/plateforme/me', $mePathItem);

        */

        /*
                    // La définition de la documentation pour la partie '/login'
        $pathItem = new PathItem(
            post: new Operation(
                operationId: 'postApiLogin',
                tags: ['User'],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials'
                            ]
                        ]
                    ])
                ),
                /*
                    responses: [
                        '200' => [
                            'description' => 'Utilisateur connecté',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/User.jsonld-read.User'
                                    ]
                                ]
                            ]
                        ]
                    ]
                * !!


                responses: [ // La reponse pour 'jwt'
                    '200' => [
                        'description' => 'JWT Token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token'
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );
        $openApi->getPaths()->addPath('/fr/api/login', $pathItem);

                // Route de deconnexion, permettra plutârd d'invalidé le token
        $pathItem = new PathItem(
            post: new Operation(
                operationId: 'postApiLogout',
                tags: ['User'], // Auth
                responses: [
                    '204' => []
                ]
                // Header application/json
            )
        );
        $openApi->getPaths()->addPath('/logout', $pathItem); // LogoutListener pour ne pas redirigé lors d'un appel Api


        */







/*



                $openApi->getPaths()->addPath('/ping', new PathItem(null, 'Ping', null, new Operation('ping-id', [], [], 'Repond'))); // Permet de créer une nouvelle opération

        # Authentification --
        /*
            $schemas = $openApi->getComponents()->getSecuritySchemes(); - Authentification par cookie
            $schemas['cookieAuth'] = new ArrayObject([
                'type' => 'apiKey',
                'in' => 'cookie', -- On indique que ça sera dans un cookie
                'name' => 'PHPSESSID'
            ]); -- Ensuite dans la doc j'ai la possiblité de définir mon cookie

            -- Ou
            $schemas['cookieAuth'] = new SecurityScheme('apiKey', 'header', 'PHPSESSID', 'cookie');

            -- Si on veut dire que toutes les routes ou une seule route sont privée
            $openApi = $openApi->withSecurity([['cookieAuth' => []]]); -- Ou le faire dans la partie opération 'openapi' - 'security'
        *

        // jwt --
        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['bearerAuth'] = new ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer', // On utilise le schema 'bearer' qui est reconnu par 'openApi'
            'bearerFormat' => 'JWT' // On peut définir le format
        ]);

        $schemas = $openApi->getComponents()->getSchemas(); // On crée un nouveau schema
        $schemas['Token'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true // Permet de spécifier que ça ne sera jamais modifiable
                ]
            ]
        ]);
        // --

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Credentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'username' => [
                    'type' => 'string',
                    'example' => 'admin'
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'admin'
                ]
            ]
        ]);

        // - Pour pouvoir se connecter
        $pathItem = new PathItem(
            post: new Operation( // Lorsqu'on 'post' on crée une opération
                operationId: 'postApiLogin', // Vu qu'il a besoin d'un 'operationId' unique
                tags: ['User'],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials' // On se refère au 'Credentials' qu'on a crée pour le contenu
                            ]
                        ]
                    ])
                ),
                /* -- La reponse pour 'json'
                    responses: [
                        '200' => [
                            'description' => 'Utilisateur connecté',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/User.jsonld-read.User' -- Le schema de l'utilisateur
                                    ]
                                ]
                            ]
                        ]
                    ]
                *
                responses: [ // La reponse pour 'jwt'
                    '200' => [
                        'description' => 'JWT Token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token'
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );
        $openApi->getPaths()->addPath('/fr/api/login', $pathItem);
        # --





                /*


            
            - Permet de caché les paramètre dans la documentation
                $meOperation = $openApi->getPaths()->getPath('/api/plateforme/me')->getGet()->withParameters([]);
                $mePathItem = $openApi->getPaths()->getPath('/api/plateforme/me')->withGet($meOperation);
                $openApi->getPaths()->addPath('/api/plateforme/me', $mePathItem);
        *

        /*
            - Pour enlever les paramètre dans la documentation
        *
        $meOperation = $openApi->getPaths()->getPath('/fr/api/plateforme/me')->getGet()->withParameters([]);
        $mePathItem = $openApi->getPaths()->getPath('/fr/api/plateforme/me')->withGet($meOperation);
        $openApi->getPaths()->addPath('/fr/api/plateforme/me', $mePathItem);
        // $meOperation = $meOperation->withSecurity([['bearerAuth' => []]]); -- Pour indiquer la protection ou le faire au niveau des attribut





        // - On a ajouté la route pour la deconnexion dans la doc de 'ApiPlatform'
        $pathItem = new PathItem(
            post: new Operation(
                operationId: 'postApiLogout',
                tags: ['User'], // Auth
                responses: [
                    '204' => []
                ]
                // Header 'application/json'
            )
        );
        $openApi->getPaths()->addPath('/fr/deconnexion', $pathItem); // 'LogoutListener' pour ne pas redirigé lors d'un appel api
        */


        return $openApi;
    }

} // 'summury = hidden' cache un champ