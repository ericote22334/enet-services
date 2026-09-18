/*
 * consultas.js
 * Todo el acceso a la base enet_services.
 * Los estados y los inserts en historial_proyectos son idénticos
 * a los que hace el sistema PHP, así la web y el bot quedan sincronizados.
 */

const mysql = require('mysql2/promise');

const db = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'enet_services',
    waitForConnections: true,
    connectionLimit: 10
});

/* ---------------------------------------------------------------- */
/* Usuarios                                                          */
/* ---------------------------------------------------------------- */

const SELECT_USUARIO = `
    SELECT p.dni, p.nombre, p.apellido, p.pass, p.telefono,
           r.cargo, r.departamento
    FROM personal p
    INNER JOIN roles r ON r.dni = p.dni
`;

async function buscarPorTelefono(telefono) {
    const [rows] = await db.execute(
        `${SELECT_USUARIO} WHERE RIGHT(p.telefono, 10) = ?`,
        [telefono.slice(-10)]
    );
    return rows[0] || null;
}

async function buscarPorDni(dni) {
    const [rows] = await db.execute(`${SELECT_USUARIO} WHERE p.dni = ?`, [dni]);
    return rows[0] || null;
}

async function vincularTelefono(dni, telefono) {
    await db.execute('UPDATE personal SET telefono = ? WHERE dni = ?', [telefono, dni]);
}

async function desvincularTelefono(dni) {
    await db.execute('UPDATE personal SET telefono = NULL WHERE dni = ?', [dni]);
}

async function telefonosDeDireccion() {
    const [rows] = await db.execute(`
        SELECT p.telefono
        FROM personal p
        INNER JOIN roles r ON r.dni = p.dni
        WHERE r.cargo IN ('Director', 'Subdirector')
          AND p.telefono IS NOT NULL
    `);
    return rows.map((r) => r.telefono);
}

async function profesoresDelDepartamento(departamento) {
    const [rows] = await db.execute(`
        SELECT p.dni, p.nombre, p.apellido
        FROM personal p
        INNER JOIN roles r ON r.dni = p.dni
        WHERE r.cargo = 'Profesor' AND r.departamento = ?
        ORDER BY p.apellido, p.nombre
    `, [departamento]);
    return rows;
}

/* ---------------------------------------------------------------- */
/* Proyectos                                                         */
/* ---------------------------------------------------------------- */

const SELECT_PROYECTO = `
    SELECT p.id_proyecto, p.titulo, p.descripcion, p.estado, p.archivo,
           p.fecha_creacion, p.director_dni, p.jefe_dni, p.profesor_dni,
           d.nombre AS director_nombre, d.apellido AS director_apellido,
           j.nombre AS jefe_nombre, j.apellido AS jefe_apellido,
           j.telefono AS jefe_telefono,
           pf.nombre AS profesor_nombre, pf.apellido AS profesor_apellido
    FROM proyectos p
    INNER JOIN personal d ON d.dni = p.director_dni
    LEFT JOIN personal j ON j.dni = p.jefe_dni
    LEFT JOIN personal pf ON pf.dni = p.profesor_dni
`;

async function proyectoPorId(id) {
    const [rows] = await db.execute(`${SELECT_PROYECTO} WHERE p.id_proyecto = ?`, [id]);
    return rows[0] || null;
}

async function proyectosDelJefe(dniJefe, { soloAbiertos = true } = {}) {
    const filtro = soloAbiertos ? "AND p.estado <> 'Finalizado'" : '';
    const [rows] = await db.execute(
        `${SELECT_PROYECTO} WHERE p.jefe_dni = ? ${filtro} ORDER BY p.fecha_creacion DESC`,
        [dniJefe]
    );
    return rows;
}

async function proyectosSinProfesor(dniJefe) {
    const [rows] = await db.execute(
        `${SELECT_PROYECTO}
         WHERE p.jefe_dni = ?
           AND (p.profesor_dni IS NULL OR p.profesor_dni = 0)
           AND p.estado <> 'Finalizado'
         ORDER BY p.fecha_creacion DESC`,
        [dniJefe]
    );
    return rows;
}

async function proyectosDevolvibles(dniJefe) {
    const [rows] = await db.execute(
        `${SELECT_PROYECTO}
         WHERE p.jefe_dni = ?
           AND p.profesor_dni IS NOT NULL AND p.profesor_dni <> 0
           AND p.estado <> 'Finalizado'
         ORDER BY p.fecha_creacion DESC`,
        [dniJefe]
    );
    return rows;
}

