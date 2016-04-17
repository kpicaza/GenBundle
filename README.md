# GenBundle

## Modo de uso

### Configuraciones:

**1.** Debemos instalar, activar y configurar los sigientes Bundles contribuidos.

**1.1.** Instalamos los bundles con composer


    // composer.json
    ...
        "require": {
            ...
            "nelmio/cors-bundle": "^1.4",
            "friendsofsymfony/rest-bundle": "^1.7",
            "nelmio/api-doc-bundle": "^2.12",
            "jms/serializer-bundle": "^1.1"

**1.2.** Activamos los modulos en el kernel

    // app/config/AppKernel.php
    ...
            $bundles = [
                ...
                new JMS\SerializerBundle\JMSSerializerBundle(),
                new Nelmio\CorsBundle\NelmioCorsBundle(),
                new FOS\RestBundle\FOSRestBundle(),
                new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
                new AppBundle\AppBundle(),
            ]
    ...

**1.3.** Ahora configuramos los bundles recién activados.

**1.3.1.** Actualizamos el `config.yml`.

    // app/config/config.yml
    ...
    framework:
        ...
        validation:      { enable_annotations: true }
        serializer:      { enabled: true }
        ...
    nelmio_cors:
        defaults:
            allow_credentials: false
            allow_origin: []
            allow_headers: []
            allow_methods: []
            expose_headers: []
            max_age: 0
            hosts: []
            origin_regex: false
        paths:
        paths:
            '^/api/':
                allow_origin: ['*']
                allow_headers: ['*']
                allow_methods: ['POST', 'PUT', 'PATCH', 'GET', 'DELETE', 'OPTIONS']
                max_age: 3600

    nelmio_api_doc:
        name: 'Sf3 test API docs'
        default_sections_opened:  true
    # Api Docs template
    #    motd:
    #        template: ::base_print.html.twig
        sandbox:
    #        enabled: false # default true
            request_format:
                method: accept_header
    fos_rest:
        param_fetcher_listener: true
        disable_csrf_role: ROLE_USER
        routing_loader:
            default_format:       json
            include_format:       false

**1.3.2.** El `security.yml`

    // app/config/security.yml
        firewalls:
            ...
            main:
                pattern: ^/
                anonymous: ~
                # activate different ways to authenticate

                # http_basic: ~
                # http://symfony.com/doc/current/book/security.html#a-configuring-how-your-users-will-authenticate

                # form_login: ~
                # http://symfony.com/doc/current/cookbook/security/form_login_setup.html
            docs:
                pattern:  ^/api/doc
                stateless: true
                anonymous: true

            api:
                pattern:   ^/api
                stateless: true
                anonymous: ~

        access_decision_manager:
            strategy: unanimous

        access_control:
            #- { path: ^/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }

**1.3.3.** Por ultimo damos de alta las rutas en el `routing.yml`.

    // app/config/routing.yml
    ...
    NelmioApiDocBundle:
        resource: "@NelmioApiDocBundle/Resources/config/routing.yml"
        prefix:   /api/doc

