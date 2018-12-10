<?php
/**
 * Created by PhpStorm.
 * User: archer.developer
 * Date: 01.08.14
 * Time: 10:06
 */

namespace ITM\ImagePreviewBundle\Resolver;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Filesystem\Filesystem;

class PathResolver
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Путь к каталогу хранения файлов
     *
     * @param $entity
     * @param $relative
     * @return string
     */
    public function getUploadPath($entity, $relative = false)
    {
        $entityClassName = ClassUtils::getRealClass(get_class($entity));

        $config = $this->container->getParameter('ITMImagePreviewBundleConfiguration');
        $path = str_replace("\\", '/', $config['upload_path'] . '/' . $entityClassName);
        if( !$relative )
        {
            $path = $this->container->getParameter('kernel.root_dir') . '/../' . $config['public_dir'] . '/' . $path;
        }

        return $path;
    }

    /**
     * Путь к файлу
     *
     * @param $entity - сущность
     * @param $field - имя поля сущности или имя файла (значение поля)
     * @return string
     */
    public function getPath($entity, $field, $relative = false)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        // Если не удается получить доступ к такому свйоству, то считаем что это имя файла
        if($accessor->isReadable( $entity, $field ))
        {
            $field = $accessor->getValue($entity, $field);
            if(empty($field)) return '';
        }

        return $this->getUploadPath($entity, $relative) . '/' . $field;
    }

    /**
     * URL для доступа к файлу
     *
     * @param $entity - сущность
     * @param $field - имя поля сущности или имя файла (значение поля)
     * @return string
     */
    public function getUrl($entity, $field)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $config = $this->container->getParameter('ITMImagePreviewBundleConfiguration');
        // Если не удается получить доступ к такому свйоству, то считаем что это имя файла
        if($accessor->isReadable( $entity, $field ))
        {
            $field = $accessor->getValue($entity, $field);
            if(empty($field)) return '';
        }

        $entityClassName = ClassUtils::getRealClass(get_class($entity));

        return $config['upload_url'] . '/' . str_replace("\\", "/", $entityClassName) . '/' . $field;
    }

    /**
     * Проверка на существование файла
     *
     * @param $entity - сущность
     * @param $field - имя поля сущности или имя файла (значение поля)
     * @return bool
     */
    public function isExists( $entity, $field )
    {
        $fs = new Filesystem();
        $filePath = $this->getPath($entity, $field);
        return $fs->exists($filePath);
    }
} 