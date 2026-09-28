/*
 * conversacion.js
 * Maneja el diálogo con cada usuario. No sabe nada de Baileys:
 * recibe un "responder" y un "notificar" y se limita a la lógica.
 */

const q = require('./consultas');

const sesiones = new Map();
const MINUTOS_INACTIVIDAD = 30;

// Limpieza de sesiones abandonadas
setInterval(() => {
    const limite = Date.now() - MINUTOS_INACTIVIDAD * 60 * 1000;
    for (const [jid, sesion] of sesiones) {
        if (sesion.ultimoUso < limite) sesiones.delete(jid);
    }
}, 5 * 60 * 1000);

/* ---------------------------------------------------------------- */
/* Helpers                                                           */
/* ---------------------------------------------------------------- */

const ICONO_ESTADO = {
    'Pendiente': '🟡',
    'Delegado': '🔵',
    'En Revision': '🟠',
    'Devuelto': '🔴',
    'Finalizado': '🟢'
};

function esDireccion(usuario) {
    return usuario.cargo === 'Director' || usuario.cargo === 'Subdirector';
}

function telefonoDesdeJid(jid) {
    // 5492257602642@s.whatsapp.net -> 2257602642
    return jid.split('@')[0].replace(/\D/g, '').replace(/^549?/, '');
}

function fecha(valor) {
    if (!valor) return '';
    const d = new Date(valor);
    return d.toLocaleDateString('es-AR');
}

function lineaProyecto(p, index = null) {
    const prefijo = index === null ? '' : `${index + 1}. `;
    let texto = `${prefijo}#${p.id_proyecto} ${p.titulo}\n   ${ICONO_ESTADO[p.estado] || ''} ${p.estado}`;
    if (p.profesor_nombre) texto += ` · 👨‍🏫 ${p.profesor_apellido} ${p.profesor_nombre}`;
    else if (p.jefe_nombre) texto += ` · 👤 ${p.jefe_apellido} ${p.jefe_nombre}`;
    return texto;
}

function listaNumerada(proyectos) {
    return proyectos.map((p, i) => lineaProyecto(p, i)).join('\n');
}

function elegirDeLista(texto, lista) {
    const numero = parseInt(texto, 10);
    if (Number.isNaN(numero) || numero < 1 || numero > lista.length) return null;
    return lista[numero - 1];
}

function menu(usuario) {
    if (esDireccion(usuario)) {
        return (
            '*¿Qué querés hacer?*\n\n' +
            '1. Resumen de proyectos\n' +
            '2. Ver proyectos devueltos\n' +
            '3. Ver proyectos en revisión\n' +
            '4. Finalizar un proyecto\n\n' +
            '_Escribí "menu" en cualquier momento para volver acá, o "salir" para cerrar la sesión._'
        );
    }
    return (
        '*¿Qué querés hacer?*\n\n' +
        '1. Ver mis proyectos\n' +
        '2. Asignar un profesor\n' +
        '3. Devolver un proyecto a dirección\n\n' +
        '_Escribí "menu" en cualquier momento para volver acá, o "salir" para cerrar la sesión._'
    );
}

/* ---------------------------------------------------------------- */
/* Entrada principal                                                 */
/* ---------------------------------------------------------------- */

/**
 * @param {string} jid       remitente (5491122334455@s.whatsapp.net)
 * @param {string} texto     mensaje recibido, ya trimmeado
 * @param {(mensaje:string)=>Promise} responder
 * @param {(telefono:string, mensaje:string)=>Promise} notificar
 */
async function manejarMensaje(jid, texto, responder, notificar) {
    let sesion = sesiones.get(jid);
    if (!sesion) {
        sesion = { paso: 'inicio' };
        sesiones.set(jid, sesion);
    }
    sesion.ultimoUso = Date.now();

    const comando = texto.toLowerCase();

    if (comando === 'salir' || comando === 'cerrar') {
        sesiones.delete(jid);
        return responder('👋 Sesión cerrada. Escribí cualquier mensaje para volver a entrar.');
    }

    if (sesion.usuario && ['menu', 'menú', 'volver', 'inicio'].includes(comando)) {
        sesion.paso = 'menu';
        limpiarTemporales(sesion);
        return responder(menu(sesion.usuario));
    }

    try {
        await enrutar(jid, sesion, texto, responder, notificar);
    } catch (error) {
        console.log('Error en la conversación:', error);
        sesion.paso = sesion.usuario ? 'menu' : 'inicio';
        await responder('❌ Ocurrió un error inesperado. Volvé a intentarlo desde el menú.');
    }
}

