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


### Migration de l'ancienne version

supprimer le paramétrage du service, ResponseListener is removed
```yaml
# app/services.yml
    Orca\UserLogBundle\EventListener\ResponseListener:
        class: Orca\UserLogBundle\EventListener\ResponseListener
        arguments:  ['@service_container']
        tags:
            - { name: kernel.event_listener, event: kernel.response, channel: security }
```

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
### Parameteres - app/config.yml
Il faut totalement définir l'entité Utilisateur, et de rajouter les getters suivants :
```php
    public function getUserId()
    {
        return $this->id;
    }
    public function getFirstName()
    {
        return $this->userNom;
    }
    public function getLastName()
    {
        return $this->userPrenom;
    }

    function __toString(){
        return $this->getFirstName().' '.$this->getLastName();
    }
```

```yaml
orca_user_log:
    user_class: BackendAppBundle:TblUser #required
```

Pour modifier et/ou écraser les services du bundle, il faut spécifier les params suivants
```yaml
orca_user_log:
    //...
    userlog_entity: Acme\AppBundle\Entity\TblUserLog #optional
    userlog_repository: AcmeAppBundle:TblUserLog #optional
    table_name: tbl_user_log #optional
```

Pour exclure des liens des alerts vous pouvez ajouter le paramétre suivant 
```yaml
orca_user_log:
    //...
    exclude_uri: [''] #optional
```

#Exemple 
```yaml
orca_user_log:
    //...
    exclude_uri: ['test/something']
```

### Créer une nouvelle entité dans votre bundle qui herite de l'entité TblUserLog

Le bundle gére les modifs faite sur l'entité [le même principe de doctrine migration].

```php
//votreBundle/Entity

use Doctrine\ORM\Mapping as ORM;
use Orca\UserLogBundle\Entity\TblUserLog;


/**
 * TblUserLog
 *
 * @ORM\Entity(repositoryClass="Acme\AppBundle\Repository\TblUserLogRepository")
 *
 */
class TblUserLog extends TblUserLog
{

    //...
   public function getUserId()
       {
           return $this->id;
       }
       public function getFirstName()
       {
           return $this->userNom;
       }
       public function getLastName()
       {
           return $this->userPrenom;
       }
   
       function __toString(){
           return $this->getFirstName().' '.$this->getLastName();
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

    public function onKernelResponse(FilterResponseEvent $event)
    {
        parent::onKernelResponse($event);
        //...autre traitement
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        parent::onKernelException($event);
        //...autre traitement
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


Pour tracker les login et les logout il faut ajouter les lignes suivantes au niveau du pare-feu de securité - la section qui gère la connexion 

##### connexion avec Symfony authentification :

```yaml
#app/config/security.yml
firewalls:
        secured_area:
            form_login:
                success_handler: orca_user_log.component.authentication.handler.login_success_handler
            logout:
                success_handler: orca_user_log.component.authentication.handler.logout_success_handler
```
##### connexion avec FOS user :
```yaml
#app/config/security.yml
firewalls:
        main:
            pattern: ^/
           //...
            form_login:
                provider: fos_userbundle
                //...
                success_handler: orca_user_log.component.authentication.handler.login_success_handler
            logout:
                path: fos_user_security_logout
                target: fos_user_security_login
                success_handler: orca_user_log.component.authentication.handler.logout_success_handler      # redirect, no_redirect, redirect_without_path
            logout:       true
            anonymous:    false
            provider: fos_userbundle
```

##### Si Vous utilisez multiple méthodologies de connextion [WS, Token, FOS...].
Vous pouvez toujours appeler le dispatcher pour catcher l'évent login.
```php
#Backend\AppBundle\Controller\SecurityController.php

$token = new UsernamePasswordToken($user, '', 'secured_area', $result['userRoles']);
$this->get('security.token_storage')->setToken($token);

/** this statement for userLogBundle */
    $orcaUserLogSuccessAuthEvent = new AuthenticationSuccessEvent($user ,$request); // params: Instance of userInteface and Request object.
    $this->get('event_dispatcher')->dispatch(Events::AUTHENTICATION_SUCCESS, $orcaUserLogSuccessAuthEvent);
/** end Statement */
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

le Bundle gère la creation et la modification des la table `tbl_user_log` du la première installation du bundle, sans utilisé ` doctrine:schema:update`.

### Ajouter à votre entité la méthode getUserId() si elle n'existe Pas

### Mettre le bundle hors pare-feu
```yaml
#app/config/security.yml
firewalls:
       userLog:
            pattern: ^/userLogChart
            security: false 
```
