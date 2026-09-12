"""Pruebas HTTP: ejecutar SOLO contra una base de prueba (crea datos).
python Backend/tests/integracion.py http://127.0.0.1:8092
"""
import json
import sys
import time
import urllib.request
import urllib.error
import http.cookiejar

BASE = sys.argv[1].rstrip('/') + '/Backend/api/'
RUN = str(int(time.time()))
checks = 0

class Cliente:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.csrf = ''

    def pedir(self, path, method='GET', data=None, expected=200, csrf=True, raw=None):
        global checks
        headers = {'Content-Type': 'application/json'}
        if csrf:
            headers['X-CSRF-Token'] = self.csrf
        body = (raw if raw is not None else json.dumps(data or {})).encode() if method != 'GET' else None
        request = urllib.request.Request(BASE + path, data=body, headers=headers, method=method)
        try:
            response = self.opener.open(request)
        except urllib.error.HTTPError as error:
            response = error
        result = json.loads(response.read())
        assert response.status == expected, (path, expected, response.status, result)
        checks += 1
        return result

    def login(self, email, password):
        result = self.pedir('usuarios/login.php', 'POST', {'email': email, 'password': password})
        self.csrf = result['csrf']

def ci(numero):
    digits = str(numero).zfill(7)
    return digits + str((-sum(int(d)*w for d,w in zip(digits,[2,9,8,7,6,3,4]))) % 10)

admin, vecino, recolector, operario, anon = [Cliente() for _ in range(5)]
anon.pedir('usuarios/usuarios.php', expected=401)
admin.login('admin@sigeru.uy', 'admin123')
vecino.login('vecino@sigeru.uy', 'vecino123')
recolector.login('cuadrilla@sigeru.uy', 'cuadrilla123')
operario.login('operario@sigeru.uy', 'operario123')
vecino.pedir('usuarios/usuarios.php', expected=403)
vecino.pedir('usuarios/usuarios.php?id=1', expected=403)
vecino.pedir('usuarios/usuarios.php?id=1&email=vecino@sigeru.uy','PUT',{'nombre':'No permitido'},expected=403)
vecino.pedir('usuarios/usuarios.php?id=2','PUT',{'rol':'administrador'},expected=403)
vecino.pedir('usuarios/usuarios.php?id=2','PUT',{'nombre':'Vecino Prueba','email':'vecino@sigeru.uy','rol':'vecino'})
anon.pedir('usuarios/registro.php','POST',{'nombre':'Prueba','email':'registro'+RUN+'@example.test','cedula':ci(2000000+int(RUN)%1000000),'password':'debil123'},expected=400)
anon.pedir('usuarios/registro.php','POST',{'nombre':'Prueba','email':'registro'+RUN+'@example.test','cedula':ci(2000000+int(RUN)%1000000),'password':'Prueba!234','rol':'administrador'},expected=201)
registrado=Cliente()
registrado.pedir('usuarios/login.php','POST',{'email':'registro'+RUN+'@example.test','password':'Prueba!234'},expected=403)
pendiente=next(u for u in admin.pedir('usuarios/usuarios.php')['data'] if u['email']=='registro'+RUN+'@example.test')
assert pendiente['estado_registro']=='pendiente'
admin.pedir(f"usuarios/usuarios.php?id={pendiente['id']}",'PUT',{'estado_registro':'aprobado'})
registrado.login('registro'+RUN+'@example.test','Prueba!234')
assert registrado.pedir('usuarios/sesion.php')['usuario']['rol']=='vecino'
for _ in range(5):
    anon.pedir('usuarios/login.php','POST',{'email':'noexiste'+RUN+'@example.test','password':'Incorrecta'},expected=401)