function limpiarTemporales(sesion) {
    delete sesion.lista;
    delete sesion.proyecto;
    delete sesion.profesor;
    delete sesion.comentario;
    delete sesion.motivo;
}

/* ---------------------------------------------------------------- */
/* Enrutador de pasos                                                */
/* ---------------------------------------------------------------- */

async function enrutar(jid, sesion, texto, responder, notificar) {

    /* ---------- Identificación ---------- */

    if (sesion.paso === 'inicio') {
        const usuario = await q.buscarPorTelefono(telefonoDesdeJid(jid));

        if (usuario) {
            sesion.usuario = usuario;
            sesion.paso = 'menu';
            return responder(
                `👋 Hola ${usuario.nombre}, bienvenido a *ENET SERVICES*.\n` +
                `Ingresaste como ${usuario.cargo}` +
                (usuario.departamento ? ` de ${usuario.departamento}` : '') + '.\n\n' +
                menu(usuario)
            );
        }

        sesion.paso = 'login_dni';
        return responder(
            '👋 Bienvenido a *ENET SERVICES*.\n\n' +
            'Este número todavía no está vinculado.\n' +
            'Ingresá tu DNI (solo números, sin puntos):'
        );
    }

    if (sesion.paso === 'login_dni') {
        if (!/^\d{7,8}$/.test(texto)) {
            return responder('⚠️ DNI inválido. Ingresalo de nuevo (solo números).');
        }

        const usuario = await q.buscarPorDni(texto);

        if (!usuario) {
            return responder('❌ Ese DNI no figura en el sistema. Consultá con dirección.');
        }

        if (usuario.cargo !== 'Jefe' && !esDireccion(usuario)) {
            sesiones.delete(jid);
            return responder(
                '🚫 El bot está disponible solo para dirección y jefes de departamento.'
            );
        }

        sesion.pendiente = usuario;
        sesion.intentos = 0;
        sesion.paso = 'login_pass';
        return responder(
            `Hola ${usuario.nombre}. Ingresá tu contraseña del sistema para vincular este número:`
        );
    }

    if (sesion.paso === 'login_pass') {
        if (texto !== sesion.pendiente.pass) {
            sesion.intentos += 1;

            if (sesion.intentos >= 3) {
                sesiones.delete(jid);
                return responder('🚫 Demasiados intentos fallidos. Escribí de nuevo para reintentar.');
            }

            return responder(`⚠️ Contraseña incorrecta. Te quedan ${3 - sesion.intentos} intentos.`);
        }

        await q.vincularTelefono(sesion.pendiente.dni, telefonoDesdeJid(jid));

        sesion.usuario = sesion.pendiente;
        delete sesion.pendiente;
        delete sesion.intentos;
        sesion.paso = 'menu';

        return responder(
            `✅ Número vinculado a ${sesion.usuario.apellido}, ${sesion.usuario.nombre} (${sesion.usuario.cargo}).\n\n` +
            menu(sesion.usuario)
        );
    }

    const usuario = sesion.usuario;
    if (!usuario) {
        sesion.paso = 'inicio';
        return enrutar(jid, sesion, texto, responder, notificar);
    }

    /* ---------- Menú ---------- */

    if (sesion.paso === 'menu') {
        return esDireccion(usuario)
            ? menuDireccion(sesion, texto, responder)
            : menuJefe(sesion, texto, responder);
    }

    /* ---------- Flujos de jefe de departamento ---------- */

    if (sesion.paso === 'asignar_proyecto') {
        const proyecto = elegirDeLista(texto, sesion.lista);
        if (!proyecto) return responder('⚠️ Elegí uno de los números de la lista.');

        const profesores = await q.profesoresDelDepartamento(usuario.departamento);

        if (profesores.length === 0) {
            sesion.paso = 'menu';
            return responder(
                `❌ No hay profesores cargados en el departamento de ${usuario.departamento}.\n\n` +
                menu(usuario)
            );
        }

        sesion.proyecto = proyecto;
        sesion.lista = profesores;
        sesion.paso = 'asignar_profesor';

        return responder(
            `📌 *${proyecto.titulo}*\n\n` +
            '¿A qué profesor se lo asignás?\n\n' +
            profesores.map((p, i) => `${i + 1}. ${p.apellido}, ${p.nombre}`).join('\n')
        );
    }

    if (sesion.paso === 'asignar_profesor') {
        const profesor = elegirDeLista(texto, sesion.lista);
        if (!profesor) return responder('⚠️ Elegí uno de los números de la lista.');

        sesion.profesor = profesor;
        sesion.paso = 'asignar_comentario';

        return responder(
            'Escribí un comentario para el historial (o mandá *-* para dejarlo vacío):'
        );
    }

    if (sesion.paso === 'asignar_comentario') {
        sesion.comentario = texto === '-' ? '' : texto;
        sesion.paso = 'asignar_confirmar';

        return responder(
            '✅ *CONFIRMAR ASIGNACIÓN*\n\n' +
            `📌 Proyecto: #${sesion.proyecto.id_proyecto} ${sesion.proyecto.titulo}\n` +
            `👨‍🏫 Profesor: ${sesion.profesor.apellido}, ${sesion.profesor.nombre}\n` +
            (sesion.comentario ? `💬 ${sesion.comentario}\n` : '') +
            '\nEl proyecto pasa a estado *En Revisión*.\n\n' +
            '1. Confirmar\n2. Cancelar'
        );
    }

    if (sesion.paso === 'asignar_confirmar') {
        if (texto === '2') {
            sesion.paso = 'menu';
            limpiarTemporales(sesion);
            return responder('Operación cancelada.\n\n' + menu(usuario));
        }
        if (texto !== '1') return responder('Respondé 1 o 2.');

        const nombreProfesor = `${sesion.profesor.apellido} ${sesion.profesor.nombre}`;
        const ok = await q.asignarProfesor(
            sesion.proyecto.id_proyecto,
            usuario.dni,
            sesion.profesor.dni,
            nombreProfesor,
            sesion.comentario
        );

        if (!ok) {
            sesion.paso = 'menu';
            limpiarTemporales(sesion);
            return responder('❌ No se pudo asignar el proyecto.\n\n' + menu(usuario));
        }

        await responder(
            `✅ Listo. "${sesion.proyecto.titulo}" quedó asignado a ${nombreProfesor} y en estado *En Revisión*.`
        );

        // Aviso a dirección: el proyecto vuelve con un profesor asignado
        for (const telefono of await q.telefonosDeDireccion()) {
            await notificar(
                telefono,
                `📤 *PROYECTO ENVIADO*\n\n` +
                `${sesion.proyecto.titulo}\n` +
                `${sesion.proyecto.descripcion || 'Sin descripción.'}\n` +
                `Profesor asignado: ${nombreProfesor}`
            );
        }

        sesion.paso = 'menu';
        limpiarTemporales(sesion);
        return responder(menu(usuario));
    }

    if (sesion.paso === 'devolver_proyecto') {
        const proyecto = elegirDeLista(texto, sesion.lista);
        if (!proyecto) return responder('⚠️ Elegí uno de los números de la lista.');

        sesion.proyecto = proyecto;
        sesion.paso = 'devolver_motivo';

        return responder(
            `📌 *${proyecto.titulo}*\n\n` +
            'Escribí el motivo de la devolución y el estado actual del trabajo:'
        );
    }

    if (sesion.paso === 'devolver_motivo') {
        if (texto.length < 10) {
            return responder('⚠️ El motivo es muy corto. Contá con un poco más de detalle.');
        }

        sesion.motivo = texto;
        sesion.paso = 'devolver_confirmar';

        return responder(
            '✅ *CONFIRMAR DEVOLUCIÓN*\n\n' +
            `📌 #${sesion.proyecto.id_proyecto} ${sesion.proyecto.titulo}\n` +
            `💬 ${sesion.motivo}\n\n` +
            'El proyecto pasa a estado *Devuelto* y dirección recibe el aviso.\n\n' +
            '1. Confirmar\n2. Cancelar'
        );
    }

    if (sesion.paso === 'devolver_confirmar') {
        if (texto === '2') {
            sesion.paso = 'menu';
            limpiarTemporales(sesion);
            return responder('Operación cancelada.\n\n' + menu(usuario));
        }
        if (texto !== '1') return responder('Respondé 1 o 2.');

        const ok = await q.devolverProyecto(
            sesion.proyecto.id_proyecto,
            usuario.dni,
            sesion.motivo
        );

        if (!ok) {
            sesion.paso = 'menu';
            limpiarTemporales(sesion);
            return responder('❌ No se pudo devolver el proyecto.\n\n' + menu(usuario));
        }

        await responder('✅ Proyecto devuelto a dirección.');

        for (const telefono of await q.telefonosDeDireccion()) {
            await notificar(
                telefono,
                `🔴 *Proyecto devuelto*\n\n` +
                `#${sesion.proyecto.id_proyecto} ${sesion.proyecto.titulo}\n` +
                `Departamento: ${usuario.departamento}\n` +
                `Devuelto por ${usuario.apellido}, ${usuario.nombre}\n\n` +
                `💬 ${sesion.motivo}`
            );
        }

        sesion.paso = 'menu';
        limpiarTemporales(sesion);
        return responder(menu(usuario));
    }

    /* ---------- Flujos de dirección ---------- */

    if (sesion.paso === 'ver_devoluciones') {
        const proyecto = elegirDeLista(texto, sesion.lista);
        if (!proyecto) return responder('⚠️ Elegí uno de los números de la lista.');

        const devoluciones = await q.devolucionesDelProyecto(proyecto.id_proyecto);

        let mensaje = `💬 *Devoluciones de #${proyecto.id_proyecto} ${proyecto.titulo}*\n`;
        if (devoluciones.length === 0) {
            mensaje += '\nNo hay devoluciones registradas.';
        } else {
            for (const d of devoluciones) {
                mensaje += `\n🗓 ${fecha(d.fecha)} — ${d.usuario_apellido} ${d.usuario_nombre}\n${d.comentario}\n`;
            }
        }

        sesion.paso = 'menu';
        limpiarTemporales(sesion);
        await responder(mensaje);
        return responder(menu(usuario));
    }

    if (sesion.paso === 'finalizar_proyecto') {
        const proyecto = elegirDeLista(texto, sesion.lista);
        if (!proyecto) return responder('⚠️ Elegí uno de los números de la lista.');

        sesion.proyecto = proyecto;
        sesion.paso = 'finalizar_confirmar';

        const historial = await q.historialDelProyecto(proyecto.id_proyecto, 3);
        let detalle =
            `📌 *#${proyecto.id_proyecto} ${proyecto.titulo}*\n` +
            `${ICONO_ESTADO[proyecto.estado]} ${proyecto.estado}\n`;
        if (proyecto.jefe_nombre) detalle += `👤 Jefe: ${proyecto.jefe_apellido} ${proyecto.jefe_nombre}\n`;
        if (proyecto.profesor_nombre) detalle += `👨‍🏫 Profesor: ${proyecto.profesor_apellido} ${proyecto.profesor_nombre}\n`;

        if (historial.length > 0) {
            detalle += '\n*Últimos movimientos:*\n';
            for (const h of historial) {
                detalle += `• ${h.accion} (${fecha(h.fecha)}): ${h.comentario}\n`;
            }
        }

        return responder(detalle + '\n¿Finalizar este proyecto?\n\n1. Confirmar\n2. Cancelar');
    }

    if (sesion.paso === 'finalizar_confirmar') {
        if (texto === '2') {
            sesion.paso = 'menu';
            limpiarTemporales(sesion);
            return responder('Operación cancelada.\n\n' + menu(usuario));
        }
        if (texto !== '1') return responder('Respondé 1 o 2.');

        const ok = await q.finalizarProyecto(sesion.proyecto.id_proyecto, usuario.dni);

        if (!ok) {
            sesion.paso = 'menu';
            limpiarTemporales(sesion);
            return responder('❌ No se pudo finalizar el proyecto.\n\n' + menu(usuario));
        }

        await responder(`🟢 Proyecto #${sesion.proyecto.id_proyecto} finalizado.`);

        if (sesion.proyecto.jefe_telefono) {
            await notificar(
                sesion.proyecto.jefe_telefono,
                `✅ PROYECTO ${sesion.proyecto.titulo} finalizado, gracias por su trabajo`
            );
        }

        sesion.paso = 'menu';
        limpiarTemporales(sesion);
        return responder(menu(usuario));
    }

    // Paso desconocido
    sesion.paso = 'menu';
    return responder(menu(usuario));
}

