Test Generator - GenBundle
==========================

[Home](milestone.md)

## 1. Test Generator:

- Test unitarios y funcionales.
- Pueden ser como mínimo de 2 tipos.
    - Test unitarios.
    - Test funcionales.
- El objetivo es cubrir por encima del 90% de cobertura.
- version 0.1.0

### 1.1. Puesta en marcha

Necesitaremos dar de alta una base de datos de testing, para no llenar la base de datos
de desarrollo de datos repetidos

### 1.2. Test unitarios

Para la primera version necesitaremos testear principamente las entidades de doctrine.
creará un test muy simple con PHPUnit `testNew<<Entity>>`.

    <?php
    // tests/AppBundle/Model/<<Entity>>RepositoryTest.php
    namespace AppBundle\Tests\Model;

    use AppBundle\Entity\<<Entity>>\<<Entity>>;
    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    class <<Entity>>RepositoryTest extends WebTestCase
    {
        // <<Entity>> Fields example.
        const SUBJECT = 'Test <<Entity>> subject';
        const DESCRIPTION = 'ha sido el texto de relleno estándar de las industrias desde el año 1500, ';
        const ENABLED = true;

        public function testNew<<Entity>>()
        {
            $entity = new <<Entity>>();

            $entity
                ->setSubject(self::SUBJECT)
                ->setDescription(self::DESCRIPTION)
                ->setEnabled(self::ENABLED)
                ->setUpdatedAt(new \DateTime())
            ;
        }
    }

Puede que necesitemos la librería [Faker](https://github.com/fzaninotto/Faker) para
generar contenido aleatorio controlado para nuestros tests.

