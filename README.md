# sporta
Este es el repositorio para el sistema de gestión de turnos deportivos Sporta

Hay un cron (cada 1 minuto) dentro del servidor que agarra todo lo que está dentro del directorio **servidor** de este repo (si hay un cambio) y lo copia dentro de /var/www/html/

Dejo el cron en este repo por si se pierde este auto-deploy


## Pasos antes de cargarlo en pre
1. Modificar en config/init.php la BASE_URL como está explicado para que quede como subcarpeta
2. Editar los datos de conexion.php por los correctos de pre (o el .env si ya está aplicado)
3. Renombrar el directorio servidor a sporta y zipear sólo ese directorio (OJO, por ejemplo no deberían estar ni este README, ni un .gitignore, etc.)
