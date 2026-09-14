// Confirmación previa a escrituras importantes. Cancelar nunca envía la petición.
export async function confirmarOperacion(url, opciones={}) {
    const metodo=(opciones.method || 'GET').toUpperCase();
    const destino=new URL(url,location.href);
    const recurso=destino.pathname.split('/').pop().replace('.php','');
    if(!['POST','PUT','DELETE'].includes(metodo) || recurso==='logout' || (recurso==='notificaciones' && metodo==='PUT')) return true;
    const nombres={usuarios:'usuario',cuadrillas:'cuadrilla',camiones:'camión',contenedores:'contenedor',centros_acopio:'instalación',maquinaria:'máquina',rutas:'ruta',asignaciones:'asignación de ruta',relaciones:'asignación de recursos',recepciones:'recepción de residuos',capacidad:'capacidad de la instalación',incidencias:'incidencia',recolecciones:'recolección',reclamos:'reclamo',residuos:'tipo de residuo',mantenimientos:'mantenimiento',reparaciones:'reparación',notificaciones:'anuncio'};
    const nombre=nombres[recurso] || 'registro';
    const eliminar=metodo==='DELETE';
    const accion=eliminar?'Confirmar baja':metodo==='PUT'?'Guardar cambios':'Confirmar registro';
    let datos={}; try { datos=JSON.parse(opciones.body || '{}'); } catch {}
    const referencia=datos.nombre || datos.matricula || destino.searchParams.get('matricula') || (destino.searchParams.get('id') ? '#'+destino.searchParams.get('id') : '');
    const dialog=document.createElement('dialog');dialog.className='confirmacion';
    dialog.setAttribute('aria-labelledby','confirmacion-titulo');
    dialog.innerHTML='<form method="dialog"><h2 id="confirmacion-titulo"></h2><p class="confirmacion-descripcion"></p><dl class="confirmacion-detalle"></dl><div class="confirmacion-acciones"><button value="cancelar" class="btn-mini boton-secundario" autofocus>Cancelar</button><button value="confirmar" class="btn-mini"></button></div></form>';
    dialog.querySelector('h2').textContent=accion;
    dialog.querySelector('p').textContent=eliminar?`Se dará de baja este ${nombre}. El historial se conservará.`:`Se ${metodo==='PUT'?'actualizarán':'guardarán'} los datos de ${nombre}. Revisá la información antes de confirmar.`;
    const detalles=dialog.querySelector('dl');
    const agregar=(etiqueta,valor)=>{if(valor===undefined || valor===null || valor==='')return;const dt=document.createElement('dt');dt.textContent=etiqueta;const dd=document.createElement('dd');dd.textContent=String(valor);detalles.append(dt,dd);};
    agregar('Registro',referencia);
    const etiquetas={email:'Correo',rol:'Rol',estado_registro:'Solicitud',disponibilidad:'Disponibilidad',cuadrilla_id:'Cuadrilla',usuario_id:'Usuario',chofer_id:'Chofer',centro_id:'Instalación',ruta_id:'Ruta',estado:'Estado',capacidad_ocupada:'Capacidad ocupada',cantidad:'Cantidad',fecha:'Fecha',accion:'Operación'};
    for(const [campo,etiqueta] of Object.entries(etiquetas)) agregar(etiqueta,datos[campo]);
    if(datos.password) agregar('Contraseña','Se cambiará la contraseña');
    agregar('Motivo',destino.searchParams.get('motivo'));
    const aceptar=dialog.querySelector('[value="confirmar"]');aceptar.textContent=accion;
    if(eliminar) aceptar.classList.add('boton-peligro');
    const anterior=document.activeElement;
    document.body.append(dialog);
    return new Promise(resolve=>{
        dialog.addEventListener('close',()=>{const ok=dialog.returnValue==='confirmar';dialog.remove();if(anterior?.isConnected)anterior.focus();resolve(ok);},{once:true});
        dialog.showModal();
    });
}
