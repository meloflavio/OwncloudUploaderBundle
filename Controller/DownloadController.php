<?php

namespace MeloFlavio\OwncloudUploaderBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends AbstractController
{

    public function downloadAction(Request $request,  string $obj)
    {
        $internalDonload = $this->container->getParameter('melo_flavio_owncloud_uploader.internal_download');
        $this->denyAccessUnlessGranted($internalDonload['role_permission']);
        $storage = $this->get('cds.owncloud_storage');
        $obj = $this->getDoctrine()->getRepository($internalDonload['class_file'])->find($obj);
        $reflector = new \ReflectionClass(get_class($obj));

        $field = array_filter($reflector->getProperties(),function( $field)
        {
            return (strpos( $field->getDocComment(),'@Vich\UploadableField')!==false)?$field:null;
        } );

        return $storage->downloadFile($obj, current($field)->getName());
    }

}