anon.pedir('usuarios/login.php','POST',{'email':'noexiste'+RUN+'@example.test','password':'Incorrecta'},expected=429)
admin.pedir('gestion/contenedores.php','POST',{},expected=403,csrf=False)
vecino.pedir('gestion/contenedores.php','POST',{},expected=403)
admin.pedir('gestion/contenedores.php','POST',{'ubicacion':'Prueba 123','estado':'lleno','tipo_residuo':'Orgánico'},expected=400)
admin.pedir('gestion/contenedores.php','POST',{'ubicacion':'Prueba '+RUN,'estado':'funcional','tipo_residuo':'Orgánico'},expected=201)
contenedor = admin.pedir('gestion/contenedores.php')['data'][0]['id']
vecino.pedir('recoleccion/incidencias.php','POST',{'ubicacion':'Prueba 123','estado_contenedor':'roto','tipo_basura':'Orgánico','descripcion':'Rotura','latitud':'abc','longitud':0},expected=400)
vecino.pedir('recoleccion/incidencias.php','POST',{'ubicacion':'Prueba 123','estado_contenedor':'roto','tipo_basura':'Orgánico','descripcion':'Rotura de tapa','contenedor_id':contenedor,'usuario_id':1},expected=201)
incidencia=vecino.pedir('recoleccion/incidencias.php')['data'][0]
assert int(incidencia['usuario_id'])==2
i=incidencia['id']
admin.pedir('recoleccion/camiones.php','POST',{'matricula':'T'+RUN,'capacidad_carga':30,'estado':'Disponible'},expected=201)
matricula='T'+RUN
q=admin.pedir('recoleccion/cuadrillas.php','POST',{'nombre':'Equipo '+RUN,'disponibilidad':1},expected=201)['id']
admin.pedir('recoleccion/camiones.php?matricula='+matricula+'&accion=asignar_cuadrilla','PUT',{'cuadrilla_id':q})
ruta=admin.pedir('recoleccion/rutas.php','POST',{'nombre':'Ruta '+RUN,'zona':'Buceo','frecuencia':'Diaria','horario':'09:00'},expected=201)['id']
admin.pedir('gestion/relaciones.php','POST',{'accion':'contenedor','ruta_id':ruta,'contenedor_id':contenedor})
admin.pedir('recoleccion/asignaciones.php','POST',{'ruta_id':ruta,'cuadrilla_id':q,'matricula':matricula,'fecha':time.strftime('%Y-%m-%d')},expected=201)
admin.pedir('recoleccion/asignaciones.php','POST',{'ruta_id':ruta,'cuadrilla_id':q,'matricula':matricula,'fecha':time.strftime('%Y-%m-%d')},expected=409)
admin.pedir(f'recoleccion/incidencias.php?id={i}&accion=resolver','PUT',{'solucion':'Prueba'},expected=409)
admin.pedir(f'recoleccion/incidencias.php?id={i}&accion=asignar','PUT',{'matricula_camion':matricula,'cuadrilla_id':q})
admin.pedir(f'recoleccion/incidencias.php?id={i}&accion=asignar','PUT',{'matricula_camion':matricula,'cuadrilla_id':q},expected=409)
recolector.pedir(f'recoleccion/incidencias.php?id={i}&accion=resolver','PUT',{'solucion':'Ajena'},expected=403)
admin.pedir('gestion/relaciones.php','POST',{'accion':'miembro','usuario_id':4,'cuadrilla_id':q})
recolector.pedir(f'recoleccion/incidencias.php?id={i}&accion=resolver','PUT',{},expected=400)
recolector.pedir('recoleccion/recolecciones.php','POST',{'contenedor_id':contenedor,'ruta_id':ruta,'cuadrilla_id':q,'recolector_id':4,'fecha':time.strftime('%Y-%m-%d')+' 10:30:00','volumen':2,'simulado':1},expected=201)
vecino.pedir('recoleccion/reclamos.php','POST',{'incidencia_id':i,'descripcion':'Solicito reparación','ubicacion':'Prueba 123','prioridad':'alta'},expected=201)
recolector.pedir(f'recoleccion/incidencias.php?id={i}&accion=resolver','PUT',{'solucion':'Se reparó la tapa y retiró el residuo.'})
recolector.pedir(f'recoleccion/incidencias.php?id={i}&accion=resolver','PUT',{'solucion':'Repetida'},expected=409)
assert vecino.pedir('recoleccion/reclamos.php')['data'][0]['estado']=='cerrado'
assert admin.pedir('recoleccion/camiones.php?matricula='+matricula)['data']['estado']=='Disponible'
admin.pedir('recoleccion/mantenimientos.php','POST',{'matricula':matricula,'descripcion':'Servicio','tipo_man':'Preventivo','fecha_man':'2026-09-11','prox_man':'2026-08-01'},expected=400)
admin.pedir('recoleccion/mantenimientos.php','POST',{'matricula':matricula,'descripcion':'Servicio','tipo_man':'Preventivo','fecha_man':'2026-09-11','prox_man':'2026-10-01'},expected=201)
admin.pedir('recoleccion/reparaciones.php','POST',{'incidencia_id':i,'tipo_reparacion':'Tapa','fecha_inicio':'2026-09-10','fecha_fin':'2026-09-11'},expected=201)
admin.pedir('gestion/centros_acopio.php','POST',{'nombre':'Planta '+RUN,'ubicacion':'Prueba 123','estado':'Operativo','capacidad':10,'tipo_centro':'vertedero'},expected=201)
centro=next(c['id'] for c in admin.pedir('gestion/centros_acopio.php')['data'] if c['nombre']=='Planta '+RUN)
admin.pedir('gestion/relaciones.php','POST',{'accion':'operario','usuario_id':3,'centro_id':centro})
residuo=admin.pedir('gestion/residuos.php','POST',{'tipo_residuo':'Residuo '+RUN},expected=201)['id']
operario.pedir('gestion/recepciones.php','POST',{'centro_id':centro,'residuo_id':residuo,'cantidad':1,'simulado':1},expected=409)
admin.pedir('gestion/relaciones.php','POST',{'accion':'residuo','centro_id':centro,'residuo_id':residuo})
result=operario.pedir('gestion/recepciones.php','POST',{'centro_id':centro,'residuo_id':residuo,'cantidad':12,'simulado':1},expected=201)
assert result['alerta']
operario.pedir('gestion/recepciones.php','POST',{'centro_id':1,'residuo_id':residuo,'cantidad':1,'simulado':1},expected=403)
assert len(operario.pedir('gestion/centros_acopio.php')['data'])==1
operario.pedir('gestion/capacidad.php','PUT',{'centro_id':centro,'capacidad_ocupada':5,'estado':'Operativo'})
operario.pedir('gestion/capacidad.php','PUT',{'centro_id':centro,'capacidad_ocupada':-1,'estado':'Operativo'},expected=400)
operario.pedir('gestion/capacidad.php','PUT',{'centro_id':1,'capacidad_ocupada':5,'estado':'Operativo'},expected=403)
admin.pedir('gestion/reportes.php?desde=2026-13-01',expected=400)
reportes=admin.pedir('gestion/reportes.php?desde=2020-01-01&hasta=2030-12-31')['data']
assert reportes['incidencias'] and reportes['historial'] and reportes['residuos']
vecino.pedir('gestion/reportes.php',expected=403)
admin.pedir(f'gestion/contenedores.php?id={contenedor}&motivo=Prueba','DELETE')
admin.pedir(f'gestion/contenedores.php?id={contenedor}',expected=404)
admin.pedir('gestion/relaciones.php','POST',{'accion':'contenedor','ruta_id':ruta,'contenedor_id':contenedor},expected=409)
notificaciones=vecino.pedir('notificaciones/notificaciones.php')['data']
privada=next(n['id'] for n in notificaciones if n['usuario_id'] is not None)
recolector.pedir(f'notificaciones/notificaciones.php?id={privada}','PUT',expected=403)
anuncio=next(n['id'] for n in notificaciones if n['usuario_id'] is None)
vecino.pedir(f'notificaciones/notificaciones.php?id={anuncio}','PUT')
assert next(n for n in admin.pedir('notificaciones/notificaciones.php')['data'] if n['id']==anuncio)['leida']==0
vecino.pedir('usuarios/logout.php','POST')
vecino.pedir('usuarios/sesion.php',expected=401)

