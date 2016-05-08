<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kpicaza\GenBundle\Command;

use Kpicaza\GenBundle\Generator\GenRepositoryGenerator;
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generates a form type class for a given Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 */
class RepositoryGenCommand extends GenerateDoctrineCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gen:generate:repository-pattern')
            ->setAliases(array('generate:doctrine:repository-pattern'))
            ->setDescription('Generates a repository pattern classes based on a Doctrine entity')
            ->setDefinition(array(
                new InputArgument('entity', InputArgument::REQUIRED, 'The entity class name to initialize (shortcut notation)'),
                new InputOption('overwrite', '', InputOption::VALUE_NONE, 'Overwrite any existing class when generating the Repository pattern contents'),
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a repository pattern classes based on a Doctrine entity.

<info>php %command.full_name% AcmeBlogBundle:Post</info>

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

        $forceOverwrite = $input->getOption('overwrite');

        $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
        $metadata = $this->getEntityMetadata($entityClass);
        $bundle = $this->getApplication()->getKernel()->getBundle($bundle);
        $generator = $this->getGenerator($bundle);

        $generator->generate($bundle, $entity, $metadata[0], 'yml', $forceOverwrite);

        $output->writeln(
            'The new Repository pattern implementation has been created under Model and Repository folders.'
        );
    }

    protected function createGenerator()
    {
        return new GenRepositoryGenerator(
            $this->getContainer()->get('filesystem'),
            $this->getContainer()->getParameter('kernel.root_dir')
        );
    }

    protected function getGenerator(BundleInterface $bundle = null)
    {
        $repositoryGenerator = $this->createGenerator();
        $repositoryGenerator->setSkeletonDirs($this->getSkeletonDirs($bundle));

        return $repositoryGenerator;
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = parent::getSkeletonDirs($bundle);

        if (isset($bundle) && is_dir($dir = $bundle->getPath().'/Resources/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir().'/Resources/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $kernel = $this->getApplication()->getKernel();

        $skeletonDirs[] = $kernel->locateResource('@KpicazaGenBundle/Resources/skeleton');
        $skeletonDirs[] = $kernel->locateResource('@KpicazaGenBundle/Resources');

        return array_reverse($skeletonDirs);
    }
}
