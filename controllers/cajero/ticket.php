<?php
session_start();
require_once '../../controllers/db.php';

header('Content-Type: application/json');

class TicketController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function generarTicket($mesa_id, $pedido_id) {
        try {
            mysqli_begin_transaction($this->conn);
            
            // Obtener información del pedido
            $query = "SELECT p.*, m.numero_mesa, m.estado as estado_mesa 
                     FROM pedidos p 
                     JOIN mesas m ON p.mesa_id = m.id 
                     WHERE p.id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $pedido_id);
            $stmt->execute();
            $pedido = $stmt->get_result()->fetch_assoc();
            
            if (!$pedido) {
                throw new Exception("Pedido no encontrado");
            }
            
            // Obtener detalles del pedido
            $query_detalles = "SELECT dp.*, pl.nombre as nombre_plato, pl.precio 
                              FROM detalle_pedido dp 
                              JOIN platos pl ON dp.plato_id = pl.id 
                              WHERE dp.pedido_id = ?";
            $stmt_detalles = $this->conn->prepare($query_detalles);
            $stmt_detalles->bind_param("i", $pedido_id);
            $stmt_detalles->execute();
            $detalles = $stmt_detalles->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (empty($detalles)) {
                throw new Exception("No hay detalles en el pedido");
            }
            
            // Calcular totales
            $subtotal = 0;
            foreach ($detalles as $detalle) {
                $subtotal += $detalle['precio'] * $detalle['cantidad'];
            }
            
            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;
            
            // Crear registro de venta
            $folio = $this->generarFolio();
            $query_venta = "INSERT INTO ventas (folio, pedido_id, mesa_id, usuario_id, subtotal, iva, total, fecha_venta) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt_venta = $this->conn->prepare($query_venta);
            $usuario_id = $_SESSION['user_id'];
            $stmt_venta->bind_param("siiiddd", $folio, $pedido_id, $mesa_id, $usuario_id, $subtotal, $iva, $total);
            $stmt_venta->execute();
            
            $venta_id = $this->conn->insert_id;
            
            // Actualizar estado del pedido y mesa
            $query_update_pedido = "UPDATE pedidos SET estado = 'pagado' WHERE id = ?";
            $stmt_update_pedido = $this->conn->prepare($query_update_pedido);
            $stmt_update_pedido->bind_param("i", $pedido_id);
            $stmt_update_pedido->execute();
            
            $query_update_mesa = "UPDATE mesas SET estado = 'disponible' WHERE id = ?";
            $stmt_update_mesa = $this->conn->prepare($query_update_mesa);
            $stmt_update_mesa->bind_param("i", $mesa_id);
            $stmt_update_mesa->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'venta_id' => $venta_id,
                'folio' => $folio,
                'pedido' => $pedido,
                'detalles' => $detalles,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function generarFolio() {
        $query = "SELECT MAX(id) as ultimo_id FROM ventas";
        $result = $this->conn->query($query);
        $ultimo_id = $result->fetch_assoc()['ultimo_id'] ?? 0;
        $nuevo_id = $ultimo_id + 1;
        
        return 'TK' . str_pad($nuevo_id, 6, '0', STR_PAD_LEFT);
    }
    
    public function obtenerMesasActivas() {
        try {
            $query = "SELECT p.id as pedido_id, p.mesa_id, m.numero_mesa, p.nombre_cliente, 
                             SUM(dp.cantidad * pl.precio) as total
                      FROM pedidos p
                      JOIN mesas m ON p.mesa_id = m.id
                      JOIN detalle_pedido dp ON p.id = dp.pedido_id
                      JOIN platos pl ON dp.plato_id = pl.id
                      WHERE p.estado = 'pendiente_pago'
                      GROUP BY p.id, p.mesa_id, m.numero_mesa, p.nombre_cliente";
            
            $result = $this->conn->query($query);
            $mesas = $result->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'mesas' => $mesas];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Manejo de peticiones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ticketController = new TicketController();
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'generar':
                if (isset($data['mesa_id']) && isset($data['pedido_id'])) {
                    $result = $ticketController->generarTicket($data['mesa_id'], $data['pedido_id']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                }
                break;
                
            case 'obtenerMesasActivas':
                $result = $ticketController->obtenerMesasActivas();
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No se especificó acción']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>