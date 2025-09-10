<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cocinero') {
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
    <title>Dashboard Cocinero - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .pedido-card {
            transition: transform 0.2s;
        }
        .pedido-card:hover {
            transform: translateY(-2px);
        }
        .tiempo-transcurrido {
            font-size: 0.9em;
            color: #6c757d;
        }
        .urgente {
            border-left: 4px solid #dc3545;
        }
        .normal {
            border-left: 4px solid #ffc107;
        }
        .completado {
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">Sistema POS - Cocinero</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
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
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pendientes">
                                <i class="fas fa-clock"></i> Pedidos Pendientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#preparacion">
                                <i class="fas fa-fire"></i> En Preparación
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#completados">
                                <i class="fas fa-check-circle"></i> Completados Hoy
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../../controllers/index/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Cocinero</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="actualizarPedidos()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalPendientes">0</h4>
                                        <p>Pendientes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
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
                                        <h4 id="totalEnPreparacion">0</h4>
                                        <p>En Preparación</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-fire fa-2x"></i>
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
                                        <h4 id="totalCompletadosHoy">0</h4>
                                        <p>Completados Hoy</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
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
                                        <h4 id="tiempoPromedio">0 min</h4>
                                        <p>Tiempo Promedio</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pedidos Pendientes -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Pedidos Pendientes
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="listaPedidosPendientes">
                                    <!-- Datos dinámicos -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pedidos En Preparación -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-fire"></i> En Preparación
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="listaPedidosPreparacion">
                                    <!-- Datos dinámicos -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pedidos Completados -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Completados Hoy
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="listaPedidosCompletados">
                                    <!-- Datos dinámicos -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para ver detalles del pedido -->
    <div class="modal fade" id="modalDetallePedido" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoDetallePedido"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    function actualizarPedidos() {
        cargarPedidosPendientes();
        cargarPedidosEnPreparacion();
        cargarPedidosCompletados();
        actualizarEstadisticas();
    }

    function cargarPedidosPendientes() {
        $.ajax({
            url: '../../controllers/cocinero/contar_pendientes.php',
            type: 'POST',
            data: JSON.stringify({action: 'obtenerPendientes'}),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    let html = '';
                    response.pedidos.forEach(function(pedido) {
                        html += crearCardPedido(pedido, 'pendiente');
                    });
                    $('#listaPedidosPendientes').html(html);
                    $('#totalPendientes').text(response.pedidos.length);
                }
            }
        });
    }

    function cargarPedidosEnPreparacion() {
        $.ajax({
            url: '../../controllers/cocinero/contar_pendientes.php',
            type: 'POST',
            data: JSON.stringify({action: 'obtenerEnPreparacion'}),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    let html = '';
                    response.pedidos.forEach(function(pedido) {
                        html += crearCardPedido(pedido, 'preparacion');
                    });
                    $('#listaPedidosPreparacion').html(html);
                    $('#totalEnPreparacion').text(response.pedidos.length);
                }
            }
        });
    }

    function cargarPedidosCompletados() {
        $.ajax({
            url: '../../controllers/cocinero/contar_pendientes.php',
            type: 'POST',
            data: JSON.stringify({action: 'obtenerCompletadosHoy'}),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    let html = '';
                    response.pedidos.forEach(function(pedido) {
                        html += crearCardPedido(pedido, 'completado');
                    });
                    $('#listaPedidosCompletados').html(html);
                    $('#totalCompletadosHoy').text(response.pedidos.length);
                }
            }
        });
    }

    function crearCardPedido(pedido, tipo) {
        let claseCard = tipo === 'pendiente' ? 'urgente' : 
                       tipo === 'preparacion' ? 'normal' : 'completado';
        
        let botonesAccion = '';
        if(tipo === 'pendiente') {
            botonesAccion = `
                <button class="btn btn-warning btn-sm" onclick="marcarEnPreparacion(${pedido.id})">
                    <i class="fas fa-play"></i> Iniciar
                </button>
            `;
        } else if(tipo === 'preparacion') {
            botonesAccion = `
                <button class="btn btn-success btn-sm" onclick="marcarCompletado(${pedido.id})">
                    <i class="fas fa-check"></i> Completar
                </button>
            `;
        }

        return `
            <div class="card mb-3 pedido-card ${claseCard}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">
                                <i class="fas fa-table"></i> Mesa ${pedido.numero_mesa} - ${pedido.nombre_cliente}
                            </h6>
                            <p class="card-text">
                                <strong>Platos:</strong><br>
                                ${pedido.detalles.map(detalle => 
                                    `${detalle.cantidad}x ${detalle.nombre_plato}`
                                ).join('<br>')}
                            </p>
                            <p class="tiempo-transcurrido">
                                <i class="fas fa-clock"></i> ${pedido.tiempo_transcurrido}
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            ${botonesAccion}
                            <button class="btn btn-info btn-sm mt-1" onclick="verDetalles(${pedido.id})">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function marcarEnPreparacion(pedido_id) {
        $.ajax({
            url: '../../controllers/cocinero/actualizar_pedido.php',
            type: 'POST',
            data: JSON.stringify({
                action: 'marcarEnPreparacion',
                pedido_id: pedido_id
            }),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    actualizarPedidos();
                }
            }
        });
    }

    function marcarCompletado(pedido_id) {
        $.ajax({
            url: '../../controllers/cocinero/actualizar_pedido.php',
            type: 'POST',
            data: JSON.stringify({
                action: 'marcarCompletado',
                pedido_id: pedido_id
            }),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    actualizarPedidos();
                }
            }
        });
    }

    function verDetalles(pedido_id) {
        $.ajax({
            url: '../../controllers/cocinero/contar_pendientes.php',
            type: 'POST',
            data: JSON.stringify({
                action: 'obtenerDetalles',
                pedido_id: pedido_id
            }),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    $('#contenidoDetallePedido').html(response.html);
                    $('#modalDetallePedido').modal('show');
                }
            }
        });
    }

    function actualizarEstadisticas() {
        // Aquí puedes agregar más llamadas para estadísticas avanzadas
    }

    // Cargar datos al iniciar y cada 15 segundos
    $(document).ready(function() {
        actualizarPedidos();
        setInterval(actualizarPedidos, 15000);
    });
    </script>
</body>
</html>