async function proyectosPorEstado(estados) {
    const marcadores = estados.map(() => '?').join(',');
    const [rows] = await db.execute(
        `${SELECT_PROYECTO} WHERE p.estado IN (${marcadores}) ORDER BY p.fecha_creacion DESC`,
        estados
    );
    return rows;
}

async function resumenDeEstados() {
    const [rows] = await db.execute(`
        SELECT estado, COUNT(*) AS total
        FROM proyectos
        GROUP BY estado
    `);
    const resumen = {};
    for (const fila of rows) resumen[fila.estado] = fila.total;
    return resumen;
}

/* ---------------------------------------------------------------- */
/* Acciones (replican exactamente lo que hace el PHP)                */
/* ---------------------------------------------------------------- */

async function registrarHistorial(idProyecto, dniUsuario, accion, comentario) {
    await db.execute(
        `INSERT INTO historial_proyectos (proyecto, usuario, accion, comentario)
         VALUES (?, ?, ?, ?)`,
        [idProyecto, dniUsuario, accion, comentario]
    );
}

// Equivalente a asignar-proyecto.php
async function asignarProfesor(idProyecto, dniJefe, dniProfesor, nombreProfesor, comentario) {
    const [resultado] = await db.execute(
        `UPDATE proyectos
         SET profesor_dni = ?, estado = 'En Revision'
         WHERE id_proyecto = ? AND jefe_dni = ?`,
        [dniProfesor, idProyecto, dniJefe]
    );

    if (resultado.affectedRows === 0) return false;

    const texto = comentario
        ? `Asignado a ${nombreProfesor}. ${comentario}`
        : `Asignado a ${nombreProfesor}.`;

    await registrarHistorial(idProyecto, dniJefe, 'Asignación', `${texto} (vía WhatsApp)`);
    return true;
}

// Equivalente a devolver-proyecto.php
async function devolverProyecto(idProyecto, dniJefe, motivo) {
    const [resultado] = await db.execute(
        `UPDATE proyectos SET estado = 'Devuelto'
         WHERE id_proyecto = ? AND jefe_dni = ?`,
        [idProyecto, dniJefe]
    );

    if (resultado.affectedRows === 0) return false;

    const comentario = motivo && motivo.trim() !== ''
        ? motivo
        : 'Devolución de proyecto a dirección.';

    await registrarHistorial(idProyecto, dniJefe, 'Devolución', `${comentario} (vía WhatsApp)`);
    return true;
}

// Equivalente a finalizar-proyecto.php
async function finalizarProyecto(idProyecto, dniDirector) {
    const [resultado] = await db.execute(
        `UPDATE proyectos SET estado = 'Finalizado' WHERE id_proyecto = ?`,
        [idProyecto]
    );

    if (resultado.affectedRows === 0) return false;

    await registrarHistorial(
        idProyecto,
        dniDirector,
        'Finalización',
        'Proyecto finalizado por dirección. (vía WhatsApp)'
    );
    return true;
}

async function historialDelProyecto(idProyecto, limite = 5) {
    const [rows] = await db.query(
        `SELECT h.accion, h.comentario, h.fecha,
                u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
         FROM historial_proyectos h
         LEFT JOIN personal u ON u.dni = h.usuario
         WHERE h.proyecto = ?
         ORDER BY h.fecha DESC
         LIMIT ?`,
        [idProyecto, Number(limite)]
    );
    return rows;
}

async function devolucionesDelProyecto(idProyecto) {
    const [rows] = await db.execute(
        `SELECT h.comentario, h.fecha,
                u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
         FROM historial_proyectos h
         LEFT JOIN personal u ON u.dni = h.usuario
         WHERE h.proyecto = ? AND h.accion = 'Devolución'
         ORDER BY h.fecha DESC`,
        [idProyecto]
    );
    return rows;
}

module.exports = {
    db,
    buscarPorTelefono,
    buscarPorDni,
    vincularTelefono,
    desvincularTelefono,
    telefonosDeDireccion,
    profesoresDelDepartamento,
    proyectoPorId,
    proyectosDelJefe,
    proyectosSinProfesor,
    proyectosDevolvibles,
    proyectosPorEstado,
    resumenDeEstados,
    asignarProfesor,
    devolverProyecto,
    finalizarProyecto,
    historialDelProyecto,
    devolucionesDelProyecto
};
