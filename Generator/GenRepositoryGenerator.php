<?php

namespace Kpicaza\GenBundle\Generator;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

class GenRepositoryGenerator extends Generator
{
    protected $filesystem;
    protected $entity;
    protected $entitySingularized;
    protected $entityPluralized;
    protected $bundle;
    protected $metadata;
    protected $rootDir;
    protected $data;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem A Filesystem instance
     * @param string $rootDir The root dir
     */
    public function __construct(Filesystem $filesystem, $rootDir)
    {
        $this->filesystem = $filesystem;
        $this->rootDir = $rootDir;
    }

    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $format, $forceOverwrite)
    {
        if (count($metadata->identifier) != 1) {
            throw new \RuntimeException('The REST generator does not support entity classes with multiple or no primary keys.');
        }

        $this->entity = $entity;
        $this->entitySingularized = lcfirst(Inflector::singularize($entity));
        $this->entityPluralized = lcfirst(Inflector::pluralize($entity));
        $this->bundle = $bundle;
        $this->metadata = $metadata;
        $this->setFormat($format);

        $this->data = $this->genParamsData();

        foreach ($this->data[0]['Repository'] as $service => $arguments) {
            $this->generateRepository($arguments, $forceOverwrite);
        }

        $this->generateTestClass();
    }

    public function generateRepository($arguments, $forceOverwrite)
    {

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $serviceNamespace = $arguments['dir'];

        $items = array(
            'Interface',
            ''
        );

        foreach ($items as $item) {
            if ('Gateway' == $arguments['type'] && '' == $item) {
                $arguments['dir'] = str_replace('Model/' . $this->entity, 'Repository', $arguments['dir']);
                $serviceNamespace = $arguments['dir'];
            }
            $dir = $this->bundle->getPath() . '/' . $arguments['dir'];

            $target = sprintf(
                '%s/%s.php',
                $dir,
                $arguments['classname'] . $item
            );

            if (!$forceOverwrite && file_exists($target)) {
                throw new \RuntimeException('Unable to generate the repository pattern as it already exists.');
            }

            if ('Interface' == $item && 'Repository' == $arguments['type']) {
                $this->generateEntityInterface($arguments, $forceOverwrite);
                continue;
            }

            $this->processRenderFile(
                sprintf('repository/%s.php.twig', strtolower($arguments['type']) . $item),
                $target,
                $arguments['classname'],
                $entityClass,
                $serviceNamespace,
                null
            );
        }

        $this->addPatternToServices($this->data[0]['Repository']);
    }

    /**
     * @param $arguments
     * @param $forceOverwrite
     */
    public function generateEntityInterface($arguments, $forceOverwrite)
    {
        $dir = $this->bundle->getPath() . '/' . $arguments['dir'];

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $serviceNamespace = $arguments['dir'];
        $arguments['classname'] = $entityClass . 'Interface';
        $target = sprintf(
            '%s/%s.php',
            $dir,
            $arguments['classname']
        );

        if (!$forceOverwrite && file_exists($target)) {
            throw new \RuntimeException('Unable to generate the Interface as it already exists.');
        }

        $this->processRenderFile(
            'repository/entityInterface.php.twig',
            $target,
            $arguments['classname'],
            $entityClass,
            $serviceNamespace,
            null
        );
    }

    protected function generateTestClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);

        $dir = sprintf('%s/../tests/%s/Model/', $this->rootDir, $this->bundle->getName());

        $target = $dir . $entityClass . 'RepositoryTest.php';


        $this->processRenderFile(
            'crud/tests/repositoryTest.php.twig',
            $target,
            sprintf('%sRepositoryTest', $entityClass),
            $entityClass,
            'Model',
            null
        );
    }

    /**
     * @param $file
     * @param $target
     * @param $classname
     * @param $entityClass
     * @param $serviceNamespace
     * @param null $service
     */
    protected function processRenderFile($file, $target, $classname, $entityClass, $serviceNamespace, $service = null, $options = null)
    {
        $this->renderFile($file, $target, array(
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'entity_singularized' => $this->entitySingularized,
            'entity_pluralized' => $this->entityPluralized,
            'entity_class' => $entityClass,
            'namespace' => $this->bundle->getNamespace(),
            'service_namespace' => $serviceNamespace,
            'entity_namespace' => $serviceNamespace,
            'class_name' => $classname,
            'service' => $service,
            'fields' => $this->metadata->fieldMappings,
            'options' => $options
        ));
    }

    /**
     * Sets the configuration format.
     *
     * @param string $format The configuration format
     */
    protected function setFormat($format)
    {
        switch ($format) {
            case 'yml':
            case 'xml':
            case 'php':
            case 'annotation':
                $this->format = $format;
                break;
            default:
                $this->format = 'yml';
                break;
        }
    }

    /**
     * @return array
     */
    protected function genParamsData()
    {
        $data = array();
        $dir = sprintf('%s/config/gen/%s', $this->rootDir, $this->entity);
        $yaml = new Parser();

        $files = scandir(str_replace('\\', '/', $dir));

        foreach ($files as $file) {
            if (0 == strpos($file, '.')) {
                continue;
            }

            $data[] = $yaml->parse(file_get_contents($dir . '/' . $file));
        }

        return $data;
    }

    /**
     * @param $definitions
     */
    protected function addPatternToServices($definitions)
    {
        $file = sprintf('%s/config/%s', $this->rootDir, 'services.yml');

        $yaml = new Parser();
        $services = $yaml->parse(file_get_contents($file));

        foreach ($definitions as $key => $definition) {
            $array = array(
                $key => array(
                    'class' => $definition['class'],
                    'arguments' => empty($definition['arguments']) ? null : array(
                        $definition['arguments'][0][0],
                        empty($definition['arguments'][1][0]) ?: $definition['arguments'][1][0]
                    )
                )
            );

            $arguments = array();

            if (!empty($definition['factory'])) {
                $arguments['factory'] = $definition['factory'];
            }

            if (!empty($definition['arguments'])) {
                $arguments['arguments'][] = $definition['arguments'][0];
                if (!empty($definition['arguments'][1])) {
                    $arguments['arguments'][] = $definition['arguments'][1];
                }
            }

            $services['services'][$key] = array_merge($array[$key], $arguments);
        }

        $dumper = new Dumper();

        $yaml = $dumper->dump($services, 4);

        $file = sprintf('%s/config/%s', $this->rootDir, 'gen/services.yml');

        file_put_contents($file, $yaml);
    }
}
