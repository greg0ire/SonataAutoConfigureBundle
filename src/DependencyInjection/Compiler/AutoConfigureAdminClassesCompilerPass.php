<?php

declare(strict_types=1);

namespace KunicMarko\SonataAutoConfigureBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Inflector\Inflector;
use KunicMarko\SonataAutoConfigureBundle\Annotation\AdminOptions;
use KunicMarko\SonataAutoConfigureBundle\Exception\EntityNotFound;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use ReflectionClass;
use function explode;
use function preg_replace;
use function end;
use function str_replace;
use function class_exists;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
final class AutoConfigureAdminClassesCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $entityNamespaces;

    /**
     * @var array
     */
    private $controllerNamespaces;

    /**
     * @var string
     */
    private $controllerSuffix;

    /**
     * @var string
     */
    private $managerType;

    public function process(ContainerBuilder $container): void
    {
        /** @var AnnotationReader $annotationReader */
        $annotationReader = $container->get('annotation_reader');
        $adminSuffix = $container->getParameter('sonata.auto_configure.admin.suffix');
        $this->managerType = $container->getParameter('sonata.auto_configure.admin.manager_type');
        $this->entityNamespaces = $container->getParameter('sonata.auto_configure.entity.namespaces');
        $this->controllerNamespaces = $container->getParameter('sonata.auto_configure.controller.namespaces');
        $this->controllerSuffix = $container->getParameter('sonata.auto_configure.controller.suffix');

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {
            $definition = $container->getDefinition($id);

            if (!$definition->isAutoconfigured()) {
                continue;
            }

            $adminClassAsArray = explode('\\', $adminClass = $definition->getClass());

            $name = end($adminClassAsArray);

            if ($adminSuffix) {
                $name = preg_replace("/$adminSuffix$/", '', $name);
            }

            /** @var AdminOptions $annotation */
            $annotation = $annotationReader->getClassAnnotation(
                new ReflectionClass($adminClass),
                AdminOptions::class
            ) ?? new AdminOptions();

            $this->setDefaultValuesForAnnotation($annotation, $name);

            $container->removeDefinition($id);
            $container->setDefinition(
                $annotation->adminCode,
                (new Definition($adminClass))
                    ->addTag('sonata.admin', $annotation->getOptions())
                    ->setArguments([
                        $annotation->adminCode,
                        $annotation->entity,
                        $annotation->controller
                    ])
                    ->setAutoconfigured(true)
                    ->setAutowired(true)
            );
        }
    }

    private function setDefaultValuesForAnnotation(AdminOptions $annotation, string $name): void
    {
        if (!$annotation->label) {
            $annotation->label = Inflector::ucwords(str_replace('_', ' ', Inflector::tableize($name)));
        }

        if (!$annotation->adminCode) {
            $annotation->adminCode = 'admin.' . Inflector::tableize($name);
        }

        if (!$annotation->entity) {
            [$annotation->entity, $managerType] = $this->findEntity($name);

            if (!$annotation->managerType) {
                $annotation->managerType = $managerType;
            }
        }

        if (!$annotation->managerType) {
            $annotation->managerType = $this->managerType;
        }

        if (!$annotation->controller) {
            $annotation->controller = $this->findController($name . $this->controllerSuffix);
        }
    }

    private function findEntity(string $name): array
    {
        foreach ($this->entityNamespaces as $namespaceOptions) {
            if (class_exists($className = "{$namespaceOptions['namespace']}\\$name")) {
                return [$className, $namespaceOptions['manager_type']];
            }
        }

        throw new EntityNotFound($name, $this->entityNamespaces);
    }

    private function findController(string $name): ?string
    {
        foreach ($this->controllerNamespaces as $namespace) {
            if (class_exists($className = "$namespace\\$name")) {
                return $className;
            }
        }

        return null;
    }
}