/* ---------------------------------------------------------------- */
/* Menús                                                             */
/* ---------------------------------------------------------------- */

async function menuJefe(sesion, texto, responder) {
    const usuario = sesion.usuario;

    if (texto === '1') {
        const proyectos = await q.proyectosDelJefe(usuario.dni);
        if (proyectos.length === 0) {
            return responder('📭 No tenés proyectos abiertos.\n\n' + menu(usuario));
        }

        let mensaje = '📋 *Tus proyectos*\n\n';
        for (const p of proyectos) {
            mensaje += `${lineaProyecto(p)}\n`;
            if (p.descripcion) mensaje += `   📄 ${p.descripcion}\n`;
            mensaje += `   🗓 ${fecha(p.fecha_creacion)}\n\n`;
        }

        await responder(mensaje);
        return responder(menu(usuario));
    }

    if (texto === '2') {
        const proyectos = await q.proyectosSinProfesor(usuario.dni);
        if (proyectos.length === 0) {
            return responder('✅ No tenés proyectos sin profesor asignado.\n\n' + menu(usuario));
        }

        sesion.lista = proyectos;
        sesion.paso = 'asignar_proyecto';
        return responder(
            '¿A qué proyecto le asignás profesor?\n\n' + listaNumerada(proyectos)
        );
    }

    if (texto === '3') {
        const proyectos = await q.proyectosDevolvibles(usuario.dni);
        if (proyectos.length === 0) {
            return responder(
                'No tenés proyectos con profesor asignado para devolver.\n\n' + menu(usuario)
            );
        }

        sesion.lista = proyectos;
        sesion.paso = 'devolver_proyecto';
        return responder('¿Qué proyecto devolvés a dirección?\n\n' + listaNumerada(proyectos));
    }

    return responder('Respondé con 1, 2 o 3.');
}

