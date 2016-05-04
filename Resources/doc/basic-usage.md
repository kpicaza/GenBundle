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

**2.** Crando el primer recurso

**2.1.** Para generar el REST necesitaos generar primero las entidades de Doctrine, que
utilizaremos como recursos. Como ejemplo crearemos la entidad "Post" en yml.

    // src/AppBundle/config/docritne/Post.orm.yml
    AppBundle\Entity\Post:
        type: entity
        table: gentest_post
        repositoryClass: AppBundle\Repository\PostGateway
        id:
            id:
                type: integer
                id: true
                generator:
                    strategy: AUTO
        fields:
            title:
                length: '140'
            description:
                type: text
            created:
                type: datetime
            updated:
                type: datetime
                nullable: true
        lifecycleCallbacks:
            prePersist: [ setCreatedAtValue ]
            preUpdate: [ setUpdatedAtValue ]

**2.2.** Generamos el modelo

Creamos la bbdd si no la tenemos creada ya

    php bin/console doctrine:database:create

Generamos la entidad, podemos utilizar doctrine o hacerla a mano

    php bin/console doctrine:generate:entities AppBundle:Post

Esto genera un modelo tonto con getters y setters para todos los campos.

Actualizamos el esquema de bbdd.

    php bin/console doctrine:schema:update --force

**3.** Instrucciones del generador

**3.1.** Crearemos el archivo `intructions.yml` en el directorio `app/config/gen/<<Entity>>`

    // app/config/gen/Post/intructions.yml
    Controller:
        index:
            security:
                - ROLE_USER
        get:
            security:
                - ROLE_USER
        post:
            security:
                - ROLE_ADMIN
        put:
            security:
                - ROLE_ADMIN
        delete:
            security:
                - ROLE_ADMIN
        options:

    Handlers:
        app.api_post_handler:
            class: AppBundle\Handler\Post\ApiPostHandler
            arguments:
                - [ "@app.post_repository", AppBundle\Model\Post\PostRepository ]
                - [ "@form.factory", Symfony\Component\Form\Form ]
            dir: "Handler/Post"
            classname: "ApiPostHandler"
            type: Handler

    Repository:
        app.post_factory:
            class: AppBundle\Model\Post\PostFactory
            dir: "Model/Post"
            classname: "PostFactory"
            type: Factory

        app.post_gateway:
            class: AppBundle\Repository\PostGateway
            factory: [ "@doctrine", getRepository] #  "@doctrine" or "@doctrine_mongodb"
            arguments: [ "AppBundle:Post" ]
            dir: "Model/Post"
            classname: "PostGateway"
            type: Gateway

        app.post_repository:
            class: AppBundle\Model\Post\PostRepository
            arguments: [ "@app.post_gateway", "@app.post_factory" ]
            dir: "Model/Post"
            classname: "PostRepository"
            type: Repository

**3.1.1.** Controller:

Podemos generar un array con los diferentes metodos de nuestro controlador para
aplicarles la capa de seguridad por rol de symfony, podemos asignar uno, varios o ningún
rol a cada método.

Ejemplo de definición de seguridad en controlador:

    // app/config/gen/intructions.yml
    ...
    Controller:
        index:
            security:
                - ROLE_USER
        get:
            security:
                - ROLE_USER
        post:
            security:
                - ROLE_ADMIN
        put:
            security:
                - ROLE_ADMIN
        delete:
            security:
                - ROLE_ADMIN
        options:


**3.1.2.** Handler:

Es necesario definir el handler para nuestros formularios, la primera clave del array,
será el nombre del servicio, en el ejemplo `app.post_factory`(Este comportamiento se
repite en typo Repository).

Esta clave requiere un array los siguientes campos:

* **class:** Namespace de la clase "Handler".
* **arguments:** Argumentos necesario para el constructor de la clase "Handler".
* **dir:** Directorio en que se alojará la clase "Handler".
* **classname:** El nombre de la clase "Handler"
* **type:** El tipo de servicio que vamos a crear, en este caso siempre será "Handler".

