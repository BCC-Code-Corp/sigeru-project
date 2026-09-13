export const mensajePassword = 'La contraseña debe tener entre 8 y 72 bytes, mayúscula, minúscula, número y símbolo.';
export function validarPassword(valor) {
    const bytes = new TextEncoder().encode(valor).length;
    return bytes >= 8 && bytes <= 72 && /[A-Z]/.test(valor) && /[a-z]/.test(valor) && /[0-9]/.test(valor) && /[^A-Za-z0-9]/.test(valor);
}
export function validarCedula(valor) {
    const digitos = String(valor).replace(/[^0-9]/g, '');
    if (digitos.length < 7 || digitos.length > 8 || Number(digitos) === 0) return false;
    const c = digitos.padStart(8, '0');
    const suma = [2, 9, 8, 7, 6, 3, 4].reduce((a, p, i) => a + p * Number(c[i]), 0);
    return (10 - suma % 10) % 10 === Number(c[7]);
}
export function validarRegistro({ nombre, email, cedula, password }) {
    if (nombre.trim().length < 3 || nombre.length > 100) return 'El nombre debe tener entre 3 y 100 caracteres.';
    if (email.length > 100 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Correo electrónico inválido.';
    if (!validarCedula(cedula)) return 'Cédula de Identidad inválida.';
    if (!validarPassword(password)) return mensajePassword;
    return null;
}
