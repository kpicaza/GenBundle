<?php

namespace Kpicaza\GenBundle\Command;

use Kpicaza\GenBundle\Generator\GenFormGenerator;
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineFormCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class FormGenCommand
 * @package Kpicaza\GenBundle\Command
 */
class FormGenCommand extends GenerateDoctrineFormCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gen:generate:form')
            ->setDescription('Generates a form type class based on a Doctrine entity')
            ->setDefinition(array(
                new InputArgument('entity', InputArgument::REQUIRED, 'The entity class name to initialize (shortcut notation)'),
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a form class based on a Doctrine entity.

<info>php %command.full_name% AcmeBlogBundle:Post</info>

Every generated file is based on a template. There are default templates but they can be overriden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/form
APP_PATH/Resources/SensioGeneratorBundle/skeleton/form</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton
EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = Validators::validateEntityName($input->getArgument('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
        $metadata = $this->getEntityMetadata($entityClass);
        $bundle = $this->getApplication()->getKernel()->getBundle($bundle);
        $generator = $this->getGenerator($bundle);

        $generator->generate($bundle, $entity, $metadata[0]);

        $output->writeln(sprintf(
            'The new %s.php class file has been created under %s.',
            $generator->getClassName(),
            $generator->getClassPath()
        ));
    }

    protected function createGenerator()
    {
        return new GenFormGenerator($this->getContainer()->get('filesystem'));
    }

    protected function getGenerator(BundleInterface $bundle = null)
    {
        $formGenerator = $this->createGenerator();
        $formGenerator->setSkeletonDirs($this->getSkeletonDirs($bundle));

        return $formGenerator;
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = parent::getSkeletonDirs($bundle);

        if (isset($bundle) && is_dir($dir = $bundle->getPath() . '/Resources/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir() . '/Resources/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $kernel = $this->getApplication()->getKernel();

        $skeletonDirs[] = $kernel->locateResource('@KpicazaGenBundle/Resources/skeleton');
        $skeletonDirs[] = $kernel->locateResource('@KpicazaGenBundle/Resources');

        return array_reverse($skeletonDirs);
    }
}