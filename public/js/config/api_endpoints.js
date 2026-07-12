const APLICATIVO_API = {
    AUTHENTICATION: {
        GET: {
            LOGIN: '/Authentication/Login',
            FORGOT_PASSWORD: '/Authentication/ForgotPassword',
            RESET_PASSWORD: '/Authentication/ResetPassword',
            GET_USER_BY_ID: '/Authentication/GetUserById',
            ACTUALIZAR_SESION: '/Authentication/ActualizarSesion'
        },
        POST: {
            LOGIN: '/Authentication/Login',
            LOGOUT: '/Authentication/Logout',
            UPDATE_THEME: '/Authentication/UpdateThemePreference',
            FORGOT_PASSWORD: '/Authentication/ForgotPassword',
            RESET_PASSWORD: '/Authentication/ResetPassword',
            REGISTER: '/Registro/Store'
        }
    },
    ADMIN: {
        POST: {
            BACKUP_GENERATE: '/Administracion/Backups/Generar',
            BACKUP_ISSUE_LINK: '/Administracion/Backups/EnlaceDescarga',
            EMERGENCY_DECRYPT: '/Administracion/DescifradoEmergencia/Ejecutar'
        }
    },
    PERFIL: {
        PUT: {
            UPDATE: '/Perfil/Update',
            UPDATE_PASSWORD: '/Perfil/UpdatePassword'
        }
    },
    USUARIOS: {
        GET: {
            DATATABLE: '/Usuarios/GetDatatableServerSide'
        },
        POST: {
            STORE: '/Usuarios/Store'
        },
        PUT: {
            UPDATE: '/Usuarios/Update'
        },
        DELETE: {
            DELETE: '/Usuarios/Delete'
        }
    },
    GRAFICOS: {
        GET: {
            METRICS: '/Graficos/GetMetrics'
        }
    },
    EXPORT: {
        GET: {
            VIAJES: '/Export/Viajes',
            GASTOS: '/Export/Gastos',
            RESUMEN: '/Export/Resumen'
        }
    },
    VIAJES: {
        GET: {
            DATATABLE: '/Viajes/GetDatatableServerSide',
            COMPARATIVA_MENSUAL: '/Viajes/GetComparativaMensual',
            RENTAL_SUGGESTION: '/Viajes/GetRentalSuggestion',
            SELECT2: '/Viajes/Select2Paginated',
            SELECT2_PLATFORMS: '/Viajes/Select2Platforms'
        },
        POST: {
            STORE: '/Viajes/Store'
        }
    },
    GASTOS: {
        GET: {
            DATATABLE: '/Gastos/GetDatatableServerSide',
            SELECT2_CATEGORIES: '/Gastos/Select2Categories',
            SELECT2_VEHICLES: '/Gastos/Select2Vehicles'
        },
        POST: {
            STORE: '/Gastos/Store'
        }
    },
    DASHBOARD: {
        GET: {
            RESUMEN: '/Dashboard/GetResumen'
        }
    },
    VEHICULOS: {
        GET: {
            DATATABLE: '/Vehiculos/GetDatatableServerSide',
            SELECT2: '/Vehiculos/Select2Paginated'
        },
        POST: {
            STORE: '/Vehiculos/Store'
        },
        PUT: {
            UPDATE: '/Vehiculos/Update'
        }
    },
    TIPOS_PROPIEDAD: {
        GET: {
            DATATABLE: '/Maestros/TiposPropiedad/GetDatatableServerSide'
        },
        POST: {
            STORE: '/Maestros/TiposPropiedad/Store'
        },
        PUT: {
            UPDATE: '/Maestros/TiposPropiedad/Update'
        }
    },
    CATEGORIAS_GASTO: {
        GET: {
            DATATABLE: '/Maestros/CategoriasGasto/GetDatatableServerSide'
        },
        POST: {
            STORE: '/Maestros/CategoriasGasto/Store'
        },
        PUT: {
            UPDATE: '/Maestros/CategoriasGasto/Update'
        }
    },
    PLATAFORMAS: {
        GET: {
            DATATABLE: '/Maestros/Plataformas/GetDatatableServerSide'
        },
        POST: {
            STORE: '/Maestros/Plataformas/Store'
        },
        PUT: {
            UPDATE: '/Maestros/Plataformas/Update'
        }
    },
    TIPOS_VIAJE: {
        GET: {
            DATATABLE: '/Maestros/TiposViaje/GetDatatableServerSide'
        },
        POST: {
            STORE: '/Maestros/TiposViaje/Store'
        },
        PUT: {
            UPDATE: '/Maestros/TiposViaje/Update'
        }
    },
    PERMISOS: {
        GET: {
            MATRIX: '/Administracion/Permisos/GetMatrix'
        },
        PUT: {
            UPDATE: '/Administracion/Permisos/Update'
        }
    }
};
window.APLICATIVO_API = APLICATIVO_API;
