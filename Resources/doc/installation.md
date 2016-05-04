# GenBundle

## Installation

    composer require kpicaza/gen-bundle dev-master

**1.** We need to activate and configure our new bundle and each dependencies.

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
            if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
                $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
                $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
                $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Kpicaza\GenBundle\KpicazaGenBundle();
            }

See [basic usage](basic-usage.md) for dependencies basic configurations.

**2.** Check if command is correctly enabled:

    php bin/console gen:generate:rest --help
    php bin/console gen:generate:form --help
