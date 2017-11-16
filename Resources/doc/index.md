# UserLogBundle

Symfony 3x bundle pour le tracking des actions des utilisateurs.


Installation
------------

### Composer

#### Pour Symfony >= 2.4

executer la commande:
`composer require "Orca/userLogBundle:dev"`

Ou ajouter à `composer.json` à `require` section:

```json
{
    "Orca/userLogBundle": "dev"
}
```
et executer la commande:
`php composer.phar update`

### Ajouter ce bundle au kernel

```php
//app/AppKernel.php
public function registerBundles()
{
    return array(
         // ...
         new Orca\UserLogBundle\OrcaUserLogBundle(),
        // ...
    );
}
```

### Configuration

Pour tracker les reqêtes émise il faut ajouter aux services du projet la configuration suivante :

```yaml
#app/conﬁg/services.yml
services:
    Orca\UserLogBundle\EventListener\ResponseListener:
            class: Orca\UserLogBundle\EventListener\ResponseListener
            arguments:  ['@service_container']
            tags:
                - { name: kernel.event_listener, event: kernel.response, channel: security }
```

Pour tracker les login et les logout il faut ajouter les lignes suivantes au niveau du pare-feu de securité

```yaml
#app/config/security.yml
firewalls:
        secured_area:
            form_login:
                success_handler: orca_user_log.component.authentication.handler.login_success_handler
            logout:
                success_handler: orca_user_log.component.authentication.handler.logout_success_handler      # redirect, no_redirect, redirect_without_path
```


### Générer la table des Log : Tbl_User_Log
``` console 
php bin/console doctrine:schema:update --dump-sql
```

Récupere le code sql de la table et l'éxecuter au niveau du SGBD ou utiliser la commande suivante :
 
``` console
php bin/console doctrine:schema:update --force
```