Ejemplo de definición de Handler:

    // app/config/gen/intructions.yml
    ...
    Handlers:
        app.api_post_handler:
            class: AppBundle\Handler\Post\ApiPostHandler
            arguments:
                - [ "@app.post_repository", AppBundle\Model\Post\PostRepository ]
                - [ "@form.factory", Symfony\Component\Form\Form ]
            dir: "Handler/Post"
            classname: "ApiPostHandler"
            type: Handler

**3.1.3.** Repository:

Por último necesitaremos definir las clases de nuestro repositorio

    // app/config/gen/intructions.yml
    ...
    Repository:
        app.post_factory:
            class: AppBundle\Model\Post\PostFactory
            dir: "Model/Post"
            classname: "PostFactory"
            type: Factory

        app.post_gateway:
            class: AppBundle\Repository\PostGateway
            factory: [ "@doctrine", getRepository] #  "@doctrine" or "@doctrine_mongodb"
            arguments: [ "AppBundle:Post" ]
            dir: "Model/Post"
            classname: "PostGateway"
            type: Gateway

        app.post_repository:
            class: AppBundle\Model\Post\PostRepository
            arguments: [ "@app.post_gateway", "@app.post_factory" ]
            dir: "Model/Post"
            classname: "PostRepository"
            type: Repository

**4.** Ejecutando el comando

**4.1.** Podemos ver todas las opciones del generador con el siguiente commando:

    php bin/console gen:generate:rest --help

**4.2.** Pasamos a generar nuestra primera entidad.

    php bin/console gen:generate:rest \
    --entity AppBundle:Post \
    --with-write \
    --format annotation \
    --overwrite \
    -vvv \
    --no-interaction

**4.2.1.** Veamos el código generado:

    AppBundle/
    |_ Controller/
    |   |_ PostController.php
    |_ Entity/
    |   |_ Post.php
    |_ Form/
    |   |_ PostType.php
    |_ Handler/
    |   |_ Post/
    |       |_ ApiPostHandler.php
    |_ Model/
    |   |_ Post/
    |       |_ PostInterface.php
    |       |_ PostGatewayInterface.php
    |       |_ PostFactoryInterface.php
    |       |_ PostFactory.php
    |       |_ PosrRepository.php
    |_ Repository/
    |   |_ PostGateway.php

Fuera de nuestro bundle en la carpeta `app/config/gen/` se ha generado un archivo
`services.yml` copiamos su contenido y lo pegamos en `app/config/services.yml`. Este
archivo contiene los servicios que se ha auto generado, más todo el contenido que
disponia el archivo `services.yml` original.

En este momento hemos generado cantidad de código, todo el modelo, el handler y el
controlador de nuestra entidad, para que el patrón repositorio funcione
correctamente necesitamo hacer que nuestra entidad implemente el nuevo interface
que se ha generado.

        // src/AppBundle/Entity/Post.php
        <?php

        namespace AppBundle\Entity;

        use AppBundle\Model\Post\PostInterface;
        use Symfony\Component\Validator\Constraints as Assert;
        use Doctrine\ORM\Mapping as ORM;

        /**
         * Post
         */
        class Post implements PostInterface
        ...
            /**
             * @ORM\PrePersist
             */
            public function setCreatedAtValue()
            {
                if (!$this->getCreatedAt()) {
                    $this->created = new \DateTime();
                }
            }

            /**
             * @ORM\PreUpdate
             */
            public function setUpdatedAtValue()
            {
                $this->updated = new \DateTime();
            }
        ...

Copiamos las definiciones de los servicios que se han generado en el archivo `app/config/gen/services.yml` y las pegamos
en en `app/configservices.yml`.

Damos de alta la ruta apra fosrest en `app/config/routing.yml`.

    # app/config/routing.yml
    app_post:
        type: rest
        prefix: /api
        resource: AppBundle\Controller\PostController


*Añadimos el namespace `Constraints` como `Assert` para añadir validacion mediante
anotaciones. Ver documentación oficicial sobre validaciones.

Ya tenemos nuestro rest creado, podemos ver la documentación y probar los recursos
 en la ruta `/api/doc`.

![Generated docs](Resources/doc/docs-snapshot.png)
