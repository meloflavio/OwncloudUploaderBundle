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
        $this->baseUrl = $container->getParameter('melo_flavio_owncloud_uploader.owncloud_url');
        $this->uploadUrl = array_values($container->getParameter('vich_uploader.mappings'))[0]['upload_destination'];
        $this->shareUrl = $this->baseUrl. '/ocs/v1.php/apps/files_sharing/api/v1/shares';
        $this->user = $container->getParameter('melo_flavio_owncloud_uploader.owncloud_user');
        $this->userUrl = $this->baseUrl. '/ocs/v1.php/cloud/users/'. $this->user;
        $this->password = $container->getParameter('melo_flavio_owncloud_uploader.owncloud_password');
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

    function noramtizeName(string $name){
        return  str_replace(' ', '_', $name);
    }

    function resolveUri(PropertyMapping $mapping, string $name,?string $dir){
        $name = $this->noramtizeName($name);
        $path = !empty($dir) ? "/".$dir.'/'.$name : $mapping->getUriPrefix()."/$name";
        $data = $this->getShareUrl($path);

        if(!$data) {
            return "#";
        }
        return  $data['url'];
    }

    function resolvePath(PropertyMapping $mapping, ?string $dir, string $name, ?bool $relative = false){
        $name = $this->noramtizeName($name);
        $path = !empty($dir) ? $dir.'/'.$name :  $mapping->getUriPrefix()."/$name";

        if ($relative) {
            return $path;
        }
        return  $this->baseUrl.$path;
    }

    public function deleteFile(PropertyMapping $mapping, ?string $dir, string $name){
        $name = $this->noramtizeName($name);
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

        $name = $this->noramtizeName($name);
        if('' == $dir){
            $dir = $mapping->getUriPrefix();
        }
        $this->hasFolder($dir);
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
            if(is_array($json_decode->data->element)){
                $data['url'] = array_pop($json_decode->data->element)->url;
            }else{
                $data['url'] = $json_decode->data->element->url;
            }
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

    /**
     *
     */
    public function createFolder($url){

        $handler = $this->inicializar();
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "MKCOL");
        $result = curl_exec($handler);
        $httpCode = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        curl_close($handler);

        if($httpCode == 204 or $httpCode == 404){
            return true;
        }
        return false;

    }

    /**
     *
     */
    public function hasFolder($dir)
    {
        $handler = $this->inicializar();
        $url = $this->uploadUrl;
        $folders = explode('/',$dir);
        $has = true;
        for ($i=0; $i< count($folders)-1;$i++){
            if($folders[$i] != ''){
                $url = $url.$folders[$i].'/';
            }
            curl_setopt($handler, CURLOPT_URL, $url);
            curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "PROPFIND");
            $result = curl_exec($handler);
            if (strpos($result,$folders[$i+1])===false){
                $isCreate = $this->createFolder($url.'/'.$folders[$i+1]);
            }

        }
    }
    
    
    public function formatUserTwigResponse($response){
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
            if(is_array($json_decode->data)){
                $data['data'] = array_pop($json_decode->data);
            }else{
                $data['data'] = $json_decode->data;
            }
        }catch (\Exception $exception){
            return false;
        }

        return $data['data'];
    }


    public function getUserInfo()
    {
        $handler = $this->inicializar();

        curl_setopt($handler, CURLOPT_URL, $this->userUrl);


        curl_setopt($handler, CURLOPT_POSTFIELDS, null);
        curl_setopt($handler, CURLOPT_POST, FALSE);
        curl_setopt($handler, CURLOPT_HTTPGET, TRUE);
        $response = curl_exec($handler);

        curl_close($handler);
        return $this->formatUserTwigResponse($response);
    }

    public function setExpiredSharedFile($url)
    {
        $handler = $this->inicializar();
        curl_setopt($handler, CURLOPT_URL, $this->shareUrl);
        $post = [
            'path' => $url,
            'shareType' => 3,
            'permissions' => 1,
            'expireDate' => (new \DateTime())->modify('+1 day')->format('Y-m-d H:i:s')
        ];
        curl_setopt($handler, CURLOPT_POSTFIELDS, http_build_query($post));
        $response = curl_exec($handler);
        curl_close($handler);
        return $response;
    }

    public function deleteShareUrl($id)
    {

        $handler = $this->inicializar();
        curl_setopt($handler, CURLOPT_URL, $this->shareUrl.'/'.$id);

        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "DELETE");
        $response = curl_exec($handler);
        curl_close($handler);
        return $response;
    }
}
