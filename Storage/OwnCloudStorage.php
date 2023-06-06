<?php


namespace MeloFlavio\OwncloudUploaderBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    protected function doUpload(PropertyMapping $mapping, File $file, ?string $dir, string $name)
    {
        $response = $this->ownCloudTools->uploadFile($mapping,  $file,  $dir,  $name);
    }

    protected function doRemove(PropertyMapping $mapping, ?string $dir, string $name): ?bool
    {
        return $this->ownCloudTools->deleteFile( $mapping, $dir,  $name);
    }

    public function resolveUri($obj, ?string $fieldName = null, ?string $className = null): ?string
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


    public function downloadFile($obj, ?string $fieldName = null, ?string $className = null)
    {
        $url = $this->resolveUri($obj,  $fieldName ,  $className ).'/download';
        $class = new \ReflectionClass(get_class($obj));
        $methods = $class->getMethods();
        $method = current(array_filter($methods, function ($method) { if($method->getReturnType() && $method->getReturnType()->getName() === "Vich\UploaderBundle\Entity\File") return $method;}));
        [$mapping, $filename] = $this->getFilename($obj, $fieldName, $className);
        $size = $method->invoke($obj)?$method->invoke($obj)->getSize():0;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: public');
        header( "Content-length: " . $size );
        flush();
        readfile($url,false,stream_context_create([
            'ssl' => [
                'verify_peer'=> false,
                'verify_peer_name'=> false,
            ],
        ]));
        exit;
    }
}
