---
title: Solución de problemas
summary: Qué comprobar cuando algo no funciona como se espera.
order: 70
---

| Problema | Causa posible |
|---|---|
| Los menús no cambian | El plugin está desactivado, o el tema no está basado en Boost |
| Un elemento no aparece | Su visibilidad, roles o idiomas excluyen al usuario, o su padre está oculto. Un elemento nuevo necesita además un título y una URL |
| Una página no se bloquea | **Bloquear el acceso a la URL** no está activado, el usuario es administrador del sitio, la URL es externa, u otro elemento que el usuario puede ver usa la misma URL |
| Un usuario es enviado al Área personal | Un elemento que no puede ver bloquea la página que abrió. Busque los elementos con la insignia **URL bloqueada** |
| Un usuario ve «Página no disponible» | Todas las páginas de destino (Área personal, página principal del sitio, Mis cursos y la página de inicio predeterminada) están bloqueadas para él |
| Faltan los iconos del menú de usuario | El menú de usuario solo muestra iconos FontAwesome; un icono de Moodle sin icono FontAwesome, como una imagen, no se muestra |
| Las pestañas de FontAwesome del selector de iconos están vacías | El plugin lee la lista de FontAwesome donde la guarda Moodle 5.3; escriba la clase en el campo |
| Se rechaza una importación | La página lista cada elemento no válido y el motivo. No se guarda nada hasta que todo el archivo es válido |