async function menuDireccion(sesion, texto, responder) {
    const usuario = sesion.usuario;

    if (texto === '1') {
        const r = await q.resumenDeEstados();
        const total = Object.values(r).reduce((a, b) => a + b, 0);

        return responder(
            '📊 *Resumen de proyectos*\n\n' +
            `📁 Total: ${total}\n` +
            `🟡 Pendientes: ${r['Pendiente'] || 0}\n` +
            `🔵 Delegados: ${r['Delegado'] || 0}\n` +
            `🟠 En revisión: ${r['En Revision'] || 0}\n` +
            `🔴 Devueltos: ${r['Devuelto'] || 0}\n` +
            `🟢 Finalizados: ${r['Finalizado'] || 0}\n\n` +
            menu(usuario)
        );
    }

    if (texto === '2') {
        const proyectos = await q.proyectosPorEstado(['Devuelto']);
        if (proyectos.length === 0) {
            return responder('✅ No hay proyectos devueltos.\n\n' + menu(usuario));
        }

        sesion.lista = proyectos;
        sesion.paso = 'ver_devoluciones';
        return responder(
            '🔴 *Proyectos devueltos*\n\n' + listaNumerada(proyectos) +
            '\n\nElegí un número para leer la devolución.'
        );
    }

    if (texto === '3') {
        const proyectos = await q.proyectosPorEstado(['En Revision', 'Pendiente']);
        if (proyectos.length === 0) {
            return responder('No hay proyectos en revisión.\n\n' + menu(usuario));
        }

        let mensaje = '🟠 *En revisión / pendientes*\n\n';
        for (const p of proyectos) {
            mensaje += `${lineaProyecto(p)}\n   🗓 ${fecha(p.fecha_creacion)}\n\n`;
        }

        await responder(mensaje);
        return responder(menu(usuario));
    }

    if (texto === '4') {
        const proyectos = await q.proyectosPorEstado([
            'Pendiente', 'Delegado', 'En Revision', 'Devuelto'
        ]);

        if (proyectos.length === 0) {
            return responder('No hay proyectos abiertos para finalizar.\n\n' + menu(usuario));
        }

        sesion.lista = proyectos;
        sesion.paso = 'finalizar_proyecto';
        return responder('¿Qué proyecto finalizás?\n\n' + listaNumerada(proyectos));
    }

    return responder('Respondé con 1, 2, 3 o 4.');
}

module.exports = { manejarMensaje, telefonoDesdeJid };
