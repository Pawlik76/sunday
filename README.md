# DataSync Sunday
Gestor web (PHP + MySQL/MariaDB): crear bases y tablas, ver, insertar, editar y eliminar registros.
Cada usuario escribe su servidor/usuario/contraseña en `conectar.php`; no hay credenciales en el código.

## Local (Linux / CachyOS)
    sudo pacman -S php mariadb
    sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
    sudo systemctl enable --now mariadb
    # habilita mysqli en /etc/php/php.ini (quita el ; de extension=mysqli)
    php -S localhost:8000     # abre http://localhost:8000/index.html
Crea tu usuario de MySQL con tu propia contraseña y úsalo en la pantalla de conexión.

## Nube
Necesita hosting con PHP 8+ y MySQL (hosting compartido, o Docker con el Dockerfile incluido).
Variables opcionales: `DB_HOST` (servidor sugerido) y `DB_ALLOWED_HOSTS` (lista separada por comas
de servidores permitidos; recomendado en producción). Usa siempre HTTPS.
