require('dotenv').config();

const { default: makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const qrcode = require('qrcode-terminal');
const express = require('express');

const q = require('./consultas');
const { manejarMensaje } = require('./conversacion');

const PUERTO = process.env.PUERTO_API || 3001;
const TOKEN = process.env.API_TOKEN || '';

let sock = null;

/* ---------------------------------------------------------------- */
/* Envío de mensajes                                                 */
/* ---------------------------------------------------------------- */

function jidDesdeTelefono(telefono) {
    const numero = String(telefono).replace(/\D/g, '').replace(/^549?/, '');
    return `549${numero}@s.whatsapp.net`;
}

async function notificar(telefono, mensaje) {
    if (!sock || !telefono) return false;
    try {
        await sock.sendMessage(jidDesdeTelefono(telefono), { text: mensaje });
        return true;
    } catch (error) {
        console.log('No se pudo notificar a', telefono, '-', error.message);
        return false;
    }
}

/* ---------------------------------------------------------------- */
/* WhatsApp                                                          */
/* ---------------------------------------------------------------- */

async function iniciarWhatsapp() {
    const { state, saveCreds } = await useMultiFileAuthState('session');

    sock = makeWASocket({
        auth: state,
        printQRInTerminal: false,
        shouldIgnoreJid: (jid) => jid.endsWith('@g.us'),
        syncFullHistory: false,
        markOnlineOnConnect: false
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            if (process.env.NUMERO_BOT) {
                try {
                    const code = await sock.requestPairingCode(process.env.NUMERO_BOT);
                    console.log('Código de vinculación:', code);
                } catch (error) {
                    console.log('Error al pedir el código:', error.message);
                }
            } else {
                qrcode.generate(qr, { small: true });
            }
        }

        if (connection === 'close') {
            const motivo = lastDisconnect?.error?.output?.statusCode;
            console.log('🔴 Conexión cerrada. Motivo:', motivo);

            if (motivo !== DisconnectReason.loggedOut) {
                console.log('Reintentando en 5 segundos...');
                setTimeout(iniciarWhatsapp, 5000);
            } else {
                console.log('La sesión fue cerrada desde el celular. Borrá la carpeta session/ y volvé a vincular.');
            }
        }

        if (connection === 'open') {
            console.log('🟢 Bot conectado a WhatsApp');
        }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        const msg = messages[0];
        if (!msg || !msg.message || msg.key.fromMe) return;

        const jid = msg.key.remoteJid;
        if (!jid || jid.endsWith('@g.us') || jid === 'status@broadcast') return;

        const texto = (
            msg.message.conversation ||
            msg.message.extendedTextMessage?.text ||
            ''
        ).trim();

        if (!texto) return;

        const responder = (mensaje) => sock.sendMessage(jid, { text: mensaje });

        await manejarMensaje(jid, texto, responder, notificar);
    });
}

/* ---------------------------------------------------------------- */
/* API que consume el sistema PHP                                    */
/* ---------------------------------------------------------------- */

