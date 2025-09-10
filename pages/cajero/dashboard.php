<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cajero') {
    header("Location: ../../index/login.php");
    exit();
}

require_once '../../controllers/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cajero - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
        
        .field-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">Sistema POS - Cajero</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="../../controllers/index/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-home"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#mesas">
                                <i class="fas fa-table"></i>
                                Mesas Activas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#ventas">
                                <i class="fas fa-receipt"></i>
                                Ventas del Día
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#reportes">
                                <i class="fas fa-chart-bar"></i>
                                Reportes
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../../controllers/index/logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Cajero</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="actualizarDatos()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalMesasActivas">0</h4>
                                        <p>Mesas Activas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-table fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalVentasDia">$0.00</h4>
                                        <p>Ventas del Día</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalPedidosPendientes">0</h4>
                                        <p>Pedidos Pendientes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalTicketsHoy">0</h4>
                                        <p>Tickets Hoy</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-receipt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mesas Activas -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-table"></i> Mesas Activas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mesa</th>
                                                <th>Cliente</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaMesas">
                                            <!-- Datos dinámicos -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Acciones rápidas -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cash-register"></i> Acciones Rápidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary w-100 mb-2" onclick="generarReporte()">
                                    <i class="fas fa-file-invoice"></i> Generar Reporte
                                </button>
                                <button class="btn btn-success w-100 mb-2" onclick="verVentas()">
                                    <i class="fas fa-list"></i> Ver Ventas
                                </button>
                                <button class="btn btn-info w-100" onclick="imprimirUltimoTicket()">
                                    <i class="fas fa-print"></i> Reimprimir Ticket
                                </button>
                            </div>
                        </div>

                        <!-- Últimas ventas -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Últimas Ventas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="ultimasVentas">
                                    <!-- Datos dinámicos -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal para generar ticket -->
                <div class="modal fade" id="modalGenerarTicket" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Generar Ticket</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="detallePedido">
                                    <!-- Contenido dinámico -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="confirmarPago()">Confirmar Pago</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    function actualizarDatos() {
        cargarMesasActivas();
        actualizarEstadisticas();
    }

    function cargarMesasActivas() {
        $.ajax({
            url: '../../controllers/cajero/ticket.php',
            type: 'POST',
            data: JSON.stringify({action: 'obtenerMesasActivas'}),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    let html = '';
                    response.mesas.forEach(function(mesa) {
                        html += `
                            <tr>
                                <td>Mesa ${mesa.numero_mesa}</td>
                                <td>${mesa.nombre_cliente}</td>
                                <td>$${parseFloat(mesa.total).toFixed(2)}</td>
                                <td><span class="badge bg-warning">Pendiente</span></td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="generarTicket(${mesa.mesa_id}, ${mesa.pedido_id})">
                                        <i class="fas fa-cash-register"></i> Cobrar
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#tablaMesas').html(html);
                    $('#totalMesasActivas').text(response.mesas.length);
                }
            }
        });
    }

    function generarTicket(mesa_id, pedido_id) {
        if(confirm('¿Confirmar pago para esta mesa?')) {
            $.ajax({
                url: '../../controllers/cajero/ticket.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'generar',
                    mesa_id: mesa_id,
                    pedido_id: pedido_id
                }),
                contentType: 'application/json',
                success: function(response) {
                    if(response.success) {
                        alert('Ticket generado exitosamente: ' + response.folio);
                        cargarMesasActivas();
                        actualizarEstadisticas();
                    } else {
                        alert('Error: ' + response.error);
                    }
                }
            });
        }
    }

    function actualizarEstadisticas() {
        // Aquí puedes agregar más llamadas AJAX para actualizar estadísticas
        console.log('Actualizando estadísticas...');
    }

    // Cargar datos al iniciar
    $(document).ready(function() {
        cargarMesasActivas();
        setInterval(actualizarDatos, 30000); // Actualizar cada 30 segundos
    });
    </script>
</body>
</html>