# Quién puede hacer qué

**Esto es una propuesta, falta que el equipo la apruebe.** Ya está cargada en el sistema para
poder probar, pero se cambia cuando se acuerde con el CTO (ver [más abajo](#diferencias-con-el-reparto-del-cto)).

La sacamos de la tabla de requerimientos del documento IEEE: ahí cada requerimiento (RF01 a RF27)
dice qué rol lo usa.

## Cómo leer la tabla

- `CRUD` = puede ver, crear, editar y eliminar
- `ver` = solo puede mirar
- `—` = no entra

| Rol | Tripulac. | Recomp. | Órd. captura | Navegac. | Eventos | Alianzas | Amenazas | Informes | Dashboard | Usuarios | Bitácora |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Almirante de la Marina | ver | ver | ver | ver | ver | ver | ver | ver+crear | **CRUD** | ver | — |
| Director de Cipher Pol | **CRUD** | — | **CRUD** | ver | ver | **CRUD** | ver | **CRUD** | ver | — | — |
| Analista de Recompensas | ver | **CRUD** | — | — | — | ver | **CRUD** | ver | ver | — | — |
| Especialista en Navegación | ver | — | — | **CRUD** | **CRUD** | — | — | — | ver | — | — |
| Administrador de Bases Navales | ver | — | ver | ver | ver | — | — | ver | ver | — | — |
| Experto en Seguridad | — | — | — | — | — | — | — | — | — | **CRUD** | ver |
| Desarrollador de Monitoreo | — | — | — | — | — | — | — | — | — | — | — |

En el sistema cada casilla se guarda como un permiso `MODULO.ACCION` (por ejemplo
`TRIPULACIONES.CREAR`). Son **41 permisos**: 10 módulos × 4 acciones, más `BITACORA.VER`.

## De dónde salió cada fila

| Rol | Requerimientos que el IEEE le da |
|---|---|
| Director de Cipher Pol | RF01–RF05 (tripulaciones), RF16–RF18 (alianzas), RF20–RF22 (informes), RF24–RF26 (órdenes de captura) |
| Analista de Recompensas | RF06–RF08 (recompensas), RF19 (amenazas) |
| Especialista en Navegación | RF11–RF12 (rutas), RF13–RF15 (eventos) |
| Almirante de la Marina | RF22 y RF27 como rol secundario, RF23 (dashboard) → casi todo de solo lectura |
| Administrador de Bases Navales | Aparece como rol secundario en varios casos de uso → todo de solo lectura |
| Experto en Seguridad | RF27 (roles y permisos) + la bitácora (RNF08) |
| Desarrollador de Monitoreo | Ninguno en el IEEE |

RF09 (recompensas automáticas), RF10 (mapa) y RF23 (dashboard) el IEEE los marca como
"proceso automático del sistema": no los ejecuta ningún rol, aunque alguien tenga que poder verlos.

**Qué agregamos nosotros:** el módulo `BITACORA` (solo `VER`), que no estaba en la lista original.
Lo tiene únicamente el Experto en Seguridad.

## Diferencias con el reparto del CTO

En el documento `analisis sobre IEEE 26_08_2026.docx` el CTO propuso otro reparto de
requerimientos por rol. Donde no coinciden:

| Módulo | Nuestra matriz | Propuesta del CTO |
|---|---|---|
| **Eventos** (RF13–RF15) | Especialista en Navegación: CRUD | RF13 y RF14 (registrar y clasificar) → **Cipher Pol**, porque "es inteligencia". RF15 (consultar) → **Administrador de Bases Navales** |
| **Navegación** (RF11–RF12) | Especialista: CRUD. Monitoreo: nada | RF11 → Especialista. **RF12 (seguimiento de rutas) → Desarrollador de Monitoreo** |
| **Informes** (RF20–RF22) | Cipher Pol: CRUD. Almirante: ver+crear. Administrador: ver | RF20 → Cipher Pol. **RF21 (consolidar) → Administrador de Bases Navales**. **RF22 (reportes) → Almirante** |
| **Amenazas** (RF19) | Analista de Recompensas: CRUD | Proceso **automático** del sistema, sin rol |

En todo lo demás coinciden: tripulaciones, alianzas y órdenes de captura para Cipher Pol;
recompensas para el Analista; roles y permisos para el Experto en Seguridad.

**Lo bueno de la propuesta del CTO:** le da trabajo al **Desarrollador de Monitoreo** (RF12), que
en la nuestra quedó sin nada. Eso resuelve la primera pregunta de abajo.

**Ojo:** el CTO reparte requerimientos, no acciones. Aunque se acepte su reparto, hay que decidir
casilla por casilla qué es `VER`, `CREAR`, `EDITAR` o `ELIMINAR`.

## Cómo se cambia

Hay dos maneras:

1. **Para todos (lo definitivo):** cambiar la lista `MATRIZ` en
   `database/seeders/PermisoSeeder.php` y subirlo al repositorio. Cada quien corre
   `php artisan db:seed --class=PermisoSeeder`.
   Ojo: ese seeder **agrega** permisos pero **no quita** los que ya estaban. Para quitar uno,
   hay que hacerlo desde la pantalla de la matriz.
2. **Solo en tu base (para probar):** entrar como `seguridad@marina.gov` → Matriz de permisos →
   elegir el rol → marcar o desmarcar casillas → Guardar. El cambio queda anotado en la bitácora.

Nadie puede quitarle a su propio rol el permiso `USUARIOS.EDITAR`: si el Experto en Seguridad
se lo quitara, nadie podría volver a administrar usuarios.

## Cosas que hay que preguntar

1. **¿El Desarrollador de Sistemas de Monitoreo entra al sistema?** En el IEEE no tiene ningún
   requerimiento; el CTO propone darle el RF12. Mientras se decide, quedó sin permisos.
2. **Eventos: ¿Especialista en Navegación o Cipher Pol?** El IEEE dice uno y el CTO otro.
3. **Al Almirante le dejamos ver todo.** El caso de estudio dice que es el patrocinador. ¿Está bien así?
4. **Nadie puede eliminar tripulaciones de verdad.** El RF04 habla de "baja lógica", que es
   marcarla como inactiva. Por eso en la práctica es `EDITAR`, aunque el permiso `ELIMINAR` exista.

## Sobre la organización del usuario

La organización (Marina, Cipher Pol, Gobierno Afiliado) **no decide nada** de esta tabla.
Quien decide el acceso es el rol.

La organización se usa para dos cosas: saber de dónde viene cada persona (se ve en la lista de
usuarios) y, más adelante, rellenar automáticamente de dónde viene un informe (lo pide el RF20).

Detalle curioso: 3 de los 7 roles ya traen la organización en el nombre (Almirante **de la
Marina**, Director **Cipher Pol**, Administrador **de Bases Navales**). Se podría validar que
coincidan cuando se aprueba un usuario. Por ahora no se valida.
