export const fechaLetras = (f) => {
    if (f === '0000-00-00 00:00:00') {
        return 'No se ha asignado fecha';
    }

    const diassemanaN = [
        "Domingo", "Lunes", "Martes", "Miércoles",
        "Jueves", "Viernes", "Sábado"
    ];

    const mesesN = [
        "", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
        "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
    ];

    const fecha = new Date(f);
    if (isNaN(fecha.getTime())) {
        return 'Fecha inválida';
    }

    const diasemana = fecha.getDay();
    const diames = fecha.getDate();
    const mes = fecha.getMonth() + 1;
    const anio = fecha.getFullYear();

    return `${diassemanaN[diasemana]} ${diames} de ${mesesN[mes]}`;
};
