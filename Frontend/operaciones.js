export function instalarOperaciones({ apiFetch, espacio, sesion, vincularMenu, escaparHtml }) {
    const e = escaparHtml;
    const endpoints = {};
    ['cuadrillas', 'rutas', 'asignaciones', 'recolecciones', 'mantenimientos', 'reparaciones', 'reclamos', 'incidencias', 'camiones'].forEach(x => endpoints[x] = `../Backend/api/recoleccion/${x}.php`);
    ['capacidad', 'residuos', 'maquinaria', 'recepciones', 'relaciones', 'reportes', 'centros_acopio', 'contenedores'].forEach(x => endpoints[x] = `../Backend/api/gestion/${x}.php`);
    endpoints.usuarios = '../Backend/api/usuarios/usuarios.php';
    const etiquetas = { capacidad_ocupada: 'Capacidad ocupada (m³)', nombre: 'Nombre', disponibilidad: 'Disponible', zona: 'Zona', frecuencia: 'Frecuencia', horario: 'Horario', ruta_id: 'Ruta', cuadrilla_id: 'Cuadrilla', matricula: 'Camión', fecha: 'Fecha', contenedor_id: 'Contenedor', recolector_id: 'Recolector', volumen: 'Volumen (m³)', simulado: 'Datos simulados', descripcion: 'Descripción', tipo_man: 'Tipo de mantenimiento', fecha_man: 'Fecha de mantenimiento', prox_man: 'Próximo mantenimiento', tipo_reparacion: 'Tipo de reparación', fecha_inicio: 'Inicio', fecha_fin: 'Fin', incidencia_id: 'Incidencia', tipo_residuo: 'Tipo de residuo', centro_id: 'Instalación', residuo_id: 'Residuo', cantidad: 'Cantidad (m³)', estado: 'Estado', prioridad: 'Prioridad', ubicacion: 'Ubicación', usuario_id: 'Usuario', chofer_id: 'Chofer', modelo: 'Modelo', lic_conducir: 'Licencia de conducir', especialidad: 'Especialidad' };
    const relaciones = { ruta_id: 'rutas', cuadrilla_id: 'cuadrillas', matricula: 'camiones', contenedor_id: 'contenedores', recolector_id: 'usuarios', incidencia_id: 'incidencias', centro_id: 'centros_acopio', residuo_id: 'residuos', usuario_id: 'usuarios', chofer_id: 'usuarios' };
    const configs = {
        cuadrillas: ['Cuadrillas', ['nombre', 'disponibilidad']], rutas: ['Rutas', ['nombre', 'zona', 'frecuencia', 'horario']],
        asignaciones: ['Calendario de rutas', ['ruta_id', 'cuadrilla_id', 'matricula', 'fecha']],
        recolecciones: ['Recolecciones', ['contenedor_id', 'ruta_id', 'cuadrilla_id', 'recolector_id', 'fecha', 'volumen', 'simulado']],
        mantenimientos: ['Mantenimientos de camiones', ['matricula', 'descripcion', 'tipo_man', 'fecha_man', 'prox_man']],
        reparaciones: ['Reparaciones de contenedores', ['incidencia_id', 'tipo_reparacion', 'fecha_inicio', 'fecha_fin']],
        residuos: ['Tipos de residuos', ['tipo_residuo']], maquinaria: ['Maquinaria', ['nombre', 'estado', 'centro_id']],
        recepciones: ['Recepción de residuos', ['centro_id', 'residuo_id', 'cantidad', 'simulado']],
        reclamos: ['Reclamos', ['incidencia_id', 'descripcion', 'ubicacion', 'prioridad']]
    };
    const opcionales = ['volumen', 'prox_man', 'fecha_fin'];

    function tabla(rows) {
        if (!rows.length) return '<p>No hay registros.</p>';
        const cols = Object.keys(rows[0]);
        return `<div style="overflow:auto"><table class="tabla-api"><thead><tr>${cols.map(c => `<th>${e(etiquetas[c] || c.replaceAll('_', ' '))}</th>`).join('')}</tr></thead><tbody>${rows.map(r => `<tr>${cols.map(c => `<td>${e(r[c] ?? 'Sin registrar')}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
    }

    async function formulario(titulo, campos, enviar, valores = {}) {
        espacio.innerHTML = `<div class="seccion-bloque"><h3>${e(titulo)}</h3><form id="form-operacion"><div id="campos-operacion" class="form-fila"></div><p id="resultado-operacion" role="status"></p><button class="btn-mini">Guardar</button></form><div id="listado-operacion"></div></div>`;
        const form = document.getElementById('form-operacion');
        const cont = document.getElementById('campos-operacion');
        for (const campo of campos) {
            let opciones = null;
            if (relaciones[campo]) {
                let rows;
                if (campo === 'recolector_id' && sesion.rol === 'recolector') rows = [sesion];
                else if (campo === 'cuadrilla_id' && ['recolector', 'chofer'].includes(sesion.rol)) rows = sesion.cuadrilla_id ? [{ id: sesion.cuadrilla_id, nombre: `Mi cuadrilla #${sesion.cuadrilla_id}` }] : [];
                else { const r = await apiFetch(endpoints[relaciones[campo]]); if (r.status !== 'success') throw Error(r.message); rows = r.data || []; }
                if (campo === 'recolector_id') rows = rows.filter(r => r.rol === 'recolector');
                if (campo === 'chofer_id') rows = rows.filter(r => r.rol === 'chofer');
                opciones = rows.map(r => [campo === 'matricula' ? r.matricula : r.id, `${r.id || ''} ${r.nombre || r.ubicacion || r.matricula || r.tipo_residuo || ''}`]);
            }
            if (['simulado', 'disponibilidad'].includes(campo)) opciones = [['0', 'No'], ['1', 'Sí']];
            if (campo === 'estado') opciones = ['Operativo', 'Mantenimiento', 'Cerrado'].map(x => [x, x]);
            if (campo === 'prioridad') opciones = ['media', 'baja', 'alta'].map(x => [x, x]);
            const tipo = campo === 'fecha' && titulo === 'Recolecciones' ? 'datetime-local' : campo.startsWith('fecha') || campo === 'prox_man' ? 'date' : ['cantidad', 'volumen', 'capacidad_ocupada'].includes(campo) ? 'number' : 'text';
            const required = opcionales.includes(campo) ? '' : 'required';
            const min = campo === 'nombre' ? 3 : 1;
            const max = { nombre: 100, descripcion: 5000, ubicacion: 255, tipo_man: 100, tipo_reparacion: 100, modelo: 100, lic_conducir: 100 }[campo] || 100;
            const input = opciones ? `<select id="op-${campo}" name="${campo}" class="campo-entrada" ${required}>${opciones.map(([v, l]) => `<option value="${e(v)}" ${String(valores[campo]) === String(v) ? 'selected' : ''}>${e(l)}</option>`).join('')}</select>` : `<input id="op-${campo}" name="${campo}" type="${tipo}" class="campo-entrada" ${required} minlength="${min}" maxlength="${max}" ${tipo === 'number' ? 'step="0.01" min="0"' : ''} value="${e(valores[campo] || '')}">`;
            cont.insertAdjacentHTML('beforeend', `<div><label for="op-${campo}">${e(etiquetas[campo] || campo)}</label>${input}</div>`);
        }
        form.addEventListener('submit', async event => {
            event.preventDefault(); const boton = form.querySelector('button'); boton.disabled = true;
            const d = Object.fromEntries(new FormData(form));
            const salida = document.getElementById('resultado-operacion');
            for (const [c, v] of Object.entries(d)) { if (!opcionales.includes(c) && !v.trim()) { salida.textContent = 'Completá los campos obligatorios.'; boton.disabled = false; return; } }
            if (d.nombre && d.nombre.trim().length < 3) { salida.textContent = 'El nombre debe tener al menos 3 caracteres.'; boton.disabled = false; return; }
            if (d.fecha_fin && d.fecha_inicio && d.fecha_fin < d.fecha_inicio) { salida.textContent = 'La fecha final es anterior al inicio.'; boton.disabled = false; return; }
            try { const r = await enviar(d); salida.textContent = r.message; if (r.status === 'success') await cargarListado(); }
            catch (error) { salida.textContent = error.message; }
            finally { boton.disabled = false; }
        });
    }
    let cargarListado = async () => { };
    async function mostrar(recurso) {
        const [titulo, campos] = configs[recurso];
        let editar = null;
        const admiteEdicion = ['cuadrillas', 'rutas', 'maquinaria', 'residuos'].includes(recurso);
        const enviar = d => { if (recurso === 'recolecciones') d.fecha = d.fecha.replace('T', ' ') + ':00'; return apiFetch(endpoints[recurso] + (editar ? `?id=${editar}` : ''), { method: editar ? 'PUT' : 'POST', body: JSON.stringify(d) }); };
        await formulario(titulo, campos, enviar);
        const formularioActual = document.getElementById('form-operacion');
        const modo = document.createElement('p'); modo.setAttribute('role', 'status'); modo.textContent = 'Nuevo registro';
        formularioActual.prepend(modo);
        const nuevo = document.createElement('button'); nuevo.type = 'button'; nuevo.className = 'btn-mini'; nuevo.textContent = 'Nuevo registro';
        nuevo.onclick = () => { editar = null; formularioActual.reset(); modo.textContent = 'Nuevo registro'; };
        formularioActual.append(nuevo);
        const soloLectura = (sesion.rol !== 'administrador' && ['rutas', 'asignaciones'].includes(recurso)) || (sesion.rol === 'administrador' && recurso === 'reclamos');
        if (soloLectura) document.getElementById('form-operacion').hidden = true;
        cargarListado = async () => {
            const r = await apiFetch(endpoints[recurso]);
            const listado = document.getElementById('listado-operacion');
            if (!listado) return;
            if (r.status !== 'success') { listado.textContent = r.message; return; }
            listado.innerHTML = tabla(r.data || []);
            if (admiteEdicion && !soloLectura && r.data?.length) {
                const th = document.createElement('th'); th.textContent = 'Acciones'; listado.querySelector('thead tr').append(th);
            }
            if (admiteEdicion && !soloLectura) (r.data || []).forEach((row, indice) => {
                const celda = document.createElement('td'); const acciones = document.createElement('div'); acciones.className = 'acciones-fila'; celda.append(acciones); listado.querySelectorAll('tbody tr')[indice].append(celda);
                const b = document.createElement('button'); b.className = 'btn-mini boton-secundario'; b.textContent = 'Editar'; b.setAttribute('aria-label', `Editar ${titulo} #${row.id}`);
                b.onclick = () => { editar = row.id; modo.textContent = `Editando registro #${row.id}`; campos.forEach(c => { const el = document.getElementById('op-' + c); if (el) el.value = row[c] ?? ''; }); };
                acciones.append(b);
                if (sesion.rol === 'administrador' && ['maquinaria', 'rutas'].includes(recurso)) {
                    const baja = document.createElement('button'); baja.className = 'btn-mini boton-peligro'; baja.textContent = 'Dar de baja'; baja.setAttribute('aria-label', `Dar de baja ${titulo} #${row.id}`);
                    baja.onclick = async () => {
                        const motivo = prompt('Motivo de la baja:'); if (!motivo || !motivo.trim()) return;
                        const resultado = await apiFetch(`${endpoints[recurso]}?id=${row.id}&motivo=${encodeURIComponent(motivo)}`, { method: 'DELETE' });
                        document.getElementById('resultado-operacion').textContent = resultado.message;
                        if (resultado.status === 'success') await cargarListado();
                    };
                    acciones.append(baja);
                }
            });
        };
        await cargarListado();
    }
    async function relacion(accion, campos, titulo) {
        cargarListado = async () => { };
        await formulario(titulo, campos, d => apiFetch(endpoints.relaciones, { method: 'POST', body: JSON.stringify({ ...d, accion }) }));
    }
    function menu(seccion, texto, fn) {
        const contenedor = document.getElementById(seccion);
        if (!contenedor) return;
        const a = document.createElement('a'); a.href = '#'; a.className = 'item-menu'; a.textContent = texto;
        a.onclick = ev => { ev.preventDefault(); document.querySelectorAll('.item-menu').forEach(item => item.classList.remove('activo')); a.classList.add('activo'); Promise.resolve(fn()).catch(error => { espacio.textContent = error.message; }); };
        contenedor.append(a);
    }
    menu('grupo-equipos-admin', 'Cuadrillas', () => mostrar('cuadrillas'));
    menu('grupo-equipos-admin', 'Integrantes de cuadrillas', () => relacion('miembro', ['usuario_id', 'cuadrilla_id'], 'Asignar integrante'));
    menu('grupo-equipos-admin', 'Choferes y camiones', () => relacion('chofer', ['chofer_id', 'matricula', 'modelo', 'lic_conducir'], 'Asignar chofer'));
    menu('grupo-rutas-admin', 'Calendario de rutas', () => mostrar('asignaciones'));
    menu('grupo-contenedores-admin', 'Contenedores de rutas', () => relacion('contenedor', ['ruta_id', 'contenedor_id'], 'Agregar contenedor a ruta'));
    menu('grupo-centros-admin', 'Residuos habilitados', () => relacion('residuo', ['centro_id', 'residuo_id'], 'Habilitar residuo en instalación'));
    menu('grupo-equipos-admin', 'Mantenimientos', () => mostrar('mantenimientos'));
    menu('grupo-equipos-admin', 'Reparaciones', () => mostrar('reparaciones'));
    menu('grupo-equipos-admin', 'Recolecciones', () => mostrar('recolecciones'));
    menu('grupo-centros-admin', 'Recepción de residuos', () => mostrar('recepciones'));
    menu('grupo-centros-admin', 'Maquinaria', () => mostrar('maquinaria'));
    menu('grupo-usuarios-admin', 'Reclamos de vecinos', () => mostrar('reclamos'));
    menu('seccion-operario', 'Actualizar capacidad y recepción', async () => { cargarListado = async () => { }; await formulario('Capacidad y estado', ['centro_id', 'capacidad_ocupada', 'estado'], d => apiFetch(endpoints.capacidad, { method: 'PUT', body: JSON.stringify(d) })); });
    menu('seccion-vecino', 'Reclamos', () => mostrar('reclamos'));
    menu('seccion-cuadrilla', 'Mis rutas', () => mostrar('rutas'));
    if (sesion.rol === 'recolector') menu('seccion-cuadrilla', 'Registrar recolección', () => mostrar('recolecciones'));
    vincularMenu('menu-gestionar-rutas', () => mostrar('rutas'));
    vincularMenu('menu-capacidad-residuos', () => mostrar('recepciones'));
    vincularMenu('menu-gestionar-maquinaria', () => mostrar('maquinaria'));
    vincularMenu('menu-reportes-area', async () => { const r = await apiFetch(endpoints.recepciones); espacio.innerHTML = `<h3>Recepciones de mi instalación</h3>${tabla(r.data || [])}`; });
    vincularMenu('menu-reportes-estadisticas', async () => {
        espacio.innerHTML = '<h3>Reportes por período</h3><form id="f-reporte"><label>Desde <input type="date" name="desde" required></label> <label>Hasta <input type="date" name="hasta" required></label> <button class="btn-mini">Consultar</button></form><div id="reporte"></div>';
        document.getElementById('f-reporte').onsubmit = async ev => {
            ev.preventDefault(); const q = new URLSearchParams(new FormData(ev.target)); const r = await apiFetch(endpoints.reportes + '?' + q);
            document.getElementById('reporte').innerHTML = r.status === 'success' ? Object.entries(r.data).map(([k, v]) => `<h4>${e(k)}</h4>${tabla(v)}`).join('') : e(r.message);
        };
    });
}
