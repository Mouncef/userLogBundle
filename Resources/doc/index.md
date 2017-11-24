# UserLogBundle

Bundle Symfony 3x pour le tracking des actions des utilisateurs.


Installation
------------

### Composer

Ajouter au `composer.json` les lignes suivantes:

```json
"require" : {
...
        "Orcaformation" : "dev-master"
    },
"repositories" : [{
        "type" : "vcs",
        "url" : "git@github.com:orcaformation/userLogBundle.git"
    }],    
```
et executer la commande:
`composer update "Orcaformation" `

### Ajouter ce bundle au kernel

```php
//app/AppKernel.php
public function registerBundles()
{
    return array(
         // ...
         new Orca\UserLogBundle\OrcaUserLogBundle(),
         new SunCat\MobileDetectBundle\MobileDetectBundle(),
        // ...
    );
}
```
### Ajouter au config.yml

Pour activer les extensions de doctrine ajouter au `config.yml` les lignes suivantes :

```yaml
#app/conﬁg/config.yml
doctrine:
    ...
    orm:
        ...
        dql:
            datetime_functions:
                date_format: DoctrineExtensions\Query\Mysql\DateFormat
            string_functions:
                group_concat: DoctrineExtensions\Query\Mysql\GroupConcat
```

### Ajouter au routing.yml

Pour afficher la page d'administration ajouter au `routing.yml` les lignes suivantes :
 
```yaml
#app/conﬁg/routing.yml
_demo:
    resource: "@OrcaUserLogBundle/Resources/config/routing.yml"
    type:     yaml
    prefix:   /userLogChart
```

### Installer les Assets
Pour installer les assets, exécuter la commande suivante : 
``` console 
php bin/console assets:install
```

### Configuration

Pour tracker les reqêtes émisent il faut ajouter aux services du projet la configuration suivante :

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
### préparation route principale : userLog_homepage_login

```DefaultController
    /**
     * @Route("/",name="homepage")
     * @Route("/",name="userLog_homepage_login")
     */
    public function indexAction(Request $request)
    {
```
### Générer la table des Log : Tbl_User_Log
``` console 
php bin/console doctrine:schema:update --dump-sql
```

Récupere le code sql de la table et l'éxecuter au niveau du SGBD ou utiliser la commande suivante :
 
``` console
php bin/console doctrine:schema:update --force
```
ou executer la requête suivante : 
``` sql
CREATE TABLE tbl_user_log (id INT AUTO_INCREMENT NOT NULL, DATE DATETIME NOT NULL, user_id INT NOT NULL, pays VARCHAR(255) DEFAULT NULL, ip VARCHAR(255) DEFAULT NULL, ville VARCHAR(255) DEFAULT NULL, terminal VARCHAR(255) DEFAULT NULL, ACTION VARCHAR(255) DEFAULT NULL, route_name VARCHAR(255) DEFAULT NULL, uri LONGTEXT DEFAULT NULL, error_code VARCHAR(255) DEFAULT NULL, terminal_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB;
```
### Ajouter à votre entité la méthode getUserId() si elle n'existe Pas
```User Entity
    class User {
    ...
        public function getUserId()
        {
           return $this->id;
        }
    }
```
### Mettre le bundle hors pare-feu
```yaml
#app/config/security.yml
firewalls:
       userLog:
            pattern: ^/userLogChart
            security: false 
```
