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

### Créer une nouvelle entité dans votre bundle qui herite de TblUserModel

```php
//votreBundle/Entity

use Doctrine\ORM\Mapping as ORM;
use Orca\UserLogBundle\Model\TblUserModel;


/**
 * TblUserLog
 *
 * @ORM\Table(name="tbl_user_log")
 * @ORM\Entity(repositoryClass="Acme\AppBundle\Repository\TblUserLogRepository")
 *
 */
class TblUserLog extends TblUserModel
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}

```

### Créer un EventSubscriber qui herite de LogEventsSubscriber pour tracker les requêtes émises et les réponses recu

```php

// Acme\AppBundle\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Orca\UserLogBundle\Subscriber\LogEventsSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LogEventSubscriber extends LogEventsSubscriber implements EventSubscriberInterface
{

    private $em;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        parent::__construct($container);
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
            );
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $userLog = parent::onKernelResponse($event);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $userLog = parent::onKernelException($event);
    }
```

et ajouter le dans votre bundle service.yml

```yaml

Acme\Appbundle\Subscriber\LogEventSubscriber:
        class: Acme\AppBundle\Subscriber\LogEventSubscriber
        arguments:  ['@service_container', "@doctrine.orm.entity_manager"]
        tags:
            - { name: kernel.event_subscriber}
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

parameters:
    userlog_entity: 'Acme\AppBundle\Entity\TblUserLog' #namespace de votre entité
    userlog_repo: 'AcmeAppBundle:TblUserLog' #repository de votre entité de log
    userlog_tbl: 'tbl_user_log' #le nom de la table dans la base de données
    TblUserRepo: 'AcmeAppBundle:TblUser' #repository de votre entité utilisateur
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

si vous utilisez d'autre methodes de login vous devez suprimer les lignes form_login et ajouter ce ligne de code juste avant la redirection au page d'utilisateur

```php
	$this->get('Orca\UserLogBundle\Services\LoginSuccessService')->onLoginSuccess($request, $this->getParameter('userlog_entity'));
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
### Générer la table des Log : ex: Tbl_User_Log
``` console 
php bin/console doctrine:schema:update --dump-sql
```

Récupere le code sql de la table et l'éxecuter au niveau du SGBD ou utiliser la commande suivante :
 
``` console
php bin/console doctrine:schema:update --force
```
ou executer la requête suivante : 
``` sql
CREATE TABLE `tbl_user_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `pays` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code_pays` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `terminal` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `route_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uri` longtext COLLATE utf8_unicode_ci,
  `error_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `terminal_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `header` longtext COLLATE utf8_unicode_ci,
  `post_params` longtext COLLATE utf8_unicode_ci,
  `get_params` longtext COLLATE utf8_unicode_ci,
  `json_response` text COLLATE utf8_unicode_ci,
  `exception_msg` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