function iniciarApi() {
    const app = express();
    app.use(express.json());

    // Solo se acepta desde el mismo servidor y con token
    app.use((req, res, next) => {
        if (TOKEN && req.headers['x-token'] !== TOKEN) {
            return res.status(401).json({ error: 'Token inválido' });
        }
        next();
    });

    app.get('/estado', (req, res) => {
        res.json({ conectado: Boolean(sock?.user), usuario: sock?.user?.id || null });
    });

    // Aviso genérico: { telefono, mensaje }
    app.post('/notificar', async (req, res) => {
        const { telefono, mensaje } = req.body;
        if (!telefono || !mensaje) {
            return res.status(400).json({ error: 'Faltan telefono y/o mensaje' });
        }
        const ok = await notificar(telefono, mensaje);
        res.json({ ok });
    });

    /*
     * Eventos del sistema. El PHP manda { id_proyecto, evento } y el bot
     * arma el texto y decide a quién avisar.
     *   delegado    -> avisa al jefe de departamento
     *   devuelto    -> avisa a dirección
     *   finalizado  -> avisa al jefe
     *   reabierto   -> avisa al jefe
     */
    app.post('/evento', async (req, res) => {
        const { id_proyecto, evento } = req.body;

        if (!id_proyecto || !evento) {
            return res.status(400).json({ error: 'Faltan id_proyecto y/o evento' });
        }

        try {
            const p = await q.proyectoPorId(id_proyecto);
            if (!p) return res.status(404).json({ error: 'Proyecto inexistente' });

            const encabezado = `#${p.id_proyecto} ${p.titulo}`;
            let enviados = 0;

            if (evento === 'delegado') {
                const texto =
                    `📌 *SE TE ASIGNÓ UN PROYECTO*\n\n` +
                    `${p.titulo}\n` +
                    `${p.descripcion || 'Sin descripción.'}`;

                if (await notificar(p.jefe_telefono, texto)) enviados++;

            } else if (evento === 'asignado') {
                // El jefe le asignó un profesor y el proyecto vuelve a dirección.
                const nombreProfesor = p.profesor_nombre
                    ? `${p.profesor_apellido} ${p.profesor_nombre}`
                    : 'Sin profesor asignado';

                const texto =
                    `📤 *PROYECTO ENVIADO*\n\n` +
                    `${p.titulo}\n` +
                    `${p.descripcion || 'Sin descripción.'}\n` +
                    `Profesor asignado: ${nombreProfesor}`;

                for (const tel of await q.telefonosDeDireccion()) {
                    if (await notificar(tel, texto)) enviados++;
                }

                // Aviso al profesor designado
                if (p.profesor_telefono) {
                    const textoProfesor =
                        `📋 *FUISTE DESIGNADO*\n\n` +
                        `${p.titulo}\n` +
                        `${p.descripcion || 'Sin descripción.'}`;
                    if (await notificar(p.profesor_telefono, textoProfesor)) enviados++;
                }

            } else if (evento === 'devuelto') {
                const devoluciones = await q.devolucionesDelProyecto(p.id_proyecto);
                const ultima = devoluciones[0];

                let texto =
                    `🔴 *Proyecto devuelto*\n\n${encabezado}\n` +
                    `👤 ${p.jefe_apellido} ${p.jefe_nombre}\n`;
                if (ultima) texto += `\n💬 ${ultima.comentario}\n`;

                for (const tel of await q.telefonosDeDireccion()) {
                    if (await notificar(tel, texto)) enviados++;
                }

            } else if (evento === 'finalizado') {
                const texto = `✅ PROYECTO ${p.titulo} finalizado, gracias por su trabajo`;
                if (await notificar(p.jefe_telefono, texto)) enviados++;

            } else if (evento === 'editado') {
                if (p.jefe_telefono) {
                    const texto =
                        `✏️ *PROYECTO ACTUALIZADO*\n\n` +
                        `${p.titulo}\n` +
                        `${p.descripcion || 'Sin descripción.'}\n\n` +
                        `Dirección actualizó la información del proyecto.`;
                    if (await notificar(p.jefe_telefono, texto)) enviados++;
                }

            } else if (evento === 'eliminado') {
                if (p.jefe_telefono) {
                    const texto =
                        `🗑️ *Proyecto eliminado*\n\n${encabezado}\n` +
                        `Fue eliminado por dirección.`;
                    if (await notificar(p.jefe_telefono, texto)) enviados++;
                }

            } else if (evento === 'reabierto') {
                const texto =
                    `🔵 *Proyecto reabierto*\n\n${encabezado}\n` +
                    `Estado actual: ${p.estado}\n` +
                    `Por ${p.director_apellido} ${p.director_nombre}`;

                if (await notificar(p.jefe_telefono, texto)) enviados++;

            } else {
                return res.status(400).json({ error: 'Evento desconocido' });
            }

            res.json({ ok: true, enviados });
        } catch (error) {
            console.log('Error en /evento:', error);
            res.status(500).json({ error: 'Error interno' });
        }
    });

    app.listen(PUERTO, '127.0.0.1', () => {
        console.log(`🟢 API del bot escuchando en http://127.0.0.1:${PUERTO}`);
    });
}

/* ---------------------------------------------------------------- */

async function main() {
    try {
        await q.db.query('SELECT 1');
        console.log('🟢 Conectado a la base enet_services');
    } catch (error) {
        console.log('🔴 No se pudo conectar a la base:', error.message);
        process.exit(1);
    }

    iniciarApi();
    await iniciarWhatsapp();
}

main();
