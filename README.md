# devSecOps-projet
Projet DevSecOps 2026

## Lancer l'image Docker localement

Nécessite Docker installé. A la racine du projet, faire :

```bash
docker compose build
docker compose up -d
```

Puis pour finaliser l'installation et la vérifier :

```bash
docker ps # Pour récupérer le container_id
docker exec -it <container_id> bash
php -v
composer install
sqlite3 --version
exit
```

Se rendre sur http://localhost:8080/ pour voir l'app PHP.
