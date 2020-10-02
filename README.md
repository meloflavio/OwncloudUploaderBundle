OwncloudUploaderBundle
================

Bundle de integração com symfony flex e mercure bundle para notificações.


Installation
-------------
####1. Composer Require 
 
        composer require meloflavio/owncloud-uploader-bundle

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
    storage: '@cds.owncloud_storage'                                  #storage do onwcloud uploader
    mappings:
        resposta_anexo:
            uri_prefix: /ANEXO/RESPOSTAS                              #pasta padrao para os arquivos do owncloud
            upload_destination: '%env(resolve:OWNCLOUD_URL)%/remote.php/webdav/'
            namer:
                service: Vich\UploaderBundle\Naming\PropertyNamer
                options: { property: 'fileName' }
 ```
####4. Adicionar em cds_owncloud.yaml ou adicionar em vich_uploader.yaml 

```yaml
melo_flavio_owncloud_uploader:
    OWNCLOUD_URL: '%env(resolve:OWNCLOUD_URL)%'
    OWNCLOUD_USER: '%env(resolve:OWNCLOUD_USER)%'
    OWNCLOUD_PASSWORD: '%env(resolve:OWNCLOUD_PASSWORD)%'
```
####5. configure a Entity 
Siga os passos para uso VichUploaderBundle exemplo em:
 https://github.com/dustin10/VichUploaderBundle/blob/master/docs/usage.md 
 