# Rúbrica de segunda entrega: CRUD completo de todos los roles y recursos.
for n, rol in enumerate(['administrador','vecino','chofer','recolector','operario']):
    email=f'crud-{rol}-{RUN}@example.test'
    datos={'nombre':'Usuario '+rol,'email':email,'cedula':ci(3000000+(int(RUN)%100000)*5+n),'password':'Prueba!234','rol':rol}
    admin.pedir('usuarios/usuarios.php','POST',datos,expected=201)
    admin.pedir('usuarios/usuarios.php','POST',datos,expected=409)
    usuario=admin.pedir('usuarios/usuarios.php?email='+email)['usuario']
    admin.pedir(f"usuarios/usuarios.php?id={usuario['id']}",'PUT',{'nombre':'Editado '+rol})
    assert admin.pedir(f"usuarios/usuarios.php?id={usuario['id']}")['usuario']['nombre']=='Editado '+rol
    admin.pedir(f"usuarios/usuarios.php?id={usuario['id']}",'DELETE')
    admin.pedir(f"usuarios/usuarios.php?id={usuario['id']}",expected=404)
    anon.pedir('usuarios/login.php','POST',{'email':email,'password':'Prueba!234'},expected=401)

maquina=admin.pedir('gestion/maquinaria.php','POST',{'nombre':'Prensa '+RUN,'centro_id':centro,'estado':'Operativo'},expected=201)['id']
assert admin.pedir(f'gestion/maquinaria.php?id={maquina}')['data'][0]['nombre']=='Prensa '+RUN
admin.pedir(f'gestion/maquinaria.php?id={maquina}','PUT',{'nombre':'Prensa revisada','centro_id':centro,'estado':'Mantenimiento'})
admin.pedir(f'gestion/maquinaria.php?id={maquina}','PUT',{'nombre':'Prensa revisada','centro_id':centro,'estado':'Invalido'},expected=400)
operario.pedir(f'gestion/maquinaria.php?id={maquina}&motivo=Prueba','DELETE',expected=403)
admin.pedir(f'gestion/maquinaria.php?id={maquina}','DELETE',expected=400)
admin.pedir(f'gestion/maquinaria.php?id={maquina}&motivo=Prueba','DELETE')
admin.pedir(f'gestion/maquinaria.php?id={maquina}',expected=404)
admin.pedir(f'gestion/maquinaria.php?id={maquina}','PUT',{'nombre':'Prensa revisada','centro_id':centro,'estado':'Operativo'},expected=404)

