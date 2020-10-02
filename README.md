OwncloudUploaderBundle
================

Bundle de integração com symfony flex e mercure bundle para notificações.


Installation
-------------
####1. Composer Require 
 
        composer require cds/cds/onwcloud-bundle  

####2. Adicionar variaveis de ambiente 
  
  ```env
  OWNCLOUD_URL=https://owncloud.com
  OWNCLOUD_USER=user
  OWNCLOUD_PASSWORD=pass
  ```
  
####3. Adicionar em vich_uploader.yaml 
Adicionar o storage do owncload demais opções podem ser alteradas

```yaml
vich_uploader:
    db_driver: orm
    storage: '@cds.onwcloud_storage'                                  #storage do onwcloud uploader
    mappings:
        resposta_anexo:
            uri_prefix: /ANEXO/RESPOSTAS                              #pasta padrao para os arquivos do owncloud
            upload_destination: '%OWNCLOUD_URL%/remote.php/webdav/'
            namer:
                service: Vich\UploaderBundle\Naming\PropertyNamer
                options: { property: 'fileName' }
 ```
####4. Adicionar em cds_owncloud.yaml ou adicionar em vich_uploader.yaml 

```yaml
cds_owncload:
    OWNCLOUD_URL: '%env(resolve:OWNCLOUD_URL)%'
    OWNCLOUD_USER: '%env(resolve:OWNCLOUD_USER)%'
    OWNCLOUD_PASSWORD: '%env(resolve:OWNCLOUD_PASSWORD)%'
```
