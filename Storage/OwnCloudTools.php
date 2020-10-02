<?php


namespace MeloFlavio\OwncloudUploaderBundle\Storage;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class OwnCloudTools
{
    /**
     * @var ContainerInterface $container
    */
    private $container;
    /**
     * @var string $user
     */
    private $user;
    /**
     * @var string $password
     */
    private $password;
    /**
     * @var string $baseUrl
     */
    protected $baseUrl;
    /**
     * @var string $uploadUrl
     */
    protected $uploadUrl;
    /**
     * @var string $shareUrl
     */
    protected $shareUrl;

    /**
     * OwnCloudTools constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->baseUrl = $container->getParameter('cds_uploader.owncloud_url');
        $this->uploadUrl = $this->baseUrl.'/remote.php/webdav/';
        $this->shareUrl = $this->baseUrl. '/ocs/v1.php/apps/files_sharing/api/v1/shares';
        $this->user = $container->getParameter('cds_uploader.owncloud_user');
        $this->password = $container->getParameter('cds_uploader.owncloud_password');
    }

    public function inicializar(){
        $h = curl_init();
        curl_setopt($h,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_HEADER, true);
        curl_setopt($h, CURLOPT_USERPWD, $this->user . ":" . $this->password);
        curl_setopt($h, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);

        return $h;
    }

    function resolveUri(PropertyMapping $mapping, string $name,?string $dir){
        $path = !empty($dir) ? "/".$dir.'/'.$name : $mapping->getUriPrefix()."/$name";
        $data = $this->getShareUrl($path);
        if(!$data) {
            return "#";
        }
        return  $data['url'];
    }

    function resolvePath(PropertyMapping $mapping, ?string $dir, string $name, ?bool $relative = false){
        $path = !empty($dir) ? $dir.'/'.$name :  $mapping->getUriPrefix()."/$name";

        if ($relative) {
            return $path;
        }
        return  $this->baseUrl.$path;
    }

    public function deleteFile(PropertyMapping $mapping, ?string $dir, string $name){
        if('' == $dir){
            $dir = $mapping->getUriPrefix();
        }
        $handler = $this->inicializar();
        $fileString = "$dir/$name";
        $url = $this->uploadUrl.$fileString;
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "DELETE");
        $result = curl_exec($handler);
        $httpCode = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        curl_close($handler);

        if($httpCode == 204 or $httpCode == 404){
            return true;
        }
        return false;

    }
    public function uploadFile(PropertyMapping $mapping, UploadedFile $file, ?string $dir, string $name){

        if('' == $dir){
            $dir = $mapping->getUriPrefix();
        }
        $handler = $this->inicializar();
        $fileString = "$dir/$name";
        $fh_res = fopen($file->getPathname(), 'r');
        $url = $this->uploadUrl.$fileString;
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_PUT, true);
        curl_setopt($handler, CURLOPT_INFILE, $fh_res);
        curl_setopt($handler, CURLOPT_INFILESIZE, $file->getSize());
        $response = curl_exec($handler);
        fclose($fh_res);
        $share = $this->setShareFile('/'.$fileString);
        return $this->formatResponse($share);
    }


    public function formatResponse($response){

        $data = explode('<?xml version="1.0"?>', $response);
        if(count($data)==0){
            return $response;
        }
        $xml = simplexml_load_string($data[1]);
        $json = json_encode($xml);
        $json_decode = json_decode($json);
        try{
            $data['status'] = $json_decode->meta->status;
            $data['code'] = $json_decode->meta->statuscode;
            $data['url'] = $json_decode->data->url;
        }catch (\Exception $exception){
            return false;
        }


        return $data;
    }

    public function formatTwigResponse($response){
        $data = explode('<?xml version="1.0"?>', $response);
        if(count($data)==0){
            return $response;
        }
        $xml = simplexml_load_string($data[1]);
        $json = json_encode($xml);
        $json_decode = json_decode($json);
        try{
            $data['status'] = $json_decode->meta->status;
            $data['code'] = $json_decode->meta->statuscode;
            $data['url'] = $json_decode->data->element->url;
        }catch (\Exception $exception){
            return false;
        }

        return $data;
    }

    public function setShareFile($url)
    {
        $handler = $this->inicializar();
        curl_setopt($handler, CURLOPT_URL, $this->shareUrl);
        $post = [
            'path' => $url,
            'shareType' => 3,
        ];
        curl_setopt($handler, CURLOPT_POSTFIELDS, http_build_query($post));
        $response = curl_exec($handler);
        curl_close($handler);
        return $response;
    }

    public function getShareUrl($url)
    {
        $handler = $this->inicializar();

        $data = [
            'path' => $url
        ];
        $getUrl ="?".http_build_query($data);
        curl_setopt($handler, CURLOPT_URL, $this->shareUrl.$getUrl);


        curl_setopt($handler, CURLOPT_POSTFIELDS, null);
        curl_setopt($handler, CURLOPT_POST, FALSE);
        curl_setopt($handler, CURLOPT_HTTPGET, TRUE);
        $response = curl_exec($handler);

        curl_close($handler);
        return $this->formatTwigResponse($response);
    }


    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

}