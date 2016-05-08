<?php

namespace Kpicaza\GenBundle\Generator;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

class GenCrudGenerator extends DoctrineCrudGenerator
{
    protected $services;

    protected $data;

    /**
     * {@inheritdoc}
     */
    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite)
    {
        $this->routePrefix = $routePrefix;
        $this->routeNamePrefix = self::getRouteNamePrefix($routePrefix);
        $this->actions = $needWriteActions ? array('index', 'show', 'new', 'edit', 'delete', 'options') : array('index', 'show', 'options');

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

        foreach ($this->data[0]['Handlers'] as $service => $arguments) {
            $this->generateHandlers($arguments, $forceOverwrite);
        }

        $this->generateHandlerException();

        $this->generateControllerClass($forceOverwrite);

        $dir = sprintf('%s/Resources/views/%s', $this->rootDir, str_replace('\\', '/', strtolower($this->entity)));

        if (!file_exists($dir)) {
            $this->filesystem->mkdir($dir, 0777);
        }

        $this->generateTestClass();
        $this->generateConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    protected function generateControllerClass($forceOverwrite)
    {
        $dir = $this->bundle->getPath();

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);
        $options = $this->data[0]['Controller'];

        $target = sprintf(
            '%s/Controller/%s/%sController.php',
            $dir,
            str_replace('\\', '/', $entityNamespace),
            $entityClass
        );

        if (!$forceOverwrite && file_exists($target)) {
            throw new \RuntimeException('Unable to generate the controller as it already exists.');
        }

        $handler = $this->data[0]['Handlers'];

        $this->processRenderFile(
            'crud/controller.php.twig',
            $target,
            null,
            $entityClass,
            $entityNamespace,
            key($handler),
            $options
        );
    }

    /**
     * @param $arguments
     * @param $forceOverwrite
     */
    protected function generateHandlers($arguments, $forceOverwrite)
    {
        $dir = $this->bundle->getPath().'/'.$arguments['dir'];

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $serviceNamespace = $arguments['dir'];

        $target = sprintf(
            '%s/%s.php',
            $dir,
            $arguments['classname']
        );

        if (!$forceOverwrite && file_exists($target)) {
            throw new \RuntimeException('Unable to generate the handler as it already exists.');
        }

        $this->processRenderFile(
            'crud/handler.php.twig',
            $target,
            $arguments['classname'],
            $entityClass,
            $serviceNamespace,
            null
        );

        $this->addToServices($this->data[0]['Handlers']);
    }

    protected function generateHandlerException()
    {
        return $this->renderFile('crud/formException.php.twig', sprintf('%s/Exception/InvalidFormException.php', $this->bundle->getPath()), array(
            'namespace' => $this->bundle->getNamespace(),
        ));
    }

    protected function generateTestClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);

        $dir = sprintf('%s/../tests/%s/Controller/', $this->rootDir, $this->bundle->getName());

        $target = $dir.$entityClass.'ControllerTest.php';

        $this->processRenderFile(
            'crud/tests/controllerTest.php.twig',
            $target,
            sprintf('%sControllerTest', $entityClass),
            $entityClass,
            'Controller',
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
            'actions' => $this->actions,
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'entity_singularized' => $this->entitySingularized,
            'entity_pluralized' => $this->entityPluralized,
            'entity_class' => $entityClass,
            'namespace' => $this->bundle->getNamespace(),
            'service_namespace' => $serviceNamespace,
            'entity_namespace' => $serviceNamespace,
            'format' => $this->format,
            'class_name' => $classname,
            'service' => $service,
            'fields' => $this->metadata->fieldMappings,
            'options' => $options,
        ));
    }

    /**
     * @param $definition
     */
    protected function addToServices($definition)
    {
        $file = sprintf('%s/config/%s', $this->rootDir, 'services.yml');

        $yaml = new Parser();
        $services = $yaml->parse(file_get_contents($file));

        if (empty($services['services'][sprintf('app.%s_repository', strtolower($this->entity))])) {
            $file = sprintf('%s/config/%s', $this->rootDir, 'gen/services.yml');
            $services = array_merge($services, $yaml->parse(file_get_contents($file)));
        }

        $array = array(
            key($definition) => array(
                'class' => $definition[key($definition)]['class'],
                'arguments' => array(
                    $definition[key($definition)]['arguments'][0][0],
                    $definition[key($definition)]['arguments'][1][0],
                ),
            ),
        );

        $services['services'][key($array)] = $array[key($array)];

        $dumper = new Dumper();

        $yaml = $dumper->dump($services, 4);

        $file = sprintf('%s/config/%s', $this->rootDir, 'gen/services.yml');

        file_put_contents($file, $yaml);
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

            $data[] = $yaml->parse(file_get_contents($dir.'/'.$file));
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateConfiguration()
    {
        if (!in_array($this->format, array('yml', 'xml', 'php'))) {
            return;
        }

        $target = sprintf(
            '%s/Resources/config/routing/%s.%s',
            $this->bundle->getPath(),
            strtolower(str_replace('\\', '_', $this->entity)),
            $this->format
        );

        $this->renderFile('crud/config/routing.'.$this->format.'.twig', $target, array(
            'actions' => $this->actions,
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
        ));
    }
}
