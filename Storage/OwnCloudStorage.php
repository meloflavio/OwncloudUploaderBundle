<?php


namespace UFTCds\OwncloudUploaderBundle\Storage;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\AbstractStorage;


class OwnCloudStorage  extends AbstractStorage
{
    /**
     * @var PropertyMappingFactory
     */
    protected $factory;

    /**
     * @var OwnCloudTools
     */
    protected $ownCloudTools;

    public function __construct(PropertyMappingFactory $factory, OwnCloudTools $ownCloudTools)
    {
        $this->factory = $factory;
        $this->ownCloudTools = $ownCloudTools;
    }

    protected function doUpload(PropertyMapping $mapping, UploadedFile $file, ?string $dir, string $name)
    {
        $response = $this->ownCloudTools->uploadFile($mapping,  $file,  $dir,  $name);
    }

    protected function doRemove(PropertyMapping $mapping, ?string $dir, string $name): ?bool
    {
        return $this->ownCloudTools->deleteFile( $mapping, $dir,  $name);
    }

    public function resolveUri($obj, string $fieldName, ?string $className = null): ?string
    {
        [$mapping, $filename] = $this->getFilename($obj, $fieldName, $className);

        if (empty($filename)) {
            return null;
        }
        return $this->ownCloudTools->resolveUri($mapping,$filename,'');
    }

    protected function doResolvePath(PropertyMapping $mapping, ?string $dir, string $name, ?bool $relative = false): string
    {
        return $this->ownCloudTools->resolvePath($mapping,  $dir, $name,  $relative);
    }
}