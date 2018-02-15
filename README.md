# UserLogBundle

Symfony 3x bundle pour le tracking des actions des utilisateurs.

Introduction
------------

Ce bundle requiert l'installation des bundles : 
- [MobileDetectBundle](https://github.com/suncat2000/MobileDetectBundle)
- [DoctrineExtensions](https://github.com/beberlei/DoctrineExtensions)
- [GeoIP2-php](https://github.com/maxmind/GeoIP2-php)

Ce bundle à pour finalité de tracker les actions des utilisateurs notamment : 
- Les Login et les logout.
- Les requêtes émisent. 
## Configuration Obligatoire*

	- Il faut unzipé le fichier "GeoLite2-City" dans vendor\Orcaformation\Orca\UserLogBundle, afin d'avoir le fichier sous le nom **GeoLite2-City.mmdb**

## Documentation

La documentation est au niveau du fichier `Resources/doc/index.md` :

[Lire la documentation](https://github.com/orcaformation/userLogBundle/blob/master/Resources/doc/index.md)

## Credits

- [OrcaFormation](https://github.com/orcaformation)