admin.pedir('gestion/contenedores.php','POST',{'ubicacion':'CRUD contenedor '+RUN,'estado':'funcional','tipo_residuo':'Reciclable'},expected=201)
contenedor_crud=admin.pedir('gestion/contenedores.php')['data'][0]['id']
admin.pedir(f'gestion/contenedores.php?id={contenedor_crud}','PUT',{'ubicacion':'CRUD actualizado','estado':'roto','tipo_residuo':'Orgánico'})
assert admin.pedir(f'gestion/contenedores.php?id={contenedor_crud}')['data']['estado']=='roto'
admin.pedir(f'gestion/contenedores.php?id={contenedor_crud}&motivo=Prueba','DELETE')
admin.pedir('recoleccion/camiones.php?matricula='+matricula,'PUT',{'capacidad_carga':33,'estado':'Mantenimiento'})
assert float(admin.pedir('recoleccion/camiones.php?matricula='+matricula)['data']['capacidad_carga'])==33
admin.pedir('recoleccion/camiones.php?matricula='+matricula+'&motivo=Prueba','DELETE')
admin.pedir('recoleccion/camiones.php?matricula='+matricula,expected=404)
admin.pedir(f'gestion/centros_acopio.php?id={centro}','PUT',{'nombre':'Centro editado','ubicacion':'CRUD 123','capacidad':20,'tipo_centro':'acopio','estado':'Mantenimiento'})
assert admin.pedir(f'gestion/centros_acopio.php?id={centro}')['data']['tipo_centro']=='acopio'
admin.pedir(f'gestion/centros_acopio.php?id={centro}&motivo=Prueba','DELETE')
admin.pedir(f'gestion/centros_acopio.php?id={centro}',expected=404)
for recurso in ['usuarios/usuarios.php','gestion/contenedores.php','gestion/centros_acopio.php','gestion/maquinaria.php','recoleccion/camiones.php']:
    admin.pedir(recurso,'PATCH',{},expected=405)
admin.pedir('gestion/contenedores.php?id=abc',expected=400)
admin.pedir('gestion/contenedores.php','POST',raw='{',expected=400)
admin.pedir('gestion/contenedores.php','POST',raw='[]',expected=400)
admin.pedir('usuarios/usuarios.php','POST',{'nombre':'x'*101},expected=400)
admin.pedir('recoleccion/camiones.php','POST',{'matricula':'VALIDA123','capacidad_carga':'30foo'},expected=400)
anon.pedir('usuarios/login.php','POST',{'email':"' OR 1=1 --",'password':'Invalida!123'},expected=401)
admin.pedir('gestion/contenedores.php','POST',{'ubicacion':'Depósito de prueba','estado':'funcional','tipo_residuo':'Orgánico','en_servicio':0},expected=201)
repuesto=admin.pedir('gestion/contenedores.php?repuestos=1')['data'][0]
assert repuesto['en_servicio']==0
admin.pedir('gestion/relaciones.php','POST',{'accion':'contenedor','ruta_id':ruta,'contenedor_id':repuesto['id']},expected=409)
admin.pedir(f"gestion/contenedores.php?id={repuesto['id']}",'PUT',{'ubicacion':'Calle de prueba','estado':'funcional','tipo_residuo':'Orgánico','en_servicio':1})
assert admin.pedir(f"gestion/contenedores.php?id={repuesto['id']}")['data']['en_servicio']==1
print(f'{checks} comprobaciones HTTP correctas; ciclo operativo, roles, historial y alertas verificados.')
